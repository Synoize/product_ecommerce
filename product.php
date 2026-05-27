<?php

/**
 * Product Detail Page
 * Shows product information with gallery, reviews, and add to cart
 */

$pageTitle = 'Products';
require_once __DIR__ . '/includes/db_connect.php';

// Get product ID
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($productId <= 0) {
    setFlash('Invalid product ID', 'danger');
    redirect(BASE_URL . 'shop.php');
}

// Handle wishlist action via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['wishlist_action'])) {
    if (!isLoggedIn()) {
        setFlash('Please login to manage wishlist', 'warning');
        redirect(BASE_URL . 'user/login.php');
    }

    $action = $_POST['wishlist_action'];

    if ($action === 'add') {
        if (addToWishlist($productId)) {
            setFlash('Added to wishlist!', 'success');
        } else {
            setFlash('Error adding to wishlist', 'danger');
        }
    } elseif ($action === 'remove') {
        if (removeFromWishlist($productId)) {
            setFlash('Removed from wishlist', 'success');
        } else {
            setFlash('Error removing from wishlist', 'danger');
        }
    }

    redirect(BASE_URL . 'product.php?id=' . $productId);
}

// Fetch product details
try {
    $stmt = $pdo->prepare("SELECT p.*, c.name as category_name 
                          FROM products p 
                          LEFT JOIN categories c ON p.category_id = c.id 
                          WHERE p.id = ? AND p.status = 1");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();

    if (!$product) {
        setFlash('Product not found', 'danger');
        redirect(BASE_URL . 'shop.php');
    }
} catch (PDOException $e) {
    setFlash('Error loading product', 'danger');
    redirect(BASE_URL . 'shop.php');
}

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_action'])) {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = BASE_URL . 'product.php?id=' . $productId . '#reviews';
        setFlash('Please login to write a review.', 'warning');
        redirect(BASE_URL . 'user/login.php');
    }

    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
    $reviewErrors = [];

    if ($rating < 1 || $rating > 5) {
        $reviewErrors[] = 'Please select a rating from 1 to 5 stars.';
    }

    if ($comment === '') {
        $reviewErrors[] = 'Please write your review.';
    } elseif (strlen($comment) > 1000) {
        $reviewErrors[] = 'Review must be 1000 characters or less.';
    }

    if (empty($reviewErrors)) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM reviews WHERE product_id = ? AND user_id = ? ORDER BY created_at DESC LIMIT 1");
            $stmt->execute([$productId, $_SESSION['user_id']]);
            $existingReview = $stmt->fetch();

            if ($existingReview) {
                $stmt = $pdo->prepare("UPDATE reviews SET rating = ?, comment = ?, created_at = NOW() WHERE id = ?");
                $stmt->execute([$rating, $comment, $existingReview['id']]);
                setFlash('Your review has been updated.', 'success');
            } else {
                $stmt = $pdo->prepare("INSERT INTO reviews (product_id, user_id, rating, comment, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$productId, $_SESSION['user_id'], $rating, $comment]);
                setFlash('Thank you for your review!', 'success');
            }
        } catch (PDOException $e) {
            error_log('Review Save Error: ' . $e->getMessage());
            setFlash('Unable to save your review right now.', 'danger');
        }
    } else {
        setFlash(implode(' ', $reviewErrors), 'danger');
    }

    redirect(BASE_URL . 'product.php?id=' . $productId . '#reviews');
}

// Parse gallery images
$gallery = [];
if (!empty($product['gallery'])) {
    $gallery = json_decode($product['gallery'], true) ?: [];
}

// Add main image to gallery if not already there
$mainImage = $product['image'];
if (!empty($mainImage) && !in_array($mainImage, $gallery)) {
    array_unshift($gallery, $mainImage);
}

// Fetch product weights
$productWeights = [];
try {
    $stmt = $pdo->prepare("SELECT id, product_id, flavour AS flavor, weight, price, original_price, stock, image, sort_order, created_at FROM product_variants WHERE product_id = ? AND status = 1 ORDER BY sort_order ASC");
    $stmt->execute([$productId]);
    $productWeights = $stmt->fetchAll();
} catch (PDOException $e) {
    // Silent fail
}

