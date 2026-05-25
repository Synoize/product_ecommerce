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
    $definition = shiprocketStatusDefinition($status, $statusCode);

    if ($definition) {
        if (in_array($definition['stage'], ['delivered'], true)) {
            return 'delivered';
        }

        if (
            in_array($definition['stage'], ['cancelled', 'rto', 'rto_delivered'], true)
            || in_array($definition['code'], [12, 24, 25, 44, 76], true)
        ) {
            return 'cancelled';
        }

        if (in_array($definition['stage'], ['picked_up', 'shipped', 'in_transit', 'out_for_delivery', 'exception'], true)) {
            return 'shipped';
        }

        return 'processing';
    }

    if ($code === 7 || strpos($normalized, 'DELIVERED') !== false) {
        return 'delivered';
    }

    if (in_array($code, [8, 16, 45], true) || strpos($normalized, 'CANCEL') !== false) {
        return 'cancelled';
    }

    if (
        in_array($code, [6, 17, 18, 19, 22, 27, 38, 42, 48], true)
        || preg_match('/SHIPPED|TRANSIT|PICKED|PICKUP|OFD|DESTINATION|WAREHOUSE/', $normalized)
    ) {
        return 'shipped';
    }

    if (
        in_array($code, [1, 2, 3, 4, 5, 11, 13, 15, 20, 21], true)
        || preg_match('/AWB|LABEL|MANIFEST|PENDING|PROCESS|PICKUP|UNDELIVERED|EXCEPTION/', $normalized)
    ) {
        return 'processing';
    }

    return null;
}

