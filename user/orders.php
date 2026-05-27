<?php

/**
 * User Orders Page
 * Order history for logged in users
 */

$pageTitle = 'My Orders';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/shiprocket.php';
require_once __DIR__ . '/../includes/order_lifecycle.php';

// Require login
requireLogin();

$userId = $_SESSION['user_id'];
shiprocketEnsureSchema($pdo);
orderLifecycleEnsureSchema($pdo);
$statusFilter = $_GET['status'] ?? '';
$allowedOrderFilters = ['', 'active', 'cancelled'];
if (!in_array($statusFilter, $allowedOrderFilters, true)) {
    $statusFilter = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['order_action'] ?? '') === 'cancel_order') {
    $result = orderCancelByUser(
        $pdo,
        $_POST['order_id'] ?? 0,
        $userId,
        $_POST['cancel_reason'] ?? '',
        $_POST['cancel_reason_detail'] ?? ''
    );

    setFlash($result['message'], $result['success'] ? 'success' : 'danger');
    redirect(BASE_URL . 'user/orders.php');
}

// Fetch user's orders
try {
    $query = "SELECT * FROM orders WHERE user_id = ?";
    $params = [$userId];

    if ($statusFilter === 'cancelled') {
        $query .= " AND status = 'cancelled'";
    } elseif ($statusFilter === 'active') {
        $query .= " AND status <> 'cancelled'";
    }

    $query .= " ORDER BY created_at DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    $orders = [];
    error_log('Orders Fetch Error: ' . $e->getMessage());
}

$orderCounts = ['all' => 0, 'active' => 0, 'cancelled' => 0];
try {
    $countStmt = $pdo->prepare("SELECT status, COUNT(*) AS total FROM orders WHERE user_id = ? GROUP BY status");
    $countStmt->execute([$userId]);
    foreach ($countStmt->fetchAll() as $row) {
        $count = (int)$row['total'];
        $orderCounts['all'] += $count;
        if (($row['status'] ?? '') === 'cancelled') {
            $orderCounts['cancelled'] += $count;
        } else {
            $orderCounts['active'] += $count;
        }
    }
} catch (PDOException $e) {
    error_log('Order Count Fetch Error: ' . $e->getMessage());
}

