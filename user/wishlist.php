<?php

/**
 * User Wishlist Page
 */

require_once __DIR__ . '/../includes/header.php';
requireLogin();

$pageTitle = 'My Wishlist';

$userId = $_SESSION['user_id'];

// Handle remove from wishlist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove') {
    $productId = (int)$_POST['product_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$userId, $productId]);
        setFlash('Product removed from wishlist', 'success');
    } catch (PDOException $e) {
        setFlash('Error removing from wishlist', 'danger');
    }
}

// Handle move to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'move_to_cart') {
    $productId = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'] ?? 1;

    // Add to cart session
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Check if product already in cart
    $found = false;
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['product_id'] == $productId) {
            $item['quantity'] += $quantity;
            $found = true;
            break;
        }
    }

    if (!$found) {
        $_SESSION['cart'][] = [
            'product_id' => $productId,
            'quantity' => $quantity
        ];
    }

    // Remove from wishlist
    try {
        $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$userId, $productId]);
    } catch (PDOException $e) {
        // Silent fail
    }

    setFlash('Product moved to cart', 'success');
}

// Fetch wishlist items
$wishlistItems = [];
try {
    $stmt = $pdo->prepare("
        SELECT p.*, c.name as category_name 
        FROM wishlist w 
        JOIN products p ON w.product_id = p.id 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE w.user_id = ? 
        ORDER BY w.created_at DESC
    ");
    $stmt->execute([$userId]);
    $wishlistItems = $stmt->fetchAll();
} catch (PDOException $e) {
    setFlash('Error loading wishlist', 'danger');
}
?>

<!-- Breadcrumb -->
<section class="py-12">
    <div class="max-w-[1920px] mx-auto px-4 sm:px-6 lg:px-12">
        <nav class="text-sm text-gray-600 mb-2">
            <ol class="flex items-center space-x-2">
                <li><a href="<?php echo BASE_URL; ?>" class="hover:text-primary">Home</a></li>
                <li><i class="fas fa-chevron-right text-xs"></i></li>
                <li><a href="<?php echo BASE_URL; ?>user/profile.php" class="hover:text-primary">My Account</a></li>
                <li><i class="fas fa-chevron-right text-xs"></i></li>
                <li class="text-primary font-medium">Wishlist</li>
            </ol>
        </nav>
        <h1 class="text-2xl md:text-3xl font-bold text-gray-900">My Wishlist</h1>
    </div>
</section>

<!-- Wishlist Section -->
<section>
    <div class="max-w-[1920px] mx-auto px-4 sm:px-6 lg:px-12">

        <?php if (empty($wishlistItems)): ?>
            <div class="p-12 text-center">
                <div class="mb-4">
                    <i class="fas fa-heart text-6xl text-gray-300"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 mb-2">Your wishlist is empty</h2>
                <p class="text-gray-600 mb-6">Start adding products you love to your wishlist</p>
                <a href="<?php echo BASE_URL; ?>shop.php" class="inline-flex items-center bg-accent hover:bg-accent-700/90 text-white text-sm font-semibold py-4 px-6 rounded-full transition hover:shadow-sm">
                    <i class="fas fa-shopping-bag mr-2"></i>Continue Shopping
                </a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3 lg:gap-6">
                <?php foreach ($wishlistItems as $product): ?>
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

            <!-- Continue Shopping -->
            <div class="mt-12 flex justify-center">
                <a href="<?php echo BASE_URL; ?>shop.php"
                    class="group inline-flex items-center gap-2 border-2 border-primary text-primary 
        hover:bg-primary hover:text-white font-medium py-3 px-6 rounded-full 
        transition whitespace-nowrap">

                    <i class="fas fa-arrow-left transition-transform duration-300 group-hover:-translate-x-1"></i>
                    Continue Shopping

                </a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>