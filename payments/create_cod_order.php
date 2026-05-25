<?php
/**
 * Create COD Order
 * Creates order for Cash on Delivery option
 */

require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/shiprocket.php';

// Require login
if (!isLoggedIn()) {
    rememberRedirectAfterLogin(BASE_URL . 'checkout.php');
    setFlash('Please login to continue.', 'warning');
    redirect(BASE_URL . 'user/login.php');
}

// Check for required data
if (!isset($_SESSION['checkout_data']) || !isset($_SESSION['is_cod_order'])) {
    setFlash('Checkout data not found. Please try again.', 'danger');
    redirect(BASE_URL . 'checkout.php');
}

$checkoutData = $_SESSION['checkout_data'];
$userId = $_SESSION['user_id'];
$cart = $_SESSION['cart'];

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Insert order with COD payment method
    $orderQuery = "INSERT INTO orders (user_id, total_amount, discount_amount, coupon_code, status, 
                   payment_method, payment_status, initial_payment_amount, remaining_payment_amount,
                   initial_payment_status, name, email, mobile, address, city, state, pincode, created_at) 
                   VALUES (?, ?, ?, ?, 'pending', 'cod', 'pending', ?, ?, 'pending', ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $orderStmt = $pdo->prepare($orderQuery);
    $orderStmt->execute([
        $userId,
        $checkoutData['total'],
        $checkoutData['discount'],
        $checkoutData['coupon_code'],
        $checkoutData['initial_payment_amount'],
        $checkoutData['remaining_payment_amount'],
        $checkoutData['name'],
        $checkoutData['email'],
        $checkoutData['mobile'],
        $checkoutData['address'],
        $checkoutData['city'],
        $checkoutData['state'],
        $checkoutData['pincode']
    ]);
    
    $orderId = $pdo->lastInsertId();
    
    // Insert order items and update stock
    $itemQuery = "INSERT INTO order_items (order_id, product_id, variant_id, flavour, weight, quantity, price) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $itemStmt = $pdo->prepare($itemQuery);
    
    foreach ($cart as $cartKey => $item) {
        $productId = $item['id'];
        $weightId = $item['variant_id'] ?? ($item['weight_id'] ?? null);
        $weight = $item['weight'] ?? null;
        $flavour = $item['flavour'] ?? null;
        
        // Insert order item
        $itemStmt->execute([
            $orderId,
            $productId,
            $weightId,
            $flavour,
            $weight,
            $item['quantity'],
            $item['price']
        ]);
        
        // Update stock - decrement from appropriate table
        if ($weightId) {
            // Update weight-specific stock
            $weightStockQuery = "UPDATE product_variants SET stock = stock - ? WHERE id = ? AND stock >= ?";
            $weightStockStmt = $pdo->prepare($weightStockQuery);
            $weightStockStmt->execute([$item['quantity'], $weightId, $item['quantity']]);
            
            // Check if stock update succeeded
            if ($weightStockStmt->rowCount() === 0) {
                throw new Exception('Insufficient stock for weight variant: ' . $item['name'] . ' (' . $weight . ')');
            }
        } else {
            // Update main product stock
            $stockQuery = "UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?";
            $stockStmt = $pdo->prepare($stockQuery);
            $stockStmt->execute([$item['quantity'], $productId, $item['quantity']]);
            
            // Check if stock update succeeded
            if ($stockStmt->rowCount() === 0) {
                throw new Exception('Insufficient stock for product: ' . $item['name']);
            }
        }
    }
    
    // Update coupon usage count and track per-user usage if coupon was used
    if (!empty($checkoutData['coupon_code'])) {
        // Update global usage count
        $couponUpdateQuery = "UPDATE coupons SET used_count = used_count + 1 WHERE code = ?";
        $couponStmt = $pdo->prepare($couponUpdateQuery);
        $couponStmt->execute([$checkoutData['coupon_code']]);

        // Track per-user coupon usage (get coupon_id first)
        $couponIdQuery = $pdo->prepare("SELECT id FROM coupons WHERE code = ?");
        $couponIdQuery->execute([$checkoutData['coupon_code']]);
        $couponId = $couponIdQuery->fetchColumn();

        if ($couponId) {
            $couponUseQuery = "INSERT INTO coupon_uses (coupon_id, user_id, order_id, used_at) VALUES (?, ?, ?, NOW())";
            $couponUseStmt = $pdo->prepare($couponUseQuery);
            $couponUseStmt->execute([$couponId, $userId, $orderId]);
        }
    }
    
    // Commit transaction
    $pdo->commit();

    shiprocketCreateShipmentForOrder($pdo, $orderId);
    
    // Clear cart and checkout data
    unset($_SESSION['cart']);
    unset($_SESSION['coupon']);
    unset($_SESSION['checkout_data']);
    unset($_SESSION['razorpay_order_id']);
    unset($_SESSION['is_cod_order']);
    
    // Store order ID for success page
    $_SESSION['last_order_id'] = $orderId;
    $_SESSION['is_cod_order_created'] = true;
    
    // Redirect to success page
    setFlash('COD Order created successfully! Please pay the initial amount to confirm.', 'success');
    redirect(BASE_URL . 'order-success.php');
    
} catch (Exception $e) {
    // Rollback on error
    $pdo->rollBack();
    error_log('COD Order Creation Error: ' . $e->getMessage());
    setFlash('Failed to create order: ' . $e->getMessage(), 'danger');
    redirect(BASE_URL . 'checkout.php');
}
