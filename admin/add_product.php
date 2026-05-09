<?php
/**
 * Admin - Add Product
 */

$pageTitle = 'Add Product';
require_once __DIR__ . '/includes/header.php';
requireAdmin();

// Fetch categories
$categories = [];
try {
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    // Silent fail
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $ingredients = isset($_POST['ingredients']) ? trim($_POST['ingredients']) : '';
    $shippingReturn = isset($_POST['shipping_return']) ? trim($_POST['shipping_return']) : '';
    $legalMandatories = isset($_POST['legal_mandatories']) ? trim($_POST['legal_mandatories']) : '';
    $categoryId = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
    $price = isset($_POST['price']) ? (float)$_POST['price'] : 0;
    $originalPrice = isset($_POST['original_price']) ? (float)$_POST['original_price'] : 0;
    $stock = isset($_POST['stock']) ? (int)$_POST['stock'] : 0;
    $weight = isset($_POST['weight']) ? trim($_POST['weight']) : '';
    
    // Validation
    if (empty($name)) $errors[] = 'Product name is required';
    if (empty($description)) $errors[] = 'Description is required';
    if ($categoryId <= 0) $errors[] = 'Please select a category';
    if ($price <= 0) $errors[] = 'Price must be greater than 0';
    if ($stock < 0) $errors[] = 'Stock cannot be negative';
    
    // Handle main image upload
    $imageName = '';
    if (isset($_FILES['image'])) {
        if ($_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = PRODUCTS_PATH;
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $fileName = time() . '_' . preg_replace('/[^A-Za-z0-9\.\-_]/', '_', basename($_FILES['image']['name']));
            $targetPath = $uploadDir . $fileName;

            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            $fileType = $_FILES['image']['type'];

            if (!in_array($fileType, $allowedTypes)) {
                $errors[] = 'Only JPG, PNG, and WEBP images are allowed';
            } elseif ($_FILES['image']['size'] > 2 * 1024 * 1024) {
                $errors[] = 'Image size must be less than 2MB';
            } elseif (!is_uploaded_file($_FILES['image']['tmp_name'])) {
                $errors[] = 'Uploaded file is invalid';
            } elseif (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                $imageName = $fileName;
            } else {
                $errors[] = 'Failed to upload image';
            }
        } elseif ($_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $errors[] = 'Image upload failed (error code ' . $_FILES['image']['error'] . ')';
        }
    }
    
    // Handle gallery images
    $galleryImages = [];
    if (isset($_FILES['gallery']) && is_array($_FILES['gallery']['name'])) {
        $uploadDir = PRODUCTS_PATH;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        foreach ($_FILES['gallery']['name'] as $key => $galleryFileName) {
            if ($_FILES['gallery']['error'][$key] === UPLOAD_ERR_OK) {
                $fileName = time() . '_gallery_' . $key . '_' . preg_replace('/[^A-Za-z0-9\.\-_]/', '_', basename($galleryFileName));
                $targetPath = $uploadDir . $fileName;
                
                $fileType = $_FILES['gallery']['type'][$key];
                if (!in_array($fileType, ['image/jpeg', 'image/png', 'image/webp'])) {
                    $errors[] = 'Only JPG, PNG, and WEBP gallery images are allowed';
                    continue;
                }
                if ($_FILES['gallery']['size'][$key] > 2 * 1024 * 1024) {
                    $errors[] = 'Each gallery image must be less than 2MB';
                    continue;
                }
                if (!is_uploaded_file($_FILES['gallery']['tmp_name'][$key])) {
                    $errors[] = 'Gallery image upload failed for ' . e($galleryFileName);
                    continue;
                }
                if (move_uploaded_file($_FILES['gallery']['tmp_name'][$key], $targetPath)) {
                    $galleryImages[] = $fileName;
                } else {
                    $errors[] = 'Failed to upload gallery image: ' . e($galleryFileName);
                }
            } elseif ($_FILES['gallery']['error'][$key] !== UPLOAD_ERR_NO_FILE) {
                $errors[] = 'Gallery image upload failed (error code ' . $_FILES['gallery']['error'][$key] . ')';
            }
        }
    }
    
    // Handle weights
    $weights = isset($_POST['weights']) ? $_POST['weights'] : [];
    $validWeights = [];
    
    if (!empty($weights)) {
        foreach ($weights as $weightData) {
            $weight = trim($weightData['weight'] ?? '');
            $weightPrice = (float)($weightData['price'] ?? 0);
            $weightStock = (int)($weightData['stock'] ?? 0);
            
            if (!empty($weight) && $weightPrice > 0 && $weightStock >= 0) {
                $validWeights[] = [
                    'weight' => $weight,
                    'price' => $weightPrice,
                    'stock' => $weightStock
                ];
            }
        }
    }
    
    if (empty($errors)) {
        try {
            $galleryJson = !empty($galleryImages) ? json_encode($galleryImages) : null;
            
            $stmt = $pdo->prepare("INSERT INTO products (name, description, ingredients, shipping_return, legal_mandatories, category_id, price, original_price, stock, weight, image, gallery, status, created_at) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())");
            $stmt->execute([$name, $description, $ingredients, $shippingReturn, $legalMandatories, $categoryId, $price, $originalPrice, $stock, $weight, $imageName, $galleryJson]);
            
            $productId = $pdo->lastInsertId();
            
            // Insert weights if provided
            if (!empty($validWeights)) {
                $stmt = $pdo->prepare("INSERT INTO product_weights (product_id, weight, price, stock, sort_order) VALUES (?, ?, ?, ?, ?)");
                foreach ($validWeights as $index => $weightData) {
                    $stmt->execute([$productId, $weightData['weight'], $weightData['price'], $weightData['stock'], $index]);
                }
            }
            
            setFlash('Product added successfully!', 'success');
            redirect(BASE_URL . 'admin/manage_products.php');
        } catch (PDOException $e) {
            $errors[] = 'Failed to add product: ' . $e->getMessage();
        }
    }
}
?>

