<?php
/**
 * Razorpay Payment Verification
 * Verifies payment and creates/updates order
 * Handles both Online Payments and COD Initial Payments
 */

require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/razorpay.php';

// Require login
if (!isLoggedIn()) {
    setFlash('Please login to continue.', 'warning');
    redirect(BASE_URL . 'user/login.php');
}

// Check for required data
if (!isset($_SESSION['checkout_data']) || !isset($_SESSION['razorpay_order_id'])) {
    setFlash('Checkout data not found. Please try again.', 'danger');
    redirect(BASE_URL . 'checkout.php');
}

// Verify POST data
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlash('Invalid request method.', 'danger');
    redirect(BASE_URL . 'checkout.php');
}

$razorpayPaymentId = isset($_POST['razorpay_payment_id']) ? trim($_POST['razorpay_payment_id']) : '';
$razorpayOrderId = isset($_POST['razorpay_order_id']) ? trim($_POST['razorpay_order_id']) : '';
$razorpaySignature = isset($_POST['razorpay_signature']) ? trim($_POST['razorpay_signature']) : '';

// Validate required fields
if (empty($razorpayPaymentId) || empty($razorpayOrderId) || empty($razorpaySignature)) {
    setFlash('Payment verification failed: Missing payment data.', 'danger');
    redirect(BASE_URL . 'checkout.php');
}

// Verify signature
try {
    $isValid = verifyRazorpaySignature($razorpayOrderId, $razorpayPaymentId, $razorpaySignature);
    
    if (!$isValid) {
        setFlash('Payment verification failed: Invalid signature.', 'danger');
        error_log('Razorpay Signature Verification Failed: Order=' . $razorpayOrderId . ', Payment=' . $razorpayPaymentId);
        redirect(BASE_URL . 'checkout.php');
    }
    
    // Signature verified
    $checkoutData = $_SESSION['checkout_data'];
    $userId = $_SESSION['user_id'];
    $cart = $_SESSION['cart'];
    $paymentMethod = $checkoutData['payment_method'] ?? 'online';
    $isCodInitialPayment = !empty($_SESSION['is_cod_initial_payment']);
    
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        if ($isCodInitialPayment) {
            // COD Initial Payment - Create order with initial payment marked as paid
            $orderQuery = "INSERT INTO orders (user_id, total_amount, discount_amount, coupon_code, status, 
                           payment_method, payment_status, initial_payment_amount, remaining_payment_amount,
                           initial_payment_status, razorpay_payment_id, razorpay_order_id,
                           name, email, mobile, address, city, state, pincode, created_at) 
                           VALUES (?, ?, ?, ?, 'pending', 'cod', 'pending', ?, ?, 'paid', ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $orderStmt = $pdo->prepare($orderQuery);
            $orderStmt->execute([
                $userId,
                $checkoutData['total'],
                $checkoutData['discount'],
                $checkoutData['coupon_code'],
                $checkoutData['initial_payment_amount'],
                $checkoutData['remaining_payment_amount'],
                $razorpayPaymentId,
                $razorpayOrderId,
                $checkoutData['name'],
                $checkoutData['email'],
                $checkoutData['mobile'],
                $checkoutData['address'],
                $checkoutData['city'],
                $checkoutData['state'],
                $checkoutData['pincode']
            ]);
        } else {
            // Online Payment - Create order with full payment
            $orderQuery = "INSERT INTO orders (user_id, total_amount, discount_amount, coupon_code, status, 
                           payment_method, payment_status, razorpay_payment_id, razorpay_order_id,
                           name, email, mobile, address, city, state, pincode, created_at) 
                           VALUES (?, ?, ?, ?, 'pending', 'online', 'paid', ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $orderStmt = $pdo->prepare($orderQuery);
            $orderStmt->execute([
                $userId,
                $checkoutData['total'],
                $checkoutData['discount'],
                $checkoutData['coupon_code'],
                $razorpayPaymentId,
                $razorpayOrderId,
                $checkoutData['name'],
                $checkoutData['email'],
                $checkoutData['mobile'],
                $checkoutData['address'],
                $checkoutData['city'],
                $checkoutData['state'],
                $checkoutData['pincode']
            ]);
        }
        
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
        
        // Clear cart and checkout data
        unset($_SESSION['cart']);
        unset($_SESSION['coupon']);
        unset($_SESSION['checkout_data']);
        unset($_SESSION['razorpay_order_id']);
        unset($_SESSION['is_cod_initial_payment']);
        
        // Store order ID for success page
        $_SESSION['last_order_id'] = $orderId;
        $_SESSION['payment_method_used'] = $paymentMethod;
        
        // Redirect to success page
        if ($isCodInitialPayment) {
            setFlash('Initial payment received! Your order is confirmed. The remaining amount will be collected on delivery.', 'success');
        } else {
            setFlash('Order placed successfully! Thank you for your purchase.', 'success');
        }
        redirect(BASE_URL . 'order-success.php');
        
    } catch (Exception $e) {
        // Rollback on error
        $pdo->rollBack();
        error_log('Order Creation Error: ' . $e->getMessage());
        setFlash('Error creating order: ' . $e->getMessage(), 'danger');
        redirect(BASE_URL . 'checkout.php');
    }
    
} catch (Exception $e) {
    error_log('Razorpay Verification Error: ' . $e->getMessage());
    setFlash('Payment verification error: ' . $e->getMessage(), 'danger');
    redirect(BASE_URL . 'checkout.php');
}
