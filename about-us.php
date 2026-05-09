<?php

/**
 * About Us Page
 */

$pageTitle = 'About Us';
require_once 'includes/header.php';
?>

<!-- About Hero -->
<section class="py-12 md:pt-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-3xl sm:text-5xl text-primary-600 leading-[1.2] mb-6 font-luckiest animate-slide-top">
            About
            <span class="text-accent" style="-webkit-text-stroke: 1px black;">
                Earthence
            </span>
        </h1>
        <p class="text-sm md:text-base text-gray-600 max-w-2xl mx-auto animate-slide-bottom">Your trusted destination for quality products and exceptional shopping experience</p>
    </div>
</section>

<!-- About Content -->
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 gap-80">
    <div class="grid grid-cols-1 lg:grid-cols-2 md:gap-12 items-center">
        <div>
            <img src="<?php echo IMAGES_URL; ?>makhana_bowl.png"
                alt="About Earthence" class="w-full animate-float">
        </div>
        <div class="text-center md:text-start">
            <h1 class="text-2xl sm:text-3xl text-primary-600 leading-[1.2] mb-6 font-luckiest animate-slide-right">
                Who We
                <span class="text-accent" style="-webkit-text-stroke: 1px black;">
                    Are
                </span>
            </h1>
            <p class="text-gray-600 mb-4 leading-relaxed text-sm md:text-base animate-slide-right">Earthence is a leading e-commerce platform dedicated to providing customers with a seamless and enjoyable shopping experience. Founded with a vision to make quality products accessible to everyone, we have grown into a trusted marketplace serving thousands of satisfied customers.</p>
            <p class="text-gray-600 mb-6 leading-relaxed text-sm md:text-base animate-slide-right">Our team is passionate about curating the best products, ensuring competitive prices, and delivering exceptional customer service. We believe in building lasting relationships with our customers based on trust, transparency, and mutual respect.</p>
            <a href="<?php echo BASE_URL; ?>shop.php" class="inline-flex items-center bg-accent hover:bg-accent-800 text-white text-sm font-semibold py-3 px-6 rounded-full transition hover:shadow-md animate-slide-bottom">
                <i class="fas fa-shopping-bag mr-2"></i>Start Shopping
            </a>
        </div>
    </div>

    <!-- Our Mission -->
    <div class="flex flex-col-reverse md:grid grid-cols-1 lg:grid-cols-2 md:gap-12 items-center my-20">
        <div class="text-center md:text-start">
            <h1 class="text-2xl sm:text-3xl text-primary-600 leading-[1.2] mb-6 font-luckiest animate-slide-right">
                Our
                <span class="text-accent" style="-webkit-text-stroke: 1px black;">
                    Mission
                </span>
            </h1>

            <p class="text-gray-600 mb-4 leading-relaxed text-sm md:text-base animate-slide-right">
                To bring healthy and flavorful snacks to every home by offering premium quality makhana, authentic spices, and delicious traditional products made with care. We are committed to delivering freshness, taste, and nutrition in every bite while maintaining the highest standards of quality and hygiene.
            </p>

            <p class="text-gray-600 mb-6 leading-relaxed text-sm md:text-base animate-slide-right">
                Our passion lies in promoting wholesome snacking and rich Indian flavors through carefully sourced ingredients and handcrafted blends. From crunchy makhana to aromatic spices, we aim to create products that add both health and taste to your everyday lifestyle.
            </p>
            <a href="<?php echo BASE_URL; ?>shop.php" class="inline-flex items-center bg-accent hover:bg-accent-800 text-white text-sm font-semibold py-3 px-6 rounded-full transition hover:shadow-md animate-slide-bottom">
                <i class="fas fa-shopping-bag mr-2"></i>Start Shopping
            </a>
        </div>
        <div>
            <img src="<?php echo IMAGES_URL; ?>makhana_bowl.png"
                alt="About Earthence" class="w-full animate-float">
        </div>
    </div>

    <!-- Our Vision -->
    <div class="grid grid-cols-1 lg:grid-cols-2 md:gap-12 items-center">
        <div>
            <img src="<?php echo IMAGES_URL; ?>natural_ingredients.png"
                alt="About Earthence" class="w-full animate-float">
        </div>
        <div class="text-center md:text-start">
            <h1 class="text-2xl sm:text-3xl text-primary-600 leading-[1.2] mb-6 font-luckiest animate-slide-right">
                Our
                <span class="text-accent" style="-webkit-text-stroke: 1px black;">
                    Vision
                </span>
            </h1>

            <p class="text-gray-600 mb-4 leading-relaxed text-sm md:text-base animate-slide-right">
                Our vision is to become a trusted household name for healthy snacks and authentic spices by combining traditional flavors with modern quality standards. We aim to inspire healthier lifestyles through nutritious products that celebrate the richness of Indian taste and culture.
            </p>

            <p class="text-gray-600 mb-6 leading-relaxed text-sm md:text-base animate-slide-right">
                We envision creating a brand that delivers purity, freshness, and happiness to customers across the country while supporting sustainable sourcing and maintaining a strong commitment to quality, innovation, and customer satisfaction.
            </p>
            <a href="<?php echo BASE_URL; ?>shop.php" class="inline-flex items-center bg-accent hover:bg-accent-800 text-white text-sm font-semibold py-3 px-6 rounded-full transition hover:shadow-md animate-slide-bottom">
                <i class="fas fa-shopping-bag mr-2"></i>Start Shopping
            </a>
        </div>
    </div>
