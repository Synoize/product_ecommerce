<?php
/**
 * Admin - Manage Products
 */

$pageTitle = 'Manage Products';
require_once __DIR__ . '/includes/header.php';
requireAdmin();

// Handle delete action
if (isset($_GET['delete'])) {
    $productId = (int)$_GET['delete'];
    try {
        // Check if product exists in orders
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM order_items WHERE product_id = ?");
        $stmt->execute([$productId]);
        $inOrders = $stmt->fetch()['count'] > 0;
        
        if ($inOrders) {
            // Soft delete - just disable
            $stmt = $pdo->prepare("UPDATE products SET status = 0 WHERE id = ?");
            $stmt->execute([$productId]);
            setFlash('Product has been disabled (cannot delete - exists in orders)', 'warning');
        } else {
            // Hard delete
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$productId]);
            setFlash('Product deleted successfully', 'success');
        }
    } catch (PDOException $e) {
        setFlash('Error deleting product', 'danger');
    }
    redirect(BASE_URL . 'admin/manage_products.php');
}

// Fetch all products
try {
    $stmt = $pdo->query("SELECT p.*, c.name as category_name, v.total_variant_stock
                        FROM products p 
                        LEFT JOIN categories c ON p.category_id = c.id 
                        LEFT JOIN (
                            SELECT product_id, SUM(stock) AS total_variant_stock
                            FROM product_variants
                            WHERE status = 1
                            GROUP BY product_id
                        ) v ON v.product_id = p.id
                        ORDER BY p.created_at DESC");
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    $products = [];
}
?>

<div class="bg-white mt-20">
    <div class="h-[calc(100vh-80px)] flex">
        <!-- Admin Sidebar -->
        <?php include __DIR__ . '/includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="flex-1 overflow-y-auto p-6 md:p-8">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                <h2 class="text-2xl font-bold text-gray-900">Manage Products</h2>
                <a href="<?php echo BASE_URL; ?>admin/add_product.php" class="inline-flex items-center bg-primary-500 hover:bg-primary-600 text-white font-semibold py-3 px-6 rounded-full transition shadow-lg hover:shadow-xl">
                    <i class="fas fa-plus mr-2"></i>Add New Product
                </a>
            </div>
            
            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">ID</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Image</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Name</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Category</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Price</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Stock</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Status</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($products as $product): ?>
                            <tr>
                                <td class="px-4 py-3 text-sm"><?php echo $product['id']; ?></td>
                                <td class="px-4 py-3">
                                    <?php $imageUrl = getImageUrl($product['image'], 'products'); ?>
                                    <img src="<?php echo $imageUrl; ?>" class="w-12 h-12 rounded-lg object-cover" alt="">
                                </td>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900"><?php echo e($product['name']); ?></td>
                                <td class="px-4 py-3 text-sm"><?php echo e($product['category_name'] ?? 'N/A'); ?></td>
                                <td class="px-4 py-3 text-sm"><?php echo formatCurrency($product['price']); ?></td>
                                <td class="px-4 py-3 text-sm"><?php echo $product['total_variant_stock'] !== null ? (int)$product['total_variant_stock'] : (int)$product['stock']; ?></td>
                                <td class="px-4 py-3">
                                    <span class="inline-block px-2 py-1 rounded text-xs font-medium <?php echo $product['status'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                                        <?php echo $product['status'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex gap-2">
                                        <a href="<?php echo BASE_URL; ?>admin/edit_product.php?id=<?php echo $product['id']; ?>" 
                                           class="inline-flex items-center border-2 border-primary-500 text-primary-500 hover:bg-primary-500 hover:text-white font-medium py-1 px-2 rounded-lg transition text-sm" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="<?php echo BASE_URL; ?>product.php?id=<?php echo $product['id']; ?>" 
                                           class="inline-flex items-center border-2 border-blue-500 text-blue-500 hover:bg-blue-500 hover:text-white font-medium py-1 px-2 rounded-lg transition text-sm" title="View" target="_blank">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="<?php echo BASE_URL; ?>admin/manage_products.php?delete=<?php echo $product['id']; ?>" 
                                           class="inline-flex items-center border-2 border-red-500 text-red-500 hover:bg-red-500 hover:text-white font-medium py-1 px-2 rounded-lg transition text-sm" title="Delete"
                                           onclick="return confirm('Are you sure you want to delete this product?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-gray-500">No products found. <a href="<?php echo BASE_URL; ?>admin/add_product.php" class="text-primary-500 hover:text-primary-600">Add your first product</a></td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
