<?php

/**
 * Home Page
 * eCommerce Website Main Landing Page
 */

require_once 'includes/header.php';

$heroFeatures = [];
try {
    $stmt = $pdo->query("SELECT * FROM hero_features ORDER BY id DESC");
    $heroFeatures = $stmt->fetchAll();
} catch (PDOException $e) {
    // hero_features table might not exist yet
}


$featuredProductsVideo = [];

try {

    $stmt = $pdo->query("SELECT * FROM featured_products_video ORDER BY id DESC");
    $featuredProductsVideo = $stmt->fetchAll();
} catch (PDOException $e) {

    // Fallback Videos - Examples of both types (File Upload & YouTube)
    $featuredProductsVideo = [
        [
            "badge" => "Best Seller",
            "file_path" => "makhana-classic.mp4"  // Uploaded file
        ],
        [
            "badge" => "New Arrival",
            "file_path" => "https://www.youtube.com/embed/dQw4w9WgXcQ"  // YouTube video
        ],
        [
            "badge" => "Cheesy",
            "file_path" => "cheese-makhana.mp4"  // Uploaded file
        ],
        [
            "badge" => "Trending",
            "file_path" => "https://www.youtube.com/embed/dQw4w9WgXcQ"  // YouTube video
        ],
        [
            "badge" => "Premium",
            "file_path" => "premium-spices.mp4"  // Uploaded file
        ],
    ];
}

