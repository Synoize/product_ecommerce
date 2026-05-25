<?php
/**
 * Admin - Manage Shipping
 */

$pageTitle = 'Manage Shipping';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../includes/shiprocket.php';
requireAdmin();

shiprocketEnsureSchema($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['shiprocket_action'], $_POST['order_id'])) {
    $orderId = (int)$_POST['order_id'];
    $action = $_POST['shiprocket_action'];
    $result = ['success' => false, 'message' => 'Invalid Shiprocket action.'];

    if ($orderId > 0 && $action === 'create') {
        $result = shiprocketCreateShipmentForOrder($pdo, $orderId);
    } elseif ($orderId > 0 && $action === 'sync') {
        $result = shiprocketSyncTrackingForOrder($pdo, $orderId);
    }

    setFlash($result['message'], $result['success'] ? 'success' : 'warning');
    redirect(BASE_URL . 'admin/manage_shipping.php');
}

$shippingFilter = $_GET['shipping'] ?? '';
$search = trim($_GET['search'] ?? '');

$query = "SELECT o.*, u.name AS user_name, u.email AS user_email
          FROM orders o
          JOIN users u ON o.user_id = u.id
          WHERE 1=1";
$params = [];

if ($shippingFilter === 'not_created') {
    $query .= " AND o.shiprocket_shipment_id IS NULL AND o.shiprocket_awb_code IS NULL AND o.shiprocket_error IS NULL";
} elseif ($shippingFilter === 'needs_setup') {
    $query .= " AND o.shiprocket_error IS NOT NULL";
} elseif ($shippingFilter === 'created') {
    $query .= " AND (o.shiprocket_shipment_id IS NOT NULL OR o.shiprocket_awb_code IS NOT NULL)";
} elseif ($shippingFilter === 'delivered') {
    $query .= " AND o.status = 'delivered'";
} elseif ($shippingFilter === 'in_transit') {
    $query .= " AND o.status IN ('processing', 'shipped')";
}

