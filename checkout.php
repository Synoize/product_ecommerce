<?php

/**
 * Checkout Page
 * Order placement with Razorpay integration
 */

$pageTitle = 'Checkout';
require_once 'includes/header.php';
require_once 'includes/razorpay.php';

// Require login
requireLogin();

// Require cart items
if (empty($_SESSION['cart'])) {
    setFlash('Your cart is empty. Please add products first.', 'warning');
    redirect(BASE_URL . 'shop.php');
}

// Get user details
$userId = $_SESSION['user_id'];
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    $user = [];
}

// Get saved addresses
$savedAddresses = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
    $stmt->execute([$userId]);
    $savedAddresses = $stmt->fetchAll();
} catch (PDOException $e) {
    $savedAddresses = [];
}

function checkoutFindAddressById(array $addresses, int $addressId): ?array
{
    foreach ($addresses as $address) {
        if ((int)$address['id'] === $addressId) {
            return $address;
        }
    }

    return null;
}

function checkoutAddressLabel(array $address): string
{
    $defaultText = !empty($address['is_default']) ? 'Default - ' : '';
    return $defaultText . $address['name'] . ' - ' . $address['city'] . ', ' . $address['state'] . ' (' . $address['pincode'] . ')';
}

$selectedAddressId = isset($_POST['selected_address_id'])
    ? (int)$_POST['selected_address_id']
    : (isset($_GET['address_id']) ? (int)$_GET['address_id'] : 0);

$selectedAddress = $selectedAddressId > 0
    ? checkoutFindAddressById($savedAddresses, $selectedAddressId)
    : null;

if (!$selectedAddress && !empty($savedAddresses)) {
    $selectedAddress = $savedAddresses[0];
    $selectedAddressId = (int)$selectedAddress['id'];
}

// Calculate cart totals
$subtotal = 0;
$discount = 0;
$total = 0;