// Fetch featured products
$featuredProducts = [];
try {
    $stmt = $pdo->query("SELECT p.*, c.name as category_name 
                         FROM products p 
                         LEFT JOIN categories c ON p.category_id = c.id 
                         WHERE p.status = 1 
                         ORDER BY p.created_at DESC 
                         LIMIT 8");
    $featuredProducts = $stmt->fetchAll();
} catch (PDOException $e) {
    // Products table might not exist yet
}

// Fetch categories with images
$categories = [];
try {
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    // Categories table might not exist yet
}

// Fetch trending products (most viewed/ordered)
$trendingProducts = [];
try {
    $stmt = $pdo->query("SELECT p.*, c.name as category_name 
                         FROM products p 
                         LEFT JOIN categories c ON p.category_id = c.id 
                         WHERE p.status = 1 
                         ORDER BY RAND() 
                         LIMIT 4");
    $trendingProducts = $stmt->fetchAll();
} catch (PDOException $e) {
    // Silent fail
}
?>

<!-- Hero Section -->
<section class="bg-primary mt-4 py-16 md:py-20 overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="grid md:grid-cols-2 gap-10 lg:gap-16 items-center">

            <!-- IMAGE (SHOW FIRST ON MOBILE) -->
            <div class="order-1 md:order-2 relative w-full h-[200px] sm:h-[400px] md:h-[450px] lg:h-[500px] flex items-center justify-center">
                <?php if (!empty($heroFeatures)) : ?>

                    <?php
                    foreach ($heroFeatures as $feature) :

                        $images = json_decode($feature['images'], true);

                        if (!empty($images)) :

                            foreach ($images as $image) :
                    ?>

                                <img
                                    src="<?php echo IMAGES_URL . 'info_image/' . $image; ?>"
                                    class="pkg absolute max-h-full w-auto opacity-0 scale-75">

                    <?php
                            endforeach;

                        endif;

                    endforeach;
                    ?>

                <?php else : ?>

                    <!-- Fallback Images -->
                    <img src="<?php echo IMAGES_URL; ?>info_image/pkg1.png" class="pkg absolute max-h-full w-auto opacity-0 scale-75">
                    <img src="<?php echo IMAGES_URL; ?>info_image/pkg2.png" class="pkg absolute max-h-full w-auto opacity-0 scale-75">
                    <img src="<?php echo IMAGES_URL; ?>info_image/pkg3.png" class="pkg absolute max-h-full w-auto opacity-0 scale-75">

                <?php endif; ?>
            </div>

            <!-- TEXT (SHOW BELOW ON MOBILE) -->
            <div class="order-2 md:order-1 text-center md:text-left">

                <h1 class="text-4xl sm:text-6xl text-accent leading-[1.2] mb-6 font-luckiest animate-slide-left" style="-webkit-text-stroke: 0.5px black;">
                    Discover
                    <span class="text-white">
                        Delicious Snacks
                    </span>
                    and Indian Authentic
                    <span class="text-white">
                        Spices
                    </span>
                </h1>

                <p class="text-base text-white mb-20 max-w-lg mx-auto md:mx-0 animate-slide-left">
                    Discover the rich flavors of our carefully curated selection of wholesome snacks and aromatic spices, crafted to bring freshness, taste, and authenticity to every bite and every meal you prepare.
                </p>

                <div class="grid grid-cols-2 w-full md:max-w-sm gap-4">

                    <!-- Explore Flavors -->
                    <a href="<?php echo BASE_URL; ?>about-us.php"
                        class="group relative inline-flex items-center justify-center
    overflow-hidden rounded-full
    bg-accent-500 px-8 py-3
    font-semibold text-gray-900
    shadow-[4px_4px_0_#000]
    transition-all duration-500 ease-out
    hover:-translate-y-1 hover:shadow-[6px_6px_0_#000]
    active:translate-y-0 active:shadow-[2px_2px_0_#000]
    animate-slide-bottom">

                        <span class="transition-all duration-300 group-hover:tracking-wide text-nowrap">
                            Explore Flavors
                        </span>

                    </a>

                    <!-- Shop Now -->
                    <a href="<?php echo BASE_URL; ?>shop.php"
                        class="group relative inline-flex items-center justify-center
    overflow-hidden rounded-full
    bg-white px-8 py-3
    font-semibold text-gray-900
    shadow-[4px_4px_0_#000]
    transition-all duration-500 ease-out
    hover:-translate-y-1 hover:shadow-[6px_6px_0_#000]
    active:translate-y-0 active:shadow-[2px_2px_0_#000]
    animate-slide-bottom">
                        <i class="fas fa-shopping-bag mr-2"></i>
                        <span class="transition-all duration-300 group-hover:tracking-wide text-nowrap">
                            Shop Now
                        </span>
                    </a>

                </div>

            </div>

            <script>
                document.addEventListener("DOMContentLoaded", () => {

                    const images = document.querySelectorAll(".pkg");

                    const animations = [
                        "animate-slide-left",
                        "animate-slide-right",
                        "animate-slide-top",
                        "animate-slide-bottom",
                        "animate-pop",
                    ];

                    let current = 0;

                    function showNextImage() {

                        // Hide current image smoothly
                        images.forEach((img) => {

                            img.classList.remove(
                                "opacity-100",
                                "scale-100",
                                "animate-pop",
                                "animate-float",
                                "animate-slide-left",
                                "animate-slide-right",
                                "animate-slide-top",
                                "animate-slide-bottom"
                            );

                            img.classList.add(
                                "opacity-0",
                                "scale-95",
                                "transition-all",
                                "duration-700",
                                "ease-in-out"
                            );
                        });

                        // Active image
                        const activeImage = images[current];

                        const randomAnimation =
                            animations[Math.floor(Math.random() * animations.length)];

                        activeImage.classList.remove(
                            "opacity-0",
                            "scale-95"
                        );

                        activeImage.classList.add(
                            "opacity-100",
                            "scale-100",
                            randomAnimation,
                            "transition-all",
                            "duration-700",
                            "ease-in-out"
                        );

                        // Floating effect after entrance
                        setTimeout(() => {
                            activeImage.classList.add("animate-float");
                        }, 700);

                        current = (current + 1) % images.length;
                    }

                    showNextImage();

                    setInterval(showNextImage, 4500);
                });
            </script>

        </div>
    </div>
</section>

<!-- Categories Section -->
<section class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Heading -->
        <div class="text-center mb-12 scroll-animate-top">
            <h2 class="text-3xl md:text-5xl font-luckiest text-primary mb-2" style="-webkit-text-stroke: 0.5px black;">
                Shop by
                <span class="text-accent">
                    Category
                </span>
            </h2>
            <p class="text-gray-500 text-base">
                Explore our wide range of product categories
            </p>
        </div>

        <!-- Categories Container -->
        <div class="flex gap-4 md:gap-16 overflow-x-auto 
                justify-center md:justify-center 
                 [&::-webkit-scrollbar]:hidden 
            scrollbar-hide">

            <?php foreach ($categories as $category): ?>
                <a href="<?php echo BASE_URL; ?>shop.php?category=<?php echo $category['id']; ?>"
                    class="group flex-shrink-0 text-center">

                    <!-- Image -->
                    <div class="relative transition duration-300 
                      group-hover:scale-105 py-4">

                        <?php if ($category['image']): ?>
                            <img src="<?php echo getImageUrl($category['image'], 'categories'); ?>"
                                alt="<?php echo e($category['name']); ?>"
                                class="w-full h-20 md:h-48 object-contain 
                          mx-auto">
                        <?php else: ?>
                            <div class="w-full h-24 md:h-48 bg-gradient-to-br from-primary-400 to-primary-600 
                          flex items-center justify-center rounded-xl">
                                <span class="text-white font-bold text-center px-2">
                                    <?php echo e($category['name']); ?>
                                </span>
                            </div>
                        <?php endif; ?>

                    </div>

                    <!-- Title -->
                    <h5 class="mt-2 text-gray-600 text-center text-xs md:text-lg">
                        <?php echo e($category['name']); ?>
                    </h5>

                </a>
            <?php endforeach; ?>

            <img src="<?php echo IMAGES_URL; ?>/new.png" class="hidden md:block mt-8 w-24 h-24 md:w-56 md:h-56 animate-float">

        </div>

    </div>
</section>

<!-- Featured Products Section -->
<section>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-end mb-12">
            <div class="scroll-animate-top mx-auto md:mx-0 text-center md:text-left">
                <h2 class="text-3xl md:text-5xl font-luckiest text-primary mb-2" style="-webkit-text-stroke: 0.5px black;">Featured <span class="text-accent">Products</span></h2>
                <p class="text-gray-500 text-base">Handpicked items just for you</p>
            </div>
            <a href="<?php echo BASE_URL; ?>shop.php"
                class="scroll-animate-right hidden md:inline-flex items-center gap-2 
          text-primary-600 hover:text-primary-700 
           transition duration-300 group border px-4 py-2 rounded-full">

                View All

                <i class="fas fa-arrow-right 
            transition-transform duration-300 
            group-hover:translate-x-1"></i>
            </a>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 md:gap-8">
            <?php foreach ($featuredProducts as $product): ?>
                <div class="bg-white rounded-2xl border hover:shadow-md transition-all duration-300 overflow-hidden group">

                    <!-- IMAGE -->
                    <div class="relative overflow-hidden p-3 md:p-4 bg-primary-100/50">
                        <?php $imageUrl = getImageUrl($product['image'], 'products'); ?>

                        <a href="<?php echo BASE_URL; ?>product.php?id=<?php echo $product['id']; ?>">
                            <img
                                src="<?php echo $imageUrl; ?>"
                                alt="<?php echo e($product['name']); ?>"
                                class="h-28 sm:h-40 md:h-44 object-contain 
                       group-hover:scale-105 transition duration-300">
                        </a>

                        <!-- BADGES -->
                        <?php if ($product['stock'] <= 10 && $product['stock'] > 0): ?>
                            <span class="absolute top-3 left-3 bg-spice/10 backdrop-blur-sm text-spice text-xs font-semibold px-2 py-1 rounded-full shadow">
                                Only <?php echo $product['stock']; ?> left
                            </span>
                        <?php endif; ?>

                        <?php renderWishlistIconButton($product['id'], 'absolute top-3 right-3 z-10'); ?>
                    </div>

                    <!-- CONTENT -->
                    <div class="p-3 md:p-4 flex flex-col justify-between h-[160px] md:h-[180px]">

                        <!-- CATEGORY -->
                        <!-- <small class="text-gray-400 text-xs uppercase tracking-wide">
                            <?php echo e($product['category_name']); ?>
                        </small> -->

                        <!-- NAME -->
                        <h3 class="font-semibold text-gray-900 text-sm sm:text-lg leading-tight line-clamp-2 md:line-clamp-1">
                            <?php echo e($product['name']); ?>
                        </h3>

                        <!-- WEIGHT -->
                        <?php
                        $displayWeight = '';
                        if (!empty($product['weight'])) {
                            $displayWeight = trim((string)$product['weight']);
                        }
                        ?>
                        <?php if ($displayWeight !== ''): ?>
                            <p class="text-gray-500 text-xs mt-1">
                                <i class="fas fa-weight-hanging mr-1 text-accent"></i><?php echo e($displayWeight); ?>
                            </p>
                        <?php endif; ?>

                        <!-- PRICE -->
                        <div class="w-full flex justify-between items-center">

                            <!-- LEFT: PRICE -->
                            <div class="flex items-baseline flex-wrap space-x-1">

                                <span class="text-green-600 font-semibold text-base sm:text-lg md:text-xl">
                                    <?php echo formatCurrency($product['price']); ?>
                                </span>

                                <?php if ($product['original_price'] > $product['price']): ?>
                                    <span class="text-gray-400 line-through text-xs sm:text-sm">
                                        <?php echo formatCurrency($product['original_price']); ?>
                                    </span>
                                <?php endif; ?>

                            </div>

                            <!-- RIGHT: DISCOUNT -->
                            <?php if ($product['original_price'] > $product['price']): ?>
                                <span class="bg-green-100 text-green-600 text-[8px] md:text-xs text-center font-semibold px-2 py-1 rounded-full text-nowrap">
                                    <?php
                                    $discount = round((($product['original_price'] - $product['price']) / $product['original_price']) * 100);
                                    echo $discount . '% OFF';
                                    ?>
                                </span>
                            <?php endif; ?>

                        </div>

                        <!-- BUTTON -->
                        <?php if ($product['stock'] > 0): ?>
                            <form action="<?php echo BASE_URL; ?>cart.php" method="POST">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="quantity" value="1">

                                <button type="submit"
                                    class="w-full p-2.5 flex items-center justify-center 
                       bg-primary-700 hover:bg-primary-800 text-white text-xs md:text-base font-semibold 
                       rounded-full gap-2 ">
                                    <i class="fas fa-cart-plus text-xs"></i>
                                    Add to Cart
                                </button>
                            </form>
                        <?php elseif ($product['stock'] <= 0): ?>
                            <button disabled
                                class="w-full p-2.5 flex items-center justify-center 
                   bg-red-500 text-white text-xs md:text-base font-semibold rounded-full cursor-not-allowed">
                                Out of Stock
                            </button>
                        <?php endif; ?>


                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-8 text-center md:hidden">
            <a href="<?php echo BASE_URL; ?>shop.php" class="inline-flex items-center text-primary-500 hover:text-primary-600 border px-4 py-2 rounded-full transition">
                View All <i class="fas fa-arrow-right ml-2"></i>
            </a>
        </div>
    </div>
</section>

<!-- Featured Videos Section -->
<section class="pt-20">

    <div class="relative mx-auto max-w-7xl">

        <!-- Heading -->
        <div class="text-center px-4 scroll-animate-top">
            <h2 class="text-3xl sm:text-5xl font-luckiest text-primary-600 leading-tight" style="-webkit-text-stroke: 0.5px black;">
                Crunch. Watch.
                <span class="text-accent">
                    Enjoy.
                </span>
            </h2>

            <p class="mt-4 text-gray-500 max-w-2xl mx-auto text-sm md:text-base leading-relaxed">
                Premium makhana & authentic Indian spices in every bite.
            </p>

        </div>

        <!-- Carousel Wrapper -->
        <div class="relative mt-12 overflow-hidden">

            <!-- Carousel -->
            <div id="featureCarousel"
                class="flex items-center gap-5 md:gap-7 overflow-x-auto scroll-smooth px-4 md:px-10 pb-4
        snap-x snap-mandatory
        [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none]">

                <?php foreach ($featuredProductsVideo as $video):

                    $videoIsYouTube = strpos($video['file_path'], 'youtube.com/embed') !== false;

                ?>

                    <!-- Card -->
                    <div class="snap-center shrink-0
                w-[290px]">

                        <div class="group relative overflow-hidden rounded-[1.5rem]
                    h-[400px] md:h-[430px]
                    bg-white border border-gray-100 shadow-sm">

                            <!-- Video Wrapper -->
                            <div class="relative w-full h-full overflow-hidden rounded-2xl">

                                <?php if ($videoIsYouTube): ?>

                                    <!-- YouTube Video -->
                                    <iframe
                                        src="<?php echo e($video['file_path']) . '?autoplay=1&mute=1&loop=1&controls=0&playlist=' . substr(strrchr($video['file_path'], '/'), 1); ?>"
                                        class="w-full h-full object-cover scale-[1.02] group-hover:scale-105 transition duration-700"
                                        frameborder="0"
                                        allow="autoplay; encrypted-media"
                                        allowfullscreen>
                                    </iframe>

                                <?php else: ?>

                                    <!-- Uploaded Video -->
                                    <video
                                        src="<?php echo UPLOADS_URL . $video['file_path']; ?>"
                                        class="w-full h-full object-cover group-hover:scale-105 transition duration-700"
                                        autoplay
                                        muted
                                        loop
                                        playsinline>
                                    </video>

                                <?php endif; ?>

                                <!-- Overlay -->
                                <div class="absolute inset-0 bg-gradient-to-t from-black/10 via-black/10 to-transparent pointer-events-none"></div>

                            </div>

                            <!-- Badge -->
                            <div class="absolute top-4 left-4 z-20">
                                <span class="bg-white/90 backdrop-blur-xl text-primary text-xs font-bold px-4 py-2 rounded-full border">
                                    <?= e($video['badge']); ?>
                                </span>
                            </div>
                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

            <!-- Left Button -->
            <button onclick="scrollCarousel(-1)"
                class="absolute left-2 md:left-4 top-1/2 -translate-y-1/2 z-30
        w-12 h-12 flex items-center justify-center
        rounded-full bg-white/90 backdrop-blur-sm shadow-sm
        text-primary hover:bg-primary hover:text-white
        transition duration-300">

                <i data-lucide="chevron-left" class="w-5 h-5"></i>
            </button>

            <!-- Right Button -->
            <button onclick="scrollCarousel(1)"
                class="absolute right-2 md:right-4 top-1/2 -translate-y-1/2 z-30
        w-12 h-12 flex items-center justify-center
        rounded-full bg-white/90 backdrop-blur-sm shadow-sm
        text-primary hover:bg-primary hover:text-white
        transition duration-300">

                <i data-lucide="chevron-right" class="w-5 h-5"></i>
            </button>

        </div>

        <script>
            document.addEventListener("DOMContentLoaded", () => {

                const container = document.getElementById("featureCarousel");

                if (!container) return;

                function getScrollAmount() {

                    const firstCard = container.children[0];

                    if (!firstCard) return 300;

                    const style = window.getComputedStyle(container);

                    const gap = parseInt(style.gap) || 20;

                    return firstCard.offsetWidth + gap;
                }

                window.scrollCarousel = function(direction) {

                    container.scrollBy({
                        left: direction * getScrollAmount(),
                        behavior: "smooth"
                    });

                };

            });
        </script>

    </div>

</section>

<!-- Every Mood -->
<section class="my-20 relative bg-primary">

    <!-- SCALLOP TOP FULL WIDTH -->
    <div class="absolute -top-0.5 left-0 w-full leading-none">
        <svg
            class="w-full h-[40px] md:h-[80px]"
            viewBox="0 0 1440 80"
            preserveAspectRatio="none">
            <defs>
                <pattern
                    id="scallop"
                    width="80"
                    height="80"
                    patternUnits="userSpaceOnUse">
                    <circle cx="40" cy="0" r="40" fill="white" />
                </pattern>
            </defs>

            <!-- FULL HEIGHT RECT -->
            <rect width="100%" height="100%" fill="url(#scallop)" />
        </svg>
    </div>

    <!-- MAIN CONTENT -->
    <div class="max-w-7xl mx-auto px-6 pt-24 pb-24 grid lg:grid-cols-2 gap-12 items-center">

        <!-- LEFT IMAGE -->
        <div class="flex justify-center lg:justify-start">
            <div class="relative">

                <!-- Circle -->
                <div class="w-64 h-64 sm:w-80 sm:h-80 lg:w-[420px] lg:h-[420px] flex items-center justify-center animate-float ">
                    <img
                        src="<?php echo IMAGES_URL; ?>/makhana_bowl.png"
                        class="w-full h-full object-contain" />
                </div>
            </div>
        </div>

        <!-- RIGHT CONTENT -->
        <div class="text-center lg:text-left space-y-6">

            <h2 class="scroll-animate-top text-3xl md:text-5xl font-luckiest text-accent mb-3" style="-webkit-text-stroke: 0.5px black;">
                From Classic to
                <span class="text-white">Bold — </span> Discover a Flavor for Every
                <span class="text-white">Mood.</span>
            </h2>

            <!-- FEATURES -->
            <ul class="space-y-4 text-xs sm:text-base text-gray-800 max-w-lg text-start">

                <li class="scroll-animate-left flex items-center gap-3 justify-start">
                    <img src="<?php echo IMAGES_URL; ?>/makhana_icon.png"
                        class="w-8 opacity-80 mt-1" />

                    Premium Raw & Flavoured Makhana crafted with quality and care.
                </li>

                <li class="scroll-animate-left flex items-center gap-3 justify-start">
                    <img src="<?php echo IMAGES_URL; ?>/makhana_icon.png"
                        class="w-8 opacity-80 mt-1" />

                    Advanced dehydration and roasting technologies for superior freshness.
                </li>

                <li class="scroll-animate-left flex items-center gap-3 justify-start">
                    <img src="<?php echo IMAGES_URL; ?>/makhana_icon.png"
                        class="w-8 opacity-80 mt-1" />

                    Globally compliant healthy snacks and agro-based food products.
                </li>

                <li class="scroll-animate-left flex items-center gap-3 justify-start">
                    <img src="<?php echo IMAGES_URL; ?>/makhana_icon.png"
                        class="w-8 opacity-80 mt-1" />

                    Sustainable sourcing that empowers farmers and supports wellness.
                </li>

            </ul>

            <!-- Explore Flavors -->
            <div class="flex justify-center lg:justify-start">
                <a href="<?php echo BASE_URL; ?>about-us.php"
                    class="group relative inline-flex items-center justify-center
    overflow-hidden rounded-full
    bg-accent-500 px-8 py-3
    font-semibold text-gray-900
    shadow-[4px_4px_0_#000]
    transition-all duration-500 ease-out
    hover:-translate-y-1 hover:shadow-[6px_6px_0_#000]
    active:translate-y-0 active:shadow-[2px_2px_0_#000]
    scroll-animate-top">

                    <span class="transition-all duration-300 group-hover:tracking-wide text-nowrap">
                        Explore Flavors
                    </span>

                </a>
            </div>

        </div>
    </div>

    <!-- SCALLOP (OPPOSITE / FLIPPED) -->
    <div class="absolute -bottom-0.5 left-0 w-full leading-none">
        <svg
            class="w-full h-[50px] sm:h-[60px] md:h-[80px]"
            viewBox="0 0 1440 80"
            preserveAspectRatio="none">
            <defs>
                <pattern
                    id="scallop-bottom"
                    width="80"
                    height="80"
                    patternUnits="userSpaceOnUse">
                    <!-- MOVE CIRCLE TO BOTTOM -->
                    <circle cx="40" cy="80" r="40" fill="white" />
                </pattern>
            </defs>

            <rect width="100%" height="100%" fill="url(#scallop-bottom)" />
        </svg>
    </div>

    <!-- RIGHT FLOATING PRODUCT -->
    <div class="hidden lg:block absolute right-10 bottom-10 rotate-12 animate-float">
        <img
            src="https://cdn-icons-png.flaticon.com/512/2553/2553691.png"
            class="w-20 drop-shadow-xl" />
    </div>

</section>

<!-- Trending Products Section -->
<section class="bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="scroll-animate-top text-center mb-12">
            <h2 class="text-3xl md:text-5xl font-luckiest text-primary-600 mb-2" style="-webkit-text-stroke: 0.5px black;">Trending <span class="text-accent">Now</span></h2>
            <p class="text-gray-500 text-base">Most popular items this week</p>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 md:gap-8">
            <?php foreach ($trendingProducts as $product): ?>
                <div class="bg-white rounded-2xl border hover:shadow-md transition-all duration-300 overflow-hidden group">

                    <!-- IMAGE -->
                    <div class="relative overflow-hidden p-3 md:p-4 bg-primary-100/50">
                        <?php $imageUrl = getImageUrl($product['image'], 'products'); ?>

                        <a href="<?php echo BASE_URL; ?>product.php?id=<?php echo $product['id']; ?>">
                            <img
                                src="<?php echo $imageUrl; ?>"
                                alt="<?php echo e($product['name']); ?>"
                                class="h-28 sm:h-40 md:h-44 object-contain 
                       group-hover:scale-105 transition duration-300">
                        </a>

                        <!-- BADGES -->
                        <?php if ($product['stock'] <= 10 && $product['stock'] > 0): ?>
                            <span class="absolute top-3 left-3 bg-spice/10 backdrop-blur-sm text-spice text-xs font-semibold px-2 py-1 rounded-full shadow">
                                Only <?php echo $product['stock']; ?> left
                            </span>
                        <?php endif; ?>

                        <?php renderWishlistIconButton($product['id'], 'absolute top-3 right-3 z-10'); ?>
                    </div>

                    <!-- CONTENT -->
                    <div class="p-3 md:p-4 flex flex-col justify-between h-[160px] md:h-[180px]">

                        <!-- CATEGORY -->
                        <!-- <small class="text-gray-400 text-xs uppercase tracking-wide">
                            <?php echo e($product['category_name']); ?>
                        </small> -->

                        <!-- NAME -->
                        <h3 class="font-semibold text-gray-900 text-sm sm:text-lg leading-tight line-clamp-2 md:line-clamp-1">
                            <?php echo e($product['name']); ?>
                        </h3>

                        <!-- WEIGHT -->
                        <?php
                        $displayWeight = '';
                        if (!empty($product['weight'])) {
                            $displayWeight = trim((string)$product['weight']);
                        }
                        ?>
                        <?php if ($displayWeight !== ''): ?>
                            <p class="text-gray-500 text-xs mt-1">
                                <i class="fas fa-weight-hanging mr-1 text-accent"></i><?php echo e($displayWeight); ?>
                            </p>
                        <?php endif; ?>

                        <!-- PRICE -->
                        <div class="w-full flex justify-between items-center">

                            <!-- LEFT: PRICE -->
                            <div class="flex items-baseline flex-wrap space-x-1">

                                <span class="text-green-600 font-semibold text-base sm:text-lg md:text-xl">
                                    <?php echo formatCurrency($product['price']); ?>
                                </span>

                                <?php if ($product['original_price'] > $product['price']): ?>
                                    <span class="text-gray-400 line-through text-xs sm:text-sm">
                                        <?php echo formatCurrency($product['original_price']); ?>
                                    </span>
                                <?php endif; ?>

                            </div>

                            <!-- RIGHT: DISCOUNT -->
                            <?php if ($product['original_price'] > $product['price']): ?>
                                <span class="bg-green-100 text-green-600 text-[8px] md:text-xs text-center font-semibold px-2 py-1 rounded-full text-nowrap">
                                    <?php
                                    $discount = round((($product['original_price'] - $product['price']) / $product['original_price']) * 100);
                                    echo $discount . '% OFF';
                                    ?>
                                </span>
                            <?php endif; ?>

                        </div>

                        <!-- BUTTON -->
                        <?php if ($product['stock'] > 0): ?>
                            <form action="<?php echo BASE_URL; ?>cart.php" method="POST">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="quantity" value="1">

                                <button type="submit"
                                    class="w-full p-2.5 flex items-center justify-center 
                       bg-primary-700 hover:bg-primary-800 text-white text-xs md:text-base font-semibold 
                       rounded-full gap-2 ">
                                    <i class="fas fa-cart-plus text-xs"></i>
                                    Add to Cart
                                </button>
                            </form>
                        <?php elseif ($product['stock'] <= 0): ?>
                            <button disabled
                                class="w-full p-2.5 flex items-center justify-center 
                   bg-red-500 text-white text-xs md:text-base font-semibold rounded-full cursor-not-allowed">
                                Out of Stock
                            </button>
                        <?php endif; ?>


                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="my-20">
    <svg
        viewBox="0 0 1440 200"
        class="w-full h-8 md:h-10"
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

    <div class="py-8 md:py-20 bg-primary text-white">
        <div class="max-w-7xl mx-auto px-4 text-center">

            <!-- TITLE -->
            <h2 class="text-3xl md:text-5xl font-luckiest text-white mb-12 md:mb-20 scroll-animate-top" style="-webkit-text-stroke: 0.5px black;">Why Choose <span class="text-accent">Earthance?</span></h2>

            <!-- FEATURES -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6">

                <!-- ITEM 1 -->
                <div class="flex items-start md:flex-col md:items-center gap-4 text-left md:text-center scroll-animate-left">

                    <span class="flex-shrink-0 w-16 h-16 sm:w-24 sm:h-24 flex items-center justify-center bg-accent-500 rounded-full shadow-md">
                        <i class="fas fa-seedling text-2xl md:text-4xl text-primary-600"></i>
                    </span>

                    <div>
                        <h3 class="font-semibold text-white mb-2">
                            Premium Sourcing
                        </h3>

                        <p class="text-sm text-gray-100 leading-relaxed">
                            Direct procurement from certified Indian farms.
                        </p>
                    </div>

                </div>

                <!-- ITEM 2 -->
                <div class="flex items-start md:flex-col md:items-center gap-4 text-left md:text-center scroll-animate-left">

                    <span class="flex-shrink-0 w-16 h-16 sm:w-24 sm:h-24 flex items-center justify-center bg-accent-500 rounded-full shadow-md">
                        <i class="fas fa-industry text-2xl md:text-4xl text-primary-600"></i>
                    </span>

                    <div>
                        <h3 class="font-semibold text-white mb-2">
                            Advanced Processing
                        </h3>

                        <p class="text-sm text-gray-100 leading-relaxed">
                            State-of-the-art dehydration and roasting technologies.
                        </p>
                    </div>

                </div>

                <!-- ITEM 3 -->
                <div class="flex items-start md:flex-col md:items-center gap-4 text-left md:text-center scroll-animate-left">

                    <span class="flex-shrink-0 w-16 h-16 sm:w-24 sm:h-24 flex items-center justify-center bg-accent-500 rounded-full shadow-md">
                        <i class="fas fa-globe text-2xl md:text-4xl text-primary-600"></i>
                    </span>

                    <div>
                        <h3 class="font-semibold text-white mb-2">
                            Global Standards
                        </h3>

                        <p class="text-sm text-gray-100 leading-relaxed">
                            Compliance with export regulations and food safety certifications.
                        </p>
                    </div>

                </div>

                <!-- ITEM 4 -->
                <div class="flex items-start md:flex-col md:items-center gap-4 text-left md:text-center scroll-animate-left">

                    <span class="flex-shrink-0 w-16 h-16 sm:w-24 sm:h-24 flex items-center justify-center bg-accent-500 rounded-full shadow-md">
                        <i class="fas fa-handshake text-2xl md:text-4xl text-primary-600"></i>
                    </span>

                    <div>
                        <h3 class="font-semibold text-white mb-2">
                            Reliable Partnership
                        </h3>

                        <p class="text-sm text-gray-100 leading-relaxed">
                            Ethical business practices, timely delivery, and transparency.
                        </p>
                    </div>

                </div>

                <!-- ITEM 5 -->
                <div class="flex items-start md:flex-col md:items-center gap-4 text-left md:text-center scroll-animate-left">

                    <span class="flex-shrink-0 w-16 h-16 sm:w-24 sm:h-24 flex items-center justify-center bg-accent-500 rounded-full shadow-md">
                        <i class="fas fa-leaf text-2xl md:text-4xl text-primary-600"></i>
                    </span>

                    <div>
                        <h3 class="font-semibold text-white mb-2">
                            Sustainability Focus
                        </h3>

                        <p class="text-sm text-gray-100 leading-relaxed">
                            Eco-friendly operations and farmer empowerment initiatives.
                        </p>
                    </div>

                </div>

            </div>

        </div>
    </div>

    <svg
        viewBox="0 0 1440 200"
        class="w-full h-8 md:h-10"
        preserveAspectRatio="none">

        <path
            fill="#56B4E2"
            d="
        M0,100
        C240,160 480,20 720,100
        S1200,160 1440,100
        L1440,0 L0,0 Z
        " />
    </svg>

</section>

<!-- Authentic Spices -->
<section class="mb-20">
    <!-- CONTENT -->
    <div class="max-w-7xl mx-auto px-6 grid grid-cols-1 md:grid-cols-3 items-center gap-10">

        <!-- LEFT FEATURES -->
        <div class="space-y-10 text-center md:text-right scroll-animate-left">

            <div class="flex flex-col items-center md:items-end">
                <img src="<?php echo IMAGES_URL; ?>chili.png"
                    class="w-20 h-20 object-contain animate-float">

                <h3 class="font-semibold text-primary">
                    Premium Sourcing
                </h3>

                <p class="text-sm text-gray-500">
                    Direct procurement from certified Indian farms for quality and purity.
                </p>
            </div>

            <div class="flex flex-col items-center md:items-end">
                <img src="<?php echo IMAGES_URL; ?>snacks.png"
                    class="w-20 h-20 object-contain animate-float">

                <h3 class="font-semibold text-primary">
                    Healthy Snack Innovation
                </h3>

                <p class="text-sm text-gray-500">
                    Smart, tasty, and guilt-free snacking solutions for modern lifestyles.
                </p>
            </div>

            <div class="flex flex-col items-center md:items-end">
                <img src="<?php echo IMAGES_URL; ?>spices.png"
                    class="w-20 h-20 object-contain animate-float">

                <h3 class="font-semibold text-primary">
                    Global Quality Standards
                </h3>

                <p class="text-sm text-gray-500">
                    Manufactured under strict hygiene and food safety compliance standards.
                </p>
            </div>

        </div>

        <!-- CENTER IMAGE -->
        <div class="relative flex justify-center items-center h-[420px] overflow-hidden">

            <img src="<?php echo IMAGES_URL; ?>chili.png"
                class="authentic absolute w-auto max-h-full object-contain opacity-0">

            <img src="<?php echo IMAGES_URL; ?>snacks.png"
                class="authentic absolute w-auto max-h-full object-contain opacity-0">

            <img src="<?php echo IMAGES_URL; ?>spices.png"
                class="authentic absolute w-auto max-h-full object-contain opacity-0">

            <img src="<?php echo IMAGES_URL; ?>variety_snacks.png"
                class="authentic absolute w-auto max-h-full object-contain opacity-0">

            <img src="<?php echo IMAGES_URL; ?>natural_ingredients.png"
                class="authentic absolute w-auto max-h-full object-contain opacity-0">

            <img src="<?php echo IMAGES_URL; ?>packaging.png"
                class="authentic absolute w-auto max-h-full object-contain opacity-0">

        </div>

        <script>
            document.addEventListener("DOMContentLoaded", () => {

                const images = document.querySelectorAll(".authentic");

                const animations = [
                    "animate-slide-left",
                    "animate-slide-right",
                    "animate-slide-top",
                    "animate-slide-bottom",
                    "animate-pop",
                ];

                let current = 0;

                function showNextImage() {

                    images.forEach((img) => {

                        img.classList.remove(
                            "opacity-100",
                            "animate-pop",
                            "animate-float",
                            "animate-slide-left",
                            "animate-slide-right",
                            "animate-slide-top",
                            "animate-slide-bottom"
                        );

                        img.classList.add("opacity-0");
                    });

                    const activeImage = images[current];

                    const randomAnimation =
                        animations[Math.floor(Math.random() * animations.length)];

                    activeImage.classList.remove("opacity-0");

                    activeImage.classList.add(
                        "opacity-100",
                        randomAnimation
                    );

                    setTimeout(() => {
                        activeImage.classList.add("animate-float");
                    }, 900);

                    current = (current + 1) % images.length;
                }

                showNextImage();

                setInterval(showNextImage, 4000);
            });
        </script>

        <!-- RIGHT FEATURES -->
        <div class="space-y-10 text-center md:text-left scroll-animate-right">

            <div class="flex flex-col items-center md:items-start">
                <img src="<?php echo IMAGES_URL; ?>/variety_snacks.png"
                    class="w-20 h-20 object-contain animate-float">

                <h3 class="font-semibold text-primary">
                    Export Excellence
                </h3>

                <p class="text-sm text-gray-500">
                    Reliable bulk export and private label solutions for global markets.
                </p>
            </div>

            <div class="flex flex-col items-center md:items-start">
                <img src="<?php echo IMAGES_URL; ?>/natural_ingredients.png"
                    class="w-20 h-20 object-contain animate-float">

                <h3 class="font-semibold text-primary">
                    Natural Ingredients
                </h3>

                <p class="text-sm text-gray-500">
                    Carefully selected ingredients with focus on nutrition and wellness.
                </p>
            </div>

            <div class="flex flex-col items-center md:items-start">
                <img src="<?php echo IMAGES_URL; ?>/packaging.png"
                    class="w-20 h-20 object-contain animate-float">

                <h3 class="font-semibold text-primary">
                    Sustainable Packaging
                </h3>

                <p class="text-sm text-gray-500">
                    Hygienic and eco-friendly packaging designed for freshness and safety.
                </p>
            </div>

        </div>


    </div>
</section>

<!-- Newsletter -->
<section>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl mx-auto text-center">
            <h2 class="text-3xl md:text-5xl font-luckiest text-primary-600 mb-6 scroll-animate-top" style="-webkit-text-stroke: 0.5px black;">Subscribe to <span class="text-accent">Our Newsletter</span></h2>
            <p class="text-gray-500 text-base mb-12 scroll-animate-top">Get the latest updates on new products and exclusive offers</p>

            <form class="flex flex-col sm:flex-row gap-4" action="<?php echo BASE_URL; ?>subscribe.php" method="POST">
                <input type="email" name="email" placeholder="Enter your email" required
                    class="flex-1 px-6 py-4 rounded-full bg-white border text-gray-800 placeholder-gray-400 outline-none focus:border-black shadow-[3px_3px_0_#000] hover:shadow-[4px_3px_0_#000] scroll-animate-left">
                <button type="submit" class="bg-accent-500 text-gray-900 font-semibold 
            py-3 px-8 rounded-full 
            shadow-[3px_3px_0_#000] hover:shadow-[4px_3px_0_#000] 
            transition duration-150 scroll-animate-right">
                    <i class="fas fa-paper-plane mr-2"></i>Subscribe
                </button>
            </form>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '../includes/floating-icons.php'; ?>

<?php require_once 'includes/footer.php'; ?>