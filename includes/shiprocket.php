<?php

/**
 * Shiprocket integration helpers.
 *
 * Configure credentials in config/database.php or through environment variables.
 */

if (!defined('SHIPROCKET_API_BASE')) {
    define('SHIPROCKET_API_BASE', getenv('SHIPROCKET_API_BASE') ?: 'https://apiv2.shiprocket.in/v1/external');
}

function shiprocketIsConfigured()
{
    return defined('SHIPROCKET_EMAIL') && defined('SHIPROCKET_PASSWORD')
        && SHIPROCKET_EMAIL !== ''
        && SHIPROCKET_PASSWORD !== ''
        && defined('SHIPROCKET_PICKUP_LOCATION')
        && SHIPROCKET_PICKUP_LOCATION !== '';
}

function shiprocketEnsureSchema(PDO $pdo)
{
    static $checked = false;
    if ($checked) {
        return;
    }

    $columns = [
        'shiprocket_order_id' => 'VARCHAR(100) DEFAULT NULL',
        'shiprocket_shipment_id' => 'VARCHAR(100) DEFAULT NULL',
        'shiprocket_awb_code' => 'VARCHAR(100) DEFAULT NULL',
        'shiprocket_courier_name' => 'VARCHAR(150) DEFAULT NULL',
        'shiprocket_status' => 'VARCHAR(100) DEFAULT NULL',
        'shiprocket_status_code' => 'INT(11) DEFAULT NULL',
        'shiprocket_tracking_url' => 'VARCHAR(255) DEFAULT NULL',
        'shiprocket_synced_at' => 'DATETIME DEFAULT NULL',
        'shiprocket_error' => 'TEXT DEFAULT NULL',
        'shiprocket_payload' => 'JSON DEFAULT NULL',
        'shipped_at' => 'DATETIME DEFAULT NULL',
        'delivered_at' => 'DATETIME DEFAULT NULL',
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

function shiprocketRequest($method, $endpoint, array $payload = null, $token = null)
{
    if (!function_exists('curl_init')) {
        throw new Exception('PHP cURL extension is required for Shiprocket integration.');
    }

    $url = rtrim(SHIPROCKET_API_BASE, '/') . '/' . ltrim($endpoint, '/');
    $headers = ['Content-Type: application/json'];

    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    if ($payload !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    }

    $raw = curl_exec($ch);
    $curlError = curl_error($ch);
    $statusCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($raw === false) {
        throw new Exception('Shiprocket request failed: ' . $curlError);
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        throw new Exception('Shiprocket returned an invalid response.');
    }

    if ($statusCode < 200 || $statusCode >= 300) {
        $message = $data['message'] ?? $data['error'] ?? ('HTTP ' . $statusCode);
        throw new Exception('Shiprocket API error: ' . $message);
    }

    return $data;
}

function shiprocketGetToken()
{
    if (!shiprocketIsConfigured()) {
        throw new Exception('Shiprocket credentials or pickup location are not configured.');
    }

    $response = shiprocketRequest('POST', 'auth/login', [
        'email' => SHIPROCKET_EMAIL,
        'password' => SHIPROCKET_PASSWORD,
    ]);

    if (empty($response['token'])) {
        throw new Exception('Shiprocket login did not return an auth token.');
    }

    return $response['token'];
}

function shiprocketNormalizeOrderStatus($status, $statusCode = null)
{
    $normalized = strtoupper(trim((string)$status));
    $code = $statusCode !== null && $statusCode !== '' ? (int)$statusCode : null;

    if ($code === 7 || strpos($normalized, 'DELIVERED') !== false) {
        return 'delivered';
    }

    if (in_array($code, [8, 16, 45], true) || strpos($normalized, 'CANCEL') !== false) {
        return 'cancelled';
    }

    if (in_array($code, [6, 17, 18, 19, 22, 27, 38, 42, 48], true)
        || preg_match('/SHIPPED|TRANSIT|PICKED|PICKUP|OFD|DESTINATION|WAREHOUSE/', $normalized)) {
        return 'shipped';
    }

    if (in_array($code, [1, 2, 3, 4, 5, 11, 13, 15, 20, 21], true)
        || preg_match('/AWB|LABEL|MANIFEST|PENDING|PROCESS|PICKUP|UNDELIVERED|EXCEPTION/', $normalized)) {
        return 'processing';
    }

    return null;
}

function shiprocketOrderStatusInfo(array $order)
{
    $normalizedStatus = $order['status'] ?? 'pending';
    $carrierStatus = trim((string)($order['shiprocket_status'] ?? ''));

    return [
        'label' => $carrierStatus !== '' ? $carrierStatus : ucfirst($normalizedStatus ?: 'pending'),
        'normalized' => $normalizedStatus ?: 'pending',
        'source' => $carrierStatus !== '' ? 'Shiprocket' : 'System',
        'has_carrier_status' => $carrierStatus !== '',
    ];
}

function shiprocketOrderProgressInfo(array $order)
{
    $statusInfo = shiprocketOrderStatusInfo($order);
    $carrierStatus = strtoupper(trim((string)($order['shiprocket_status'] ?? '')));
    $statusCode = ($order['shiprocket_status_code'] ?? '') !== '' ? (int)$order['shiprocket_status_code'] : null;
    $normalizedStatus = $statusInfo['normalized'];
    $hasShipment = !empty($order['shiprocket_shipment_id']) || !empty($order['shiprocket_awb_code']);

    $stage = 'processing';
    if ($normalizedStatus === 'cancelled' || in_array($statusCode, [8, 16, 45], true) || strpos($carrierStatus, 'CANCEL') !== false) {
        $stage = 'cancelled';
    } elseif ($normalizedStatus === 'delivered' || $statusCode === 7 || strpos($carrierStatus, 'DELIVERED') !== false) {
        $stage = 'delivered';
    } elseif (preg_match('/IN TRANSIT|TRANSIT|OUT FOR DELIVERY|OFD|PICKED UP|INSCAN|REACHED|DESTINATION|WAREHOUSE/', $carrierStatus)) {
        $stage = 'in_transit';
    } elseif ($normalizedStatus === 'shipped' || $hasShipment || preg_match('/SHIPPED|AWB|LABEL|MANIFEST|PICKUP/', $carrierStatus)) {
        $stage = 'shipped';
    }

    $steps = [
        ['key' => 'processing', 'label' => 'Processing', 'icon' => 'fas fa-clipboard-list'],
        ['key' => 'shipped', 'label' => 'Shipped', 'icon' => 'fas fa-box'],
        ['key' => 'in_transit', 'label' => 'In Transit', 'icon' => 'fas fa-truck-fast'],
        ['key' => 'delivered', 'label' => 'Delivered', 'icon' => 'fas fa-house'],
    ];
    $stageIndex = ['processing' => 0, 'shipped' => 1, 'in_transit' => 2, 'delivered' => 3];
    $currentIndex = $stageIndex[$stage] ?? 0;

    return [
        'stage' => $stage,
        'current_index' => $currentIndex,
        'steps' => $steps,
        'status' => $statusInfo,
        'is_cancelled' => $stage === 'cancelled',
    ];
}

function renderShiprocketProgressTracker(array $order)
{
    $progress = shiprocketOrderProgressInfo($order);
    $statusInfo = $progress['status'];
    $steps = $progress['steps'];
    $currentIndex = $progress['current_index'];
    $isCancelled = $progress['is_cancelled'];
    ob_start();
    ?>
    <div class="w-full">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-5">
            <div>
                <div class="text-sm text-gray-500">Real carrier status</div>
                <div class="text-lg font-semibold <?php echo $isCancelled ? 'text-red-700' : 'text-gray-900'; ?>">
                    <?php echo e($statusInfo['label']); ?>
                </div>
            </div>
            <div class="text-sm text-gray-500">
                Source: <?php echo e($statusInfo['source']); ?>
                <?php if (!empty($order['shiprocket_synced_at'])): ?>
                    <span class="block sm:text-right">Updated <?php echo date('M d, Y H:i', strtotime($order['shiprocket_synced_at'])); ?></span>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($isCancelled): ?>
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm font-medium text-red-700">
                <i class="fas fa-circle-xmark mr-2"></i>This order is cancelled.
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 sm:gap-3">
            <?php foreach ($steps as $index => $step):
                $done = !$isCancelled && $index < $currentIndex;
                $active = !$isCancelled && $index === $currentIndex;
                $circleClass = ($done || $active)
                    ? 'bg-green-500 text-white border-green-500'
                    : 'bg-gray-100 text-gray-400 border-gray-300';
                $labelClass = ($done || $active) ? 'text-green-700' : 'text-gray-500';
                $lineClass = (!$isCancelled && $index < $currentIndex) ? 'bg-green-500' : 'bg-gray-300';
            ?>
                <div class="relative flex sm:flex-col items-center sm:items-center gap-3 sm:gap-2">
                    <?php if ($index < count($steps) - 1): ?>
                        <div class="hidden sm:block absolute left-1/2 top-8 h-1 w-full <?php echo $lineClass; ?>"></div>
                    <?php endif; ?>
                    <div class="relative z-10 flex h-16 w-16 shrink-0 items-center justify-center rounded-full border-2 <?php echo $circleClass; ?>">
                        <i class="<?php echo e($step['icon']); ?> text-2xl"></i>
                    </div>
                    <div class="min-w-0 sm:text-center">
                        <div class="text-sm font-semibold <?php echo $labelClass; ?>"><?php echo e($step['label']); ?></div>
                        <?php if ($active): ?>
                            <div class="text-xs text-gray-500">Current</div>
                        <?php elseif ($done): ?>
                            <div class="text-xs text-gray-500">Done</div>
                        <?php else: ?>
                            <div class="text-xs text-gray-400">Pending</div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function shiprocketBuildOrderPayload(PDO $pdo, $orderId)
{
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([(int)$orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        throw new Exception('Order not found.');
    }

    $stmt = $pdo->prepare("SELECT oi.*, p.name AS product_name, COALESCE(pv.sku, CONCAT('SKU-', p.id)) AS sku
                           FROM order_items oi
                           JOIN products p ON oi.product_id = p.id
                           LEFT JOIN product_variants pv ON oi.variant_id = pv.id
                           WHERE oi.order_id = ?");
    $stmt->execute([(int)$orderId]);
    $items = $stmt->fetchAll();

    if (!$items) {
        throw new Exception('Order has no items.');
    }

    $shiprocketItems = [];
    foreach ($items as $item) {
        $shiprocketItems[] = [
            'name' => (string)$item['product_name'],
            'sku' => (string)($item['sku'] ?: ('SKU-' . $item['product_id'])),
            'units' => (int)$item['quantity'],
            'selling_price' => (float)$item['price'],
            'discount' => 0,
            'tax' => 0,
        ];
    }

    $paymentMethod = ($order['payment_method'] ?? '') === 'cod' ? 'COD' : 'Prepaid';
    $collectableAmount = $paymentMethod === 'COD'
        ? max(0, (float)($order['remaining_payment_amount'] ?? $order['total_amount']))
        : 0;

    return [
        'order_id' => 'ECOM-' . $order['id'],
        'order_date' => date('Y-m-d H:i', strtotime($order['created_at'] ?? 'now')),
        'pickup_location' => SHIPROCKET_PICKUP_LOCATION,
        'channel_id' => defined('SHIPROCKET_CHANNEL_ID') ? SHIPROCKET_CHANNEL_ID : '',
        'comment' => 'Website order #' . $order['id'],
        'billing_customer_name' => (string)$order['name'],
        'billing_last_name' => '',
        'billing_address' => (string)$order['address'],
        'billing_city' => (string)$order['city'],
        'billing_pincode' => (string)$order['pincode'],
        'billing_state' => (string)$order['state'],
        'billing_country' => 'India',
        'billing_email' => (string)$order['email'],
        'billing_phone' => (string)$order['mobile'],
        'shipping_is_billing' => true,
        'order_items' => $shiprocketItems,
        'payment_method' => $paymentMethod,
        'shipping_charges' => 0,
        'giftwrap_charges' => 0,
        'transaction_charges' => 0,
        'total_discount' => (float)($order['discount_amount'] ?? 0),
        'sub_total' => (float)$order['total_amount'],
        'length' => (float)(defined('SHIPROCKET_DEFAULT_LENGTH') ? SHIPROCKET_DEFAULT_LENGTH : 10),
        'breadth' => (float)(defined('SHIPROCKET_DEFAULT_BREADTH') ? SHIPROCKET_DEFAULT_BREADTH : 10),
        'height' => (float)(defined('SHIPROCKET_DEFAULT_HEIGHT') ? SHIPROCKET_DEFAULT_HEIGHT : 5),
        'weight' => (float)(defined('SHIPROCKET_DEFAULT_WEIGHT') ? SHIPROCKET_DEFAULT_WEIGHT : 0.5),
        'collectable_amount' => $collectableAmount,
    ];
}

function shiprocketRememberError(PDO $pdo, $orderId, $message)
{
    shiprocketEnsureSchema($pdo);
    $stmt = $pdo->prepare("UPDATE orders SET shiprocket_error = ?, shiprocket_synced_at = NOW() WHERE id = ?");
    $stmt->execute([$message, (int)$orderId]);
}

function shiprocketCreateShipmentForOrder(PDO $pdo, $orderId)
{
    shiprocketEnsureSchema($pdo);

    if (!shiprocketIsConfigured()) {
        shiprocketRememberError($pdo, $orderId, 'Shiprocket is not configured.');
        return ['success' => false, 'message' => 'Shiprocket is not configured.'];
    }

    $stmt = $pdo->prepare("SELECT shiprocket_shipment_id, shiprocket_awb_code FROM orders WHERE id = ?");
    $stmt->execute([(int)$orderId]);
    $existing = $stmt->fetch();

    if (!empty($existing['shiprocket_shipment_id']) || !empty($existing['shiprocket_awb_code'])) {
        return ['success' => true, 'message' => 'Shipment already exists.'];
    }

    try {
        $token = shiprocketGetToken();
        $payload = shiprocketBuildOrderPayload($pdo, $orderId);
        $createResponse = shiprocketRequest('POST', 'orders/create/adhoc', $payload, $token);

        $shiprocketOrderId = $createResponse['order_id'] ?? null;
        $shipmentId = $createResponse['shipment_id'] ?? null;

        $awbCode = null;
        $courierName = null;
        $awbResponse = null;

        if ($shipmentId) {
            $awbResponse = shiprocketRequest('POST', 'courier/assign/awb', [
                'shipment_id' => $shipmentId,
            ], $token);

            $awbData = $awbResponse['response']['data'] ?? $awbResponse['data'] ?? $awbResponse;
            $awbCode = $awbData['awb_code'] ?? $awbData['awb'] ?? null;
            $courierName = $awbData['courier_name'] ?? $awbData['courier_company_id'] ?? null;
        }

        $stmt = $pdo->prepare("UPDATE orders
                               SET shiprocket_order_id = ?,
                                   shiprocket_shipment_id = ?,
                                   shiprocket_awb_code = ?,
                                   shiprocket_courier_name = ?,
                                   shiprocket_status = COALESCE(shiprocket_status, 'AWB Assigned'),
                                   shiprocket_status_code = COALESCE(shiprocket_status_code, 1),
                                   shiprocket_payload = ?,
                                   shiprocket_error = NULL,
                                   shiprocket_synced_at = NOW(),
                                   status = CASE WHEN status = 'pending' THEN 'processing' ELSE status END
                               WHERE id = ?");
        $stmt->execute([
            $shiprocketOrderId,
            $shipmentId,
            $awbCode,
            $courierName,
            json_encode(['create' => $createResponse, 'awb' => $awbResponse]),
            (int)$orderId,
        ]);

        return ['success' => true, 'message' => 'Shiprocket shipment created.'];
    } catch (Exception $e) {
        shiprocketRememberError($pdo, $orderId, $e->getMessage());
        error_log('Shiprocket Create Shipment Error: Order #' . (int)$orderId . ' - ' . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function shiprocketApplyTrackingUpdate(PDO $pdo, array $data)
{
    shiprocketEnsureSchema($pdo);

    $awbCode = $data['awb'] ?? $data['awb_code'] ?? $data['awb_number'] ?? null;
    $shipmentId = $data['shipment_id'] ?? $data['shiprocket_shipment_id'] ?? null;
    $shiprocketOrderId = $data['order_id'] ?? $data['shiprocket_order_id'] ?? null;
    $merchantOrderId = $data['order_id'] ?? $data['channel_order_id'] ?? $data['orderId'] ?? null;

    $shipmentStatus = $data['shipment_status'] ?? $data['current_status'] ?? $data['status'] ?? null;
    $shipmentStatusCode = $data['shipment_status_id'] ?? $data['current_status_id'] ?? $data['status_code'] ?? null;
    $courierName = $data['courier_name'] ?? $data['courier'] ?? null;
    $trackingUrl = $data['track_url'] ?? $data['tracking_url'] ?? null;

    $where = [];
    $params = [];

    if ($awbCode) {
        $where[] = 'shiprocket_awb_code = ?';
        $params[] = $awbCode;
    }

    if ($shipmentId) {
        $where[] = 'shiprocket_shipment_id = ?';
        $params[] = $shipmentId;
    }

    if ($shiprocketOrderId) {
        $where[] = 'shiprocket_order_id = ?';
        $params[] = $shiprocketOrderId;
    }

    if ($merchantOrderId && preg_match('/ECOM-(\d+)/', (string)$merchantOrderId, $matches)) {
        $where[] = 'id = ?';
        $params[] = (int)$matches[1];
    }

    if (!$where) {
        throw new Exception('Tracking update did not include a matching order identifier.');
    }

    $orderStatus = shiprocketNormalizeOrderStatus($shipmentStatus, $shipmentStatusCode);
    $set = [
        'shiprocket_awb_code = COALESCE(?, shiprocket_awb_code)',
        'shiprocket_shipment_id = COALESCE(?, shiprocket_shipment_id)',
        'shiprocket_order_id = COALESCE(?, shiprocket_order_id)',
        'shiprocket_courier_name = COALESCE(?, shiprocket_courier_name)',
        'shiprocket_status = COALESCE(?, shiprocket_status)',
        'shiprocket_status_code = COALESCE(?, shiprocket_status_code)',
        'shiprocket_tracking_url = COALESCE(?, shiprocket_tracking_url)',
        'shiprocket_payload = ?',
        'shiprocket_error = NULL',
        'shiprocket_synced_at = NOW()',
    ];
    $updateParams = [
        $awbCode,
        $shipmentId,
        $shiprocketOrderId,
        $courierName,
        $shipmentStatus,
        $shipmentStatusCode !== null && $shipmentStatusCode !== '' ? (int)$shipmentStatusCode : null,
        $trackingUrl,
        json_encode($data),
    ];

    if ($orderStatus) {
        $set[] = 'status = ?';
        $updateParams[] = $orderStatus;

        if ($orderStatus === 'shipped') {
            $set[] = 'shipped_at = COALESCE(shipped_at, NOW())';
        }

        if ($orderStatus === 'delivered') {
            $set[] = 'delivered_at = COALESCE(delivered_at, NOW())';
        }
    }

    $sql = 'UPDATE orders SET ' . implode(', ', $set) . ' WHERE ' . implode(' OR ', $where);
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge($updateParams, $params));

    if ($stmt->rowCount() === 0) {
        throw new Exception('No local order matched the Shiprocket tracking update.');
    }

    return $orderStatus;
}

function shiprocketSyncTrackingForOrder(PDO $pdo, $orderId)
{
    shiprocketEnsureSchema($pdo);

    if (!shiprocketIsConfigured()) {
        shiprocketRememberError($pdo, $orderId, 'Shiprocket is not configured.');
        return ['success' => false, 'message' => 'Shiprocket is not configured.'];
    }

    $stmt = $pdo->prepare("SELECT shiprocket_awb_code, shiprocket_shipment_id FROM orders WHERE id = ?");
    $stmt->execute([(int)$orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        return ['success' => false, 'message' => 'Order not found.'];
    }

    if (empty($order['shiprocket_awb_code']) && empty($order['shiprocket_shipment_id'])) {
        return ['success' => false, 'message' => 'Create the Shiprocket shipment before syncing tracking.'];
    }

    try {
        $token = shiprocketGetToken();
        $endpoint = !empty($order['shiprocket_awb_code'])
            ? 'courier/track/awb/' . rawurlencode($order['shiprocket_awb_code'])
            : 'courier/track/shipment/' . rawurlencode($order['shiprocket_shipment_id']);
        $response = shiprocketRequest('GET', $endpoint, null, $token);

        $tracking = $response['tracking_data']['shipment_track'][0] ?? $response['tracking_data'] ?? $response;
        if (is_array($tracking)) {
            $tracking['awb_code'] = $tracking['awb_code'] ?? $order['shiprocket_awb_code'];
            $tracking['shipment_id'] = $tracking['shipment_id'] ?? $order['shiprocket_shipment_id'];
            $tracking['shipment_status'] = $tracking['shipment_status'] ?? ($tracking['current_status'] ?? null);
            $tracking['shipment_status_id'] = $tracking['shipment_status_id'] ?? ($tracking['current_status_id'] ?? null);
            shiprocketApplyTrackingUpdate($pdo, $tracking);
        }

        return ['success' => true, 'message' => 'Shiprocket tracking synced.'];
    } catch (Exception $e) {
        shiprocketRememberError($pdo, $orderId, $e->getMessage());
        error_log('Shiprocket Tracking Sync Error: Order #' . (int)$orderId . ' - ' . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}
