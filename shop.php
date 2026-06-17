<?php

/**
 * Shop Page
 * Product Listing with Filters
 */

$pageTitle = 'Shop';
require_once 'includes/header.php';

// Get filter parameters
$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$minPrice = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$maxPrice = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 0;

// Build query
$selectClause = "SELECT p.*, c.name as category_name, pv.variant_min_price, pv.variant_original_price, pv.variant_stock, pv.variant_weight, pv.variant_flavour";
$fromClause = " FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN (
        SELECT 
            product_id,
            MIN(price) AS variant_min_price,
            MAX(original_price) AS variant_original_price,
            SUM(stock) AS variant_stock,
            SUBSTRING_INDEX(GROUP_CONCAT(weight ORDER BY sort_order ASC SEPARATOR '||'), '||', 1) AS variant_weight,
            SUBSTRING_INDEX(GROUP_CONCAT(NULLIF(flavour, '') ORDER BY sort_order ASC SEPARATOR '||'), '||', 1) AS variant_flavour
        FROM product_variants
        WHERE status = 1
        GROUP BY product_id
    ) pv ON pv.product_id = p.id";
$where = ["p.status = 1"];
$params = [];

// Category filter
if ($categoryId > 0) {
    $where[] = "p.category_id = ?";
    $params[] = $categoryId;
}

// Search filter
if (!empty($searchQuery)) {
    $where[] = "(p.name LIKE ? OR p.description LIKE ? OR c.name LIKE ? OR EXISTS (
        SELECT 1 FROM product_variants sv
        WHERE sv.product_id = p.id
          AND sv.status = 1
          AND (sv.flavour LIKE ? OR sv.weight LIKE ? OR sv.sku LIKE ?)
    ))";
    $likeSearch = "%$searchQuery%";
    array_push($params, $likeSearch, $likeSearch, $likeSearch, $likeSearch, $likeSearch, $likeSearch);
}

// Price filter
if ($minPrice > 0 || $maxPrice > 0) {
    $priceParts = [];
    $productPriceParts = [];
    if ($minPrice > 0) {
        $priceParts[] = "pv2.price >= ?";
        $productPriceParts[] = "p.price >= ?";
    }
    if ($maxPrice > 0) {
        $priceParts[] = "pv2.price <= ?";
        $productPriceParts[] = "p.price <= ?";
    }

    $where[] = "(
        EXISTS (
            SELECT 1 FROM product_variants pv2
            WHERE pv2.product_id = p.id AND pv2.status = 1 AND " . implode(' AND ', $priceParts) . "
        )
        OR (
            NOT EXISTS (SELECT 1 FROM product_variants pv3 WHERE pv3.product_id = p.id AND pv3.status = 1)
            AND " . implode(' AND ', $productPriceParts) . "
        )
    )";
    if ($minPrice > 0) {
        $params[] = $minPrice;
    }
    if ($maxPrice > 0) {
        $params[] = $maxPrice;
    }
    if ($minPrice > 0) {
        $params[] = $minPrice;
    }
    if ($maxPrice > 0) {
        $params[] = $maxPrice;
    }
}

$whereClause = " WHERE " . implode(" AND ", $where);
$query = $selectClause . $fromClause . $whereClause;

// Sorting
switch ($sortBy) {
    case 'price_low':
        $query .= " ORDER BY COALESCE(pv.variant_min_price, p.price) ASC";
        break;
    case 'price_high':
        $query .= " ORDER BY COALESCE(pv.variant_min_price, p.price) DESC";
        break;
    case 'name_asc':
        $query .= " ORDER BY p.name ASC";
        break;
    case 'name_desc':
        $query .= " ORDER BY p.name DESC";
        break;
    default: // newest
        $query .= " ORDER BY p.created_at DESC";
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 12;
$offset = ($page - 1) * $perPage;

// Count total products for pagination
$countQuery = "SELECT COUNT(*) as total" . $fromClause . $whereClause;
$countStmt = $pdo->prepare($countQuery);
$countStmt->execute($params);
$totalProducts = $countStmt->fetch()['total'];
$totalPages = ceil($totalProducts / $perPage);

// Add limit for pagination
$query .= " LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;

// Fetch products
$products = [];
try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    // Silent fail
}

function shopQuery(array $overrides = [])
{
    $query = array_merge($_GET, $overrides);
    foreach ($query as $key => $value) {
        if ($value === '' || $value === null || $value === 0 || $value === '0') {
            unset($query[$key]);
        }
    }
    unset($query['page'], $query['flavour'], $query['weight']);
    return http_build_query($query);
}

