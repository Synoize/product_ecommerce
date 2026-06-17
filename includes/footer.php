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
            fill="#56B4E2"
            d="
        M0,100
        C180,20 360,200 540,100
        S900,20 1080,100
        S1260,200 1440,100
        L1440,200 L0,200 Z
        " />
    </svg>

    <div class="bg-primary py-8 md:py-10">
       <div class="max-w-[1920px] mx-auto px-4 sm:px-6 lg:px-12">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-8 mb-8 text-gray-800">
                <!-- About Column -->
                <div class="lg:col-span-2">
                    <a href="<?php echo BASE_URL; ?>" class="flex items-center">
                        <img src="<?php echo ASSETS_URL; ?>/public/logo.png" alt="logo" class="h-24 md:h-32">
                    </a>
                    <p class="text-sm mb-6 max-w-sm">
                        We deliver a thoughtfully crafted range of snacks and spices, combining rich taste, freshness, and consistent quality you can trust every day.
                    </p>
                    <ul class="space-y-4 text-gray-800 text-sm">
                        <li class="flex items-start max-w-sm">
                            <i class="fas fa-map-marker-alt mt-1 mr-2 text-accent-900"></i>
                            Address is Plot No. 04, Samta Colony,
                            Pawanputra Society, Hudkeshwar Khurd, 
                            Outer Ring Road, Nagpur, Maharashtra, 
                            India  - 440037
                        </li>
                        <li class="flex items-center"><i class="fas fa-phone mr-2 text-green-700"></i>+91 9665661603, +91 7218927236</li>
                        <li class="flex items-center"><i class="fas fa-envelope mr-2 text-red-600"></i>support@earthence.com</li>
                    </ul>
                </div>

                <!-- Quick Links -->
                <div>
                    <h5 class="text-lg font-bold mb-4 text-accent">Quick Links</h5>
                    <ul class="space-y-2">
                        <li><a href="<?php echo BASE_URL; ?>" class="hover:text-black text-sm transition">Home</a></li>
                        <li><a href="<?php echo BASE_URL; ?>shop.php" class="hover:text-black text-sm transition">Shop</a></li>
                        <li><a href="<?php echo BASE_URL; ?>about-us.php" class="hover:text-black text-sm transition">About Us</a></li>
                        <li><a href="<?php echo BASE_URL; ?>contact-us.php" class="hover:text-black text-sm transition">Contact Us</a></li>
                        <li>
                            <a href="<?php echo BASE_URL; ?>help.php" class="hover:text-black text-sm transition">
                                Help Center
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo BASE_URL; ?>terms_of_service.php" class="hover:text-black text-sm transition">
                                Terms of Service
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo BASE_URL; ?>privacy_policy.php" class="hover:text-black text-sm transition">
                                Privacy Policy
                            </a>
                        </li>
                        <li><a href="tel:+916235559500" class="hover:text-black text-sm transition">
                                Call Support
                            </a></li>
                    </ul>
                </div>

                <!-- Categories -->
                <div>
                    <h5 class="text-lg font-bold mb-4 text-accent">Categories</h5>
                    <ul class="space-y-2">
                        <?php foreach ($categories as $category): ?>
                            <a
                                href="<?php echo BASE_URL; ?>shop.php?category=<?php echo $category['id']; ?>"
                                class="flex items-center hover:text-black transition">
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
                    <h5 class="text-lg font-bold mb-4 text-accent">My Account</h5>
                    <ul class="space-y-2">
                        <li>
                            <a href="<?php echo BASE_URL; ?>user/profile.php" class="hover:text-black text-sm transition">
                                My Account
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo BASE_URL; ?>user/orders.php" class="hover:text-black text-sm transition">
                                Order History
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo BASE_URL; ?>wishlist.php" class="hover:text-black text-sm transition">
                                Wishlist
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo BASE_URL; ?>cart.php" class="hover:text-black text-sm transition">
                                Shopping Cart
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Footer Bottom -->
            <div class="flex flex-col md:flex-row justify-between items-center gap-2 border-t border-gray-100 pt-6 text-center">
                <div class="flex space-x-4">
                    <div class="flex gap-4 md:gap-6">

                        <!-- Facebook -->
                        <a href="https://www.facebook.com/yourpage" target="_blank"
                            class="text-blue-700 hover:text-blue-800 hover:scale-110 transition duration-300">
                            <i class="fab fa-facebook-f text-lg"></i>
                        </a>

                        <!-- Twitter / X -->
                        <a href="https://twitter.com/yourprofile" target="_blank"
                            class="text-gray-800 hover:text-black hover:scale-110 transition duration-300">
                            <i class="fab fa-twitter text-lg"></i>
                        </a>

                        <!-- Instagram -->
                        <a href="https://www.instagram.com/yourprofile" target="_blank"
                            class="text-pink-600 hover:text-pink-700 hover:scale-110 transition duration-300">
                            <i class="fab fa-instagram text-lg"></i>
                        </a>

                        <!-- LinkedIn -->
                        <a href="https://www.linkedin.com/in/yourprofile" target="_blank"
                            class="text-primary-900 hover:scale-110 transition duration-300">
                            <i class="fab fa-linkedin-in text-lg"></i>
                        </a>

                    </div>
                </div>

                <p class="text-gray-800 text-sm">&copy; <?php echo date('Y'); ?> Earthence. All rights reserved.</p>
            </div>
        </div>
    </div>
</footer>

<script src="./assets/public/js/script.js"></script>
<script>
    lucide.createIcons();
</script>
</body>

</html>