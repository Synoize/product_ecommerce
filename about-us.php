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

        <h1 class="text-3xl sm:text-5xl text-primary-600 leading-[1.2] mb-6 font-secondary animate-slide-top"
             style="text-shadow: 2px 2px 4px rgba(0,0,0,0.25);">

            About
            <span class="text-accent">
                Earthence
            </span>

        </h1>

        <p class="text-sm md:text-base text-gray-500 max-w-3xl mx-auto animate-slide-bottom leading-relaxed">
            KD THE GLOBAL ENTERPRISES, the parent company behind Earthence,
            is a trusted manufacturer, exporter, importer, and trader of
            premium-quality healthy snacks, dehydrated food products,
            and agricultural commodities.
        </p>

    </div>
</section>

<!-- ABOUT CONTENT -->
<section class="max-w-[1920px] mx-auto px-4 sm:px-6 lg:px-12">

    <!-- WHO WE ARE -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">

        <div>
            <img src="<?php echo IMAGES_URL; ?>makhana_bowl.png"
                alt="Premium Earthence Makhana"
                class="w-full animate-float">
        </div>

        <div class="text-center md:text-start">

            <h2 class="text-2xl sm:text-3xl text-primary-600 leading-[1.2] mb-6 font-secondary animate-slide-right"
                style="text-shadow: 2px 2px 4px rgba(0,0,0,0.25);">

                Who We
                <span class="text-accent">
                    Are
                </span>

            </h2>

            <p class="text-gray-500 mb-4 leading-relaxed text-sm md:text-base animate-slide-right">
                KD THE GLOBAL ENTERPRISES is an India-based enterprise with a global outlook,
                operating across manufacturing, export-import, and product development
                for healthy and shelf-stable agro-based food products.
            </p>

            <p class="text-gray-500 mb-4 leading-relaxed text-sm md:text-base animate-slide-right">
                Our brand <strong>Earthence</strong> represents the harmony of
                Earth and Essence — symbolizing purity, wellness, innovation,
                and sustainable living in every product we deliver.
            </p>

            <p class="text-gray-500 mb-6 leading-relaxed text-sm md:text-base animate-slide-right">
                We specialize in Premium Raw & Flavoured Makhana (Fox Nuts),
                dehydrated fruits and vegetables, and modern healthy snack innovations
                crafted using high-quality ingredients and advanced food processing technologies.
            </p>

            <a href="<?php echo BASE_URL; ?>shop.php"
                class="inline-flex items-center bg-accent hover:bg-accent-800 text-white text-sm font-semibold py-3 px-6 rounded-full transition hover:shadow-md animate-slide-bottom">

                <i class="fas fa-shopping-bag mr-2"></i>
                Explore Products

            </a>

        </div>
    </div>

    <!-- MISSION -->
    <div class="flex flex-col-reverse lg:grid grid-cols-1 lg:grid-cols-2 gap-12 items-center my-20">

        <div class="text-center md:text-start">

            <h2 class="text-2xl sm:text-3xl text-primary-600 leading-[1.2] mb-6 font-secondary animate-slide-right"
                style="text-shadow: 2px 2px 4px rgba(0,0,0,0.25);">

                Our
                <span class="text-accent">
                    Mission
                </span>

            </h2>

            <p class="text-gray-500 mb-4 leading-relaxed text-sm md:text-base animate-slide-right">
                To deliver world-class, health-oriented food products from India
                to global consumers while empowering local farmers and maintaining
                the highest standards of quality, sustainability, and transparency.
            </p>

            <p class="text-gray-500 mb-6 leading-relaxed text-sm md:text-base animate-slide-right">
                We are committed to creating smart, sustainable, and health-conscious
                snacking solutions by combining traditional Indian agricultural richness
                with modern food processing innovation.
            </p>

            <a href="<?php echo BASE_URL; ?>shop.php"
                class="inline-flex items-center bg-accent hover:bg-accent-800 text-white text-sm font-semibold py-3 px-6 rounded-full transition hover:shadow-md animate-slide-bottom">

                <i class="fas fa-shopping-bag mr-2"></i>
                Browse Products

            </a>

        </div>

        <div>
            <img src="<?php echo IMAGES_URL; ?>natural_ingredients.png"
                alt="Natural Ingredients"
                class="w-full animate-float">
        </div>

    </div>

    <!-- VISION -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">

        <div>
            <img src="<?php echo IMAGES_URL; ?>spices.png"
                alt="Healthy Snacks and Dehydrated Foods"
                class="w-full animate-float">
        </div>

        <div class="text-center md:text-start">

            <h2 class="text-2xl sm:text-3xl text-primary-600 leading-[1.2] mb-6 font-secondary animate-slide-right"
                style="text-shadow: 2px 2px 4px rgba(0,0,0,0.25);">

                Our
                <span class="text-accent">
                    Vision
                </span>

            </h2>

            <p class="text-gray-500 mb-4 leading-relaxed text-sm md:text-base animate-slide-right">
                To position Earthence among the world’s leading brands
                in healthy snacking and dehydrated foods, recognized globally
                for innovation, quality assurance, and sustainable business practices.
            </p>

            <p class="text-gray-500 mb-6 leading-relaxed text-sm md:text-base animate-slide-right">
                We envision building a healthier and happier world through
                nutritious products, ethical sourcing, eco-friendly operations,
                and long-term partnerships with customers and farmers alike.
            </p>

            <a href="<?php echo BASE_URL; ?>shop.php"
                class="inline-flex items-center bg-accent hover:bg-accent-800 text-white text-sm font-semibold py-3 px-6 rounded-full transition hover:shadow-md animate-slide-bottom">

                <i class="fas fa-shopping-bag mr-2"></i>
                Start Shopping

            </a>

        </div>
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

            <h2 class="scroll-animate-top text-3xl md:text-5xl font-secondary text-accent mb-3"  style="text-shadow: 2px 2px 4px rgba(0,0,0,0.25);"> From Classic to <span class="text-white">Bold — </span> Discover a Flavor for Every <span class="text-white">Mood.</span> </h2>

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
            <h2 class="text-3xl md:text-5xl font-secondary text-white mb-12 md:mb-20 scroll-animate-top"  style="text-shadow: 2px 2px 4px rgba(0,0,0,0.25);">Why Choose <span class="text-accent">Earthance?</span></h2>

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