</section>

<!-- Every Mood -->
<section class="my-20 relative bg-accent">

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

            <h2 class="scroll-animate-top text-3xl md:text-5xl font-luckiest text-primary-600 mb-3">
                From Classic to 
                <span class="text-white" style="-webkit-text-stroke: 1px black;">Bold — </span> Discover a Flavor for Every 
                <span class="text-white" style="-webkit-text-stroke: 1px black;">Mood.</span>
            </h2>

            <!-- FEATURES -->
            <ul class="space-y-4 text-xs sm:text-base text-gray-800 max-w-lg mx-auto lg:mx-0">

                <li class="scroll-animate-left flex items-center gap-3 justify-start">
                    <img src="<?php echo IMAGES_URL; ?>/makhana_icon.png" class="w-8 opacity-80 mt-1" />
                    Pure crunch with perfectly balanced salt.
                </li>

                <li class="scroll-animate-left flex items-center gap-3 justify-start">
                    <img src="<?php echo IMAGES_URL; ?>/makhana_icon.png" class="w-8 opacity-80 mt-1" />
                    A bold, fiery blend that excites your taste buds.
                </li>

                <li class="scroll-animate-left flex items-center gap-3 justify-start">
                    <img src="<?php echo IMAGES_URL; ?>/makhana_icon.png" class="w-8 opacity-80 mt-1" />
                    Creamy, rich cheese in every crispy bite.
                </li>

                <li class="scroll-animate-left flex items-center gap-3 justify-start">
                    <img src="<?php echo IMAGES_URL; ?>/makhana_icon.png" class="w-8 opacity-80 mt-1" />
                    Smooth, tangy flavor with a savory twist.
                </li>

            </ul>

            <!-- BUTTON -->
            <div class="flex justify-center lg:justify-start">
                <a href="<?php echo BASE_URL; ?>about-us.php"
                    class="bg-primary-600 text-white font-semibold 
            py-3 px-8 rounded-full 
            shadow-[3px_3px_0_#000] hover:shadow-[4px_3px_0_#000] 
            transition duration-150 scroll-animate-top">
                    Explore Flavors
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

<!-- Authentic Spices -->
<section class="mb-20">
    <!-- CONTENT -->
    <div class="max-w-7xl mx-auto px-6 grid grid-cols-1 md:grid-cols-3 items-center gap-10">

        <!-- LEFT FEATURES -->
        <div class="space-y-10 text-center md:text-right scroll-animate-left">

            <div class="flex flex-col items-center md:items-end">
                <img src="<?php echo IMAGES_URL; ?>chili.png"
                    class="w-20 h-20 object-contain animate-float">
                <h3 class="font-semibold text-gray-800">Authentic Spices</h3>
                <p class="text-sm text-gray-600">
                    Rich aroma and traditional blends to enhance every dish.
                </p>
            </div>

            <div class="flex flex-col items-center md:items-end">
                <img src="<?php echo IMAGES_URL; ?>snacks.png"
                    class="w-20 h-20 object-contain animate-float">
                <h3 class="font-semibold text-gray-800">Crispy Snacks</h3>
                <p class="text-sm text-gray-600">
                    Freshly prepared snacks with perfect crunch and taste.
                </p>
            </div>

            <div class="flex flex-col items-center md:items-end">
                <img src="<?php echo IMAGES_URL; ?>spices.png"
                    class="w-20 h-20 object-contain animate-float">
                <h3 class="font-semibold text-gray-800">Premium Quality</h3>
                <p class="text-sm text-gray-600">
                    Handpicked ingredients ensuring purity and freshness.
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
                <h3 class="font-semibold text-gray-800">Variety of Snacks</h3>
                <p class="text-sm text-gray-600">
                    From namkeen to traditional treats, something for everyone.
                </p>
            </div>

            <div class="flex flex-col items-center md:items-start">
                <img src="<?php echo IMAGES_URL; ?>/natural_ingredients.png"
                    class="w-20 h-20 object-contain animate-float">
                <h3 class="font-semibold text-gray-800">Natural Ingredients</h3>
                <p class="text-sm text-gray-600">
                    No artificial flavors, only real and natural goodness.
                </p>
            </div>

            <div class="flex flex-col items-center md:items-start">
                <img src="<?php echo IMAGES_URL; ?>/packaging.png"
                    class="w-20 h-20 object-contain animate-float">
                <h3 class="font-semibold text-gray-800">Fresh Packaging</h3>
                <p class="text-sm text-gray-600">
                    Hygienically packed to preserve taste and quality.
                </p>
            </div>

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
            fill="#43a047"
            d="
        M0,100
        C180,20 360,200 540,100
        S900,20 1080,100
        S1260,200 1440,100
        L1440,200 L0,200 Z
        " />
    </svg>

    <div class="py-8 md:py-20 bg-primary-600 text-white">
        <div class="max-w-6xl mx-auto px-4 text-center">

            <!-- TITLE -->
            <h2 class="text-3xl md:text-5xl font-luckiest text-white mb-12 md:mb-20 scroll-animate-top">Why Choose <span class="text-accent" style="-webkit-text-stroke: 1px black;">Earthance?</span></h2>

            <!-- FEATURES -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-8">

                <!-- ITEM 1 -->
                <div class="flex items-center gap-4 text-left justify-center sm:justify-start scroll-animate-left">
                    <span class="flex-shrink-0 w-14 h-14 sm:w-28 sm:h-28 flex items-center justify-center bg-accent-500 rounded-full shadow-md">
                        <i class="fas fa-shopping-bag text-2xl md:text-5xl text-primary-500"></i>
                    </span>
                    <div>
                        <h3 class="font-semibold">Premium Ingredients</h3>
                        <p class="text-sm text-white/80">
                            Only high-quality potatoes & natural seasonings.
                        </p>
                    </div>
                </div>

                <!-- ITEM 2 -->
                <div class="flex items-center gap-4 text-left justify-center sm:justify-start scroll-animate-left">
                    <span class="flex-shrink-0 w-14 h-14 sm:w-28 sm:h-28 flex items-center justify-center bg-accent-500 rounded-full shadow-md">
                        <i class="fas fa-shipping-fast text-2xl md:text-5xl text-primary-500"></i>
                    </span>
                    <div>
                        <h3 class="font-semibold">Fast Delivery</h3>
                        <p class="text-sm text-white/80">
                            Fresh, crunchy snacks delivered to your doorstep.
                        </p>
                    </div>
                </div>

                <!-- ITEM 3 -->
                <div class="flex items-center gap-4 text-left justify-center sm:justify-start scroll-animate-left">
                    <span class="flex-shrink-0 w-14 h-14 sm:w-28 sm:h-28 flex items-center justify-center bg-accent-500 rounded-full shadow-md">
                        <i class="fas fa-heart text-2xl md:text-5xl text-primary-500"></i>
                    </span>
                    <div>
                        <h3 class="font-semibold">Loved Nationwide</h3>
                        <p class="text-sm text-white/80">
                            Trusted by thousands of snack lovers.
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
            fill="#43a047"
            d="
        M0,100
        C240,160 480,20 720,100
        S1200,160 1440,100
        L1440,0 L0,0 Z
        " />
    </svg>

</section>

<!-- Call to Action -->
<section class="py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-2xl sm:text-3xl text-primary-600 leading-[1.2] mb-6 font-luckiest scroll-animate-top">
            Ready to Start
            <span class="text-accent" style="-webkit-text-stroke: 1px black;">
                Shopping?
            </span>
        </h1>
        <p class="text-gray-600 text-sm md:text-base mb-6 scroll-animate-top">Explore our wide range of products and experience the Earthence difference today.</p>
        <a href="<?php echo BASE_URL; ?>shop.php" class="inline-flex items-center bg-primary-500 hover:bg-primary-600 text-white text-sm font-semibold py-4 px-8 rounded-full transition hover:shadow-md animate-pop">
            <i class="fas fa-shopping-bag mr-2"></i>Browse Products
        </a>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>