<?php
    $currentAdminPage = basename($_SERVER['SCRIPT_NAME'] ?? '');
    $adminNavGroupLinkClass = function (array $files) use ($currentAdminPage): string {
        $base = 'flex items-center px-4 py-3 rounded-lg transition';
        if (in_array($currentAdminPage, $files, true)) {
            return $base . ' bg-primary-500 text-white';
        }
        return $base . ' text-gray-300 hover:bg-gray-800';
    };
    $adminNavLinkClass = function (string $file) use ($adminNavGroupLinkClass): string {
        return $adminNavGroupLinkClass([$file]);
    };
?>

<div class=" hidden md:flex flex-col justify-between w-64 bg-gray-900 text-white">
    <div class="p-6">
        <h3 class="text-xl font-bold">Admin Panel</h3>
    </div>
    <nav class="px-4 space-y-2 flex-1 overflow-y-auto">
        <a href="<?php echo BASE_URL; ?>admin/index.php" class="<?php echo $adminNavLinkClass('index.php'); ?>">
            <i class="fas fa-tachometer-alt w-6"></i>Dashboard
        </a>
        <a href="<?php echo BASE_URL; ?>admin/manage_products.php" class="<?php echo $adminNavLinkClass('manage_products.php'); ?>">
            <i class="fas fa-box w-6"></i>Products
        </a>
        <a href="<?php echo BASE_URL; ?>admin/manage_categories.php" class="<?php echo $adminNavLinkClass('manage_categories.php'); ?>">
            <i class="fas fa-tags w-6"></i>Categories
        </a>
        <a href="<?php echo BASE_URL; ?>admin/manage_hero_features.php" class="<?php echo $adminNavLinkClass('manage_hero_features.php'); ?>">
            <i class="fas fa-images w-6"></i>Hero Features
        </a>
        <a href="<?php echo BASE_URL; ?>admin/manage_featured_videos.php" class="<?php echo $adminNavLinkClass('manage_featured_videos.php'); ?>">
            <i class="fas fa-video w-6"></i>Featured Videos
        </a>
        <a href="<?php echo BASE_URL; ?>admin/manage_orders.php" class="<?php echo $adminNavGroupLinkClass(['manage_orders.php', 'view_order.php']); ?>">
            <i class="fas fa-shopping-cart w-6"></i>Orders
        </a>
        <a href="<?php echo BASE_URL; ?>admin/manage_payments.php" class="<?php echo $adminNavLinkClass('manage_payments.php'); ?>">
            <i class="fas fa-credit-card w-6"></i>Payments
        </a>
        <a href="<?php echo BASE_URL; ?>admin/manage_shipping.php" class="<?php echo $adminNavLinkClass('manage_shipping.php'); ?>">
            <i class="fas fa-truck-fast w-6"></i>Shipping
        </a>
        <a href="<?php echo BASE_URL; ?>admin/manage_coupons.php" class="<?php echo $adminNavLinkClass('manage_coupons.php'); ?>">
            <i class="fas fa-ticket-alt w-6"></i>Coupons
        </a>
        <a href="<?php echo BASE_URL; ?>admin/manage_reviews.php" class="<?php echo $adminNavLinkClass('manage_reviews.php'); ?>">
            <i class="fas fa-star w-6"></i>Reviews
        </a>
        <a href="<?php echo BASE_URL; ?>admin/manage_users.php" class="<?php echo $adminNavLinkClass('manage_users.php'); ?>">
            <i class="fas fa-users w-6"></i>Users
        </a>
        <a href="<?php echo BASE_URL; ?>admin/contact_messages.php" class="<?php echo $adminNavLinkClass('contact_messages.php'); ?>">
            <i class="fas fa-envelope w-6"></i>Messages
        </a>
    </nav>
    <div class="p-4">
        <a href="<?php echo BASE_URL; ?>" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 rounded-lg transition">
            <i class="fas fa-arrow-left w-6"></i>Back to Site
        </a>
    </div>
</div>
