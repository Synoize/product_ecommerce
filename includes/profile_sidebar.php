<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<div class="md:col-span-1">
    <div class="bg-white md:border rounded-lg md:shadow-sm md:p-6 sticky top-24">
        
        <div class="text-center mb-6">
            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['user_name']); ?>&background=66bb6a&color=fff&size=128"
                class="w-20 h-20 rounded-full mx-auto mb-3" alt="Profile">

            <h5 class="font-bold text-gray-900 mb-1">
                <?php echo e($_SESSION['user_name']); ?>
            </h5>

            <p class="text-gray-500 text-sm">
                <?php echo e($_SESSION['user_email']); ?>
            </p>
        </div>

        <nav class="space-y-2">

            <!-- Profile -->
            <a href="<?php echo BASE_URL; ?>user/profile.php"
                class="flex items-center px-4 py-2 rounded-lg transition
                <?php echo $currentPage == 'profile.php'
                    ? 'bg-accent-50 text-accent font-medium'
                    : 'text-gray-600 hover:bg-gray-50'; ?>">
                <i class="fas fa-user mr-3"></i>
                Edit Profile
            </a>

            <!-- Addresses -->
            <a href="<?php echo BASE_URL; ?>user/addresses.php"
                class="flex items-center px-4 py-2 rounded-lg transition
                <?php echo $currentPage == 'addresses.php'
                    ? 'bg-accent-50 text-accent font-medium'
                    : 'text-gray-600 hover:bg-gray-50'; ?>">
                <i class="fas fa-map-marker-alt mr-3"></i>
                Addresses
            </a>

            <!-- Orders -->
            <a href="<?php echo BASE_URL; ?>user/orders.php"
                class="flex items-center px-4 py-2 rounded-lg transition
                <?php echo $currentPage == 'orders.php'
                    ? 'bg-accent-50 text-accent font-medium'
                    : 'text-gray-600 hover:bg-gray-50'; ?>">
                <i class="fas fa-shopping-bag mr-3"></i>
                Orders
            </a>

            <!-- Logout -->
            <a href="<?php echo BASE_URL; ?>user/logout.php"
                class="flex items-center px-4 py-2 rounded-lg text-red-600 hover:bg-red-50 transition">
                <i class="fas fa-sign-out-alt mr-3"></i>
                Logout
            </a>

        </nav>
    </div>
</div>