<?php

/**
 * User Profile Page
 */

$pageTitle = 'My Profile';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/pincode_validation.php';

// Require login
requireLogin();

$userId = $_SESSION['user_id'];
$errors = [];
$success = false;

// Fetch user data
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    $errors[] = 'Failed to load profile data.';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';
    $city = isset($_POST['city']) ? trim($_POST['city']) : '';
    $state = isset($_POST['state']) ? trim($_POST['state']) : '';
    $pincode = isset($_POST['pincode']) ? trim($_POST['pincode']) : '';

    $currentPassword = isset($_POST['current_password']) ? $_POST['current_password'] : '';
    $newPassword = isset($_POST['new_password']) ? $_POST['new_password'] : '';
    $confirmNewPassword = isset($_POST['confirm_new_password']) ? $_POST['confirm_new_password'] : '';

    // Validation
    if (empty($name)) $errors[] = 'Name is required';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required';
    if ($pincode !== '') {
        $pincodeLookup = lookupIndianPincode($pincode);
        if (!$pincodeLookup['valid']) {
            $errors[] = $pincodeLookup['message'];
        }
    }

    // Check email uniqueness (excluding current user)
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $userId]);
            if ($stmt->fetch()) {
                $errors[] = 'Email already in use by another account';
            }
        } catch (PDOException $e) {
            $errors[] = 'Error checking email availability';
        }
    }

    // Password change validation
    if (!empty($newPassword)) {
        if (empty($currentPassword)) {
            $errors[] = 'Current password is required to change password';
        } elseif (!password_verify($currentPassword, $user['password'])) {
            $errors[] = 'Current password is incorrect';
        } elseif (strlen($newPassword) < 6) {
            $errors[] = 'New password must be at least 6 characters';
        } elseif ($newPassword !== $confirmNewPassword) {
            $errors[] = 'New passwords do not match';
        }
    }

    if (empty($errors)) {
        try {
            // Update user data
            if (!empty($newPassword)) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, address = ?, city = ?, state = ?, pincode = ?, password = ? WHERE id = ?");
                $stmt->execute([$name, $email, $address, $city, $state, $pincode, $hashedPassword, $userId]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, address = ?, city = ?, state = ?, pincode = ? WHERE id = ?");
                $stmt->execute([$name, $email, $address, $city, $state, $pincode, $userId]);
            }

            // Update session
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;

            // Refresh user data
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            $success = true;
            setFlash('Profile updated successfully!', 'success');
        } catch (PDOException $e) {
            $errors[] = 'Failed to update profile. Please try again.';
            error_log('Profile Update Error: ' . $e->getMessage());
        }
    }
}
?>

<div class="min-h-screen mt-12">
    <div class="max-w-[1920px] mx-auto px-4 sm:px-6 lg:px-12">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <!-- Sidebar -->
            <?php require_once __DIR__ . '/../includes/profile_sidebar.php'; ?>

            <!-- Profile Form -->
            <div class="md:col-span-3">
                <div class="bg-white md:border md:rounded-lg md:shadow-sm md:p-8">
                    <h4 class="text-2xl font-bold text-gray-900 mb-6">Edit Profile</h4>

                    <?php if (!empty($errors)): ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
                            <ul class="mb-0 list-disc list-inside">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo e($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form action="<?php echo BASE_URL; ?>user/profile.php" method="POST" id="profileForm" class="space-y-6" data-pincode-api="<?php echo BASE_URL; ?>api/validate_pincode.php">
                        <div>
                            <h6 class="font-bold text-primary mb-4">Personal Information</h6>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                                    <input type="text" name="name" required
                                        value="<?php echo e($_POST['name'] ?? $user['name'] ?? ''); ?>"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg outline-none focus:border-accent transition">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Email Address *</label>
                                    <input type="email" name="email" required
                                        value="<?php echo e($_POST['email'] ?? $user['email'] ?? ''); ?>"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg outline-none focus:border-accent transition">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Mobile Number *</label>
                                    <input type="tel" value="<?php echo e($user['mobile'] ?? ''); ?>" readonly
                                        class="w-full px-4 py-3 border border-gray-200 bg-gray-100 text-gray-600 rounded-lg outline-none cursor-not-allowed">
                                </div>
                            </div>
                        </div>

                        <div>
                            <h6 class="font-semibold text-primary mb-4">Address Information</h6>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                                    <textarea name="address" rows="3"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg outline-none focus:border-accent transition"><?php echo e($_POST['address'] ?? $user['address'] ?? ''); ?></textarea>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">City</label>
                                        <input type="text" name="city"
                                            value="<?php echo e($_POST['city'] ?? $user['city'] ?? ''); ?>"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg outline-none focus:border-accent transition">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">State</label>
                                        <input type="text" name="state"
                                            value="<?php echo e($_POST['state'] ?? $user['state'] ?? ''); ?>"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg outline-none focus:border-accent transition">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Pincode</label>
                                        <input type="text" name="pincode" pattern="[1-9][0-9]{5}" maxlength="6" inputmode="numeric" autocomplete="postal-code"
                                            value="<?php echo e($_POST['pincode'] ?? $user['pincode'] ?? ''); ?>"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg outline-none focus:border-accent transition">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h6 class="font-bold text-red-500 mb-4">Change Password</h6>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                                    <input type="password" name="current_password"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg outline-none focus:border-accent transition">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                                    <input type="password" name="new_password" minlength="6"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg outline-none focus:border-accent transition">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                                    <input type="password" name="confirm_new_password"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg outline-none focus:border-accent transition">
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold py-3 px-8 rounded-lg transition hover:shadow-sm inline-flex items-center">
                            <i class="fas fa-save mr-2"></i>Save Changes
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Pincode validation
    document.querySelector('input[name="pincode"]').addEventListener('input', function(e) {
        this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
    });
</script>

<script src="<?php echo ASSETS_URL; ?>/public/js/pincode-lookup.js"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