// Fetch order items for each order
$orderItems = [];
foreach ($orders as $order) {
    try {
        $stmt = $pdo->prepare("SELECT oi.*, p.name as product_name, p.image as product_image 
                              FROM order_items oi 
                              JOIN products p ON oi.product_id = p.id 
                              WHERE oi.order_id = ?");
        $stmt->execute([$order['id']]);
        $orderItems[$order['id']] = $stmt->fetchAll();
    } catch (PDOException $e) {
        $orderItems[$order['id']] = [];
    }
}
?>

<div class="min-h-screen mt-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <!-- Sidebar -->
            <?php require_once __DIR__ . '/../includes/profile_sidebar.php'; ?>

            <!-- Orders List -->
            <div class="md:col-span-3">
                <div class="bg-white md:border md:rounded-lg md:shadow-sm md:p-8 min-h-[560px]">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                        <h4 class="text-2xl font-bold text-gray-900">My Orders</h4>
                        <div class="flex flex-wrap gap-2 text-sm">
                            <a href="<?php echo BASE_URL; ?>user/orders.php"
                               class="px-3 py-2 rounded-lg border <?php echo $statusFilter === '' ? 'bg-primary-500 border-primary-500 text-white' : 'border-gray-300 text-gray-700 hover:border-primary-500 hover:text-primary-600'; ?>">
                                All (<?php echo (int)$orderCounts['all']; ?>)
                            </a>
                            <a href="<?php echo BASE_URL; ?>user/orders.php?status=active"
                               class="px-3 py-2 rounded-lg border <?php echo $statusFilter === 'active' ? 'bg-primary-500 border-primary-500 text-white' : 'border-gray-300 text-gray-700 hover:border-primary-500 hover:text-primary-600'; ?>">
                                Active (<?php echo (int)$orderCounts['active']; ?>)
                            </a>
                            <a href="<?php echo BASE_URL; ?>user/orders.php?status=cancelled"
                               class="px-3 py-2 rounded-lg border <?php echo $statusFilter === 'cancelled' ? 'bg-red-500 border-red-500 text-white' : 'border-gray-300 text-gray-700 hover:border-red-500 hover:text-red-600'; ?>">
                                Cancelled (<?php echo (int)$orderCounts['cancelled']; ?>)
                            </a>
                        </div>
                    </div>

                    <?php if (empty($orders)): ?>
                        <div class="text-center py-12">
                            <i class="fas fa-box-open text-6xl text-gray-300 mb-4"></i>
                            <h5 class="text-xl font-semibold text-gray-900 mb-2">
                                <?php echo $statusFilter === 'cancelled' ? 'No cancelled orders' : ($statusFilter === 'active' ? 'No active orders' : 'No orders yet'); ?>
                            </h5>
                            <p class="text-gray-500 mb-6">
                                <?php echo $statusFilter === '' ? 'Start shopping to see your orders here!' : 'Orders in this section will appear here.'; ?>
                            </p>
                            <a href="<?php echo BASE_URL; ?>shop.php" class="inline-flex items-center bg-primary hover:bg-primary-600 text-white font-semibold py-3 px-6 rounded-full transition hover:shadow-sm">
                                <i class="fas fa-shopping-bag mr-2"></i>Start Shopping
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4" id="ordersAccordion">
                            <?php foreach ($orders as $index => $order):
                                $statusColors = [
                                    'pending' => 'bg-yellow-100 text-yellow-700',
                                    'processing' => 'bg-blue-100 text-blue-700',
                                    'shipped' => 'bg-purple-100 text-purple-700',
                                    'delivered' => 'bg-green-100 text-green-700',
                                    'cancelled' => 'bg-red-100 text-red-700'
                                ];
                                $statusInfo = shiprocketCustomerStatusInfo($order);
                                $statusClass = $statusColors[$statusInfo['normalized']] ?? 'bg-gray-100 text-gray-700';
                            ?>
                                <div class="border border-gray-200 rounded-xl overflow-hidden">
                                    <button
                                        class="w-full px-4 md:px-6 py-4 flex flex-row md:items-center justify-between gap-4 bg-gray-50 hover:bg-gray-100 transition text-left"
                                        onclick="document.getElementById('order<?php echo $order['id']; ?>').classList.toggle('hidden')">

                                        <!-- Left -->
                                        <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4">
                                            <span class="font-bold text-gray-900 text-sm md:text-base">
                                                Order #<?php echo $order['id']; ?>
                                            </span>

                                            <span class="text-gray-500 text-xs md:text-sm text-nowrap">
                                                <?php echo date('M d, Y', strtotime($order['created_at'])); ?>
                                            </span>
                                        </div>

                                        <!-- Right -->
                                        <div class="flex flex-wrap items-center gap-3">

                                            <span class="px-3 py-1 rounded-full text-xs font-medium <?php echo $statusClass; ?>">
                                                <?php echo e($statusInfo['label']); ?>
                                            </span>

                                            <span class="font-bold text-green-500 text-sm md:text-base">
                                                <?php echo formatCurrency($order['total_amount']); ?>
                                            </span>

                                            <i class="fas fa-chevron-down text-gray-400 text-sm "></i>
                                        </div>

                                    </button>
                                    <div id="order<?php echo $order['id']; ?>" class="hidden px-4 md:px-6 py-4 border-t border-gray-200">

                                        <!-- Order Items -->
                                        <h6 class="font-bold text-gray-900 mb-3">Order Items</h6>
                                        <div class="overflow-x-auto">
                                            <table class="w-full text-sm">
                                                <thead class="bg-gray-50">
                                                    <tr>
                                                        <th class="px-4 py-2 text-left font-medium text-gray-700">Product</th>
                                                        <th class="px-12 md:px-4 py-2 text-center font-medium text-gray-700">Qty</th>
                                                        <th class="px-4 py-2 text-right font-medium text-gray-700">Price</th>
                                                        <th class="px-4 py-2 text-right font-medium text-gray-700">Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-200">
                                                    <?php foreach ($orderItems[$order['id']] as $item): ?>
                                                        <tr>
                                                            <td class="px-4 py-3">
                                                                <div class="flex items-center">
                                                                    <img src="<?php echo getImageUrl($item['product_image'], 'products'); ?>"
                                                                        class="w-10 h-10 rounded-lg mr-3 object-cover" alt="">
                                                                    <div>
                                                                        <span class="text-gray-900 text-nowrap"><?php echo e($item['product_name']); ?></span>
                                                                        <?php if (!empty($item['weight'])): ?>
                                                                            <p class="text-accent text-sm">
                                                                                <i class="fas fa-weight-hanging mr-1 text-xs"></i><?php echo !empty($item['flavour']) ? e($item['flavour']) . ' - ' : ''; ?><?php echo e($item['weight']); ?>
                                                                            </p>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td class="px-12 md:px-4 py-3 text-center"><?php echo $item['quantity']; ?></td>
                                                            <td class="px-4 py-3 text-right"><?php echo formatCurrency($item['price']); ?></td>
                                                            <td class="px-4 py-3 text-right font-medium"><?php echo formatCurrency($item['price'] * $item['quantity']); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                                <tfoot class="bg-gray-50 font-medium">
                                                    <tr>
                                                        <td colspan="3" class="px-4 py-2 text-right">Subtotal</td>
                                                        <td class="px-4 py-2 text-right"><?php echo formatCurrency($order['total_amount'] + $order['discount_amount']); ?></td>
                                                    </tr>
                                                    <?php if ($order['discount_amount'] > 0): ?>
                                                        <tr>
                                                            <td colspan="3" class="px-4 py-2 text-right">Discount</td>
                                                            <td class="px-4 py-2 text-right text-green-600">-<?php echo formatCurrency($order['discount_amount']); ?></td>
                                                        </tr>
                                                    <?php endif; ?>
                                                    <tr>
                                                        <td colspan="3" class="px-4 py-2 text-right font-bold text-gray-900">Total</td>
                                                        <td class="px-4 py-2 text-right font-bold text-green-500"><?php echo formatCurrency($order['total_amount']); ?></td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>

                                        <!-- Order Details -->
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                                            <div>
                                                <h6 class="font-bold text-gray-900 mb-2">Shipping Address</h6>
                                                <p class="text-gray-600 text-xs"><?php echo e($order['name']); ?></p>
                                                <p class="text-gray-600 text-xs"><?php echo e($order['address']); ?></p>
                                                <p class="text-gray-600 text-xs"><?php echo e($order['city']); ?>, <?php echo e($order['state']); ?> - <?php echo e($order['pincode']); ?></p>
                                                <p class="text-gray-600 text-xs">Mobile: <?php echo e($order['mobile']); ?></p>
                                            </div>
                                            <div>
                                                <h6 class="font-bold text-gray-900 mb-2">Payment Details</h6>
                                                <?php
                                                $paymentMethod = $order['payment_method'] ?? '';
                                                $paymentStatus = $order['payment_status'] ?? 'pending';
                                                $initialPaymentStatus = $order['initial_payment_status'] ?? 'pending';
                                                $paymentStatusClass = $paymentStatus === 'paid'
                                                    ? 'bg-green-100 text-green-700'
                                                    : ($paymentStatus === 'failed' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700');
                                                $initialStatusClass = $initialPaymentStatus === 'paid'
                                                    ? 'bg-green-100 text-green-700'
                                                    : ($initialPaymentStatus === 'failed' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700');
                                                ?>
                                                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm">
                                                    <div class="space-y-2">
                                                        <div class="flex justify-between gap-4">
                                                            <span class="text-gray-500">Method</span>
                                                            <span class="font-medium text-gray-900 text-xs md:text-sm"><?php echo $paymentMethod === 'cod' ? 'Cash on Delivery (COD)' : 'Online Payment'; ?></span>
                                                        </div>
                                                        <div class="flex justify-between gap-4">
                                                            <span class="text-gray-500">Payment Status</span>
                                                            <span class="inline-block px-2 py-1 rounded text-xs font-medium <?php echo $paymentStatusClass; ?>">
                                                                <?php echo ucfirst(e($paymentStatus)); ?>
                                                            </span>
                                                        </div>

                                                        <?php if ($paymentMethod === 'cod'): ?>
                                                            <div class="flex justify-between gap-4">
                                                                <span class="text-gray-500">Order Total</span>
                                                                <span class="font-medium text-gray-900"><?php echo formatCurrency($order['total_amount']); ?></span>
                                                            </div>
                                                            <div class="flex justify-between gap-4">
                                                                <span class="text-gray-500">Initial Paid</span>
                                                                <span class="font-medium text-green-600"><?php echo formatCurrency($order['initial_payment_amount']); ?></span>
                                                            </div>
                                                            <div class="flex justify-between gap-4">
                                                                <span class="text-gray-500">Pay on Delivery</span>
                                                                <span class="font-medium text-orange-600"><?php echo formatCurrency($order['remaining_payment_amount']); ?></span>
                                                            </div>
                                                            <div class="flex justify-between gap-4">
                                                                <span class="text-gray-500">Initial Status</span>
                                                                <span class="inline-block px-2 py-1 rounded text-xs font-medium <?php echo $initialStatusClass; ?>">
                                                                    <?php echo ucfirst(e($initialPaymentStatus)); ?>
                                                                </span>
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="flex justify-between gap-4">
                                                                <span class="text-gray-500">Paid Amount</span>
                                                                <span class="font-medium text-gray-900"><?php echo formatCurrency($order['total_amount']); ?></span>
                                                            </div>
                                                        <?php endif; ?>

                                                        <?php if (!empty($order['razorpay_payment_id'])): ?>
                                                            <div class="pt-2 border-t border-gray-200">
                                                                <span class="block text-gray-500">Payment ID</span>
                                                                <span class="break-all text-xs font-medium text-gray-700"><?php echo e($order['razorpay_payment_id']); ?></span>
                                                            </div>
                                                        <?php endif; ?>

                                                        <?php if (!empty($order['razorpay_order_id'])): ?>
                                                            <div>
                                                                <span class="block text-gray-500">Order ID</span>
                                                                <span class="break-all text-xs font-medium text-gray-700"><?php echo e($order['razorpay_order_id']); ?></span>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>

                                                <?php if ($order['coupon_code']): ?>
                                                    <p class="text-gray-600 text-sm mt-3">Coupon: <span class="inline-block bg-primary-100 text-primary-700 px-2 py-1 rounded text-xs font-medium"><?php echo e($order['coupon_code']); ?></span></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <?php
                                        $trackingUrl = !empty($order['shiprocket_tracking_url'])
                                            ? $order['shiprocket_tracking_url']
                                            : (!empty($order['shiprocket_awb_code'])
                                                ? 'https://shiprocket.co/tracking/' . rawurlencode($order['shiprocket_awb_code'])
                                                : '');
                                        ?>
                                        <?php if (($order['status'] ?? '') !== 'cancelled'): ?>
                                        <div class="mt-6 md:rounded-lg md:border md:border-gray-200 md:bg-gray-50 md:p-8 text-sm">
                                            <h6 class="font-bold text-gray-900 mb-5">Order Tracking</h6>
                                            <div class="mb-2 md:mb-6">
                                                <?php echo renderShiprocketProgressTracker($order, 'customer'); ?>
                                            </div>
                                            <div class="flex justify-between items-center gap-3">
                                                <div>
                                                    <span class="block text-gray-500 text-xs">Order Status</span>
                                                    <span class="font-medium text-gray-900"><?php echo e($statusInfo['label']); ?></span>
                                                </div>
                                                <?php if (!empty($trackingUrl)): ?>
                                                    <a href="<?php echo e($trackingUrl); ?>" target="_blank" rel="noopener" class="text-primary-600 hover:text-primary-700 font-medium">
                                                        <i class="fas fa-location-dot mr-1"></i>Track shipment
                                                    </a>
                                                <?php endif; ?>
                                                <?php if (!empty($order['shiprocket_synced_at'])): ?>
                                                    <span class="text-gray-500 text-xs">Updated <?php echo date('M d, Y H:i', strtotime($order['shiprocket_synced_at'])); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php endif; ?>

                                        <?php
                                        $canCancelOrder = orderCanBeCancelledByUser($order);
                                        $refundStatus = $order['refund_status'] ?? 'not_applicable';
                                        $refundAmount = (float)($order['refund_amount'] ?? 0);
                                        ?>
                                        <?php if ($canCancelOrder || !empty($order['cancelled_at']) || !empty($order['cancel_reason'])): ?>
                                            <div class="mt-6 rounded-lg border <?php echo ($order['status'] ?? '') === 'cancelled' ? 'border-red-200 bg-red-50' : 'border-gray-200 bg-white'; ?> p-4 text-sm">
                                                <h6 class="font-bold <?php echo ($order['status'] ?? '') === 'cancelled' ? 'text-red-700' : 'text-gray-900'; ?> mb-3">
                                                    <?php echo ($order['status'] ?? '') === 'cancelled' ? 'Order Cancelled' : 'Cancellation'; ?>
                                                </h6>

                                                <?php if (!empty($order['cancelled_at']) || !empty($order['cancel_reason'])): ?>
                                                    <div class="space-y-2">
                                                        <div class="flex justify-between gap-4">
                                                            <span class="text-gray-500">Reason</span>
                                                            <span class="font-medium text-gray-900 text-right"><?php echo e(orderCancellationReasonLabel($order)); ?></span>
                                                        </div>
                                                        <div class="flex justify-between gap-4">
                                                            <span class="text-gray-500">Cancelled At</span>
                                                            <span class="font-medium text-gray-900"><?php echo !empty($order['cancelled_at']) ? date('M d, Y H:i', strtotime($order['cancelled_at'])) : 'Pending'; ?></span>
                                                        </div>
                                                        <div class="flex justify-between gap-4">
                                                            <span class="text-gray-500">Refund</span>
                                                            <span class="inline-block px-2 py-1 rounded text-xs font-medium <?php echo orderRefundStatusClass($refundStatus); ?>">
                                                                <?php echo e(orderRefundStatusLabel($refundStatus)); ?>
                                                            </span>
                                                        </div>
                                                        <?php if ($refundAmount > 0): ?>
                                                            <div class="flex justify-between gap-4">
                                                                <span class="text-gray-500">Refund Amount</span>
                                                                <span class="font-medium text-green-700"><?php echo formatCurrency($refundAmount); ?></span>
                                                            </div>
                                                        <?php endif; ?>
                                                        <?php if (!empty($order['refund_note'])): ?>
                                                            <div class="pt-2 border-t border-gray-200">
                                                                <span class="block text-gray-500">Admin Note</span>
                                                                <span class="text-gray-700"><?php echo e($order['refund_note']); ?></span>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <?php
                                                    $cancelDeadline = orderCancellationDeadline($order);
                                                    $cancelMinutes = orderCancellationWindowMinutes();
                                                    ?>
                                                    <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-3 text-xs text-yellow-800">
                                                        You can cancel this order within <?php echo (int)$cancelMinutes; ?> minutes after placing it<?php echo $cancelDeadline ? ', until ' . date('M d, Y H:i', $cancelDeadline) : ''; ?>.
                                                    </div>
                                                    <form method="POST" action="<?php echo BASE_URL; ?>user/orders.php" class="space-y-3">
                                                        <input type="hidden" name="order_action" value="cancel_order">
                                                        <input type="hidden" name="order_id" value="<?php echo (int)$order['id']; ?>">
                                                        <select name="cancel_reason" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                                                            <option value="">Select reason</option>
                                                            <?php foreach (orderCancellationReasons() as $reasonValue => $reasonLabel): ?>
                                                                <option value="<?php echo e($reasonValue); ?>"><?php echo e($reasonLabel); ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <textarea name="cancel_reason_detail" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500" placeholder="Custom reason, if needed"></textarea>
                                                        <button type="submit" class="inline-flex items-center bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-4 rounded-lg transition">
                                                            <i class="fas fa-ban mr-2"></i>Cancel Order
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
