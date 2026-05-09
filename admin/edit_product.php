<?php
/**
 * Admin - Edit Product
 */

$pageTitle = 'Edit Product';
require_once __DIR__ . '/includes/header.php';
requireAdmin();

// Get product ID
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($productId <= 0) {
    setFlash('Invalid product ID', 'danger');
    redirect(BASE_URL . 'admin/manage_products.php');
}

// Fetch product
try {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();
    
    if (!$product) {
        setFlash('Product not found', 'danger');
        redirect(BASE_URL . 'admin/manage_products.php');
    }
} catch (PDOException $e) {
    setFlash('Error loading product', 'danger');
    redirect(BASE_URL . 'admin/manage_products.php');
}

// Parse gallery
$gallery = [];
if (!empty($product['gallery'])) {
    $gallery = json_decode($product['gallery'], true) ?: [];
}

// Fetch product weights
$productWeights = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM product_weights WHERE product_id = ? ORDER BY sort_order ASC");
    $stmt->execute([$productId]);
    $productWeights = $stmt->fetchAll();
} catch (PDOException $e) {
    // Silent fail
}

// Fetch categories
$categories = [];
try {
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    // Silent fail
}

$errors = [];

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
    $status = isset($_POST['status']) ? 1 : 0;
    
    // Validation
    if (empty($name)) $errors[] = 'Product name is required';
    if (empty($description)) $errors[] = 'Description is required';
    if ($categoryId <= 0) $errors[] = 'Please select a category';
    if ($price <= 0) $errors[] = 'Price must be greater than 0';
    if ($stock < 0) $errors[] = 'Stock cannot be negative';
    
    $imageName = $product['image'];
    
    // Handle main image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = PRODUCTS_PATH;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileName = time() . '_' . basename($_FILES['image']['name']);
        $targetPath = $uploadDir . $fileName;
        
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        $fileType = $_FILES['image']['type'];
        
        if (!in_array($fileType, $allowedTypes)) {
            $errors[] = 'Only JPG, PNG, and WEBP images are allowed';
        } elseif ($_FILES['image']['size'] > 2 * 1024 * 1024) {
            $errors[] = 'Image size must be less than 2MB';
        } elseif (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
            // Delete old image
            if ($product['image'] && file_exists($uploadDir . $product['image'])) {
                unlink($uploadDir . $product['image']);
            }
            $imageName = $fileName;
        } else {
            $errors[] = 'Failed to upload image';
        }
    }
    
    // Handle new gallery images
    $galleryImages = $gallery;
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
    
    // Handle removed gallery images
    if (isset($_POST['remove_gallery']) && is_array($_POST['remove_gallery'])) {
        foreach ($_POST['remove_gallery'] as $removeImage) {
            $uploadDir = PRODUCTS_PATH;
            if (file_exists($uploadDir . $removeImage)) {
                unlink($uploadDir . $removeImage);
            }
            $galleryImages = array_diff($galleryImages, [$removeImage]);
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
            $galleryJson = !empty($galleryImages) ? json_encode(array_values($galleryImages)) : null;
            
            $stmt = $pdo->prepare("UPDATE products SET 
                name = ?, description = ?, ingredients = ?, shipping_return = ?, legal_mandatories = ?, category_id = ?, price = ?, original_price = ?, 
                stock = ?, weight = ?, image = ?, gallery = ?, status = ?, updated_at = NOW() 
                WHERE id = ?");
            $stmt->execute([$name, $description, $ingredients, $shippingReturn, $legalMandatories, $categoryId, $price, $originalPrice, 
                          $stock, $weight, $imageName, $galleryJson, $status, $productId]);
            
            // Delete existing weights and insert new ones
            $stmt = $pdo->prepare("DELETE FROM product_weights WHERE product_id = ?");
            $stmt->execute([$productId]);
            
            if (!empty($validWeights)) {
                $stmt = $pdo->prepare("INSERT INTO product_weights (product_id, weight, price, stock, sort_order) VALUES (?, ?, ?, ?, ?)");
                foreach ($validWeights as $index => $weightData) {
                    $stmt->execute([$productId, $weightData['weight'], $weightData['price'], $weightData['stock'], $index]);
                }
            }
            
            setFlash('Product updated successfully!', 'success');
            redirect(BASE_URL . 'admin/manage_products.php');
        } catch (PDOException $e) {
            $errors[] = 'Failed to update product: ' . $e->getMessage();
        }
    }
}
?>