function shiprocketStatusCatalog()
{
    return [
        1 => ['label' => 'AWB Assigned', 'stage' => 'awb_assigned', 'tone' => 'progress'],
        2 => ['label' => 'Label Generated', 'stage' => 'awb_assigned', 'tone' => 'progress'],
        3 => ['label' => 'Pickup Scheduled', 'stage' => 'pickup_scheduled', 'tone' => 'progress'],
        4 => ['label' => 'Pickup Queued', 'stage' => 'pickup_scheduled', 'tone' => 'progress'],
        5 => ['label' => 'Manifest Generated', 'stage' => 'pickup_scheduled', 'tone' => 'progress'],
        6 => ['label' => 'Shipped', 'stage' => 'shipped', 'tone' => 'progress'],
        7 => ['label' => 'Delivered', 'stage' => 'delivered', 'tone' => 'success'],
        8 => ['label' => 'Cancelled', 'stage' => 'cancelled', 'tone' => 'danger'],
        9 => ['label' => 'RTO Initiated', 'stage' => 'rto', 'tone' => 'danger'],
        10 => ['label' => 'RTO Delivered', 'stage' => 'rto_delivered', 'tone' => 'danger'],
        11 => ['label' => 'Pending', 'stage' => 'processing', 'tone' => 'warning'],
        12 => ['label' => 'Lost', 'stage' => 'exception', 'tone' => 'danger'],
        13 => ['label' => 'Pickup Error', 'stage' => 'exception', 'tone' => 'danger'],
        14 => ['label' => 'RTO Acknowledged', 'stage' => 'rto', 'tone' => 'danger'],
        15 => ['label' => 'Pickup Rescheduled', 'stage' => 'pickup_scheduled', 'tone' => 'warning'],
        16 => ['label' => 'Cancellation Requested', 'stage' => 'cancelled', 'tone' => 'danger'],
        17 => ['label' => 'Out For Delivery', 'stage' => 'out_for_delivery', 'tone' => 'progress'],
        18 => ['label' => 'In Transit', 'stage' => 'in_transit', 'tone' => 'progress'],
        19 => ['label' => 'Out For Pickup', 'stage' => 'pickup_scheduled', 'tone' => 'progress'],
        20 => ['label' => 'Pickup Exception', 'stage' => 'exception', 'tone' => 'danger'],
        21 => ['label' => 'Undelivered', 'stage' => 'exception', 'tone' => 'danger'],
        22 => ['label' => 'Delayed', 'stage' => 'in_transit', 'tone' => 'warning'],
        23 => ['label' => 'Partial Delivered', 'stage' => 'delivered', 'tone' => 'warning'],
        24 => ['label' => 'Destroyed', 'stage' => 'exception', 'tone' => 'danger'],
        25 => ['label' => 'Damaged', 'stage' => 'exception', 'tone' => 'danger'],
        26 => ['label' => 'Fulfilled', 'stage' => 'delivered', 'tone' => 'success'],
        27 => ['label' => 'Pickup Booked', 'stage' => 'pickup_scheduled', 'tone' => 'progress'],
        38 => ['label' => 'Reached at Destination Hub', 'stage' => 'in_transit', 'tone' => 'progress'],
        39 => ['label' => 'Misrouted', 'stage' => 'exception', 'tone' => 'danger'],
        40 => ['label' => 'RTO NDR', 'stage' => 'rto', 'tone' => 'danger'],
        41 => ['label' => 'RTO Out For Delivery', 'stage' => 'rto', 'tone' => 'danger'],
        42 => ['label' => 'Picked Up', 'stage' => 'picked_up', 'tone' => 'progress'],
        43 => ['label' => 'Self Fulfilled', 'stage' => 'delivered', 'tone' => 'success'],
        44 => ['label' => 'Disposed Off', 'stage' => 'exception', 'tone' => 'danger'],
        45 => ['label' => 'Cancelled Before Dispatched', 'stage' => 'cancelled', 'tone' => 'danger'],
        46 => ['label' => 'RTO In Transit', 'stage' => 'rto', 'tone' => 'danger'],
        47 => ['label' => 'QC Failed', 'stage' => 'exception', 'tone' => 'danger'],
        48 => ['label' => 'Reached Warehouse', 'stage' => 'in_transit', 'tone' => 'progress'],
        49 => ['label' => 'Custom Cleared', 'stage' => 'in_transit', 'tone' => 'progress'],
        50 => ['label' => 'In Flight', 'stage' => 'in_transit', 'tone' => 'progress'],
        51 => ['label' => 'Handover to Courier', 'stage' => 'shipped', 'tone' => 'progress'],
        52 => ['label' => 'Shipment Booked', 'stage' => 'awb_assigned', 'tone' => 'progress'],
        54 => ['label' => 'In Transit Overseas', 'stage' => 'in_transit', 'tone' => 'progress'],
        55 => ['label' => 'Connection Aligned', 'stage' => 'in_transit', 'tone' => 'progress'],
        56 => ['label' => 'Reached Overseas Warehouse', 'stage' => 'in_transit', 'tone' => 'progress'],
        57 => ['label' => 'Custom Cleared Overseas', 'stage' => 'in_transit', 'tone' => 'progress'],
        59 => ['label' => 'Box Packing', 'stage' => 'processing', 'tone' => 'progress'],
        60 => ['label' => 'FC Allocated', 'stage' => 'processing', 'tone' => 'progress'],
        61 => ['label' => 'Picklist Generated', 'stage' => 'processing', 'tone' => 'progress'],
        62 => ['label' => 'Ready To Pack', 'stage' => 'processing', 'tone' => 'progress'],
        63 => ['label' => 'Packed', 'stage' => 'processing', 'tone' => 'progress'],
        67 => ['label' => 'FC Manifest Generated', 'stage' => 'pickup_scheduled', 'tone' => 'progress'],
        68 => ['label' => 'Processed at Warehouse', 'stage' => 'processing', 'tone' => 'progress'],
        71 => ['label' => 'Handover Exception', 'stage' => 'exception', 'tone' => 'danger'],
        72 => ['label' => 'Packed Exception', 'stage' => 'exception', 'tone' => 'danger'],
        75 => ['label' => 'RTO Lock', 'stage' => 'rto', 'tone' => 'danger'],
        76 => ['label' => 'Untraceable', 'stage' => 'exception', 'tone' => 'danger'],
        77 => ['label' => 'Issue Related to the Recipient', 'stage' => 'exception', 'tone' => 'danger'],
        78 => ['label' => 'Reached Back at Seller City', 'stage' => 'rto', 'tone' => 'danger'],
        79 => ['label' => 'Rider Assigned', 'stage' => 'out_for_delivery', 'tone' => 'progress'],
        80 => ['label' => 'Rider Unassigned', 'stage' => 'exception', 'tone' => 'warning'],
        81 => ['label' => 'Rider Assigned', 'stage' => 'out_for_delivery', 'tone' => 'progress'],
        82 => ['label' => 'Rider Reached at Drop', 'stage' => 'out_for_delivery', 'tone' => 'progress'],
        83 => ['label' => 'Searching for Rider', 'stage' => 'out_for_delivery', 'tone' => 'warning'],
    ];
}

