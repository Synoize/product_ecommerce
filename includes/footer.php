<?php

/**
 * Footer Template
 * Included on all frontend pages
 */
?>

<?php
$categories = [];
try {
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    // Categories table might not exist yet
}
?>

<!-- Footer -->
<footer class="mt-20">

    <svg
        viewBox="0 0 1440 200"
        class="w-full h-6 md:h-10"
        preserveAspectRatio="none">

        <path
            fill="#ffee58"
            d="
        M0,100
        C180,20 360,200 540,100
        S900,20 1080,100
        S1260,200 1440,100
        L1440,200 L0,200 Z
        " />
    </svg>

    <div class="bg-accent-400 py-8 md:py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-8 mb-8">
                <!-- About Column -->
                <div class="lg:col-span-2">
                    <a href="<?php echo BASE_URL; ?>" class="flex items-center text-primary-500 font-bold text-xl">
                        <img src="<?php echo ASSETS_URL; ?>/public/logo.png" alt="logo" class="h-24 md:h-32">
                    </a>
                    <p class="text-sm mb-6 text-gray-700 max-w-sm">
                        We deliver a thoughtfully crafted range of snacks and spices, combining rich taste, freshness, and consistent quality you can trust every day.
                    </p>
                    <ul class="space-y-2 text-gray-400 text-sm">
                        <li class="flex items-start text-gray-600 max-w-sm">
                            <i class="fas fa-map-marker-alt mt-1 mr-2 text-accent-900"></i>
                            3rd Floor, IT Park, Parsodi, Hingna Road, Nagpur, Maharashtra 440022, India
                        </li>
                        <li class="flex items-center text-gray-600"><i class="fas fa-phone mr-2 text-primary-600"></i>+91 934 567 8900</li>
                        <li class="flex items-center text-gray-600"><i class="fas fa-envelope mr-2 text-red-600"></i>support@earthence.com</li>
                    </ul>
                </div>

                <!-- Quick Links -->
                <div>
                    <h5 class="text-lg font-bold mb-4 text-primary-600">Quick Links</h5>
                    <ul class="space-y-2">
                        <li><a href="<?php echo BASE_URL; ?>" class="text-gray-700 hover:text-gray-500 text-sm transition">Home</a></li>
                        <li><a href="<?php echo BASE_URL; ?>shop.php" class="text-gray-700 hover:text-gray-500 text-sm transition">Shop</a></li>
                        <li><a href="<?php echo BASE_URL; ?>about-us.php" class="text-gray-700 hover:text-gray-500 text-sm transition">About Us</a></li>
                        <li><a href="<?php echo BASE_URL; ?>contact-us.php" class="text-gray-700 hover:text-gray-500 text-sm transition">Contact Us</a></li>
                        <li>
                            <a href="<?php echo BASE_URL; ?>help.php" class="text-gray-700 hover:text-gray-500 text-sm transition">
                                Help Center
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo BASE_URL; ?>terms_of_service.php" class="text-gray-700 hover:text-gray-500 text-sm transition">
                                Terms of Service
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo BASE_URL; ?>privacy_policy.php" class="text-gray-700 hover:text-gray-500 text-sm transition">
                                Privacy Policy
                            </a>
                        </li>
                        <li><a href="tel:+916235559500" class="text-gray-700 hover:text-gray-500 text-sm transition">
                                Call Support
                            </a></li>
                    </ul>
                </div>

                <!-- Categories -->
                <div>
                    <h5 class="text-lg font-bold mb-4 text-primary-600">Categories</h5>
                    <ul class="space-y-2">
                        <?php foreach ($categories as $category): ?>
                            <a
                                href="<?php echo BASE_URL; ?>shop.php?category=<?php echo $category['id']; ?>"
                                class="flex items-center text-gray-700 hover:text-gray-500 transition">
                                <?php if ($category['image']): ?>
                                    <img
                                        src="<?php echo getImageUrl($category['image'], 'categories'); ?>"
                                        class="w-8 h-8 mr-3 object-contain">
                                <?php endif; ?>

                                <span class="text-sm font-medium">
                                    <?php echo e($category['name']); ?>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Contact Info -->
                <div>
                    <h5 class="text-lg font-bold mb-4 text-primary-600">My Account</h5>
                    <ul class="space-y-2">
                        <li>
                            <a href="<?php echo BASE_URL; ?>user/profile.php" class="text-gray-700 hover:text-gray-500 text-sm transition">
                                My Account
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo BASE_URL; ?>user/orders.php" class="text-gray-700 hover:text-gray-500 text-sm transition">
                                Order History
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo BASE_URL; ?>wishlist.php" class="text-gray-700 hover:text-gray-500 text-sm transition">
                                Wishlist
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo BASE_URL; ?>cart.php" class="text-gray-700 hover:text-gray-500 text-sm transition">
                                Shopping Cart
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Footer Bottom -->
            <div class="flex flex-col md:flex-row justify-between items-center gap-2 border-t border-accent-800 pt-6 text-center">
                <div class="flex space-x-4">
                    <div class="flex gap-4 md:gap-6">

                        <!-- Facebook -->
                        <a href="https://www.facebook.com/yourpage" target="_blank"
                            class="text-[#1877F2] hover:scale-110 transition duration-300">
                            <i class="fab fa-facebook-f text-lg"></i>
                        </a>

                        <!-- Twitter / X -->
                        <a href="https://twitter.com/yourprofile" target="_blank"
                            class="text-gray-700 hover:scale-110 transition duration-300">
                            <i class="fab fa-twitter text-lg"></i>
                        </a>

                        <!-- Instagram -->
                        <a href="https://www.instagram.com/yourprofile" target="_blank"
                            class="text-pink-500 hover:text-pink-600 hover:scale-110 transition duration-300">
                            <i class="fab fa-instagram text-lg"></i>
                        </a>

                        <!-- LinkedIn -->
                        <a href="https://www.linkedin.com/in/yourprofile" target="_blank"
                            class="text-[#0A66C2] hover:scale-110 transition duration-300">
                            <i class="fab fa-linkedin-in text-lg"></i>
                        </a>

                    </div>
                </div>

                <p class="text-gray-700 text-sm">&copy; <?php echo date('Y'); ?> Earthence. All rights reserved.</p>
            </div>
        </div>
    </div>
</footer>

<script src="<?php echo ASSETS_URL; ?>/public/js/script.js"></script>
<script>
    lucide.createIcons();
</script>
</body>

</html>