<?php
/**
 * Admin - Manage Payments
 */

$pageTitle = 'Manage Payments';
require_once __DIR__ . '/includes/header.php';
requireAdmin();

$methodFilter = $_GET['method'] ?? '';
$statusFilter = $_GET['payment_status'] ?? '';
$search = trim($_GET['search'] ?? '');

$query = "SELECT o.*, u.name AS user_name, u.email AS user_email
          FROM orders o
          JOIN users u ON o.user_id = u.id
          WHERE 1=1";
$params = [];

if ($methodFilter !== '') {
    $query .= " AND o.payment_method = ?";
    $params[] = $methodFilter;
}

if ($statusFilter !== '') {
    $query .= " AND (o.payment_status = ? OR o.initial_payment_status = ?)";
    $params[] = $statusFilter;
    $params[] = $statusFilter;
}

if ($search !== '') {
    $query .= " AND (o.id LIKE ? OR u.name LIKE ? OR u.email LIKE ? OR o.razorpay_payment_id LIKE ? OR o.razorpay_order_id LIKE ?)";
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
$totalPayments = (int)$countStmt->fetch()['total'];
$totalPages = (int)ceil($totalPayments / $perPage);

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
                <h2 class="text-2xl font-bold text-gray-900">Payments</h2>
                <form action="<?php echo BASE_URL; ?>admin/manage_payments.php" method="GET" class="flex flex-wrap gap-2">
                    <select name="method" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500" onchange="this.form.submit()">
                        <option value="">All Methods</option>
                        <option value="online" <?php echo $methodFilter === 'online' ? 'selected' : ''; ?>>Online</option>
                        <option value="cod" <?php echo $methodFilter === 'cod' ? 'selected' : ''; ?>>COD</option>
                    </select>
                    <select name="payment_status" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500" onchange="this.form.submit()">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="paid" <?php echo $statusFilter === 'paid' ? 'selected' : ''; ?>>Paid</option>
                        <option value="failed" <?php echo $statusFilter === 'failed' ? 'selected' : ''; ?>>Failed</option>
                        <option value="refunded" <?php echo $statusFilter === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                    </select>
                    <input type="search" name="search" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 w-56"
                           placeholder="Search payments..." value="<?php echo e($search); ?>">
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
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Method</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Status</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Amounts</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Gateway IDs</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Date</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($orders as $order):
                                $paymentStatus = $order['payment_status'] ?? 'pending';
                                $initialStatus = $order['initial_payment_status'] ?? 'pending';
                                $paymentClass = $paymentStatus === 'paid'
                                    ? 'bg-green-100 text-green-700'
                                    : ($paymentStatus === 'failed' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700');
                                $initialClass = $initialStatus === 'paid'
                                    ? 'bg-green-100 text-green-700'
                                    : ($initialStatus === 'failed' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700');
                            ?>
                                <tr>
                                    <td class="px-4 py-3 text-sm font-medium">#<?php echo (int)$order['id']; ?></td>
                                    <td class="px-4 py-3">
                                        <div class="text-sm font-medium text-gray-900"><?php echo e($order['user_name']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo e($order['user_email']); ?></div>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <?php echo ($order['payment_method'] ?? '') === 'cod' ? 'COD' : 'Online'; ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-block px-2 py-1 rounded text-xs font-medium <?php echo $paymentClass; ?>">
                                            <?php echo ucfirst(e($paymentStatus)); ?>
                                        </span>
                                        <?php if (($order['payment_method'] ?? '') === 'cod'): ?>
                                            <div class="mt-1">
                                                <span class="inline-block px-2 py-1 rounded text-xs font-medium <?php echo $initialClass; ?>">
                                                    Initial <?php echo ucfirst(e($initialStatus)); ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <div>Total: <span class="font-medium"><?php echo formatCurrency($order['total_amount']); ?></span></div>
                                        <?php if (($order['payment_method'] ?? '') === 'cod'): ?>
                                            <div class="text-green-600">Paid: <?php echo formatCurrency($order['initial_payment_amount']); ?></div>
                                            <div class="text-orange-600">Due: <?php echo formatCurrency($order['remaining_payment_amount']); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-500">
                                        <?php if (!empty($order['razorpay_payment_id'])): ?>
                                            <div class="max-w-44 truncate" title="<?php echo e($order['razorpay_payment_id']); ?>">Payment: <?php echo e($order['razorpay_payment_id']); ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($order['razorpay_order_id'])): ?>
                                            <div class="max-w-44 truncate" title="<?php echo e($order['razorpay_order_id']); ?>">Order: <?php echo e($order['razorpay_order_id']); ?></div>
                                        <?php endif; ?>
                                        <?php if (empty($order['razorpay_payment_id']) && empty($order['razorpay_order_id'])): ?>
                                            <span class="text-gray-400">No gateway ID</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500"><?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></td>
                                    <td class="px-4 py-3">
                                        <a href="<?php echo BASE_URL; ?>admin/view_order.php?id=<?php echo (int)$order['id']; ?>"
                                           class="inline-flex items-center border-2 border-primary-500 text-primary-500 hover:bg-primary-500 hover:text-white font-medium py-1 px-3 rounded-lg transition text-sm" title="View Order">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="8" class="px-4 py-8 text-center text-gray-500">No payments found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPages > 1): ?>
                    <div class="mt-6 flex justify-center">
                        <div class="flex space-x-2">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <a href="<?php echo BASE_URL; ?>admin/manage_payments.php?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"
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
