<?php
/**
 * User Login Page
 */

require_once __DIR__ . '/../includes/db_connect.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(BASE_URL);
}

$errors = [];

// Process form BEFORE including header (to avoid headers already sent error)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    // Validation
    if (empty($email)) $errors[] = 'Email is required';
    if (empty($password)) $errors[] = 'Password is required';
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Set session data
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                
                setFlash('Welcome back, ' . $user['name'] . '!', 'success');
                
                // Redirect to intended page or home
                $redirect = isset($_SESSION['redirect_after_login']) ? $_SESSION['redirect_after_login'] : BASE_URL;
                unset($_SESSION['redirect_after_login']);
                redirect($redirect);
                exit; // Stop execution after redirect
            } else {
                $errors[] = 'Invalid email or password';
            }
        } catch (PDOException $e) {
            $errors[] = 'Login failed. Please try again.';
            error_log('Login Error: ' . $e->getMessage());
        }
    }
}

$pageTitle = 'Login';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="h-[calc(100vh-80px)] py-12 md:py-20 px-4">
    <div class="max-w-md mx-auto">
        <div class="bg-white md:border md:rounded-lg md:shadow-sm md:p-8 text-sm">
            <div class="mb-6">
                <img src="<?php echo ASSETS_URL; ?>/public/logo.png" alt="logo" class="h-20 mx-auto mb-2">
                <h3 class="text-xl text-gray-900">Sign in</h3>
                <p class="text-sm text-gray-500">Login to your account</p>
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
            
            <form action="<?php echo BASE_URL; ?>user/login.php" method="POST" class="space-y-5">
                <div>
                    <label class="block font-medium text-gray-700 mb-1">Email Address</label>
                    <input type="email" name="email" required 
                           value="<?php echo isset($_POST['email']) ? e($_POST['email']) : ''; ?>"
                           class="w-full px-4 py-3 border rounded-lg outline-none focus:border-primary transition">
                </div>
                
                <div>
                    <label class="block font-medium text-gray-700 mb-1">Password</label>
                    <input type="password" name="password" required
                           class="w-full px-4 py-3 border rounded-lg outline-none focus:border-primary transition">
                </div>
                
                <div class="flex justify-between items-center text-xs md:text-sm">
                    <label class="flex items-center">
                        <input type="checkbox" id="remember" class="w-4 h-4 text-primary-500 border-gray-300 rounded focus:ring-primary-500">
                        <span class="ml-2 text-gray-600">Remember me</span>
                    </label>
                    <a href="<?php echo BASE_URL; ?>user/forgot-password.php" class="text-blue-500 hover:text-blue-600 font-medium">Forgot password?</a>
                </div>
                
                <button type="submit" class="w-full bg-primary-700 hover:bg-primary-800 text-white py-3 rounded-lg transition hover:shadow-sm">
                    Continue
                </button>
            </form>
            
            <div class="text-center mt-6">
                <p class="text-gray-600">Don't have an account? 
                    <a href="<?php echo BASE_URL; ?>user/signup.php" class="text-primary hover:text-primary-700">Sign up</a>
                </p>
            </div>
        </div>
    </div>
</div>

<script>
    lucide.createIcons();
</script>
</body>

</html>