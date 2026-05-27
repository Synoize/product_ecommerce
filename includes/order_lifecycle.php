<?php

/**
 * Order cancellation and refund helpers.
 */

function orderLifecycleEnsureSchema(PDO $pdo)
{
    static $checked = false;
    if ($checked) {
        return;
    }

    $columns = [
        'cancel_reason' => 'VARCHAR(100) DEFAULT NULL',
        'cancel_reason_detail' => 'TEXT DEFAULT NULL',
        'cancelled_by' => "ENUM('user','admin') DEFAULT NULL",
        'cancel_requested_at' => 'DATETIME DEFAULT NULL',
        'cancelled_at' => 'DATETIME DEFAULT NULL',
        'refund_status' => "ENUM('not_applicable','pending','processing','refunded','rejected') DEFAULT 'not_applicable'",
        'refund_amount' => 'DECIMAL(10,2) DEFAULT 0.00',
        'refund_note' => 'TEXT DEFAULT NULL',
        'refund_gateway' => 'VARCHAR(50) DEFAULT NULL',
        'refund_gateway_id' => 'VARCHAR(100) DEFAULT NULL',
        'refund_gateway_status' => 'VARCHAR(50) DEFAULT NULL',
        'refund_gateway_payload' => 'JSON DEFAULT NULL',
        'refunded_at' => 'DATETIME DEFAULT NULL',
    ];

    foreach ($columns as $column => $definition) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'orders' AND column_name = ?");
        $stmt->execute([$column]);

        if ((int)$stmt->fetchColumn() === 0) {
            $pdo->exec("ALTER TABLE orders ADD COLUMN `$column` $definition");
        }
    }

    $checked = true;
}

function orderCancellationReasons()
{
    return [
        'changed_mind' => 'Changed my mind',
        'ordered_by_mistake' => 'Ordered by mistake',
        'wrong_address' => 'Wrong address',
        'delivery_too_late' => 'Delivery is too late',
        'found_better_price' => 'Found a better price',
        'payment_issue' => 'Payment issue',
        'other' => 'Other reason',
    ];
}

function orderCancellationWindowMinutes()
{
    $minutes = getenv('ORDER_CANCEL_WINDOW_MINUTES');
    if ($minutes === false || $minutes === '') {
        $minutes = defined('ORDER_CANCEL_WINDOW_MINUTES') ? ORDER_CANCEL_WINDOW_MINUTES : 10;
    }

    return max(1, (int)$minutes);
}

function orderCancellationDeadline(array $order)
{
    if (empty($order['created_at'])) {
        return null;
    }

    $createdAt = strtotime($order['created_at']);
    if ($createdAt === false) {
        return null;
    }

    return $createdAt + (orderCancellationWindowMinutes() * 60);
}

function orderCancellationWindowExpired(array $order)
{
    $deadline = orderCancellationDeadline($order);
    return $deadline === null || time() > $deadline;
}

function orderCancellationReasonLabel(array $order)
{
    $reasons = orderCancellationReasons();
    $reason = $order['cancel_reason'] ?? '';

    if ($reason === 'other' && !empty($order['cancel_reason_detail'])) {
        return $order['cancel_reason_detail'];
    }

    return $reasons[$reason] ?? ($reason ?: '');
}

function orderRefundAmountDue(array $order)
{
    $paymentMethod = $order['payment_method'] ?? '';

    if ($paymentMethod === 'cod') {
        return ($order['initial_payment_status'] ?? '') === 'paid'
            ? (float)($order['initial_payment_amount'] ?? 0)
            : 0.0;
    }

    return ($order['payment_status'] ?? '') === 'paid'
        ? (float)($order['total_amount'] ?? 0)
        : 0.0;
}