<div class="bg-white mt-20">
    <div class="h-[calc(100vh-80px)] flex">
        <!-- Admin Sidebar -->
        <?php include __DIR__ . '/includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="flex-1 overflow-y-auto p-6 md:p-8">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                <h2 class="text-2xl font-bold text-gray-900">Add New Product</h2>
                <a href="<?php echo BASE_URL; ?>admin/manage_products.php" class="inline-flex items-center border-2 border-gray-300 hover:border-gray-400 text-gray-700 font-medium py-2 px-4 rounded-lg transition">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Products
                </a>
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
            
            <div class="bg-white rounded-xl shadow-md p-6">
                <form action="<?php echo BASE_URL; ?>admin/add_product.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Product Name *</label>
                            <input type="text" name="name" required
                                   value="<?php echo isset($_POST['name']) ? e($_POST['name']) : ''; ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Category *</label>
                            <select name="category_id" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition">
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" 
                                    <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                    <?php echo e($category['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Description *</label>
                            <textarea name="description" rows="4" required
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition"><?php echo isset($_POST['description']) ? e($_POST['description']) : ''; ?></textarea>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Ingredients</label>
                            <textarea name="ingredients" rows="3"
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition"
                                      placeholder="Add product ingredients, separated by commas or lines"><?php echo isset($_POST['ingredients']) ? e($_POST['ingredients']) : ''; ?></textarea>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Shipping & Return</label>
                            <textarea name="shipping_return" rows="3"
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition"
                                      placeholder="Add delivery timing, shipping notes, and return policy for this product"><?php echo isset($_POST['shipping_return']) ? e($_POST['shipping_return']) : ''; ?></textarea>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Legal Mandatories</label>
                            <textarea name="legal_mandatories" rows="3"
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition"
                                      placeholder="Add FSSAI, manufacturer, country of origin, shelf life, or other required details"><?php echo isset($_POST['legal_mandatories']) ? e($_POST['legal_mandatories']) : ''; ?></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Price (₹) *</label>
                            <input type="number" name="price" step="0.01" min="0" required
                                   value="<?php echo isset($_POST['price']) ? e($_POST['price']) : ''; ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Original Price (₹)</label>
                            <input type="number" name="original_price" step="0.01" min="0"
                                   value="<?php echo isset($_POST['original_price']) ? e($_POST['original_price']) : ''; ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition">
                            <p class="text-xs text-gray-500 mt-1">For showing discount</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Stock Quantity *</label>
                            <input type="number" name="stock" min="0" required
                                   value="<?php echo isset($_POST['stock']) ? e($_POST['stock']) : ''; ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Default Weight</label>
                            <input type="text" name="weight" 
                                   value="<?php echo isset($_POST['weight']) ? e($_POST['weight']) : ''; ?>"
                                   placeholder="e.g., 200g, 500ml, 1 piece"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition">
                            <p class="text-xs text-gray-500 mt-1">Default weight shown when no weight variants are added</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Main Product Image</label>
                            <input type="file" name="image" accept="image/jpeg,image/png,image/webp"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition">
                            <p class="text-xs text-gray-500 mt-1">Max 2MB (JPG, PNG, WEBP)</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Gallery Images</label>
                            <input type="file" name="gallery[]" accept="image/jpeg,image/png,image/webp" multiple
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition">
                            <p class="text-xs text-gray-500 mt-1">Select multiple images. Max 2MB each.</p>
                        </div>
                    </div>
                    
                    <!-- Product Weights Section -->
                    <div class="border-t border-gray-200 pt-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Product Weights & Pricing</h3>
                        <div id="weightsContainer">
                            <!-- Weight entries will be added here -->
                        </div>
                        <button type="button" onclick="addWeight()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2 px-4 rounded-lg transition inline-flex items-center">
                            <i class="fas fa-plus mr-2"></i>Add Weight Option
                        </button>
                        <p class="text-xs text-gray-500 mt-2">Add different weight options with their respective prices and stock levels.</p>
                    </div>
                    
                    <div class="border-t border-gray-200 pt-6 flex flex-wrap gap-3">
                        <button type="submit" class="bg-primary-500 hover:bg-primary-600 text-white font-semibold py-3 px-6 rounded-full transition shadow-lg hover:shadow-xl">
                            <i class="fas fa-save mr-2"></i>Add Product
                        </button>
                        <a href="<?php echo BASE_URL; ?>admin/manage_products.php" class="inline-flex items-center border-2 border-gray-300 hover:border-gray-400 text-gray-700 font-medium py-3 px-6 rounded-full transition">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
let weightIndex = 0;

function addWeight(weight = '', price = '', stock = '') {
    const container = document.getElementById('weightsContainer');
    const weightDiv = document.createElement('div');
    weightDiv.className = 'weight-entry bg-gray-50 p-4 rounded-lg mb-3';
    weightDiv.innerHTML = `
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Weight *</label>
                <input type="text" name="weights[${weightIndex}][weight]" value="${weight}" placeholder="e.g., 100g, 250g, 500g" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Price (₹) *</label>
                <input type="number" name="weights[${weightIndex}][price]" value="${price}" step="0.01" min="0" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Stock *</label>
                <input type="number" name="weights[${weightIndex}][stock]" value="${stock}" min="0" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition">
            </div>
            <div>
                <button type="button" onclick="removeWeight(this)" class="bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded-lg transition">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `;
    container.appendChild(weightDiv);
    weightIndex++;
}

function removeWeight(button) {
    button.closest('.weight-entry').remove();
}

// Add default weight option
addWeight();
</script>
