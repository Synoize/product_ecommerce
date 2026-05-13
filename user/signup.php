<?php
/**
 * User Signup Page
 */

require_once __DIR__ . '/../includes/db_connect.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(BASE_URL);
}

$errors = [];

// Process form BEFORE including header (to avoid headers already sent error)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $mobile = isset($_POST['mobile']) ? trim($_POST['mobile']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirmPassword = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    $mobile = preg_replace('/\\s+/', '', $mobile);
    
    // Validation
    if (empty($name)) $errors[] = 'Name is required';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required';
    if (empty($mobile) || !preg_match('/^\\+[1-9]\\d{7,14}$/', $mobile)) $errors[] = 'Valid mobile number with country code is required (e.g. +919876543210)';
    if (empty($password) || strlen($password) < 6) $errors[] = 'Password must be at least 6 characters';
    if ($password !== $confirmPassword) $errors[] = 'Passwords do not match';
    
    if (empty($errors)) {
        try {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'Email already registered. Please login.';
            } else {
                // Hash password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert user
                $stmt = $pdo->prepare("INSERT INTO users (name, email, mobile, password, role, status, created_at) 
                                      VALUES (?, ?, ?, ?, 'user', 1, NOW())");
                $stmt->execute([$name, $email, $mobile, $hashedPassword]);
                
                $userId = $pdo->lastInsertId();
                
                // Set session
                $_SESSION['user_id'] = $userId;
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_role'] = 'user';
                
                setFlash('Welcome to Earthence! Your account has been created.', 'success');
                redirect(BASE_URL);
                exit; // Stop execution after redirect
            }
        } catch (PDOException $e) {
            $errors[] = 'Registration failed. Please try again.';
            error_log('Signup Error: ' . $e->getMessage());
        }
    }
}

$pageTitle = 'Sign Up';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="h-[calc(100vh-80px)] py-12 md:py-20 px-4">
    <div class="max-w-md mx-auto  pb-8 ">
        <div class="bg-white md:border md:rounded-lg md:shadow-sm md:p-8 text-sm">
            <div class="mb-6">
                <img src="<?php echo ASSETS_URL; ?>/public/logo.png" alt="logo" class="h-20 mx-auto mb-2">
                <h3 class="text-xl text-gray-900">Create Account</h3>
                <p class="text-gray-500">Join Earthence today</p>
            </div>
            
            <?php if (!empty($errors)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
                <ul class="mb-0 list-disc list-inside">
                    <?php foreach ($errors as $error): ?>
                    <li><?php echo e($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <form action="<?php echo BASE_URL; ?>user/signup.php" method="POST" id="signupForm" class="space-y-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                    <input type="text" name="name" required
                           value="<?php echo isset($_POST['name']) ? e($_POST['name']) : ''; ?>"
                           class="w-full px-4 py-3 border rounded-lg outline-none focus:border-primary transition">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                    <input type="email" name="email" required
                           value="<?php echo isset($_POST['email']) ? e($_POST['email']) : ''; ?>"
                           class="w-full px-4 py-3 border rounded-lg outline-none focus:border-primary transition">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mobile Number</label>
                    <input type="tel" name="mobile" required
                           pattern="\+[0-9]{8,15}" maxlength="16"
                           value="<?php echo isset($_POST['mobile']) ? e($_POST['mobile']) : ''; ?>"
                           class="w-full px-4 py-3 border rounded-lg outline-none focus:border-primary transition">
                    <p class="text-xs text-gray-500 mt-1">Enter mobile with country code (e.g. +919876543210)</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password (min 6 characters)</label>
                    <input type="password" name="password" required minlength="6"
                           class="w-full px-4 py-3 border rounded-lg outline-none focus:border-primary transition">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                    <input type="password" name="confirm_password" required
                           class="w-full px-4 py-3 border rounded-lg outline-none focus:border-primary transition">
                </div>
                
                <div class="flex items-center">
                    <input type="checkbox" id="terms" required class="w-4 h-4 text-primary-500 border-gray-300 rounded focus:ring-primary-500 mt-1">
                    <label class="ml-2 text-gray-600 text-xs md:text-sm" for="terms">
                        I agree to the <a href="<?php echo BASE_URL; ?>privacy_policy.php" class="text-primary hover:text-primary-700">Terms of Service</a> and <a href="<?php echo BASE_URL; ?>privacy_policy.php" class="text-primary hover:text-primary-700">Privacy Policy</a>
                    </label>
                </div>
                
                <button type="submit" class="w-full bg-primary-700 hover:bg-primary-800 text-white py-3 rounded-lg transition hover:shadow-sm">
                    Create Account
                </button>
            </form>
            
            <div class="text-center mt-6">
                <p class="text-gray-600">Already have an account? 
                    <a href="<?php echo BASE_URL; ?>user/login.php" class="text-primary hover:text-primary-700">Login</a>
                </p>
            </div>
        </div>
    </div>
</div>

<script>
// Mobile number validation
document.querySelector('input[name="mobile"]').addEventListener('input', function(e) {
    this.value = this.value.replace(/\s+/g, '');
    if (this.value.length > 0 && this.value[0] !== '+') {
        this.value = '+' + this.value.replace(/[^0-9]/g, '');
    } else {
        this.value = '+' + this.value.substring(1).replace(/[^0-9]/g, '');
    }
    this.value = this.value.slice(0, 16);
});

// Form validation
document.getElementById('signupForm').addEventListener('submit', function(e) {
    var mobile = document.querySelector('input[name="mobile"]');
    var password = document.querySelector('input[name="password"]');
    var confirmPassword = document.querySelector('input[name="confirm_password"]');
    
    if (!/^\+[1-9]\d{7,14}$/.test(mobile.value.replace(/\s+/g, ''))) {
        e.preventDefault();
        alert('Please enter a valid mobile number with country code (e.g. +919876543210)');
        mobile.focus();
        return false;
    }
    
    if (password.value !== confirmPassword.value) {
        e.preventDefault();
        alert('Passwords do not match');
        confirmPassword.focus();
        return false;
    }
    
    return true;
});
</script>