// Fetch related products
$relatedProducts = [];
try {
    $stmt = $pdo->prepare("SELECT p.*, c.name as category_name 
                          FROM products p 
                          LEFT JOIN categories c ON p.category_id = c.id 
                          WHERE p.category_id = ? AND p.id != ? AND p.status = 1 
                          ORDER BY RAND() LIMIT 4");
    $stmt->execute([$product['category_id'], $productId]);
    $relatedProducts = $stmt->fetchAll();
} catch (PDOException $e) {
    // Silent fail
}

// Fetch reviews
$reviews = [];
$avgRating = 0;
$userReview = null;
try {
    $stmt = $pdo->prepare("SELECT r.*, u.name as user_name 
                          FROM reviews r 
                          JOIN users u ON r.user_id = u.id 
                          WHERE r.product_id = ? 
                          ORDER BY r.created_at DESC");
    $stmt->execute([$productId]);
    $reviews = $stmt->fetchAll();

    if (count($reviews) > 0) {
        $avgRating = array_sum(array_column($reviews, 'rating')) / count($reviews);
    }

    if (isLoggedIn()) {
        foreach ($reviews as $review) {
            if ((int)$review['user_id'] === (int)$_SESSION['user_id']) {
                $userReview = $review;
                break;
            }
        }
    }
} catch (PDOException $e) {
    // Reviews table might not exist
}

// Check if product is in wishlist
$inWishlist = isInWishlist($productId);

$pageTitle = $product['name'];

$productDetailSections = [
    'Description' => trim((string)($product['description'] ?? '')),
    'Ingredients' => trim((string)($product['ingredients'] ?? '')),
    'Shipping & Return' => trim((string)($product['shipping_return'] ?? '')),
    'Legal Mandatories' => trim((string)($product['legal_mandatories'] ?? '')),
];

$productName = trim((string)($product['name'] ?? 'this product'));
$productCategory = trim((string)($product['category_name'] ?? 'Food Product'));
$categoryKey = strtolower($productCategory);
$defaultIngredients = strpos($categoryKey, 'spice') !== false || strpos($categoryKey, 'masala') !== false
    ? $productName . '. Packed fresh to preserve natural aroma and flavor. Please check the product pack for the complete ingredient list.'
    : $productName . ', seasoning, spices, and permitted ingredients as applicable. Please check the product pack for complete ingredients and allergen information.';

$productDetailFallbacks = [
    'Ingredients' => $defaultIngredients,
    'Shipping & Return' => 'Orders are usually shipped within 2-4 business days. Returns are accepted for eligible unopened products as per store policy.',
    'Legal Mandatories' => "Product: {$productName}\nCategory: {$productCategory}\nCountry of Origin: India\nPlease refer to the product packaging for manufacturer details, batch number, expiry date, MRP, net quantity, and other statutory information.",
];

$productFlavors = [];
$hasUnflavoredWeights = false;
foreach ($productWeights as $weightOption) {
    $flavor = trim((string)($weightOption['flavor'] ?? ''));
    if ($flavor === '') {
        $hasUnflavoredWeights = true;
    }
    if ($flavor !== '' && !in_array($flavor, $productFlavors, true)) {
        $productFlavors[] = $flavor;
    }
}

$selectedWeightIndex = 0;
foreach ($productWeights as $index => $weight) {
    $weightFlavor = trim((string)($weight['flavor'] ?? ''));
    if ((int)$weight['stock'] > 0 && (!$hasUnflavoredWeights || $weightFlavor === '')) {
        $selectedWeightIndex = $index;
        break;
    }
}

$selectedWeight = $productWeights[$selectedWeightIndex] ?? null;
$selectedPrice = $selectedWeight ? (float)$selectedWeight['price'] : (float)$product['price'];
$selectedStock = $selectedWeight ? (int)$selectedWeight['stock'] : (int)$product['stock'];
$selectedWeightLabel = $selectedWeight ? $selectedWeight['weight'] : 'Default';
$selectedFlavorLabel = $selectedWeight ? trim((string)($selectedWeight['flavor'] ?? '')) : '';
$initialFlavorFilter = $hasUnflavoredWeights ? '__without_flavour__' : '';
$productOriginalPrice = $selectedWeight && !empty($selectedWeight['original_price'])
    ? (float)$selectedWeight['original_price']
    : (float)($product['original_price'] ?? 0);
$productAvailableStock = (int)$product['stock'];
if (!empty($productWeights)) {
    $productAvailableStock = 0;
    foreach ($productWeights as $weightOption) {
        $productAvailableStock += (int)$weightOption['stock'];
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Breadcrumb -->
<section class="py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <nav class="text-sm text-gray-600">
            <ol class="flex items-center space-x-2">
                <li><a href="<?php echo BASE_URL; ?>" class="hover:text-primary">Home</a></li>
                <li><i class="fas fa-chevron-right text-xs"></i></li>
                <li><a href="<?php echo BASE_URL; ?>shop.php" class="hover:text-primary">Shop</a></li>
                <li><i class="fas fa-chevron-right text-xs"></i></li>
                <li class="text-primary font-medium truncate max-w-xs"><?php echo e($product['name']); ?></li>
            </ol>
        </nav>
    </div>
</section>

<!-- Product Detail -->
<section>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Product Gallery -->
            <div>
                <div class="space-y-4">
                    <!-- Main Image -->
                    <div class="relative rounded-2xl border overflow-hidden shadow-sm bg-gray-50">
                        <?php
                        $mainImageUrl = !empty($gallery) ? getImageUrl($gallery[0], 'products') : getImageUrl($product['image'], 'products');
                        ?>
                        <?php renderWishlistIconButton($product['id'], 'absolute top-4 right-4 z-10'); ?>
                        <img src="<?php echo $mainImageUrl; ?>" id="mainProductImage" alt="<?php echo e($product['name']); ?>" class="w-full max-h-96 min-h-90 object-contain">
                    </div>

                    <!-- Thumbnail Images -->
                    <?php if (count($gallery) > 1): ?>
                        <div class="grid grid-cols-4 gap-3">
                            <?php foreach ($gallery as $index => $image): ?>
                                <img src="<?php echo getImageUrl($image, 'products'); ?>"
                                    alt="<?php echo e($product['name']); ?> - <?php echo $index + 1; ?>"
                                    class="w-full h-16 md:h-24 object-contain rounded-xl border cursor-pointer transition hover:opacity-75 <?php echo $index === 0 ? 'ring-2 ring-accent' : ''; ?>"
                                    onclick="changeMainImage(this.src)">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Product Info -->
            <div>
                <div class="lg:pl-8">
                    <!-- Category -->
                    <a href="<?php echo BASE_URL; ?>shop.php?category=<?php echo $product['category_id']; ?>"
                        class="text-gray-500 hover:text-accent text-sm font-medium">
                        <?php echo e($product['category_name'] ?? 'Uncategorized'); ?>
                    </a>

                    <!-- Title -->
                    <div class="mt-2 mb-4 flex items-start gap-3">
                        <h1 class="flex-1 text-2xl md:text-4xl font-bold text-gray-900 line-clamp-2"><?php echo e($product['name']); ?></h1>
                    </div>

                    <!-- Rating -->
                    <div class="flex items-center mb-4">
                        <div class="flex items-center mr-3">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star <?php echo $i <= round($avgRating) ? 'text-yellow-400' : 'text-gray-300'; ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <span class="text-gray-500">(<?php echo count($reviews); ?> reviews)</span>
                    </div>

                    <!-- Price -->
                    <div class="mb-6 flex items-center">
                        <span id="displayProductPrice" class="text-4xl font-bold text-green-600"><?php echo formatCurrency($selectedPrice); ?></span>
                        <?php if ($productOriginalPrice > $selectedPrice): ?>
                            <span id="displayOriginalPrice" class="text-xl text-gray-400 line-through ml-3"><?php echo formatCurrency($productOriginalPrice); ?></span>
                            <span id="displayDiscountBadge" class="inline-block bg-green-500 text-white text-xs font-bold px-2 py-1 rounded ml-5">
                                <?php echo round((($productOriginalPrice - $selectedPrice) / $productOriginalPrice) * 100); ?>% OFF
                            </span>
                        <?php else: ?>
                            <span id="displayOriginalPrice" class="hidden text-xl text-gray-400 line-through ml-3"></span>
                            <span id="displayDiscountBadge" class="hidden inline-block bg-green-500 text-white text-xs font-bold px-2 py-1 rounded ml-5"></span>
                        <?php endif; ?>
                    </div>

                    <?php
                    $displayWeight = '';
                    if (!empty($productWeights) && !empty($selectedWeightLabel) && $selectedWeightLabel !== 'Default') {
                        $displayWeight = (string)$selectedWeightLabel;
                    } elseif (!empty($product['weight'])) {
                        $displayWeight = (string)$product['weight'];
                    }
                    ?>
                    <?php if ($displayWeight !== ''): ?>
                        <div class="mb-6">
                            <span id="displayProductWeight" class="inline-flex items-center bg-gray-100 text-gray-700 px-3 py-1 rounded-full text-sm font-medium">
                                Net Weight: <?php echo e($displayWeight); ?>
                            </span>
                        </div>
                    <?php endif; ?>

                    <!-- Description -->
                    <p class="text-gray-600 mb-6 text-sm leading-relaxed line-clamp-2"><?php echo nl2br(e($product['description'])); ?></p>

                    <!-- Stock Status -->
                    <div class="mb-6">
                        <?php if ($productAvailableStock > 10): ?>
                            <span></span>
                        <?php elseif ($productAvailableStock > 0): ?>
                            <span class="inline-flex items-center bg-yellow-100 text-yellow-700 px-3 py-1 rounded-full text-sm font-medium"><i data-lucide="smile" class="w-4 h-4 mr-1"></i>Only <?php echo $productAvailableStock; ?> left</span>
                        <?php else: ?>
                            <span class="inline-flex items-center bg-red-100 text-red-700 px-3 py-1 rounded-full text-sm font-medium"><i data-lucide="frown" class="w-4 h-4 mr-1"></i> Out of Stock</span>
                        <?php endif; ?>
                    </div>

                    <!-- Add to Cart Form -->
                    <?php if ($productAvailableStock > 0): ?>
                        <form action="<?php echo BASE_URL; ?>cart.php" method="POST" class="mb-6">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <input type="hidden" name="action" value="add">

                            <?php if (!empty($productFlavors)): ?>
                                <div class="mb-5">
                                    <label class="block text-base font-semibold text-gray-800 mb-3">Select Flavour</label>
                                    <div class="flex flex-wrap gap-3">
                                        <?php if ($hasUnflavoredWeights): ?>
                                            <button type="button" class="flavor-filter min-w-[110px] rounded-xl border-2 <?php echo $initialFlavorFilter === '__without_flavour__' ? 'border-accent bg-accent/5 text-gray-800' : 'border-gray-300 bg-white text-gray-800'; ?> px-5 py-3 text-center text-xs font-semibold transition" data-flavor="__without_flavour__" onclick="selectFlavor(this)">
                                                No Flavour
                                            </button>
                                        <?php endif; ?>
                                        <?php foreach ($productFlavors as $flavor): ?>
                                            <button type="button" class="flavor-filter min-w-[110px] rounded-xl border-2 border-gray-300 bg-white px-5 py-3 text-center text-xs font-semibold text-gray-800 transition" data-flavor="<?php echo e($flavor); ?>" onclick="selectFlavor(this)">
                                                <?php echo e($flavor); ?>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Weight Selection -->
                            <?php if (!empty($productWeights)): ?>
                                <div class="mb-4">
                                    <label class="block text-base font-semibold text-gray-800 mb-3">Select Weight</label>
                                    <div class="flex flex-wrap gap-3">
                                        <?php foreach ($productWeights as $index => $weight): ?>
                                            <?php
                                            $weightFlavorValue = trim((string)($weight['flavor'] ?? ''));
                                            $isInitialVisible = $initialFlavorFilter === ''
                                                || ($initialFlavorFilter === '__without_flavour__' && $weightFlavorValue === '')
                                                || $initialFlavorFilter === $weightFlavorValue;
                                            ?>
                                            <label
                                                data-flavor="<?php echo e($weightFlavorValue); ?>"
                                                class="<?php echo $isInitialVisible ? '' : 'hidden'; ?> group relative flex min-w-[115px] cursor-pointer flex-col items-center justify-center overflow-hidden rounded-xl bg-white px-4 py-3 text-center transition-all duration-300
    <?php echo (int)$weight['stock'] <= 0
                                                ? 'cursor-not-allowed border border-gray-200 bg-gray-50 opacity-70'
                                                : ''; ?>">

                                                <!-- Radio -->
                                                <input
                                                    type="radio"
                                                    name="variant_id"
                                                    value="<?php echo $weight['id']; ?>"
                                                    class="peer sr-only"
                                                    data-weight="<?php echo e($weight['weight']); ?>"
                                                    data-flavor="<?php echo e(trim((string)($weight['flavor'] ?? ''))); ?>"
                                                    data-price="<?php echo (float)$weight['price']; ?>"
                                                    data-original-price="<?php echo !empty($weight['original_price']) ? (float)$weight['original_price'] : (float)($product['original_price'] ?? 0); ?>"
                                                    data-stock="<?php echo (int)$weight['stock']; ?>"
                                                    data-image-url="<?php echo !empty($weight['image']) ? e(getImageUrl($weight['image'], 'products')) : ''; ?>"
                                                    onchange="updatePrice(this)"
                                                    <?php echo $index === $selectedWeightIndex ? 'checked' : ''; ?>
                                                    <?php echo (int)$weight['stock'] <= 0 ? 'disabled' : ''; ?>>

                                                <!-- Selected Border -->
                                                <span
                                                    class="absolute inset-0 rounded-xl border-2 transition-all duration-300
        peer-checked:border-accent peer-checked:bg-accent/5">
                                                </span>

                                                <!-- Weight -->
                                                <div class="relative z-10 flex flex-col items-center">
                                                    <span
                                                        class="text-xs font-bold tracking-wide transition-all duration-300
            <?php echo (int)$weight['stock'] <= 0
                                                ? 'text-gray-400'
                                                : 'text-gray-800 peer-checked:text-accent'; ?>">
                                                        <?php echo e($weight['weight']); ?>
                                                    </span>

                                                    <!-- Stock Status -->
                                                    <?php if ((int)$weight['stock'] <= 0): ?>
                                                        <span
                                                            class="mt-2 rounded-full bg-red-100 px-2 py-1 text-[10px] font-semibold text-red-600">
                                                            Out of Stock
                                                        </span>
                                                    <?php endif; ?>
                                                </div>

                                                <!-- Check Icon -->
                                                <div
                                                    class="absolute left-2 top-2 hidden h-5 w-5 items-center justify-center rounded-full bg-accent text-white peer-checked:flex">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none"
                                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                </div>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="flex flex-col sm:flex-row sm:items-center gap-4 my-8">

                                <!-- Quantity Selector -->
                                <div class="flex items-center bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden w-fit">

                                    <!-- Decrease -->
                                    <button
                                        type="button"
                                        onclick="decreaseQty()"
                                        class="w-10 h-10 md:w-12 md:h-12 flex items-center justify-center text-xl font-semibold text-gray-700 hover:bg-gray-100 active:scale-95 transition-all duration-200">
                                        <i class="fas fa-minus text-xs md:text-sm"></i>
                                    </button>

                                    <!-- Quantity Input -->
                                    <input
                                        type="number"
                                        name="quantity"
                                        id="quantity"
                                        value="1"
                                        min="1"
                                        max="<?php echo $selectedStock; ?>"
                                        onchange="updatePriceSummary()"
                                        oninput="updatePriceSummary()"
                                        class="w-12 md:w-16 h-10 md:h-12 text-center font-bold text-sm md:text-lg border-x border-gray-200 focus:outline-none appearance-none bg-gray-50">

                                    <!-- Increase -->
                                    <button
                                        type="button"
                                        onclick="increaseQty(<?php echo $selectedStock; ?>)"
                                        class="w-10 h-10 md:w-12 md:h-12 flex items-center justify-center text-xl font-semibold text-gray-700 hover:bg-gray-100 active:scale-95 transition-all duration-200">
                                        <i class="fas fa-plus text-xs md:text-sm"></i>
                                    </button>
                                </div>

                                <div class="grid grid-cols-2 md:flex-1 gap-3 text-sm ">
                                    <!-- Add To Cart Button -->
                                    <button
                                        type="submit"
                                        class="group relative overflow-hidden bg-accent hover:bg-accent text-white font-semibold md:px-8 p-3.5 rounded-lg hover:shadow-sm transition-all duration-300 inline-flex items-center justify-center">

                                        <span class="absolute inset-0 bg-white/10 translate-y-full group-hover:translate-y-0 transition-transform duration-300"></span>

                                        <i class="fas fa-cart-plus mr-2 relative z-10 group-hover:scale-110 transition-transform duration-300"></i>

                                        <span class="relative z-10">
                                            Add to Cart
                                        </span>
                                    </button>

                                    <!-- Buy Now Button -->
                                    <button
                                        type="submit"
                                        name="redirect_to"
                                        value="cart"
                                        class="group relative overflow-hidden bg-primary-600 hover:bg-primary-700 text-white font-semibold md:px-8 p-3.5 rounded-lg hover:shadow-sm transition-all duration-300 inline-flex items-center justify-center">

                                        <span class="absolute inset-0 bg-white/10 translate-y-full group-hover:translate-y-0 transition-transform duration-300"></span>

                                        <i class="fas fa-cart-plus mr-2 relative z-10 group-hover:scale-110 transition-transform duration-300"></i>

                                        <span class="relative z-10">
                                            Buy Now
                                        </span>
                                    </button>
                                </div>

                            </div>

                            <div id="priceSummary" class="mb-5  rounded-xl border border-gray-200 bg-gray-50 p-4">
                                <div class="flex items-center justify-between gap-4 border-b border-gray-200 pb-3 mb-3">
                                    <h3 class="font-bold text-gray-900">Price Summary</h3>
                                    <span id="summaryStock" class="text-xs font-semibold <?php echo $selectedStock > 0 ? 'text-green-600' : 'text-red-500'; ?>">
                                        <?php echo $selectedStock > 0 ? 'In Stock' : 'Out of Stock'; ?>
                                    </span>
                                </div>
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between gap-4">
                                        <span class="text-gray-500">Flavor</span>
                                        <span id="summaryFlavor" class="font-medium text-gray-900"><?php echo e($selectedFlavorLabel !== '' ? $selectedFlavorLabel : 'No Flavour'); ?></span>
                                    </div>
                                    <div class="flex justify-between gap-4">
                                        <span class="text-gray-500">Weight</span>
                                        <span id="summaryWeight" class="font-medium text-gray-900"><?php echo e($selectedWeightLabel); ?></span>
                                    </div>
                                    <div class="flex justify-between gap-4">
                                        <span class="text-gray-500">Unit Price</span>
                                        <span id="summaryUnitPrice" class="font-medium text-gray-900"><?php echo formatCurrency($selectedPrice); ?></span>
                                    </div>
                                    <div id="summaryOriginalPriceRow" class="<?php echo $productOriginalPrice > $selectedPrice ? 'flex' : 'hidden'; ?> justify-between gap-4">
                                        <span class="text-gray-500">Original Price</span>
                                        <span id="summaryOriginalPrice" class="font-medium text-gray-400 line-through"><?php echo formatCurrency($productOriginalPrice); ?></span>
                                    </div>
                                    <div id="summarySavingsRow" class="<?php echo $productOriginalPrice > $selectedPrice ? 'flex' : 'hidden'; ?> justify-between gap-4">
                                        <span class="text-gray-500">Savings</span>
                                        <span id="summarySavings" class="font-medium text-green-600"><?php echo formatCurrency(max(0, $productOriginalPrice - $selectedPrice)); ?></span>
                                    </div>
                                    <div class="flex justify-between gap-4">
                                        <span class="text-gray-500">Quantity</span>
                                        <span id="summaryQuantity" class="font-medium text-gray-900">1</span>
                                    </div>
                                </div>
                                <div class="mt-3 border-t border-gray-200 pt-3 flex justify-between gap-4">
                                    <span class="font-bold text-gray-900">Total</span>
                                    <span id="summaryTotal" class="font-bold text-lg text-green-600"><?php echo formatCurrency($selectedPrice); ?></span>
                                </div>
                            </div>
                        </form>
                    <?php endif; ?>

                    <div class="flex flex-col justify-center gap-5 pb-8">
                        <h3 class="text-sm text-gray-500">Accepted Payment Methods</h3>
                        <div class="flex flex-wrap items-center gap-3" aria-label="Accepted payment methods">
                            <span class="inline-flex h-8 items-center rounded bg-gray-50 px-3 text-sm font-black italic text-blue-700">VISA</span>
                            <span class="inline-flex h-8 items-center rounded bg-gray-50 px-3 text-sm font-black text-red-600">Master<span class="text-yellow-500">Card</span></span>
                            <span class="inline-flex h-8 items-center rounded bg-gray-50 px-3 text-sm font-black text-blue-700">RuPay</span>
                            <span class="inline-flex h-8 items-center rounded bg-gray-50 px-3 text-sm font-black text-green-700">UPI</span>
                            <span class="inline-flex h-8 items-center rounded bg-gray-50 px-3 text-sm font-black text-blue-600">Maestro</span>
                        </div>
                    </div>

                    <!-- Additional Features -->

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">

                        <!-- Fresh Products -->
                        <div class="group bg-white border border-gray-100 rounded-2xl p-4 text-center hover:shadow-md transition-all duration-300">
                            <div class="w-14 h-14 mx-auto mb-3 rounded-full bg-primary-50 flex items-center justify-center group-hover:scale-110 transition">
                                <i class="fas fa-leaf text-2xl text-primary-500"></i>
                            </div>
                            <h4 class="font-semibold text-gray-800 text-sm">100% Fresh</h4>
                            <p class="text-xs text-gray-500 mt-1">Premium quality products</p>
                        </div>

                        <!-- Fast Delivery -->
                        <div class="group bg-white border border-gray-100 rounded-2xl p-4 text-center hover:shadow-md transition-all duration-300">
                            <div class="w-14 h-14 mx-auto mb-3 rounded-full bg-orange-50 flex items-center justify-center group-hover:scale-110 transition">
                                <i class="fas fa-shipping-fast text-2xl text-orange-500"></i>
                            </div>
                            <h4 class="font-semibold text-gray-800 text-sm">Fast Delivery</h4>
                            <p class="text-xs text-gray-500 mt-1">Quick doorstep service</p>
                        </div>

                        <!-- Safe Packaging -->
                        <div class="group bg-white border border-gray-100 rounded-2xl p-4 text-center hover:shadow-md transition-all duration-300">
                            <div class="w-14 h-14 mx-auto mb-3 rounded-full bg-blue-50 flex items-center justify-center group-hover:scale-110 transition">
                                <i class="fas fa-box-open text-2xl text-blue-500"></i>
                            </div>
                            <h4 class="font-semibold text-gray-800 text-sm">Safe Packing</h4>
                            <p class="text-xs text-gray-500 mt-1">Handled with care</p>
                        </div>

                        <!-- Trusted Service -->
                        <div class="group bg-white border border-gray-100 rounded-2xl p-4 text-center hover:shadow-md transition-all duration-300">
                            <div class="w-14 h-14 mx-auto mb-3 rounded-full bg-green-50 flex items-center justify-center group-hover:scale-110 transition">
                                <i class="fas fa-award text-2xl text-green-500"></i>
                            </div>
                            <h4 class="font-semibold text-gray-800 text-sm">Trusted Brand</h4>
                            <p class="text-xs text-gray-500 mt-1">Loved by customers</p>
                        </div>

                    </div>

                </div>
            </div>
        </div>

        <!-- Product Details Accordion -->
        <div class="mt-16 bg-primary text-white rounded-lg p-4 md:p-8">
            <div class="divide-y divide-white/15">
                <?php foreach ($productDetailSections as $sectionTitle => $sectionContent): ?>
                    <?php $detailContent = $sectionContent !== '' ? $sectionContent : ($productDetailFallbacks[$sectionTitle] ?? 'Details will be updated soon.'); ?>
                    <details class="group" <?php echo $sectionTitle === 'Description' ? 'open' : ''; ?>>
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 py-6 text-sm md:text-lg font-semibold">
                            <span><?php echo e($sectionTitle); ?></span>
                            <i class="fas fa-chevron-up text-sm transition group-open:rotate-90"></i>
                        </summary>
                        <div class="pb-6 leading-relaxed text-gray-100 text-xs md:text-sm">
                            <?php echo nl2br(e($detailContent)); ?>
                        </div>
                    </details>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Reviews Section -->
        <div class="mt-16" id="reviews">
            <h3 class="text-3xl md:text-4xl font-luckiest text-primary-600 text-center md:text-start mb-8" style="-webkit-text-stroke: 0.5px black;">Customer <span class="text-accent">Reviews</span></h3>

            <div class="mb-8">
                <div class="bg-primary-600 text-white px-6 md:px-10 py-6">
                    <div class="grid grid-cols-1 md:grid-cols-[1fr_1px_300px] items-center gap-6 md:gap-10">
                        <div>
                            <div class="flex items-center gap-1.5 text-accent text-xl mb-4">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="far fa-star"></i>
                                <?php endfor; ?>
                            </div>
                            <p class="text-sm md:text-lg">
                                <?php echo count($reviews) === 0 ? 'Be the first to write a review' : 'Share your experience with this product'; ?>
                            </p>
                        </div>
                        <div class="hidden md:block h-20 bg-white/15"></div>
                        <div>
                            <?php if (isLoggedIn()): ?>
                                <button type="button" onclick="toggleReviewForm()" class="w-full bg-accent-800 hover:bg-accent text-white text-sm font-semibold py-4 px-8 transition">
                                    <?php echo $userReview ? 'Update review' : 'Write a review'; ?>
                                </button>
                            <?php else: ?>
                                <a href="<?php echo BASE_URL; ?>user/login.php" class="w-full bg-accent-800 hover:bg-accent text-white text-sm font-semibold py-4 px-8 transition inline-flex items-center justify-center">
                                    Write a review
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <?php if (isLoggedIn()): ?>
                    <div id="reviewFormPanel"
                        class="border-t hidden relative overflow-hidden bg-primary-600 rounded-b-lg p-6 md:p-8">
                        <div class="relative z-10">
                            <!-- Heading -->
                            <div class="mb-6">
                                <h4 class="text-2xl font-semibold text-white tracking-tight">
                                    <?php echo $userReview ? 'Update Your Review' : 'Write a Review'; ?>
                                </h4>
                                <p class="text-sm text-gray-200 mt-1">
                                    Share your genuine experience to help other customers.
                                </p>
                            </div>

                            <form method="POST"
                                action="<?php echo BASE_URL; ?>product.php?id=<?php echo $productId; ?>#reviews"
                                class="space-y-6">

                                <input type="hidden" name="review_action" value="save">

                                <!-- Rating -->
                                <div>
                                    <!-- <label class="block text-sm font-bold uppercase tracking-wide text-gray-200 mb-3">
                                        Your Rating
                                    </label> -->

                                    <div class="flex items-center gap-4 flex-wrap">

                                        <!-- Stars -->
                                        <div class="review-star-rating flex flex-row-reverse justify-end gap-2 text-4xl">

                                            <?php for ($ratingValue = 5; $ratingValue >= 1; $ratingValue--): ?>
                                                <input
                                                    type="radio"
                                                    id="rating<?php echo $ratingValue; ?>"
                                                    name="rating"
                                                    value="<?php echo $ratingValue; ?>"
                                                    class="peer sr-only"
                                                    <?php echo ((int)($userReview['rating'] ?? 5) === $ratingValue) ? 'checked' : ''; ?>>

                                                <label
                                                    for="rating<?php echo $ratingValue; ?>"
                                                    aria-label="<?php echo $ratingValue; ?> star<?php echo $ratingValue > 1 ? 's' : ''; ?>"
                                                    class="cursor-pointer text-white transition-all duration-200 hover:scale-125 hover:text-accent
                                peer-checked:text-accent">
                                                    <i class="fas fa-star drop-shadow-sm"></i>
                                                </label>
                                            <?php endfor; ?>

                                        </div>

                                        <!-- Rating Text -->
                                        <span class="text-sm text-gray-200">
                                            Tap a star to rate this product
                                        </span>
                                    </div>
                                </div>

                                <!-- Review Box -->
                                <div>
                                    <div class="flex items-center justify-between mb-2">
                                        <label for="reviewComment"
                                            class="text-sm font-semibold uppercase tracking-wide text-gray-200">
                                            Your Review
                                        </label>

                                        <span class="text-xs text-gray-200">
                                            Max 1000 characters
                                        </span>
                                    </div>

                                    <div class="relative">
                                        <textarea
                                            id="reviewComment"
                                            name="comment"
                                            rows="4"
                                            maxlength="1000"
                                            required
                                            placeholder="Tell others what you liked, quality, delivery experience, packaging, etc..."
                                            class="w-full resize-none rounded-lg border bg-white px-5 py-4 text-gray-700 shadow-sm outline-none transition-all duration-300 placeholder:text-gray-400 focus:border-accent"><?php echo e($userReview['comment'] ?? ''); ?></textarea>

                                        <div class="absolute bottom-4 right-4 text-gray-500 text-sm">
                                            <i class="fas fa-pen"></i>
                                        </div>
                                    </div>
                                </div>

                                <!-- Buttons -->
                                <div class="flex flex-col sm:flex-row items-center gap-4 pt-2">

                                    <button type="submit"
                                        class="group inline-flex items-center justify-center rounded-full bg-accent px-7 py-3.5 font-semibold text-white text-sm hover:bg-accent-800">

                                        <?php echo $userReview ? 'Update Review' : 'Submit Review'; ?>
                                    </button>

                                    <p class="text-sm text-gray-200 flex items-center gap-2">
                                        <i class="fas fa-shield-alt text-accent"></i>
                                        Your review will be publicly visible.
                                    </p>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (count($reviews) > 0): ?>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div>
                        <div class="bg-white border rounded-lg shadow-sm p-6 text-center">
                            <h2 class="text-5xl font-bold text-gray-900 mb-2"><?php echo number_format($avgRating, 1); ?></h2>
                            <div class="flex justify-center items-center my-3">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star <?php echo $i <= round($avgRating) ? 'text-yellow-400' : 'text-gray-300'; ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <p class="text-gray-500">Based on <?php echo count($reviews); ?> reviews</p>
                        </div>
                    </div>
                    <div class="lg:col-span-2 max-h-[600px] overflow-y-auto [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden space-y-4">
                        <?php foreach ($reviews as $review): ?>
                            <div class="bg-white border rounded-lg shadow-sm p-4">
                                <div class="flex justify-between items-start mb-3">
                                    <div>
                                        <h6 class="font-semibold text-gray-900"><?php echo e($review['user_name']); ?></h6>

                                        <div class="flex items-center mt-1">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'text-accent' : 'text-gray-300'; ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                    </div>

                                    <small class="text-gray-400">
                                        <?php echo date('M d, Y', strtotime($review['created_at'])); ?>
                                    </small>
                                </div>

                                <p class="text-gray-600 text-xs">
                                    <?php echo e($review['comment']); ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="text-center py-12">
                    <i class="fas fa-comment-slash text-5xl text-gray-300 mb-4"></i>
                    <p class="text-gray-500">No reviews yet. Be the first to review!</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Related Products -->
        <?php if (!empty($relatedProducts)): ?>
            <div class="mt-16">
                <h3 class="text-3xl md:text-4xl font-luckiest text-primary-600 text-center md:text-start mb-8" style="-webkit-text-stroke: 0.5px black;">Related <span class="text-accent">Products</span></h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 lg:gap-6">
                    <?php foreach ($relatedProducts as $product): ?>
                        <div class="bg-white rounded-2xl border hover:shadow-md transition-all duration-300 overflow-hidden group">

                            <!-- IMAGE -->
                            <div class="relative overflow-hidden bg-white flex items-center justify-center">
                                <?php $imageUrl = getImageUrl($product['image'], 'products'); ?>

                                <a
                                    href="<?php echo BASE_URL; ?>product.php?id=<?php echo $product['id']; ?>"
                                    class="w-full flex items-center justify-center">
                                    <img
                                        src="<?php echo $imageUrl; ?>"
                                        alt="<?php echo e($product['name']); ?>"
                                        class="h-28 sm:h-40 md:h-44 object-contain 
                                               drop-shadow-[0_8px_40px_rgba(0,0,0,0.14)]
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
        <?php endif; ?>
    </div>
</section>

<style>
    .review-star-rating input:checked~label,
    .review-star-rating label:hover,
    .review-star-rating label:hover~label {
        color: #fb923c;
    }
</style>

<script>
    function changeMainImage(src) {
        document.getElementById('mainProductImage').src = src;

        // Update active state on thumbnails
        document.querySelectorAll('.grid-cols-4 img').forEach(img => {
            img.classList.remove('ring-2', 'ring-accent');
            if (img.src === src) {
                img.classList.add('ring-2', 'ring-accent');
            }
        });
    }

    let selectedUnitPrice = <?php echo json_encode($selectedPrice); ?>;
    let selectedOriginalPrice = <?php echo json_encode($productOriginalPrice); ?>;
    const defaultProductImageUrl = <?php echo json_encode($mainImageUrl); ?>;

    function formatMoney(amount) {
        return new Intl.NumberFormat('en-IN', {
            style: 'currency',
            currency: 'INR'
        }).format(Number(amount) || 0);
    }

    function increaseQty(max) {
        const qtyInput = document.getElementById('quantity');
        let val = parseInt(qtyInput.value);
        if (val < max) {
            qtyInput.value = val + 1;
            updatePriceSummary();
        }
    }

    function decreaseQty() {
        const qtyInput = document.getElementById('quantity');
        let val = parseInt(qtyInput.value);
        if (val > 1) {
            qtyInput.value = val - 1;
            updatePriceSummary();
        }
    }

    function updatePrice(input) {
        const price = Number(input.dataset.price || selectedUnitPrice);
        const stock = Number(input.dataset.stock || 0);
        selectedUnitPrice = price;
        selectedOriginalPrice = Number(input.dataset.originalPrice || 0);

        const qtyInput = document.getElementById('quantity');
        qtyInput.max = stock;

        if (parseInt(qtyInput.value) > stock || parseInt(qtyInput.value) < 1) {
            qtyInput.value = 1;
        }

        const increaseBtn = qtyInput.nextElementSibling;
        increaseBtn.onclick = function() {
            increaseQty(stock);
        };

        changeMainImage(input.dataset.imageUrl || defaultProductImageUrl);

        updatePriceSummary(input);
    }

    function selectFlavor(button) {
        const wasActive = button.classList.contains('border-accent');
        const defaultFlavorButton = document.querySelector('.flavor-filter[data-flavor="__without_flavour__"]');
        const flavorFilter = wasActive && defaultFlavorButton ? '__without_flavour__' : (wasActive ? null : (button.dataset.flavor || ''));
        document.querySelectorAll('.flavor-filter').forEach(filter => {
            const active = filter.dataset.flavor === flavorFilter;
            filter.classList.toggle('border-accent', active);
            filter.classList.toggle('bg-accent/5', active);
            filter.classList.toggle('text-gray-800', active);
            filter.classList.toggle('border-gray-300', !active);
            filter.classList.toggle('bg-white', !active);
        });

        let firstAvailable = null;
        document.querySelectorAll('label[data-flavor]').forEach(label => {
            const input = label.querySelector('input[name="variant_id"]');
            const matches = flavorFilter === null ||
                (flavorFilter === '__without_flavour__' && label.dataset.flavor === '') ||
                label.dataset.flavor === flavorFilter;
            label.classList.toggle('hidden', !matches);
            if (!matches && input) {
                input.checked = false;
            }
            if (matches && input && !input.disabled && firstAvailable === null) {
                firstAvailable = input;
            }
        });

        if (firstAvailable) {
            firstAvailable.checked = true;
            updatePrice(firstAvailable);
        }
    }

    function updatePriceSummary(selectedInput = null) {
        const qtyInput = document.getElementById('quantity');
        if (!qtyInput) return;

        const activeWeight = selectedInput || document.querySelector('input[name="variant_id"]:checked');
        const rawQuantity = Math.max(1, parseInt(qtyInput.value) || 1);
        const stock = activeWeight ? Number(activeWeight.dataset.stock || 0) : Number(qtyInput.max || 0);
        const quantity = stock > 0 ? Math.min(rawQuantity, stock) : rawQuantity;
        const weight = activeWeight ? activeWeight.dataset.weight : 'Default';
        const flavor = activeWeight && activeWeight.dataset.flavor ? activeWeight.dataset.flavor : 'No Flavour';
        const unitPrice = activeWeight ? Number(activeWeight.dataset.price || selectedUnitPrice) : selectedUnitPrice;
        const originalPrice = activeWeight ? Number(activeWeight.dataset.originalPrice || 0) : selectedOriginalPrice;
        const total = unitPrice * quantity;

        selectedUnitPrice = unitPrice;
        selectedOriginalPrice = originalPrice;
        qtyInput.value = quantity;

        const displayPrice = document.getElementById('displayProductPrice');
        const summaryFlavor = document.getElementById('summaryFlavor');
        const summaryWeight = document.getElementById('summaryWeight');
        const summaryUnitPrice = document.getElementById('summaryUnitPrice');
        const summaryQuantity = document.getElementById('summaryQuantity');
        const summaryTotal = document.getElementById('summaryTotal');
        const summaryStock = document.getElementById('summaryStock');
        const displayOriginalPrice = document.getElementById('displayOriginalPrice');
        const displayDiscountBadge = document.getElementById('displayDiscountBadge');
        const summaryOriginalPriceRow = document.getElementById('summaryOriginalPriceRow');
        const summaryOriginalPrice = document.getElementById('summaryOriginalPrice');
        const summarySavingsRow = document.getElementById('summarySavingsRow');
        const summarySavings = document.getElementById('summarySavings');
        const hasSavings = originalPrice > unitPrice;

        if (displayPrice) displayPrice.textContent = formatMoney(unitPrice);
        if (summaryFlavor) summaryFlavor.textContent = flavor;
        if (summaryWeight) summaryWeight.textContent = weight;
        if (summaryUnitPrice) summaryUnitPrice.textContent = formatMoney(unitPrice);
        if (summaryOriginalPrice) summaryOriginalPrice.textContent = formatMoney(originalPrice);
        if (summaryQuantity) summaryQuantity.textContent = quantity;
        if (summaryTotal) summaryTotal.textContent = formatMoney(total);
        if (displayOriginalPrice) displayOriginalPrice.classList.toggle('hidden', !hasSavings);
        if (displayOriginalPrice && hasSavings) displayOriginalPrice.textContent = formatMoney(originalPrice);
        if (displayDiscountBadge) {
            displayDiscountBadge.classList.toggle('hidden', !hasSavings);
            if (hasSavings) {
                displayDiscountBadge.textContent = `${Math.round(((originalPrice - unitPrice) / originalPrice) * 100)}% OFF`;
            }
        }
        if (summaryOriginalPriceRow) {
            summaryOriginalPriceRow.classList.toggle('hidden', !hasSavings);
            summaryOriginalPriceRow.classList.toggle('flex', hasSavings);
        }
        if (summarySavingsRow) {
            summarySavingsRow.classList.toggle('hidden', !hasSavings);
            summarySavingsRow.classList.toggle('flex', hasSavings);
        }
        if (summarySavings && hasSavings) {
            summarySavings.textContent = formatMoney((originalPrice - unitPrice) * quantity);
        }

        if (summaryStock) {
            summaryStock.textContent = stock > 0 ? 'In Stock' : 'Out of Stock';
            summaryStock.classList.toggle('text-green-600', stock > 0);
            summaryStock.classList.toggle('text-red-500', stock <= 0);
        }
    }

    updatePriceSummary();

    function toggleReviewForm() {
        const panel = document.getElementById('reviewFormPanel');
        if (!panel) return;

        panel.classList.toggle('hidden');
        if (!panel.classList.contains('hidden')) {
            panel.scrollIntoView({
                behavior: 'smooth',
                block: 'nearest'
            });
        }
    }
</script>

<?php require_once 'includes/footer.php'; ?>