function renderShopHiddenInputs($categoryId, $searchQuery, $minPrice, $maxPrice, $sortBy, $includeSearch = true, $includePrice = true)
{
    if ($categoryId > 0) {
        echo '<input type="hidden" name="category" value="' . (int)$categoryId . '">';
    }
    if ($includeSearch && $searchQuery !== '') {
        echo '<input type="hidden" name="search" value="' . e($searchQuery) . '">';
    }
    if ($includePrice && $minPrice > 0) {
        echo '<input type="hidden" name="min_price" value="' . e($minPrice) . '">';
    }
    if ($includePrice && $maxPrice > 0) {
        echo '<input type="hidden" name="max_price" value="' . e($maxPrice) . '">';
    }
    if ($sortBy !== 'newest') {
        echo '<input type="hidden" name="sort" value="' . e($sortBy) . '">';
    }
}

// Fetch categories for filter
$categories = [];
try {
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    // Silent fail
}

// Get selected category name
$selectedCategory = null;
if ($categoryId > 0) {
    foreach ($categories as $cat) {
        if ($cat['id'] == $categoryId) {
            $selectedCategory = $cat;
            break;
        }
    }
}

$paginationParams = $_GET;
unset($paginationParams['flavour'], $paginationParams['weight']);
?>

<!-- Page Header -->
<!-- <section class="bg-gradient-to-r from-pink-50 to-purple-50 mt-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
    </div>
</section> -->

