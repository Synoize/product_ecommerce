<?php
/**
 * Manage Addresses Page
 */

require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/pincode_validation.php';
requireLogin();

$userId = $_SESSION['user_id'];
$errors = [];
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $addressId = isset($_POST['address_id']) ? (int)$_POST['address_id'] : 0;
    
    switch ($action) {
        case 'add':
        case 'edit':
            $name = trim($_POST['name'] ?? '');
            $mobile = trim($_POST['mobile'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $city = trim($_POST['city'] ?? '');
            $state = trim($_POST['state'] ?? '');
            $pincode = trim($_POST['pincode'] ?? '');
            $isDefault = isset($_POST['is_default']) ? 1 : 0;

            $mobile = preg_replace('/\\s+/', '', $mobile);
            
            // Validation
            if (empty($name)) $errors[] = 'Name is required';
            if (empty($mobile) || !preg_match('/^\\+[1-9]\\d{7,14}$/', $mobile)) $errors[] = 'Valid mobile number with country code is required (e.g. +919876543210)';
            if (empty($address)) $errors[] = 'Address is required';
            if (empty($city)) $errors[] = 'City is required';
            if (empty($state)) $errors[] = 'State is required';
            $pincodeLookup = lookupIndianPincode($pincode);
            if (!$pincodeLookup['valid']) {
                $errors[] = $pincodeLookup['message'];
            }
            
            if (empty($errors)) {
                try {
                    if ($isDefault) {
                        // Remove default from other addresses
                        $stmt = $pdo->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = ?");
                        $stmt->execute([$userId]);
                    }
                    
                    if ($action === 'edit' && $addressId > 0) {
                        // Update existing
                        $stmt = $pdo->prepare("UPDATE addresses SET name = ?, mobile = ?, address = ?, city = ?, state = ?, pincode = ?, is_default = ? WHERE id = ? AND user_id = ?");
                        $stmt->execute([$name, $mobile, $address, $city, $state, $pincode, $isDefault, $addressId, $userId]);
                        setFlash('Address updated successfully!', 'success');
                    } else {
                        // Add new
                        $stmt = $pdo->prepare("INSERT INTO addresses (user_id, name, mobile, address, city, state, pincode, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$userId, $name, $mobile, $address, $city, $state, $pincode, $isDefault]);
                        setFlash('Address added successfully!', 'success');
                    }
                    
                    redirect(BASE_URL . 'user/addresses.php');
                    exit;
                } catch (PDOException $e) {
                    $errors[] = 'Failed to save address. Please try again.';
                }
            }
            break;
            
        case 'delete':
            if ($addressId > 0) {
                try {
                    $stmt = $pdo->prepare("DELETE FROM addresses WHERE id = ? AND user_id = ?");
                    $stmt->execute([$addressId, $userId]);
                    setFlash('Address deleted successfully!', 'success');
                } catch (PDOException $e) {
                    setFlash('Failed to delete address.', 'danger');
                }
            }
            redirect(BASE_URL . 'user/addresses.php');
            exit;
            
        case 'set_default':
            if ($addressId > 0) {
                try {
                    $pdo->beginTransaction();
                    $stmt = $pdo->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = ?");
                    $stmt->execute([$userId]);
                    $stmt = $pdo->prepare("UPDATE addresses SET is_default = 1 WHERE id = ? AND user_id = ?");
                    $stmt->execute([$addressId, $userId]);
                    $pdo->commit();
                    setFlash('Default address updated!', 'success');
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    setFlash('Failed to update default address.', 'danger');
                }
            }
            redirect(BASE_URL . 'user/addresses.php');
            exit;
    }
}

// Get edit address
$editAddress = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM addresses WHERE id = ? AND user_id = ?");
        $stmt->execute([$editId, $userId]);
        $editAddress = $stmt->fetch();
    } catch (PDOException $e) {
        // Ignore
    }
}

// Fetch all addresses
$addresses = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
    $stmt->execute([$userId]);
    $addresses = $stmt->fetchAll();
} catch (PDOException $e) {
    $errors[] = 'Failed to load addresses.';
}

