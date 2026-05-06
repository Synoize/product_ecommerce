<?php
/**
 * Admin - Manage Orders
 */

$pageTitle = 'Manage Orders';
require_once __DIR__ . '/includes/header.php';
requireAdmin();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $orderId = (int)$_POST['order_id'];
    $status = isset($_POST['status']) ? $_POST['status'] : '';
    $allowedStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
    
    if (in_array($status, $allowedStatuses)) {
        try {
            $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->execute([$status, $orderId]);
            setFlash('Order status updated!', 'success');
        } catch (PDOException $e) {
            setFlash('Error updating order status', 'danger');
        }
    }
    
    redirect(BASE_URL . 'admin/manage_orders.php');
}

// Get filter parameters
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query
$query = "SELECT o.*, u.name as user_name, u.email as user_email 
          FROM orders o 
          JOIN users u ON o.user_id = u.id 
          WHERE 1=1";
$params = [];

if ($statusFilter) {
    $query .= " AND o.status = ?";
    $params[] = $statusFilter;
}

if ($search) {
    $query .= " AND (o.id LIKE ? OR u.name LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " ORDER BY o.created_at DESC";

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Count total
$countQuery = str_replace("o.*, u.name as user_name, u.email as user_email", "COUNT(*) as total", $query);
$countStmt = $pdo->prepare($countQuery);
$countStmt->execute($params);
$totalOrders = $countStmt->fetch()['total'];
$totalPages = ceil($totalOrders / $perPage);

// Add limit
$query .= " LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;

// Fetch orders
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll();
?>

<div class="bg-white mt-20">
    <div class="h-[calc(100vh-80px)] flex">
        <!-- Admin Sidebar -->
       <?php include __DIR__ . '/includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="flex-1 overflow-y-auto p-6 md:p-8">
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-6 gap-4">
                <h2 class="text-2xl font-bold text-gray-900">Manage Orders</h2>
                <form action="<?php echo BASE_URL; ?>admin/manage_orders.php" method="GET" class="flex flex-wrap gap-2">
                    <select name="status" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500" onchange="this.form.submit()">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="processing" <?php echo $statusFilter === 'processing' ? 'selected' : ''; ?>>Processing</option>
                        <option value="shipped" <?php echo $statusFilter === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                        <option value="delivered" <?php echo $statusFilter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                        <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                    <input type="search" name="search" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 w-48" 
                           placeholder="Search orders..." value="<?php echo e($search); ?>">
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
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Order ID</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Customer</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Total</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Status</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Payment</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Date</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($orders as $order):
                                $statusColors = [
                                    'pending' => 'bg-yellow-100 text-yellow-700',
                                    'processing' => 'bg-blue-100 text-blue-700',
                                    'shipped' => 'bg-purple-100 text-purple-700',
                                    'delivered' => 'bg-green-100 text-green-700',
                                    'cancelled' => 'bg-red-100 text-red-700'
                                ];
                                $statusClass = $statusColors[$order['status']] ?? 'bg-gray-100 text-gray-700';
                                $paymentStatus = $order['payment_status'] ?? 'pending';
                                $paymentStatusClass = $paymentStatus === 'paid'
                                    ? 'bg-green-100 text-green-700'
                                    : ($paymentStatus === 'failed' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700');
                            ?>
                            <tr>
                                <td class="px-4 py-3 text-sm font-medium">#<?php echo $order['id']; ?></td>
                                <td class="px-4 py-3">
                                    <div class="text-sm font-medium text-gray-900"><?php echo e($order['user_name']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo e($order['user_email']); ?></div>
                                </td>
                                <td class="px-4 py-3 text-sm font-medium"><?php echo formatCurrency($order['total_amount']); ?></td>
                                <td class="px-4 py-3">
                                    <span class="inline-block px-2 py-1 rounded text-xs font-medium <?php echo $statusClass; ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-block px-2 py-1 rounded text-xs font-medium <?php echo $paymentStatusClass; ?>">
                                        <?php echo ucfirst(e($paymentStatus)); ?>
                                    </span>
                                    <div class="text-xs text-gray-500 mt-1">
                                        <?php echo ($order['payment_method'] ?? '') === 'cod' ? 'COD' : 'Online'; ?>
                                    </div>
                                    <?php if (($order['payment_method'] ?? '') === 'cod'): ?>
                                        <div class="text-xs text-gray-500 mt-1">
                                            Paid: <span class="text-green-600 font-medium"><?php echo formatCurrency($order['initial_payment_amount']); ?></span>
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            Due: <span class="text-orange-600 font-medium"><?php echo formatCurrency($order['remaining_payment_amount']); ?></span>
                                        </div>
                                    <?php elseif (!empty($order['razorpay_payment_id'])): ?>
                                        <div class="text-xs text-gray-400 mt-1 max-w-36 truncate" title="<?php echo e($order['razorpay_payment_id']); ?>">
                                            <?php echo e($order['razorpay_payment_id']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500"><?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></td>
                                <td class="px-4 py-3">
                                    <a href="<?php echo BASE_URL; ?>admin/view_order.php?id=<?php echo $order['id']; ?>" 
                                       class="inline-flex items-center border-2 border-primary-500 text-primary-500 hover:bg-primary-500 hover:text-white font-medium py-1 px-3 rounded-lg transition text-sm" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-gray-500">No orders found</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <div class="mt-6 flex justify-center">
                    <div class="flex space-x-2">
                        <?php if ($page > 1): ?>
                        <a href="<?php echo BASE_URL; ?>admin/manage_orders.php?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
                           class="w-10 h-10 flex items-center justify-center border-2 border-gray-300 hover:border-primary-500 hover:text-primary-500 rounded-lg transition">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="<?php echo BASE_URL; ?>admin/manage_orders.php?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                           class="w-10 h-10 flex items-center justify-center rounded-lg transition <?php echo $i == $page ? 'bg-primary-500 text-white' : 'border-2 border-gray-300 hover:border-primary-500 hover:text-primary-500'; ?>">
                            <?php echo $i; ?>
                        </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                        <a href="<?php echo BASE_URL; ?>admin/manage_orders.php?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
                           class="w-10 h-10 flex items-center justify-center border-2 border-gray-300 hover:border-primary-500 hover:text-primary-500 rounded-lg transition">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