<!-- KEY FACTS SECTION -->
<section class="pb-12">

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- HEADING -->
        <div class="text-center mb-12">

            <h2 class="text-3xl md:text-5xl font-secondary text-primary-600 mb-4"
                 style="text-shadow: 2px 2px 4px rgba(0,0,0,0.25);">

                Key Facts of
                <span class="text-accent">
                    KD The Global Enterprises
                </span>

            </h2>

            <p class="text-gray-500 text-sm md:text-base max-w-2xl mx-auto">
                Trusted manufacturer, exporter, importer, and trader delivering
                premium-quality healthy snacks and agro-based products globally.
            </p>

        </div>

        <!-- FACTS GRID -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

            <!-- CARD -->
            <div class="bg-white rounded-3xl p-6 shadow-md hover:shadow-xl transition-all duration-300 border border-gray-100">
                <div class="flex items-center gap-4">

                    <div class="w-14 h-14 rounded-full bg-accent-500 flex items-center justify-center">
                        <i class="fas fa-briefcase text-primary-600 text-2xl"></i>
                    </div>

                    <div>
                        <h3 class="font-semibold text-primary text-lg">
                            Nature of Business
                        </h3>

                        <p class="text-sm text-gray-500 mt-1">
                            Manufacturer, Exporter, Importer, Trader
                        </p>
                    </div>

                </div>
            </div>

            <!-- CARD -->
            <div class="bg-white rounded-3xl p-6 shadow-md hover:shadow-xl transition-all duration-300 border border-gray-100">
                <div class="flex items-center gap-4">

                    <div class="w-14 h-14 rounded-full bg-accent-500 flex items-center justify-center">
                        <i class="fas fa-map-marker-alt text-primary-600 text-2xl"></i>
                    </div>

                    <div>
                        <h3 class="font-semibold text-primary text-lg">
                            Location
                        </h3>

                        <p class="text-sm text-gray-500 mt-1">
                            Nagpur, Maharashtra, India
                        </p>
                    </div>

                </div>
            </div>

            <!-- CARD -->
            <div class="bg-white rounded-3xl p-6 shadow-md hover:shadow-xl transition-all duration-300 border border-gray-100">
                <div class="flex items-center gap-4">

                    <div class="w-14 h-14 rounded-full bg-accent-500 flex items-center justify-center">
                        <i class="fas fa-calendar-alt text-primary-600 text-2xl"></i>
                    </div>

                    <div>
                        <h3 class="font-semibold text-primary text-lg">
                            Established
                        </h3>

                        <p class="text-sm text-gray-500 mt-1">
                            2020
                        </p>
                    </div>

                </div>
            </div>

            <!-- CARD -->
            <div class="bg-white rounded-3xl p-6 shadow-md hover:shadow-xl transition-all duration-300 border border-gray-100">
                <div class="flex items-center gap-4">

                    <div class="w-14 h-14 rounded-full bg-accent-500 flex items-center justify-center">
                        <i class="fas fa-file-invoice text-primary-600 text-2xl"></i>
                    </div>

                    <div>
                        <h3 class="font-semibold text-primary text-lg">
                            GST Number
                        </h3>

                        <p class="text-sm text-gray-500 mt-1 break-all">
                            27DDEPR3313F1ZP
                        </p>
                    </div>

                </div>
            </div>

            <!-- CARD -->
            <div class="bg-white rounded-3xl p-6 shadow-md hover:shadow-xl transition-all duration-300 border border-gray-100">
                <div class="flex items-center gap-4">

                    <div class="w-14 h-14 rounded-full bg-accent-500 flex items-center justify-center">
                        <i class="fas fa-globe text-primary-600 text-2xl"></i>
                    </div>

                    <div>
                        <h3 class="font-semibold text-primary text-lg">
                            Export Percentage
                        </h3>

                        <p class="text-sm text-gray-500 mt-1">
                            60%
                        </p>
                    </div>

                </div>
            </div>

            <!-- CARD -->
            <div class="bg-white rounded-3xl p-6 shadow-md hover:shadow-xl transition-all duration-300 border border-gray-100">
                <div class="flex items-center gap-4">

                    <div class="w-14 h-14 rounded-full bg-accent-500 flex items-center justify-center">
                        <i class="fas fa-tags text-primary-600 text-2xl"></i>
                    </div>

                    <div>
                        <h3 class="font-semibold text-primary text-lg">
                            Brand Name
                        </h3>

                        <p class="text-sm text-gray-500 mt-1">
                            Earthence
                        </p>
                    </div>

                </div>
            </div>

            <!-- CARD -->
            <div class="bg-white rounded-3xl p-6 shadow-md hover:shadow-xl transition-all duration-300 border border-gray-100">
                <div class="flex items-center gap-4">

                    <div class="w-14 h-14 rounded-full bg-accent-500 flex items-center justify-center">
                        <i class="fas fa-users text-primary-600 text-2xl"></i>
                    </div>

                    <div>
                        <h3 class="font-semibold text-primary text-lg">
                            Employees
                        </h3>

                        <p class="text-sm text-gray-500 mt-1">
                            09 Team Members
                        </p>
                    </div>

                </div>
            </div>

            <!-- CARD -->
            <div class="bg-white rounded-3xl p-6 shadow-md hover:shadow-xl transition-all duration-300 border border-gray-100">
                <div class="flex items-center gap-4">

                    <div class="w-14 h-14 rounded-full bg-accent-500 flex items-center justify-center">
                        <i class="fas fa-industry text-primary-600 text-2xl"></i>
                    </div>

                    <div>
                        <h3 class="font-semibold text-primary text-lg">
                            Production Units
                        </h3>

                        <p class="text-sm text-gray-500 mt-1">
                            01 Unit
                        </p>
                    </div>

                </div>
            </div>

            <!-- CARD -->
            <div class="bg-white rounded-3xl p-6 shadow-md hover:shadow-xl transition-all duration-300 border border-gray-100">
                <div class="flex items-center gap-4">

                    <div class="w-14 h-14 rounded-full bg-accent-500 flex items-center justify-center">
                        <i class="fas fa-credit-card text-primary-600 text-2xl"></i>
                    </div>

                    <div>
                        <h3 class="font-semibold text-primary text-lg">
                            Payment Modes
                        </h3>

                        <p class="text-sm text-gray-500 mt-1">
                            NEFT / RTGS / IMPS / Cash / Cheque
                        </p>
                    </div>

                </div>
            </div>

        </div>

    </div>

</section>

<!-- Call to Action -->
<section class="py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-2xl sm:text-3xl text-primary-600 leading-[1.2] mb-6 font-secondary scroll-animate-top" style="text-shadow: 2px 2px 4px rgba(0,0,0,0.25);">
            Ready to Start
            <span class="text-accent">
                Shopping?
            </span>
        </h1>
        <p class="text-gray-500 text-sm md:text-base mb-6 scroll-animate-top">Explore our wide range of products and experience the Earthence difference today.</p>
        <a href="<?php echo BASE_URL; ?>shop.php" class="inline-flex items-center bg-primary-500 hover:bg-primary-600 text-white text-sm font-semibold py-4 px-8 rounded-full transition hover:shadow-md animate-pop">
            <i class="fas fa-shopping-bag mr-2"></i>Browse Products
        </a>
    </div>
</section>

<?php require_once 'includes/floating-icons.php'; ?>

<?php require_once 'includes/footer.php'; ?>