<div class="bg-white mt-20">
    <div class="h-[calc(100vh-80px)] flex flex-col md:flex-row gap-4">
        <!-- Admin Sidebar -->
        <?php include __DIR__ . '/includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="flex-1 overflow-y-auto p-6 md:p-8">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800">Edit Product</h2>
                <a href="<?php echo BASE_URL; ?>admin/manage_products.php" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50 transition-colors">
                    <i class="fas fa-arrow-left me-2"></i>Back to Products
                </a>
            </div>
            
            <?php if (!empty($errors)): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                <ul class="list-disc list-inside space-y-1">
                    <?php foreach ($errors as $error): ?>
                    <li><?php echo e($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                <div class="p-6">
                    <form action="<?php echo BASE_URL; ?>admin/edit_product.php?id=<?php echo $productId; ?>" method="POST" enctype="multipart/form-data">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Product Name *</label>
                                <input type="text" name="name" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500" required
                                       value="<?php echo e($_POST['name'] ?? $product['name']); ?>">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Category *</label>
                                <select name="category_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500" required>
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" 
                                        <?php echo (($product['category_id'] ?? 0) == $category['id']) ? 'selected' : ''; ?>>
                                        <?php echo e($category['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Description *</label>
                                <textarea name="description" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500" rows="4" required><?php echo e($_POST['description'] ?? $product['description']); ?></textarea>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Ingredients</label>
                                <textarea name="ingredients" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500" rows="3" placeholder="Add product ingredients, separated by commas or lines"><?php echo e($_POST['ingredients'] ?? ($product['ingredients'] ?? '')); ?></textarea>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Shipping & Return</label>
                                <textarea name="shipping_return" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500" rows="3" placeholder="Add delivery timing, shipping notes, and return policy for this product"><?php echo e($_POST['shipping_return'] ?? ($product['shipping_return'] ?? '')); ?></textarea>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Legal Mandatories</label>
                                <textarea name="legal_mandatories" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500" rows="3" placeholder="Add FSSAI, manufacturer, country of origin, shelf life, or other required details"><?php echo e($_POST['legal_mandatories'] ?? ($product['legal_mandatories'] ?? '')); ?></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Price (₹) *</label>
                                <input type="number" name="price" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500" step="0.01" min="0" required
                                       value="<?php echo e($_POST['price'] ?? $product['price']); ?>">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Original Price (₹)</label>
                                <input type="number" name="original_price" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500" step="0.01" min="0"
                                       value="<?php echo e($_POST['original_price'] ?? $product['original_price']); ?>">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Stock Quantity *</label>
                                <input type="number" name="stock" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500" min="0" required
                                       value="<?php echo e($_POST['stock'] ?? $product['stock']); ?>">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Default Weight</label>
                                <input type="text" name="weight" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500"
                                       value="<?php echo e($_POST['weight'] ?? ($product['weight'] ?? '')); ?>"
                                       placeholder="e.g., 200g, 500ml, 1 piece">
                                <p class="text-xs text-gray-500 mt-1">Default weight shown when no weight variants are added</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <div class="flex items-center mt-2">
                                    <input class="w-5 h-5 text-pink-600 focus:ring-pink-500" type="checkbox" name="status" value="1" 
                                           <?php echo ($product['status'] ?? 1) ? 'checked' : ''; ?>>
                                    <label class="ml-2 text-sm text-gray-600">Active</label>
                                </div>
                            </div>
                            
                            <!-- Main Image -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Main Product Image</label>
                                <?php if ($product['image']): ?>
                                <div class="mb-2">
                                    <img src="<?php echo getImageUrl($product['image'], 'products'); ?>" width="100" class="rounded-lg" alt="">
                                </div>
                                <?php endif; ?>
                                <input type="file" name="image" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500" accept="image/jpeg,image/png,image/webp">
                                <p class="text-xs text-gray-500 mt-1">Leave empty to keep current image. Max 2MB.</p>
                            </div>
                            
                            <!-- Gallery Images -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Gallery Images</label>
                                <?php if (!empty($gallery)): ?>
                                <div class="mb-2 flex flex-wrap gap-2">
                                    <?php foreach ($gallery as $galleryImage): ?>
                                    <div class="relative">
                                        <img src="<?php echo getImageUrl($galleryImage, 'products'); ?>" width="80" height="80" class="rounded-lg" alt="">
                                        <input class="absolute top-1 left-1 w-4 h-4" type="checkbox" name="remove_gallery[]" value="<?php echo e($galleryImage); ?>" title="Remove">
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <p class="text-xs text-gray-500 mb-2">Check to remove images</p>
                                <?php endif; ?>
                                <input type="file" name="gallery[]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500" accept="image/jpeg,image/png,image/webp" multiple>
                                <p class="text-xs text-gray-500 mt-1">Add more images. Max 2MB each.</p>
                            </div>
                        </div>
                        
                        <hr class="my-6 border-gray-200">
                        
                        <!-- Weight Variants -->
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-semibold text-gray-900">Weight Variants</h3>
                                <button type="button" onclick="addWeight()" class="px-3 py-1.5 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700 transition-colors">
                                    <i class="fas fa-plus me-1"></i>Add Weight
                                </button>
                            </div>
                            
                            <div id="weights-container">
                                <?php if (!empty($productWeights)): ?>
                                    <?php foreach ($productWeights as $index => $weight): ?>
                                        <div class="weight-entry flex gap-3 items-end p-4 border border-gray-200 rounded-lg bg-gray-50">
                                            <div class="flex-1">
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Weight</label>
                                                <input type="text" name="weights[<?php echo $index; ?>][weight]" value="<?php echo htmlspecialchars($weight['weight']); ?>" 
                                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500" 
                                                       placeholder="e.g., 100g, 250g, 500g" required>
                                            </div>
                                            <div class="flex-1">
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Price (₹)</label>
                                                <input type="number" name="weights[<?php echo $index; ?>][price]" value="<?php echo $weight['price']; ?>" 
                                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500" 
                                                       step="0.01" min="0" required>
                                            </div>
                                            <div class="flex-1">
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Stock</label>
                                                <input type="number" name="weights[<?php echo $index; ?>][stock]" value="<?php echo $weight['stock']; ?>" 
                                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500" 
                                                       min="0" required>
                                            </div>
                                            <button type="button" onclick="removeWeight(this)" class="px-3 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            
                            <p class="text-sm text-gray-600">Add different weight options for this product. Each weight can have its own price and stock level.</p>
                        </div>
                        
                        <hr class="my-6 border-gray-200">
                        
                        <div class="flex gap-3">
                            <button type="submit" class="px-5 py-2.5 bg-pink-600 text-white rounded-lg hover:bg-pink-700 transition-colors font-medium">
                                <i class="fas fa-save me-2"></i>Update Product
                            </button>
                            <a href="<?php echo BASE_URL; ?>admin/manage_products.php" class="px-5 py-2.5 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50 transition-colors">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let weightIndex = <?php echo count($productWeights); ?>;

function addWeight() {
    const container = document.getElementById('weights-container');
    const weightEntry = document.createElement('div');
    weightEntry.className = 'weight-entry flex gap-3 items-end p-4 border border-gray-200 rounded-lg bg-gray-50';
    weightEntry.innerHTML = `
        <div class="flex-1">
            <label class="block text-sm font-medium text-gray-700 mb-1">Weight</label>
            <input type="text" name="weights[${weightIndex}][weight]" 
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500" 
                   placeholder="e.g., 100g, 250g, 500g" required>
        </div>
        <div class="flex-1">
            <label class="block text-sm font-medium text-gray-700 mb-1">Price (₹)</label>
            <input type="number" name="weights[${weightIndex}][price]" 
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500" 
                   step="0.01" min="0" required>
        </div>
        <div class="flex-1">
            <label class="block text-sm font-medium text-gray-700 mb-1">Stock</label>
            <input type="number" name="weights[${weightIndex}][stock]" 
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500" 
                   min="0" required>
        </div>
        <button type="button" onclick="removeWeight(this)" class="px-3 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
            <i class="fas fa-trash"></i>
        </button>
    `;
    container.appendChild(weightEntry);
    weightIndex++;
}

function removeWeight(button) {
    button.closest('.weight-entry').remove();
}
</script>