if ($search !== '') {
    $query .= " AND (o.id LIKE ? OR u.name LIKE ? OR u.email LIKE ? OR o.shiprocket_awb_code LIKE ? OR o.shiprocket_shipment_id LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " ORDER BY o.created_at DESC";

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$countQuery = preg_replace('/SELECT o\.\*, u\.name AS user_name, u\.email AS user_email\s+FROM/s', 'SELECT COUNT(*) AS total FROM', $query, 1);
$countStmt = $pdo->prepare($countQuery);
$countStmt->execute($params);
$totalShipments = (int)$countStmt->fetch()['total'];
$totalPages = (int)ceil($totalShipments / $perPage);

$query .= " LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll();
?>

<div class="bg-white mt-20">
    <div class="h-[calc(100vh-80px)] flex">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <div class="flex-1 overflow-y-auto p-6 md:p-8">
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-6 gap-4">
                <h2 class="text-2xl font-bold text-gray-900">Shipping</h2>
                <form action="<?php echo BASE_URL; ?>admin/manage_shipping.php" method="GET" class="flex flex-wrap gap-2">
                    <select name="shipping" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500" onchange="this.form.submit()">
                        <option value="">All Shipping</option>
                        <option value="not_created" <?php echo $shippingFilter === 'not_created' ? 'selected' : ''; ?>>Not Created</option>
                        <option value="created" <?php echo $shippingFilter === 'created' ? 'selected' : ''; ?>>Created</option>
                        <option value="in_transit" <?php echo $shippingFilter === 'in_transit' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="delivered" <?php echo $shippingFilter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                        <option value="needs_setup" <?php echo $shippingFilter === 'needs_setup' ? 'selected' : ''; ?>>Needs Setup</option>
                    </select>
                    <input type="search" name="search" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 w-56"
                           placeholder="Search shipments..." value="<?php echo e($search); ?>">
                    <button type="submit" class="bg-primary-500 hover:bg-primary-600 text-white font-semibold py-2 px-4 rounded-lg transition">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>

            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Order</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Customer</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Carrier Status</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Shipment</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Address</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Sync</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($orders as $order):
                                $statusInfo = shiprocketOrderStatusInfo($order);
                                $trackingUrl = !empty($order['shiprocket_tracking_url'])
                                    ? $order['shiprocket_tracking_url']
                                    : (!empty($order['shiprocket_awb_code'])
                                        ? 'https://shiprocket.co/tracking/' . rawurlencode($order['shiprocket_awb_code'])
                                        : '');
                            ?>
                                <tr>
                                    <td class="px-4 py-3 text-sm font-medium">#<?php echo (int)$order['id']; ?></td>
                                    <td class="px-4 py-3">
                                        <div class="text-sm font-medium text-gray-900"><?php echo e($order['user_name']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo e($order['user_email']); ?></div>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <div class="font-medium text-gray-900"><?php echo e($statusInfo['label']); ?></div>
                                        <div class="text-xs text-gray-500"><?php echo e($statusInfo['source']); ?></div>
                                        <?php if (!empty($order['shiprocket_error'])): ?>
                                            <div class="mt-1 max-w-56 text-xs text-yellow-700" title="<?php echo e($order['shiprocket_error']); ?>">
                                                <?php echo e($order['shiprocket_error']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <div>Shipment: <span class="font-medium"><?php echo e($order['shiprocket_shipment_id'] ?: 'Not created'); ?></span></div>
                                        <div>AWB: <span class="font-medium"><?php echo e($order['shiprocket_awb_code'] ?: 'Pending'); ?></span></div>
                                        <div>Courier: <span class="font-medium"><?php echo e($order['shiprocket_courier_name'] ?: 'Pending'); ?></span></div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        <div class="font-medium text-gray-900"><?php echo e($order['name']); ?></div>
                                        <div><?php echo e($order['city']); ?>, <?php echo e($order['state']); ?> - <?php echo e($order['pincode']); ?></div>
                                        <div><?php echo e($order['mobile']); ?></div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500">
                                        <?php echo !empty($order['shiprocket_synced_at']) ? date('M d, Y H:i', strtotime($order['shiprocket_synced_at'])) : 'Never'; ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap gap-2">
                                            <?php if (empty($order['shiprocket_shipment_id']) && empty($order['shiprocket_awb_code'])): ?>
                                                <form method="POST" action="<?php echo BASE_URL; ?>admin/manage_shipping.php">
                                                    <input type="hidden" name="order_id" value="<?php echo (int)$order['id']; ?>">
                                                    <input type="hidden" name="shiprocket_action" value="create">
                                                    <button type="submit" class="inline-flex items-center bg-primary-500 hover:bg-primary-600 text-white font-medium py-1 px-3 rounded-lg transition text-sm">
                                                        <i class="fas fa-truck-fast mr-1"></i>Create
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <form method="POST" action="<?php echo BASE_URL; ?>admin/manage_shipping.php">
                                                    <input type="hidden" name="order_id" value="<?php echo (int)$order['id']; ?>">
                                                    <input type="hidden" name="shiprocket_action" value="sync">
                                                    <button type="submit" class="inline-flex items-center border-2 border-primary-500 text-primary-500 hover:bg-primary-500 hover:text-white font-medium py-1 px-3 rounded-lg transition text-sm">
                                                        <i class="fas fa-rotate mr-1"></i>Sync
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            <?php if (!empty($trackingUrl)): ?>
                                                <a href="<?php echo e($trackingUrl); ?>" target="_blank" rel="noopener"
                                                   class="inline-flex items-center border-2 border-gray-300 text-gray-700 hover:border-gray-400 font-medium py-1 px-3 rounded-lg transition text-sm">
                                                    <i class="fas fa-location-dot mr-1"></i>Track
                                                </a>
                                            <?php endif; ?>
                                            <a href="<?php echo BASE_URL; ?>admin/view_order.php?id=<?php echo (int)$order['id']; ?>"
                                               class="inline-flex items-center border-2 border-primary-500 text-primary-500 hover:bg-primary-500 hover:text-white font-medium py-1 px-3 rounded-lg transition text-sm">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="7" class="px-4 py-8 text-center text-gray-500">No shipments found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPages > 1): ?>
                    <div class="mt-6 flex justify-center">
                        <div class="flex space-x-2">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <a href="<?php echo BASE_URL; ?>admin/manage_shipping.php?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"
                                   class="w-10 h-10 flex items-center justify-center rounded-lg transition <?php echo $i === $page ? 'bg-primary-500 text-white' : 'border-2 border-gray-300 hover:border-primary-500 hover:text-primary-500'; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