function orderCanBeCancelledByUser(array $order)
{
    if (orderCancellationWindowExpired($order)) {
        return false;
    }

    if (in_array(($order['status'] ?? ''), ['cancelled', 'shipped', 'delivered'], true)) {
        return false;
    }

    if (!empty($order['cancelled_at']) || !empty($order['delivered_at']) || !empty($order['shipped_at'])) {
        return false;
    }

    $progress = function_exists('shiprocketOrderProgressInfo') ? shiprocketOrderProgressInfo($order, 'customer') : null;
    $stage = $progress['stage'] ?? 'processing';

    return in_array($stage, ['processing'], true);
}

function orderCancelByUser(PDO $pdo, $orderId, $userId, $reason, $detail)
{
    orderLifecycleEnsureSchema($pdo);

    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
    $stmt->execute([(int)$orderId, (int)$userId]);
    $order = $stmt->fetch();

    if (!$order) {
        return ['success' => false, 'message' => 'Order not found.'];
    }

    if (!orderCanBeCancelledByUser($order)) {
        return ['success' => false, 'message' => 'This order can only be cancelled within ' . orderCancellationWindowMinutes() . ' minutes after placing it.'];
    }

    $reasons = orderCancellationReasons();
    if (!isset($reasons[$reason])) {
        return ['success' => false, 'message' => 'Please select a cancellation reason.'];
    }

    $detail = trim((string)$detail);
    if ($reason === 'other' && $detail === '') {
        return ['success' => false, 'message' => 'Please enter your cancellation reason.'];
    }

    $refundAmount = orderRefundAmountDue($order);
    $refundStatus = $refundAmount > 0 ? 'pending' : 'not_applicable';

    $stmt = $pdo->prepare("UPDATE orders
                           SET status = 'cancelled',
                               cancel_reason = ?,
                               cancel_reason_detail = ?,
                               cancelled_by = 'user',
                               cancel_requested_at = NOW(),
                               cancelled_at = NOW(),
                               refund_status = ?,
                               refund_amount = ?
                           WHERE id = ? AND user_id = ?");
    $stmt->execute([
        $reason,
        $detail !== '' ? $detail : null,
        $refundStatus,
        $refundAmount,
        (int)$orderId,
        (int)$userId,
    ]);

    $message = $refundAmount > 0
        ? 'Order cancelled. Your refund request is pending admin review.'
        : 'Order cancelled successfully.';

    return ['success' => true, 'message' => $message];
}

function orderRefundStatusClass($status)
{
    switch ($status) {
        case 'refunded':
            return 'bg-green-100 text-green-700';
        case 'processing':
            return 'bg-blue-100 text-blue-700';
        case 'pending':
            return 'bg-yellow-100 text-yellow-700';
        case 'rejected':
            return 'bg-red-100 text-red-700';
        default:
            return 'bg-gray-100 text-gray-700';
    }
}

function orderRefundStatusLabel($status)
{
    return ucwords(str_replace('_', ' ', $status ?: 'not_applicable'));
}

function orderUpdateRefund(PDO $pdo, $orderId, $refundStatus, $refundAmount, $refundNote)
{
    orderLifecycleEnsureSchema($pdo);

    $allowedStatuses = ['not_applicable', 'pending', 'processing', 'refunded', 'rejected'];
    if (!in_array($refundStatus, $allowedStatuses, true)) {
        return ['success' => false, 'message' => 'Invalid refund status.'];
    }

    $refundAmount = max(0, (float)$refundAmount);
    $refundNote = trim((string)$refundNote);

    $set = [
        'refund_status = ?',
        'refund_amount = ?',
        'refund_note = ?',
        "refunded_at = CASE WHEN ? = 'refunded' THEN COALESCE(refunded_at, NOW()) ELSE refunded_at END",
        "payment_status = CASE WHEN payment_method <> 'cod' AND ? = 'refunded' THEN 'refunded' ELSE payment_status END",
    ];
    $params = [$refundStatus, $refundAmount, $refundNote !== '' ? $refundNote : null, $refundStatus, $refundStatus];

    if ($refundStatus === 'refunded') {
        $set[] = "payment_status = CASE WHEN payment_method != 'cod' THEN 'refunded' ELSE payment_status END";
    }

    $params[] = (int)$orderId;
    $stmt = $pdo->prepare('UPDATE orders SET ' . implode(', ', $set) . ' WHERE id = ?');
    $stmt->execute($params);

    return ['success' => true, 'message' => 'Refund details updated.'];
}

function orderProcessRazorpayRefund(PDO $pdo, $orderId, $refundAmount, $refundNote)
{
    orderLifecycleEnsureSchema($pdo);

    if (!function_exists('createRazorpayRefund')) {
        require_once __DIR__ . '/razorpay.php';
    }

    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([(int)$orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        return ['success' => false, 'message' => 'Order not found.'];
    }

    if (empty($order['razorpay_payment_id'])) {
        return ['success' => false, 'message' => 'No Razorpay payment ID found for this order.'];
    }

    if (!empty($order['refund_gateway_id'])) {
        return ['success' => false, 'message' => 'A Razorpay refund has already been created for this order.'];
    }

    $maxRefundAmount = orderRefundAmountDue($order);
    $refundAmount = (float)$refundAmount;
    if ($refundAmount <= 0) {
        $refundAmount = (float)($order['refund_amount'] ?? 0);
    }
    if ($refundAmount <= 0) {
        $refundAmount = $maxRefundAmount;
    }

    if ($refundAmount <= 0) {
        return ['success' => false, 'message' => 'No paid amount is available to refund.'];
    }

    if ($maxRefundAmount > 0 && $refundAmount > $maxRefundAmount) {
        return ['success' => false, 'message' => 'Refund amount cannot be greater than paid amount.'];
    }

    $refundNote = trim((string)$refundNote);
    $receipt = 'refund-order-' . (int)$orderId . '-' . time();

    try {
        $refund = createRazorpayRefund($order['razorpay_payment_id'], $refundAmount, [
            'order_id' => (string)$order['id'],
            'reason' => orderCancellationReasonLabel($order),
            'note' => $refundNote,
        ], $receipt);

        $gatewayStatus = $refund['status'] ?? 'pending';
        $localRefundStatus = $gatewayStatus === 'processed' ? 'refunded' : 'processing';
        $stmt = $pdo->prepare("UPDATE orders
                               SET refund_status = ?,
                                   refund_amount = ?,
                                   refund_note = ?,
                                   refund_gateway = 'razorpay',
                                   refund_gateway_id = ?,
                                   refund_gateway_status = ?,
                                   refund_gateway_payload = ?,
                                   refunded_at = CASE WHEN ? = 'refunded' THEN NOW() ELSE refunded_at END,
                                   payment_status = CASE WHEN payment_method <> 'cod' AND ? = 'refunded' THEN 'refunded' ELSE payment_status END
                               WHERE id = ?");
        $stmt->execute([
            $localRefundStatus,
            $refundAmount,
            $refundNote !== '' ? $refundNote : null,
            $refund['id'] ?? null,
            $gatewayStatus,
            json_encode($refund),
            $localRefundStatus,
            $localRefundStatus,
            (int)$orderId,
        ]);

        return ['success' => true, 'message' => 'Razorpay refund created successfully.'];
    } catch (Exception $e) {
        $stmt = $pdo->prepare("UPDATE orders
                               SET refund_status = 'processing',
                                   refund_amount = ?,
                                   refund_note = ?,
                                   refund_gateway = 'razorpay',
                                   refund_gateway_status = 'failed',
                                   refund_gateway_payload = ?
                               WHERE id = ?");
        $stmt->execute([
            $refundAmount,
            $refundNote !== '' ? $refundNote : null,
            json_encode(['error' => $e->getMessage()]),
            (int)$orderId,
        ]);

        return ['success' => false, 'message' => 'Razorpay refund failed: ' . $e->getMessage()];
    }
}