<!-- Shop Content -->
<section class="py-12">
    <div class="max-w-[1920px] mx-auto px-4 sm:px-6 lg:px-12">

        <style>
            @media (max-width: 1023px) {
                #mobileFilterDrawer {
                    transform: translateX(100%);
                    transition: transform 250ms ease;
                }

                #mobileFilterDrawer.is-open {
                    transform: translateX(0);
                }
            }
        </style>

        <!-- Mobile Filter Drawer -->
        <div id="mobileFilterOverlay" class="fixed inset-0 bg-black/40 z-40 hidden lg:hidden"></div>
        <aside id="mobileFilterDrawer" class="fixed top-0 right-0 h-full w-[85%] max-w-sm bg-white z-50 lg:hidden shadow-2xl">
            <div class="h-full flex flex-col">
                <div class="flex items-center justify-between px-4 p-6 border-b">
                    <h5 class="text-lg font-bold text-gray-700">
                        <i class="fas fa-filter mr-2 text-primary"></i>Filters
                    </h5>
                    <button type="button" id="closeMobileFilterBtn" class="rounded-lg text-gray-500 hover:text-gray-700">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <div class="p-4 overflow-y-auto">
                    <!-- Search -->
                    <form action="<?php echo BASE_URL; ?>shop.php" method="GET" class="flex items-center mb-6 w-full">
                        <?php renderShopHiddenInputs($categoryId, $searchQuery, $minPrice, $maxPrice, $sortBy, false, true); ?>

                        <div class="
        flex items-center w-full 
        bg-gray-50 border 
        rounded-lg p-1
        focus-within:bg-white
        focus-within:border-primary
        transition-all duration-300
    ">

                            <!-- Input -->
                            <input
                                type="search"
                                name="search"
                                placeholder="Search makhana, spices..."
                                value="<?php echo isset($_GET['search']) ? e($_GET['search']) : ''; ?>"
                                class="flex-1 p-2 
                                        bg-transparent text-sm text-gray-700
                                        placeholder-gray-400
                                        outline-none
                                        min-w-0 ">

                            <!-- Button -->
                            <button
                                type="submit"
                                class="
            flex items-center justify-center
            bg-primary text-white rounded-lg
            hover:bg-primary-600 px-3 py-2.5
            shadow-sm hover:shadow-md
            transition-all duration-200
        ">
                                <i data-lucide="search" class="w-4 h-4"></i>
                            </button>

                        </div>

                    </form>

                    <!-- Category Filter -->
                    <div class="mb-6">
                        <h6 class="font-semibold mb-3 text-gray-700">Categories</h6>
                        <div class="space-y-2">
                            <a href="<?php echo BASE_URL; ?>shop.php?<?php echo shopQuery(['category' => 0]); ?>"
                                class="block px-5 py-3 rounded-lg text-sm text-gray-600 <?php echo $categoryId == 0 ? 'bg-primary-100 font-medium' : 'hover:bg-gray-50'; ?>">
                                <i class="fas fa-shop text-primary-600 mr-4"></i> All Categories
                            </a>
                            <?php foreach ($categories as $category): ?>
                                <a href="<?php echo BASE_URL; ?>shop.php?<?php echo shopQuery(['category' => $category['id']]); ?>"
                                    class="block py-2.5 px-3 rounded-lg text-sm text-gray-600 <?php echo $categoryId == $category['id'] ? 'bg-primary-100 font-medium' : 'hover:bg-gray-50'; ?>">
                                    <?php if ($category['image']): ?>
                                        <img src="<?php echo getImageUrl($category['image'], 'categories'); ?>" class="w-8 mr-2 object-contain inline">
                                    <?php endif; ?>
                                    <?php echo e($category['name']); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Price Filter -->
                    <form action="<?php echo BASE_URL; ?>shop.php" method="GET">
                        <?php renderShopHiddenInputs($categoryId, $searchQuery, 0, 0, $sortBy, true, false); ?>

                        <div class="mb-6">
                            <h6 class="font-semibold mb-3 text-gray-700">Price Range</h6>
                            <div class="grid grid-cols-2 gap-3">
                                <input type="number" name="min_price" placeholder="Min"
                                    value="<?php echo $minPrice > 0 ? $minPrice : ''; ?>"
                                    class="w-full px-3 py-2 border rounded-lg text-sm outline-none focus:border-primary">
                                <input type="number" name="max_price" placeholder="Max"
                                    value="<?php echo $maxPrice > 0 ? $maxPrice : ''; ?>"
                                    class="w-full px-3 py-2 border rounded-lg text-sm outline-none focus:border-primary">
                            </div>
                        </div>

                        <button type="submit" class="w-full bg-primary-600 hover:bg-accent-700 text-white font-medium py-2.5 px-4 rounded-lg transition">
                            <i class="fas fa-filter mr-2"></i>Apply Filters
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <div class="grid grid-cols-1 lg:grid-cols-4 md:gap-8">
            <!-- Sidebar Filters -->
            <div class="lg:col-span-1">
                <div>
                    <nav class="text-sm text-gray-600 mb-2">
                        <ol class="flex items-center space-x-2">
                            <li><a href="<?php echo BASE_URL; ?>" class="hover:text-primary">Home</a></li>
                            <li><i class="fas fa-chevron-right text-xs"></i></li>
                            <li class="text-primary font-medium">Shop</li>
                        </ol>
                    </nav>
                    <h1 class="text-2xl md:text-3xl font-bold text-gray-900">
                        <?php echo $selectedCategory ? e($selectedCategory['name']) : 'All Products'; ?>
                    </h1>
                    <p class="hidden md:block text-muted mb-0">Showing <?php echo count($products); ?> of <?php echo $totalProducts; ?> products</p>
                </div>

                <div class="hidden md:block bg-white sticky p-6 border rounded-lg mt-6 top-24">
                    <h5 class="text-lg font-bold mb-4 text-gray-600">
                        <i class="fas fa-filter mr-2 text-primary"></i> Filters
                    </h5>

                    <!-- Search -->
                    <form action="<?php echo BASE_URL; ?>shop.php" method="GET" class="hidden md:flex items-center mb-6 w-full">
                        <?php renderShopHiddenInputs($categoryId, $searchQuery, $minPrice, $maxPrice, $sortBy, false, true); ?>

                        <div class="flex items-center w-full bg-gray-50 border rounded-lg p-1 focus-within:bg-white focus-within:border-primary transition-all duration-300">

                            <!-- Input -->
                            <input
                                type="search"
                                name="search"
                                placeholder="Search makhana, spices..."
                                value="<?php echo isset($_GET['search']) ? e($_GET['search']) : ''; ?>"
                                class="flex-1 p-2 bg-transparent text-sm text-gray-700 placeholder-gray-400 outline-none min-w-0">

                            <!-- Button -->
                            <button
                                type="submit"
                                class="
            flex items-center justify-center
            bg-primary text-white rounded-lg
            hover:bg-primary-600 px-3 py-2.5
            shadow-sm hover:shadow-md
            transition-all duration-200">
                                <i data-lucide="search" class="w-4 h-4"></i>
                            </button>
                        </div>

                    </form>

                    <!-- Category Filter -->
                    <div class="mb-6">
                        <h6 class="font-semibold mb-3 text-gray-700">Categories</h6>
                        <div class="space-y-2">
                            <a href="<?php echo BASE_URL; ?>shop.php?<?php echo shopQuery(['category' => 0]); ?>"
                                class="block px-5 py-3 rounded-lg text-sm text-gray-600 <?php echo $categoryId == 0 ? 'bg-primary-100 font-medium' : 'hover:bg-gray-50'; ?>">
                                <i class="fas fa-shopping-bag text-primary-600 mr-4"></i> All Categories
                            </a>
                            <?php foreach ($categories as $category): ?>
                                <a href="<?php echo BASE_URL; ?>shop.php?<?php echo shopQuery(['category' => $category['id']]); ?>"
                                    class="block py-2.5 px-3 rounded-lg text-sm text-gray-600 <?php echo $categoryId == $category['id'] ? 'bg-primary-100 font-medium' : 'hover:bg-gray-50'; ?>">
                                    <?php if ($category['image']): ?>
                                        <img src="<?php echo getImageUrl($category['image'], 'categories'); ?>" class="w-8 mr-2 object-contain inline">
                                    <?php endif; ?>
                                    <?php echo e($category['name']); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Price Filter -->
                    <form action="<?php echo BASE_URL; ?>shop.php" method="GET">
                        <?php renderShopHiddenInputs($categoryId, $searchQuery, 0, 0, $sortBy, true, false); ?>

                        <div class="mb-6">
                            <h6 class="font-semibold mb-3 text-gray-700">Price Range</h6>
                            <div class="grid grid-cols-2 gap-3">
                                <input type="number" name="min_price" placeholder="Min"
                                    value="<?php echo $minPrice > 0 ? $minPrice : ''; ?>"
                                    class="w-full px-3 py-2 border rounded-lg text-sm outline-none focus:border-primary">
                                <input type="number" name="max_price" placeholder="Max"
                                    value="<?php echo $maxPrice > 0 ? $maxPrice : ''; ?>"
                                    class="w-full px-3 py-2 border rounded-lg text-sm outline-none focus:border-primary">
                            </div>
                        </div>

                        <button type="submit" class="w-full bg-primary-600 hover:bg-primary-700 text-white font-medium py-2.5 px-4 rounded-lg transition">
                            <i class="fas fa-filter mr-2"></i>Apply Filters
                        </button>
                    </form>
                </div>
            </div>

            <!-- Product Grid -->
            <div class="lg:col-span-3 md:mt-8">
                <!-- Sort and Results -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-11 gap-4">
                    <p class="text-gray-600">
                        <?php if (!empty($searchQuery)): ?>
                            Search results for "<strong class="text-gray-900"><?php echo e($searchQuery); ?></strong>"
                        <?php else: ?>
                            Showing <?php echo count($products); ?> of <?php echo $totalProducts; ?> products
                        <?php endif; ?>
                    </p>

                    <div class="w-full md:w-auto flex justify-between items-center gap-4">
                        <!-- Mobile Filter Toggle -->
                        <button type="button" id="openMobileFilterBtn" class="md:hidden inline-flex items-center gap-2 bg-primary text-white px-4 py-2 rounded-lg text-sm">
                            <i class="fas fa-filter"></i>
                            Filter
                        </button>

                        <form action="<?php echo BASE_URL; ?>shop.php" method="GET" class="flex items-center space-x-2">
                            <?php renderShopHiddenInputs($categoryId, $searchQuery, $minPrice, $maxPrice, 'newest', true, true); ?>

                            <label class="text-sm text-gray-600 hidden md:block">Sort by:</label>
                            <select name="sort" onchange="this.form.submit()"
                                class="px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                                <option value="newest" <?php echo $sortBy == 'newest' ? 'selected' : ''; ?>>Newest First</option>
                                <option value="price_low" <?php echo $sortBy == 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                                <option value="price_high" <?php echo $sortBy == 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                                <option value="name_asc" <?php echo $sortBy == 'name_asc' ? 'selected' : ''; ?>>Name: A to Z</option>
                                <option value="name_desc" <?php echo $sortBy == 'name_desc' ? 'selected' : ''; ?>>Name: Z to A</option>
                            </select>
                        </form>
                    </div>
                </div>

                <!-- Products Grid -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 lg:gap-6">
                    <?php foreach ($products as $product): ?>
                        <?php
                        $cardPrice = !empty($product['variant_min_price']) ? (float)$product['variant_min_price'] : (float)$product['price'];
                        $cardOriginalPrice = !empty($product['variant_original_price']) ? (float)$product['variant_original_price'] : (float)($product['original_price'] ?? 0);
                        $cardStock = $product['variant_stock'] !== null ? (int)$product['variant_stock'] : (int)$product['stock'];
                        $displayWeight = !empty($product['variant_weight']) ? trim((string)$product['variant_weight']) : trim((string)($product['weight'] ?? ''));
                        $displayFlavour = trim((string)($product['variant_flavour'] ?? ''));
                        ?>
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
                                        class="h-28 sm:h-40 md:h-44 object-contain drop-shadow-[0_80px_40px_rgba(0,0,0,0.14)] group-hover:scale-105 transition duration-300">
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
                                <?php if ($displayWeight !== '' || $displayFlavour !== ''): ?>
                                    <p class="text-gray-500 text-xs mt-1">
                                        <i class="fas fa-weight-hanging mr-1 text-accent"></i><?php echo $displayFlavour !== '' ? e($displayFlavour) . ' - ' : ''; ?><?php echo e($displayWeight); ?>
                                    </p>
                                <?php endif; ?>

                                <!-- PRICE -->
                                <div class="w-full flex justify-between items-center">

                                    <!-- LEFT: PRICE -->
                                    <div class="flex items-baseline flex-wrap space-x-1">

                                        <span class="text-green-600 font-semibold text-base sm:text-lg md:text-xl">
                                            <?php echo formatCurrency($cardPrice); ?>
                                        </span>

                                        <?php if ($cardOriginalPrice > $cardPrice): ?>
                                            <span class="text-gray-400 line-through text-xs sm:text-sm">
                                                <?php echo formatCurrency($cardOriginalPrice); ?>
                                            </span>
                                        <?php endif; ?>

                                    </div>

                                    <!-- RIGHT: DISCOUNT -->
                                    <?php if ($cardOriginalPrice > $cardPrice): ?>
                                        <span class="bg-green-100 text-green-600 text-[8px] md:text-xs text-center font-semibold px-2 py-1 rounded-full text-nowrap">
                                            <?php
                                            $discount = round((($cardOriginalPrice - $cardPrice) / $cardOriginalPrice) * 100);
                                            echo $discount . '% OFF';
                                            ?>
                                        </span>
                                    <?php endif; ?>

                                </div>

                                <!-- BUTTON -->
                                <?php if ($cardStock > 0): ?>
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
                                <?php elseif ($cardStock <= 0): ?>
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

                <!-- No Results -->
                <?php if (empty($products)): ?>
                    <div class="text-center py-12">
                        <i class="fas fa-search text-5xl text-gray-300 mb-4"></i>
                        <h4 class="text-xl font-semibold text-gray-700 mb-2">No products found</h4>
                        <p class="text-gray-500 mb-4">Try adjusting your filters or search query</p>
                        <a href="<?php echo BASE_URL; ?>shop.php" class="inline-flex items-center bg-primary hover:bg-primary-700 text-white font-medium py-2 px-6 rounded-lg transition">
                            Clear Filters
                        </a>
                    </div>
                <?php endif; ?>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav class="mt-8">
                        <ul class="flex justify-center items-center space-x-2">
                            <?php if ($page > 1): ?>
                                <li>
                                    <a href="<?php echo BASE_URL; ?>shop.php?<?php echo http_build_query(array_merge($paginationParams, ['page' => $page - 1])); ?>"
                                        class="w-10 h-10 rounded-lg bg-white border border-gray-300 flex items-center justify-center text-gray-600 hover:bg-gray-50 hover:text-primary transition">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li>
                                    <a href="<?php echo BASE_URL; ?>shop.php?<?php echo http_build_query(array_merge($paginationParams, ['page' => $i])); ?>"
                                        class="w-10 h-10 rounded-lg flex items-center justify-center transition <?php echo $i == $page ? 'bg-primary text-white' : 'bg-white border border-gray-300 text-gray-600 hover:bg-gray-50 hover:text-primary'; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <li>
                                    <a href="<?php echo BASE_URL; ?>shop.php?<?php echo http_build_query(array_merge($paginationParams, ['page' => $page + 1])); ?>"
                                        class="w-10 h-10 rounded-lg bg-white border border-gray-300 flex items-center justify-center text-gray-600 hover:bg-gray-50 hover:text-primary transition">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<script>
    (function() {
        var openBtn = document.getElementById('openMobileFilterBtn');
        var closeBtn = document.getElementById('closeMobileFilterBtn');
        var overlay = document.getElementById('mobileFilterOverlay');
        var drawer = document.getElementById('mobileFilterDrawer');

        if (!openBtn || !closeBtn || !overlay || !drawer) return;

        function openDrawer() {
            overlay.classList.remove('hidden');
            drawer.classList.add('is-open');
            document.body.style.overflow = 'hidden';
        }

        function closeDrawer() {
            drawer.classList.remove('is-open');
            overlay.classList.add('hidden');
            document.body.style.overflow = '';
        }

        openBtn.addEventListener('click', openDrawer);
        closeBtn.addEventListener('click', closeDrawer);
        overlay.addEventListener('click', closeDrawer);

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeDrawer();
        });
    })();
</script>

<?php require_once 'includes/footer.php'; ?>