function shiprocketProgressStages($audience = 'admin')
{
    if ($audience === 'customer') {
        return [
            ['key' => 'processing', 'label' => 'Processing', 'icon' => 'fas fa-clipboard-list'],
            ['key' => 'shipped', 'label' => 'Shipped', 'icon' => 'fas fa-box'],
            ['key' => 'in_transit', 'label' => 'In Transit', 'icon' => 'fas fa-truck-fast'],
            ['key' => 'out_for_delivery', 'label' => 'Out For Delivery', 'icon' => 'fas fa-location-dot'],
            ['key' => 'delivered', 'label' => 'Delivered', 'icon' => 'fas fa-house'],
        ];
    }

    return [
        ['key' => 'processing', 'label' => 'Processing', 'icon' => 'fas fa-clipboard-list'],
        ['key' => 'awb_assigned', 'label' => 'AWB Assigned', 'icon' => 'fas fa-barcode'],
        ['key' => 'pickup_scheduled', 'label' => 'Pickup', 'icon' => 'fas fa-calendar-check'],
        ['key' => 'picked_up', 'label' => 'Picked Up', 'icon' => 'fas fa-box-open'],
        ['key' => 'shipped', 'label' => 'Shipped', 'icon' => 'fas fa-box'],
        ['key' => 'in_transit', 'label' => 'In Transit', 'icon' => 'fas fa-truck-fast'],
        ['key' => 'out_for_delivery', 'label' => 'Out For Delivery', 'icon' => 'fas fa-location-dot'],
        ['key' => 'delivered', 'label' => 'Delivered', 'icon' => 'fas fa-house'],
    ];
}

function shiprocketCustomerStage($stage)
{
    if (in_array($stage, ['awb_assigned', 'pickup_scheduled'], true)) {
        return 'processing';
    }

    if (in_array($stage, ['picked_up'], true)) {
        return 'shipped';
    }

    return $stage;
}

function shiprocketCustomerStatusInfo(array $order)
{
    $progress = shiprocketOrderProgressInfo($order, 'customer');
    $stageLabels = array_column($progress['steps'], 'label', 'key');
    $statusInfo = $progress['status'];

    if ($statusInfo['source'] === 'Shiprocket' && in_array($statusInfo['shiprocket_stage'], ['awb_assigned', 'pickup_scheduled'], true)) {
        $statusInfo['label'] = 'Processing';
    } elseif ($statusInfo['source'] === 'Shiprocket' && $statusInfo['shiprocket_stage'] === 'picked_up') {
        $statusInfo['label'] = 'Shipped';
    } elseif (isset($stageLabels[$progress['stage']]) && !$progress['is_exception'] && !$progress['is_cancelled']) {
        $statusInfo['label'] = $stageLabels[$progress['stage']];
    }

    return $statusInfo;
}

function shiprocketNormalizeStatusText($status)
{
    return preg_replace('/[^A-Z0-9]+/', '', strtoupper(trim((string)$status)));
}

function shiprocketStatusDefinition($status, $statusCode = null)
{
    $catalog = shiprocketStatusCatalog();
    $code = $statusCode !== null && $statusCode !== '' ? (int)$statusCode : null;

    if ($code !== null && isset($catalog[$code])) {
        return $catalog[$code] + ['code' => $code];
    }

    $normalizedStatus = shiprocketNormalizeStatusText($status);
    if ($normalizedStatus === '') {
        return null;
    }

    foreach ($catalog as $catalogCode => $definition) {
        if (shiprocketNormalizeStatusText($definition['label']) === $normalizedStatus) {
            return $definition + ['code' => $catalogCode];
        }
    }

    return null;
}