foreach ($_SESSION['cart'] as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

// Apply coupon discount
$couponCode = '';
if (isset($_SESSION['coupon'])) {
    $coupon = $_SESSION['coupon'];
    $couponCode = $coupon['code'];

    if ($coupon['type'] === 'percent') {
        $discount = ($subtotal * $coupon['value']) / 100;
        if ($coupon['max_discount'] > 0) {
            $discount = min($discount, $coupon['max_discount']);
        }
    } else {
        $discount = $coupon['value'];
    }

    $discount = min($discount, $subtotal);
}

$total = $subtotal - $discount;

// Payment settings
define('COD_INITIAL_PAYMENT_PERCENTAGE', 30); // 30% upfront payment for COD
$paymentMethod = isset($_POST['payment_method']) ? $_POST['payment_method'] : 'online';
$initialPaymentAmount = 0;
$remainingPaymentAmount = 0;

if ($paymentMethod === 'cod') {
    $initialPaymentAmount = round($total * COD_INITIAL_PAYMENT_PERCENTAGE / 100, 2);
    $remainingPaymentAmount = $total - $initialPaymentAmount;
}

// Handle checkout form submission (before payment)
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paymentMethod = isset($_POST['payment_method']) ? trim($_POST['payment_method']) : 'online';
    $selectedAddressId = isset($_POST['selected_address_id']) ? (int)$_POST['selected_address_id'] : 0;
    $selectedAddress = checkoutFindAddressById($savedAddresses, $selectedAddressId);

    $name = trim($selectedAddress['name'] ?? '');
    $email = trim($user['email'] ?? '');
    $mobile = trim($selectedAddress['mobile'] ?? '');
    $address = trim($selectedAddress['address'] ?? '');
    $city = trim($selectedAddress['city'] ?? '');
    $state = trim($selectedAddress['state'] ?? '');
    $pincode = trim($selectedAddress['pincode'] ?? '');

    if (!$selectedAddress) {
        $errors[] = 'Please select a saved delivery address';
    } else {
        if (empty($name)) $errors[] = 'Name is required';
        $mobile = preg_replace('/\\s+/', '', $mobile);
        if (empty($mobile) || !preg_match('/^\\+[1-9]\\d{7,14}$/', $mobile)) $errors[] = 'Valid mobile number with country code is required (e.g. +919876543210)';
        if (empty($address)) $errors[] = 'Address is required';
        if (empty($city)) $errors[] = 'City is required';
        if (empty($state)) $errors[] = 'State is required';
        if (empty($pincode) || !preg_match('/^[0-9]{6}$/', $pincode)) $errors[] = 'Valid 6-digit pincode is required';
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required';
    if (!in_array($paymentMethod, ['online', 'cod'])) $errors[] = 'Invalid payment method';

    // Store checkout data in session for after payment
    if (empty($errors)) {
        // Calculate payment amounts based on method
        if ($paymentMethod === 'cod') {
            $initialPaymentAmount = round($total * COD_INITIAL_PAYMENT_PERCENTAGE / 100, 2);
            $remainingPaymentAmount = $total - $initialPaymentAmount;
        } else {
            $initialPaymentAmount = $total;
            $remainingPaymentAmount = 0;
        }

        $_SESSION['checkout_data'] = [
            'name' => $name,
            'email' => $email,
            'mobile' => $mobile,
            'address' => $address,
            'city' => $city,
            'state' => $state,
            'pincode' => $pincode,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => $total,
            'payment_method' => $paymentMethod,
            'initial_payment_amount' => $initialPaymentAmount,
            'remaining_payment_amount' => $remainingPaymentAmount,
            'coupon_code' => $couponCode,
            'address_id' => $selectedAddressId
        ];

        if ($paymentMethod === 'cod') {
            // For COD, process the initial payment
            if ($initialPaymentAmount > 0) {
                // Create Razorpay Order for initial payment
                try {
                    $orderReceipt = 'cod_initial_' . time() . '_' . $userId;
                    $razorpayOrder = createRazorpayOrder($initialPaymentAmount, $orderReceipt);

                    if ($razorpayOrder && isset($razorpayOrder['id'])) {
                        $_SESSION['razorpay_order_id'] = $razorpayOrder['id'];
                        $_SESSION['is_cod_initial_payment'] = true;

                        // Show payment button for initial payment
                        $showPayment = true;
                    } else {
                        $errors[] = 'Failed to create payment order. Please try again.';
                    }
                } catch (Exception $e) {
                    $errors[] = 'Payment initialization failed: ' . $e->getMessage();
                    error_log('Razorpay Error (COD Initial): ' . $e->getMessage());
                }
            } else {
                // No initial payment required, create order directly
                $_SESSION['is_cod_order'] = true;
                redirect(BASE_URL . 'payments/create_cod_order.php');
            }
        } else {
            unset($_SESSION['is_cod_initial_payment']);

            // For Online Payment, create Razorpay Order for full amount
            try {
                $orderReceipt = 'order_' . time() . '_' . $userId;
                $razorpayOrder = createRazorpayOrder($total, $orderReceipt);

                if ($razorpayOrder && isset($razorpayOrder['id'])) {
                    $_SESSION['razorpay_order_id'] = $razorpayOrder['id'];

                    // Show payment button and options
                    $showPayment = true;
                } else {
                    $errors[] = 'Failed to create payment order. Please try again.';
                }
            } catch (Exception $e) {
                $errors[] = 'Payment initialization failed: ' . $e->getMessage();
                error_log('Razorpay Error: ' . $e->getMessage());
            }
        }
    }
}
?>

<!-- Page Header -->
<section class="md:py-12 pt-12 pb-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <nav class="text-sm text-gray-600 mb-2">
            <ol class="flex items-center space-x-2">
                <li><a href="<?php echo BASE_URL; ?>" class="hover:text-accent">Home</a></li>
                <li><i class="fas fa-chevron-right text-xs"></i></li>
                <li><a href="<?php echo BASE_URL; ?>cart.php" class="hover:text-accent">Cart</a></li>
                <li><i class="fas fa-chevron-right text-xs"></i></li>
                <li class="text-accent font-medium">Checkout</li>
            </ol>
        </nav>
        <h1 class="text-2xl md:text-3xl font-bold text-gray-900">Checkout</h1>
    </div>
</section>

<!-- Checkout Content -->
<section>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <?php if (!empty($errors)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
                <ul class="mb-0 list-disc list-inside">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo e($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <div class="flex flex-col-reverse md:grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Checkout Form -->
            <div class="lg:col-span-2">
                <div class="bg-white md:border rounded-2xl md:shadow-sm md:p-8">
                    <h4 class="text-xl font-bold text-gray-900 mb-6 hidden md:block">Delivery & Billing</h4>

                    <?php if (isset($showPayment) && $showPayment): ?>
                        <!-- Show Order Summary Before Payment -->
                        <div class="bg-gray-50 border rounded-xl p-6 mb-8">
                            <h5 class="font-semibold text-gray-900 mb-4">Order Details</h5>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Billing Information -->
                                <div>
                                    <h6 class="font-medium text-gray-900 mb-3">Billing Address</h6>
                                    <div class="text-sm text-gray-600 space-y-1">
                                        <p class="font-medium text-gray-900"><?php echo e($_SESSION['checkout_data']['name']); ?></p>
                                        <p><?php echo e($_SESSION['checkout_data']['address']); ?></p>
                                        <p><?php echo e($_SESSION['checkout_data']['city']); ?>, <?php echo e($_SESSION['checkout_data']['state']); ?> - <?php echo e($_SESSION['checkout_data']['pincode']); ?></p>
                                        <p><i class="fas fa-phone mr-2 text-primary-500"></i><?php echo e($_SESSION['checkout_data']['mobile']); ?></p>
                                        <p><i class="fas fa-envelope mr-2 text-primary-500"></i><?php echo e($_SESSION['checkout_data']['email']); ?></p>
                                    </div>
                                </div>

                                <!-- Payment Information -->
                                <div>
                                    <h6 class="font-medium text-gray-900 mb-3">Payment Details</h6>
                                    <div class="text-sm text-gray-600 space-y-2">
                                        <div class="flex justify-between items-center">
                                            <span>Payment Method:</span>
                                            <span class="font-medium text-gray-900">
                                                <?php echo $_SESSION['checkout_data']['payment_method'] === 'cod' ? 'Cash on Delivery (COD)' : 'Online Payment'; ?>
                                            </span>
                                        </div>

                                        <?php if ($_SESSION['checkout_data']['payment_method'] === 'cod'): ?>
                                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mt-3">
                                                <h6 class="font-medium text-gray-900 mb-2 text-xs">PAYMENT BREAKDOWN</h6>
                                                <div class="space-y-1">
                                                    <div class="flex justify-between items-center">
                                                        <span class="text-xs">Order Total:</span>
                                                        <span class="font-medium text-gray-900"><?php echo formatCurrency($_SESSION['checkout_data']['total']); ?></span>
                                                    </div>
                                                    <div class="flex justify-between items-center">
                                                        <span class="text-xs">Initial Payment (30%):</span>
                                                        <span class="font-medium text-accent"><?php echo formatCurrency($_SESSION['checkout_data']['initial_payment_amount']); ?></span>
                                                    </div>
                                                    <div class="flex justify-between items-center">
                                                        <span class="text-xs">Remaining at Delivery (70%):</span>
                                                        <span class="font-medium text-orange-600"><?php echo formatCurrency($_SESSION['checkout_data']['remaining_payment_amount']); ?></span>
                                                    </div>
                                                </div>
                                                <div class="mt-2 pt-2 border-t border-yellow-300">
                                                    <p class="text-xs text-gray-600">
                                                        <i class="fas fa-info-circle mr-1"></i>
                                                        Pay remaining amount when delivery arrives at your doorstep
                                                    </p>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <div class="bg-green-50 border border-green-200 rounded-lg p-3 mt-3">
                                                <div class="flex justify-between items-center">
                                                    <span class="text-xs font-medium text-gray-900">FULL PAYMENT AMOUNT:</span>
                                                    <span class="font-bold text-primary-500 text-lg"><?php echo formatCurrency($_SESSION['checkout_data']['total']); ?></span>
                                                </div>
                                                <div class="mt-2">
                                                    <p class="text-xs text-gray-600">
                                                        <i class="fas fa-check-circle mr-1 text-green-500"></i>
                                                        Complete payment now for instant order confirmation
                                                    </p>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($_SESSION['checkout_data']['coupon_code'])): ?>
                                            <div class="flex justify-between items-center pt-2 border-t border-gray-200">
                                                <span>Coupon Applied:</span>
                                                <span class="font-medium text-green-600"><?php echo strtoupper($_SESSION['checkout_data']['coupon_code']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Razorpay Payment Button -->
                        <div class="text-center md:text-right">

                            <button id="rzp-button"
                                class="w-full md:w-auto bg-primary-500 hover:bg-primary-600 text-white font-semibold py-4 px-8 rounded-lg transition hover:shadow-sm">
                                <!-- Right Side -->
                                <span class="text-right text-nowrap">
                                    <i class="fas fa-credit-card mr-2"></i> Pay Now 
                                    <?php if ($_SESSION['checkout_data']['payment_method'] === 'cod' && $_SESSION['checkout_data']['initial_payment_amount'] > 0): ?>
                                        <?php echo formatCurrency($_SESSION['checkout_data']['initial_payment_amount']); ?>
                                    <?php else: ?>
                                        <?php echo formatCurrency($total); ?>
                                    <?php endif; ?>
                                </span>

                            </button>

                            <div id="payment-loading" class="hidden mt-6">
                                <div class="w-6 h-6 md:w-12 md:h-12 border-2 border-primary-500 border-t-transparent rounded-full animate-spin mx-auto"></div>
                            </div>
                        </div>
                    <?php else: ?>

                        <!-- Checkout Form -->
                        <form action="<?php echo BASE_URL; ?>checkout.php" method="POST" id="checkoutForm" novalidate class="space-y-8">

                            <!-- Address Selection & Billing Details -->
                            <div class="pb-6 border-b border-gray-200">
                                <?php if (!empty($savedAddresses)): ?>
                                    <label for="selected_address_id" class="block md:text-sm font-semibold md:font-medium text-gray-700 mb-2">Select Delivery Address</label>
                                    <select name="selected_address_id" id="selected_address_id" required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-white outline-none focus:border-accent focus:ring-2 focus:ring-accent-50 transition">
                                        <?php foreach ($savedAddresses as $address): ?>
                                            <option value="<?php echo (int)$address['id']; ?>"
                                                <?php echo ($selectedAddress && (int)$selectedAddress['id'] === (int)$address['id']) ? 'selected' : ''; ?>>
                                                <?php echo e(checkoutAddressLabel($address)); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>

                                    <div id="selectedAddressPreview" class="mt-4 border border-gray-200 rounded-xl p-4 text-sm bg-gray-50">
                                        <div class="flex flex-wrap items-start justify-between gap-3">
                                            <div>
                                                <div class="flex flex-wrap items-center gap-2 mb-2">
                                                    <p id="previewName" class="font-semibold text-gray-900"><?php echo e($selectedAddress['name'] ?? ''); ?></p>
                                                    <span id="previewDefaultBadge" class="<?php echo !empty($selectedAddress['is_default']) ? 'inline-flex' : 'hidden'; ?> bg-accent text-white text-xs font-bold px-2 py-1 rounded">Default</span>
                                                </div>
                                                <p class="text-gray-600 mb-1">
                                                    <i class="fas fa-phone mr-2 text-primary-500"></i><span id="previewMobile"><?php echo e($selectedAddress['mobile'] ?? ''); ?></span>
                                                </p>
                                                <p id="previewFullAddress" class="text-gray-500">
                                                    <?php if ($selectedAddress): ?>
                                                        <?php echo e($selectedAddress['address']); ?>, <?php echo e($selectedAddress['city']); ?>, <?php echo e($selectedAddress['state']); ?> - <?php echo e($selectedAddress['pincode']); ?>
                                                    <?php endif; ?>
                                                </p>
                                                <p class="text-gray-500 mt-1">
                                                    <i class="fas fa-envelope mr-2 text-primary-500"></i><?php echo e($user['email'] ?? ''); ?>
                                                </p>
                                            </div>
                                            <a href="<?php echo BASE_URL; ?>user/addresses.php" class="inline-flex items-center border border-accent text-accent hover:bg-accent hover:text-white font-medium py-2 px-3 rounded-lg transition text-xs">
                                                <i class="fas fa-edit mr-2"></i>Manage
                                            </a>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="border border-yellow-200 bg-yellow-50 rounded-xl p-4 text-sm">
                                        <p class="font-medium text-gray-900 mb-1">No saved address found</p>
                                        <p class="text-gray-600 mb-4">Add a delivery address before continuing to payment.</p>
                                        <a href="<?php echo BASE_URL; ?>user/addresses.php" class="inline-flex items-center bg-primary-500 hover:bg-primary-600 text-white font-semibold py-3 px-5 rounded-lg transition">
                                            <i class="fas fa-plus mr-2"></i>Add Address
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Payment Method Selection -->
                            <div>
                                <h5 class="font-semibold text-gray-900 mb-4">Select Payment Method</h5>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">

                                    <!-- Online Payment -->
                                    <label class="flex items-start p-4 border-2 rounded-lg cursor-pointer transition payment-method-option <?php echo ($paymentMethod !== 'cod') ? 'border-accent bg-accent-50' : 'border-gray-200 hover:border-gray-300'; ?>" data-method="online">
                                        <input type="radio" name="payment_method" value="online"
                                            <?php echo ($paymentMethod !== 'cod') ? 'checked' : ''; ?>
                                            class="mt-1 w-4 h-4 text-accent cursor-pointer payment-method-input">

                                        <div class="ml-3 text-sm">
                                            <p class="font-semibold text-gray-900">
                                                Pay Online
                                            </p>
                                            <p class="text-accent text-xs">
                                                Full Amount: <?php echo formatCurrency($total); ?>
                                            </p>
                                        </div>
                                    </label>

                                    <!-- COD -->
                                    <label class="flex items-start p-4 border-2 rounded-lg cursor-pointer transition payment-method-option <?php echo ($paymentMethod === 'cod') ? 'border-accent bg-accent-50' : 'border-gray-200 hover:border-gray-300'; ?>" data-method="cod">
                                        <input type="radio" name="payment_method" value="cod"
                                            <?php echo ($paymentMethod === 'cod') ? 'checked' : ''; ?>
                                            class="mt-1 w-4 h-4 text-accent cursor-pointer payment-method-input">

                                        <div class="ml-3 text-sm">
                                            <p class="font-semibold text-gray-900">Cash on Delivery</p>
                                            <p class="text-accent text-xs">
                                                Pay <?php echo COD_INITIAL_PAYMENT_PERCENTAGE; ?>% now: <?php echo formatCurrency(round($total * COD_INITIAL_PAYMENT_PERCENTAGE / 100, 2)); ?>
                                            </p>
                                        </div>
                                    </label>

                                </div>
                            </div>

                            <div class="flex justify-end sticky top-4">
                                <button type="submit" <?php echo empty($savedAddresses) ? 'disabled' : ''; ?>
                                    class="w-full md:w-auto bg-primary-500 hover:bg-primary-600 disabled:bg-gray-300 disabled:cursor-not-allowed text-white font-semibold py-4 px-8 rounded-lg transition hover:shadow-sm self-end">
                                    <i class="fas fa-credit-card mr-2"></i>Continue to Payment
                                </button>
                            </div>

                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Order Summary -->
            <div>
                <div class="bg-white border rounded-2xl shadow-sm p-6 sticky top-24">
                    <h5 class="text-xl font-bold text-gray-900 mb-6">Order Summary</h5>

                    <!-- Cart Items -->
                    <div class="space-y-3 mb-6">
                        <?php foreach ($_SESSION['cart'] as $item): ?>
                            <div class="flex justify-between items-center gap-3">

                                <!-- LEFT SIDE -->
                                <div class="flex min-w-0 flex-1 gap-2 text-sm">
                                    <span class="text-gray-600 truncate">
                                        <?php echo e($item['name']); ?>
                                    </span>
                                    <span class="text-gray-400">
                                        (x<?php echo $item['quantity']; ?>)
                                    </span>
                                </div>

                                <!-- RIGHT SIDE (PRICE) -->
                                <span class="font-medium whitespace-nowrap">
                                    <?php echo formatCurrency($item['price'] * $item['quantity']); ?>
                                </span>

                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="border-t border-gray-200 pt-4 space-y-3 mb-4">
                        <!-- Price Breakdown -->
                        <div class="flex justify-between">
                            <span class="text-gray-500 text-sm">Subtotal</span>
                            <span class="font-medium"><?php echo formatCurrency($subtotal); ?></span>
                        </div>

                        <?php if ($discount > 0): ?>
                            <div class="flex justify-between">
                                <span class="text-gray-500 text-sm">Discount</span>
                                <span class="text-green-600 font-medium">-<?php echo formatCurrency($discount); ?></span>
                            </div>
                        <?php endif; ?>

                        <div class="flex justify-between">
                            <span class="text-gray-500 text-sm">Shipping</span>
                            <span class="text-green-600 font-medium">Free</span>
                        </div>
                    </div>

                    <div class="border-t border-gray-200 pt-4">
                        <div class="flex justify-between items-center font-semibold">
                            <span class="text-gray-900 text-xl">Total Amount</span>
                            <span class="text-xl text-primary-500"><?php echo formatCurrency($total); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php if (isset($showPayment) && $showPayment): ?>
    <!-- Razorpay Integration -->
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
        var options = {
            "key": "<?php echo RAZORPAY_KEY_ID; ?>",
            "amount": "<?php echo ($_SESSION['checkout_data']['payment_method'] === 'cod' ? $_SESSION['checkout_data']['initial_payment_amount'] : $total) * 100; ?>",
            "currency": "<?php echo RAZORPAY_CURRENCY; ?>",
            "name": "Earthence",
            "image": "<?php echo PUBLIC_URL; ?>logo.png",
            "description": "<?php echo ($_SESSION['checkout_data']['payment_method'] === 'cod' ? 'COD Initial Payment' : 'Order Payment'); ?>",
            "order_id": "<?php echo $_SESSION['razorpay_order_id']; ?>",
            "handler": function(response) {
                // Show loading
                document.getElementById('rzp-button').classList.add('hidden');
                document.getElementById('payment-loading').classList.remove('hidden');

                // Send payment data to server
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = '<?php echo BASE_URL; ?>payments/verify_razorpay_payment.php';

                var razorpayPaymentId = document.createElement('input');
                razorpayPaymentId.type = 'hidden';
                razorpayPaymentId.name = 'razorpay_payment_id';
                razorpayPaymentId.value = response.razorpay_payment_id;
                form.appendChild(razorpayPaymentId);

                var razorpayOrderId = document.createElement('input');
                razorpayOrderId.type = 'hidden';
                razorpayOrderId.name = 'razorpay_order_id';
                razorpayOrderId.value = response.razorpay_order_id;
                form.appendChild(razorpayOrderId);

                var razorpaySignature = document.createElement('input');
                razorpaySignature.type = 'hidden';
                razorpaySignature.name = 'razorpay_signature';
                razorpaySignature.value = response.razorpay_signature;
                form.appendChild(razorpaySignature);

                document.body.appendChild(form);
                form.submit();
            },
            "prefill": {
                "name": "<?php echo e($_SESSION['checkout_data']['name'] ?? ''); ?>",
                "email": "<?php echo e($_SESSION['checkout_data']['email'] ?? ''); ?>",
                "contact": "<?php echo e($_SESSION['checkout_data']['mobile'] ?? ''); ?>"
            },
            "theme": {
                "color": "#FBC02D"
            }
        };

        var rzp = new Razorpay(options);

        document.getElementById('rzp-button').onclick = function(e) {
            rzp.open();
            e.preventDefault();
        }
    </script>
<?php endif; ?>

<script>
    const checkoutAddresses = <?php echo json_encode($savedAddresses, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    const addressSelect = document.getElementById('selected_address_id');

    function findCheckoutAddress(addressId) {
        return checkoutAddresses.find(function(address) {
            return Number(address.id) === Number(addressId);
        });
    }

    function setText(id, value) {
        const element = document.getElementById(id);
        if (element) {
            element.textContent = value || '';
        }
    }

    function updateAddressPreview() {
        if (!addressSelect) {
            return;
        }

        const address = findCheckoutAddress(addressSelect.value);
        if (!address) {
            return;
        }

        setText('previewName', address.name);
        setText('previewMobile', address.mobile);
        setText('previewFullAddress', `${address.address}, ${address.city}, ${address.state} - ${address.pincode}`);

        const defaultBadge = document.getElementById('previewDefaultBadge');
        if (defaultBadge) {
            defaultBadge.classList.toggle('hidden', !Number(address.is_default));
            defaultBadge.classList.toggle('inline-flex', Boolean(Number(address.is_default)));
        }
    }

    document.getElementById('checkoutForm')?.addEventListener('submit', function(e) {
        if (addressSelect && !addressSelect.value) {
            e.preventDefault();
            alert('Please select a delivery address');
            addressSelect.focus();
        }
    });

    addressSelect?.addEventListener('change', updateAddressPreview);

    // Payment method selection handling
    document.querySelectorAll('.payment-method-input')?.forEach(function(input) {
        input.addEventListener('change', function() {
            const selectedMethod = this.value;

            // Update all payment method options styling
            document.querySelectorAll('.payment-method-option').forEach(function(label) {
                if (label.getAttribute('data-method') === selectedMethod) {
                    label.classList.remove('border-gray-200', 'hover:border-gray-300');
                    label.classList.add('border-accent', 'bg-accent-50');
                } else {
                    label.classList.remove('border-accent', 'bg-accent-50');
                    label.classList.add('border-gray-200', 'hover:border-gray-300');
                }
            });
        });
    });
</script>

<?php require_once 'includes/footer.php'; ?>