$pageTitle = 'Manage Addresses';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="min-h-screen mt-12">
    <div class="max-w-[1920px] mx-auto px-4 sm:px-6 lg:px-12">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <!-- Sidebar -->
            <?php require_once __DIR__ . '/../includes/profile_sidebar.php'; ?>
            
            <!-- Main Content -->
            <div class="md:col-span-3">
                <h4 class="text-2xl font-bold text-gray-900 mb-6">Manage Addresses</h4>
                
                <?php if (!empty($errors)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
                    <ul class="mb-0 list-disc list-inside">
                        <?php foreach ($errors as $error): ?>
                        <li><?php echo e($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <!-- Add/Edit Form -->
                <div class="bg-white md:border md:rounded-lg md:shadow-sm md:p-8">
                    <h5 class="font-bold text-gray-900 mb-4"><?php echo $editAddress ? 'Edit Address' : 'Add New Address'; ?></h5>
                    <form method="POST" class="space-y-4" data-pincode-api="<?php echo BASE_URL; ?>api/validate_pincode.php">
                        <input type="hidden" name="action" value="<?php echo $editAddress ? 'edit' : 'add'; ?>">
                        <?php if ($editAddress): ?>
                        <input type="hidden" name="address_id" value="<?php echo $editAddress['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                                <input type="text" name="name" required
                                       value="<?php echo $editAddress ? e($editAddress['name']) : e($_SESSION['user_name']); ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg outline-none focus:border-primary transition">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Mobile Number</label>
                                <input type="tel" name="mobile" required pattern="\+[0-9]{8,15}" maxlength="16"
                                       value="<?php echo $editAddress ? e($editAddress['mobile']) : ''; ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg outline-none focus:border-primary transition">
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                            <textarea name="address" rows="2" required
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg outline-none focus:border-primary transition"><?php echo $editAddress ? e($editAddress['address']) : ''; ?></textarea>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">City</label>
                                <input type="text" name="city" required
                                       value="<?php echo $editAddress ? e($editAddress['city']) : ''; ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg outline-none focus:border-primary transition">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">State</label>
                                <input type="text" name="state" required
                                       value="<?php echo $editAddress ? e($editAddress['state']) : ''; ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg outline-none focus:border-primary transition">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Pincode</label>
                                <input type="text" name="pincode" required pattern="[1-9][0-9]{5}" maxlength="6" inputmode="numeric" autocomplete="postal-code"
                                       value="<?php echo $editAddress ? e($editAddress['pincode']) : ''; ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg outline-none focus:border-primary transition">
                            </div>
                        </div>
                        
                        <div class="flex items-center">
                            <input type="checkbox" name="is_default" id="is_default" value="1"
                                   <?php echo ($editAddress && $editAddress['is_default']) || (!$editAddress && empty($addresses)) ? 'checked' : ''; ?>
                                   class="w-4 h-4 text-primary-500 border-gray-300 rounded">
                            <label class="ml-2 text-sm text-gray-600" for="is_default">Set as default address</label>
                        </div>
                        
                        <div class="flex gap-3 text-sm">
                            <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white font-semibold py-3 px-6 rounded-lg transition hover:shadow-sm">
                                <?php echo $editAddress ? 'Update Address' : 'Add Address'; ?>
                            </button>
                            <?php if ($editAddress): ?>
                            <a href="<?php echo BASE_URL; ?>user/addresses.php" class="border-2 border-gray-300 hover:borbgder-gray-50 text-gray-700 font-medium py-3 px-6 rounded-lg transition">
                                Cancel
                            </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
                
                <!-- Saved Addresses -->
                <?php if (!empty($addresses)): ?>
                <h5 class="font-semibold text-gray-900 mt-8 mb-4">Saved Addresses</h5>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach ($addresses as $address): ?>
                    <div class="bg-white rounded-lg shadow-sm p-5 border-2 <?php echo $address['is_default'] ? 'border-primary bg-primary-50' : 'border-transparent'; ?>">
                        <?php if ($address['is_default']): ?>
                        <span class="inline-block bg-accent text-white text-xs font-bold px-2 py-1 rounded mb-2">Default</span>
                        <?php endif; ?>
                        <div>
                            <h6 class="font-bold text-gray-900 mb-1"><?php echo e($address['name']); ?></h6>
                        <p class="text-gray-600 text-xs mb-1"><?php echo e($address['mobile']); ?></p>
                        <p class="text-gray-500 text-xs mb-4"><?php echo e($address['address']); ?>, <?php echo e($address['city']); ?>, <?php echo e($address['state']); ?> - <?php echo e($address['pincode']); ?></p>
                        
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <a href="?edit=<?php echo $address['id']; ?>" class="inline-flex items-center border-2 border-primary text-primary hover:bg-primary hover:text-white font-medium py-2 px-4 rounded-lg transition text-sm">
                                <i class="fas fa-edit mr-1"></i> Edit
                            </a>
                            <form method="POST" class="inline" onsubmit="return confirm('Delete this address?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="address_id" value="<?php echo $address['id']; ?>">
                                <button type="submit" class="inline-flex items-center border-2 border-red-500 text-red-500 hover:bg-red-500 text-sm hover:text-white font-medium py-2 px-4 rounded-lg transition text-sm">
                                    <i class="fas fa-trash mr-1"></i> Delete
                                </button>
                            </form>
                            <?php if (!$address['is_default']): ?>
                            <form method="POST" class="inline">
                                <input type="hidden" name="action" value="set_default">
                                <input type="hidden" name="address_id" value="<?php echo $address['id']; ?>">
                                <button type="submit" class="inline-flex items-center border-2 border-green-500 text-green-500 hover:bg-green-500 text-sm hover:text-white font-medium py-2 px-4 rounded-lg transition text-sm">Set Default</button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="text-center py-12 bg-white border rounded-lg shadow-sm mt-8">
                    <i class="fas fa-map-marker-alt text-5xl text-gray-300 mb-4"></i>
                    <p class="text-gray-500">No addresses saved yet.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo ASSETS_URL; ?>/public/js/pincode-lookup.js"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