function shiprocketOrderStatusInfo(array $order)
{
    $normalizedStatus = $order['status'] ?? 'pending';
    $carrierStatus = trim((string)($order['shiprocket_status'] ?? ''));
    $statusCode = ($order['shiprocket_status_code'] ?? '') !== '' ? (int)$order['shiprocket_status_code'] : null;
    $definition = shiprocketStatusDefinition($carrierStatus, $statusCode);
    $label = $carrierStatus !== '' ? $carrierStatus : ($definition['label'] ?? ucfirst($normalizedStatus ?: 'pending'));

    return [
        'label' => $label,
        'normalized' => $normalizedStatus ?: 'pending',
        'source' => ($carrierStatus !== '' || $definition) ? 'Shiprocket' : 'System',
        'has_carrier_status' => $carrierStatus !== '' || $definition !== null,
        'shiprocket_code' => $definition['code'] ?? $statusCode,
        'shiprocket_stage' => $definition['stage'] ?? null,
        'tone' => $definition['tone'] ?? 'progress',
    ];
}

function shiprocketOrderProgressInfo(array $order, $audience = 'admin')
{
    $statusInfo = shiprocketOrderStatusInfo($order);
    $carrierStatus = strtoupper(trim((string)($order['shiprocket_status'] ?? '')));
    $statusCode = ($order['shiprocket_status_code'] ?? '') !== '' ? (int)$order['shiprocket_status_code'] : null;
    $normalizedStatus = $statusInfo['normalized'];
    $hasShipment = !empty($order['shiprocket_shipment_id']) || !empty($order['shiprocket_awb_code']);

    $stage = $statusInfo['shiprocket_stage'] ?? 'processing';
    if (!$statusInfo['shiprocket_stage']) {
        if ($normalizedStatus === 'cancelled' || in_array($statusCode, [8, 16, 45], true) || strpos($carrierStatus, 'CANCEL') !== false) {
            $stage = 'cancelled';
        } elseif ($normalizedStatus === 'delivered' || $statusCode === 7 || strpos($carrierStatus, 'DELIVERED') !== false) {
            $stage = 'delivered';
        } elseif (preg_match('/OUT FOR DELIVERY|OFD|RIDER/', $carrierStatus)) {
            $stage = 'out_for_delivery';
        } elseif (preg_match('/IN TRANSIT|TRANSIT|INSCAN|REACHED|DESTINATION|WAREHOUSE|IN FLIGHT|CUSTOM CLEARED/', $carrierStatus)) {
            $stage = 'in_transit';
        } elseif (preg_match('/PICKED UP/', $carrierStatus)) {
            $stage = 'picked_up';
        } elseif ($normalizedStatus === 'shipped' || preg_match('/SHIPPED|HANDOVER/', $carrierStatus)) {
            $stage = 'shipped';
        } elseif ($hasShipment || preg_match('/AWB|LABEL|MANIFEST|PICKUP|BOOKED/', $carrierStatus)) {
            $stage = 'awb_assigned';
        }
    }

    if ($audience === 'customer') {
        $stage = shiprocketCustomerStage($stage);
    }

    $steps = shiprocketProgressStages($audience);
    $stageIndex = array_flip(array_column($steps, 'key'));
    $specialStageIndex = [
        'cancelled' => 0,
        'exception' => min(5, count($steps) - 1),
        'rto' => min(5, count($steps) - 1),
        'rto_delivered' => min(5, count($steps) - 1),
    ];
    $currentIndex = $stageIndex[$stage] ?? 0;
    if (!isset($stageIndex[$stage]) && isset($specialStageIndex[$stage])) {
        $currentIndex = $specialStageIndex[$stage];
    }

    return [
        'stage' => $stage,
        'current_index' => $currentIndex,
        'steps' => $steps,
        'status' => $statusInfo,
        'is_cancelled' => $stage === 'cancelled',
        'is_exception' => in_array($stage, ['exception', 'rto', 'rto_delivered'], true),
    ];
}

function renderShiprocketProgressTracker(array $order, $audience = 'admin')
{
    $progress = shiprocketOrderProgressInfo($order, $audience);
    $statusInfo = $progress['status'];
    if ($audience === 'customer') {
        $statusInfo = shiprocketCustomerStatusInfo($order);
    }
    $steps = $progress['steps'];
    $currentIndex = $progress['current_index'];
    $isCancelled = $progress['is_cancelled'];
    $isException = $progress['is_exception'];
    $tone = $statusInfo['tone'];
    $statusTextClass = $tone === 'danger'
        ? 'text-red-700'
        : ($tone === 'warning' ? 'text-yellow-700' : 'text-gray-900');
    ob_start();
?>
    <div class="w-full">
        <!-- <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-5">
            <div>
                <div class="text-sm text-gray-500">Order Status</div>
                <div class="text-lg font-semibold <?php echo $statusTextClass; ?>">
                    <?php echo e($statusInfo['label']); ?>
                </div>
            </div>
            <div class="text-sm text-gray-500">
                Source: <?php echo e($statusInfo['source']); ?>
                <?php if (!empty($order['shiprocket_synced_at'])): ?>
                    <span class="block sm:text-right">Updated <?php echo date('M d, Y H:i', strtotime($order['shiprocket_synced_at'])); ?></span>
                <?php endif; ?>
            </div>
        </div> -->

        <?php if ($isCancelled): ?>
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm font-medium text-red-700">
                <i class="fas fa-circle-xmark mr-2"></i>This order is cancelled.
            </div>
        <?php elseif ($isException): ?>
            <div class="mb-4 rounded-lg border border-yellow-200 bg-yellow-50 p-3 text-sm font-medium text-yellow-800">
                <i class="fas fa-triangle-exclamation mr-2"></i>Shipment needs attention: <?php echo e($statusInfo['label']); ?>.
            </div>
        <?php endif; ?>

       <div class="overflow-x-auto pb-2 [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden">
            <div class="flex <?php echo $audience === 'customer' ? 'min-w-[380px]' : 'min-w-[880px]'; ?> items-start">
                <?php foreach ($steps as $index => $step):
                    $done = !$isCancelled && $index < $currentIndex;
                    $active = !$isCancelled && $index === $currentIndex;
                    if ($done) {
                        $circleClass = 'bg-green-500 text-white border-green-500';
                        $labelClass = 'text-green-700';
                    } elseif ($active && $isException) {
                        $circleClass = $tone === 'danger'
                            ? 'bg-red-500 text-white border-red-500'
                            : 'bg-yellow-500 text-white border-yellow-500';
                        $labelClass = $tone === 'danger' ? 'text-red-700' : 'text-yellow-700';
                    } elseif ($active) {
                        $circleClass = 'bg-green-500 text-white border-green-500';
                        $labelClass = 'text-green-700';
                    } else {
                        $circleClass = 'bg-white text-gray-400 border-gray-300';
                        $labelClass = 'text-gray-500';
                    }
                    $lineClass = (!$isCancelled && $index < $currentIndex) ? 'bg-green-500' : 'bg-gray-300';
                ?>
                    <div class="relative flex flex-1 flex-col items-center text-center">

                        <!-- LINE -->
                        <?php if ($index < count($steps) - 1): ?>
                            <div class="absolute top-5 md:top-8 left-1/2 w-full h-[3px] <?php echo $lineClass; ?> rounded-full"></div>
                        <?php endif; ?>

                        <!-- ICON -->
                        <div class="relative z-10 flex h-10 w-10 md:h-16 md:w-16 items-center justify-center rounded-full border-2 transition-all duration-300 shadow-md
        <?php echo $circleClass; ?>">

                            <!-- Glow Effect -->
                            <div class="absolute inset-0 rounded-full blur-md opacity-20 <?php echo $circleClass; ?>"></div>

                            <i class="<?php echo e($step['icon']); ?> text-sm md:text-xl relative z-10"></i>
                        </div>

                        <!-- CONTENT -->
                        <div class="mt-3 flex flex-col items-center">

                            <!-- LABEL -->
                            <h4 class="text-[11px] md:text-sm font-semibold tracking-wide <?php echo $labelClass; ?>">
                                <?php echo e($step['label']); ?>
                            </h4>

                            <!-- STATUS -->
                            <?php if ($active): ?>
                                <span class="mt-1 rounded-full bg-green-100 px-3 py-1 text-[10px] md:text-xs font-medium text-green-600">
                                    Current
                                </span>

                            <?php elseif ($done): ?>
                                <span class="mt-1 rounded-full bg-green-100 px-3 py-1 text-[10px] md:text-xs font-medium text-green-600">
                                    Completed
                                </span>

                            <?php else: ?>
                                <span class="mt-1 rounded-full bg-gray-100 px-3 py-1 text-[10px] md:text-xs font-medium text-gray-500">
                                    Pending
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
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
