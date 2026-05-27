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
    $stmt = $pdo->prepare("SELECT id, product_id, flavour AS flavor, weight, price, original_price, stock, image, sort_order, created_at FROM product_variants WHERE product_id = ? AND status = 1 ORDER BY sort_order ASC");
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
    
    $uploadVariantImage = function ($index, $existingImage = '') use (&$errors) {
        if (!isset($_FILES['weights']['error'][$index]['image'])) {
            return $existingImage;
        }

        $error = $_FILES['weights']['error'][$index]['image'];
        if ($error === UPLOAD_ERR_NO_FILE) {
            return $existingImage;
        }
        if ($error !== UPLOAD_ERR_OK) {
            $errors[] = 'Variant image upload failed (error code ' . $error . ')';
            return $existingImage;
        }

        $fileType = $_FILES['weights']['type'][$index]['image'] ?? '';
        $fileSize = (int)($_FILES['weights']['size'][$index]['image'] ?? 0);
        $tmpName = $_FILES['weights']['tmp_name'][$index]['image'] ?? '';
        $originalName = $_FILES['weights']['name'][$index]['image'] ?? 'variant-image';

        if (!in_array($fileType, ['image/jpeg', 'image/png', 'image/webp'])) {
            $errors[] = 'Only JPG, PNG, and WEBP variant images are allowed';
            return $existingImage;
        }
        if ($fileSize > 2 * 1024 * 1024) {
            $errors[] = 'Each variant image must be less than 2MB';
            return $existingImage;
        }
        if (!is_uploaded_file($tmpName)) {
            $errors[] = 'Variant image upload failed for ' . e($originalName);
            return $existingImage;
        }

        $fileName = time() . '_variant_' . (int)$index . '_' . preg_replace('/[^A-Za-z0-9\.\-_]/', '_', basename($originalName));
        if (move_uploaded_file($tmpName, PRODUCTS_PATH . $fileName)) {
            if ($existingImage && file_exists(PRODUCTS_PATH . $existingImage)) {
                unlink(PRODUCTS_PATH . $existingImage);
            }
            return $fileName;
        }

        $errors[] = 'Failed to upload variant image: ' . e($originalName);
        return $existingImage;
    };

    // Handle weights
    $weights = isset($_POST['weights']) ? $_POST['weights'] : [];
    $validWeights = [];
    
    if (!empty($weights)) {
        foreach ($weights as $index => $weightData) {
            $weightFlavor = trim($weightData['flavour'] ?? ($weightData['flavor'] ?? ''));
            $weight = trim($weightData['weight'] ?? '');
            $weightPrice = (float)($weightData['price'] ?? 0);
            $weightOriginalPrice = (float)($weightData['original_price'] ?? 0);
            $weightStock = (int)($weightData['stock'] ?? 0);
            
            if (!empty($weight) && $weightPrice > 0 && $weightStock >= 0) {
                $variantImage = $uploadVariantImage($index, trim($weightData['existing_image'] ?? ''));
                $validWeights[] = [
                    'flavour' => $weightFlavor,
                    'weight' => $weight,
                    'price' => $weightPrice,
                    'original_price' => $weightOriginalPrice > 0 ? $weightOriginalPrice : null,
                    'stock' => $weightStock,
                    'image' => $variantImage
                ];
            }
        }
    }

    if (!empty($validWeights)) {
        $stock = array_sum(array_column($validWeights, 'stock'));
        if ($price <= 0) {
            $price = (float)$validWeights[0]['price'];
        }
        if ($originalPrice <= 0 && !empty($validWeights[0]['original_price'])) {
            $originalPrice = (float)$validWeights[0]['original_price'];
        }
    }

    if ($price <= 0) $errors[] = 'Price must be greater than 0';
    if ($originalPrice <= 0) $errors[] = 'Original price must be greater than 0';
    if ($stock < 0) $errors[] = 'Stock cannot be negative';
    
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
            $stmt = $pdo->prepare("DELETE FROM product_variants WHERE product_id = ?");
            $stmt->execute([$productId]);
            
            if (!empty($validWeights)) {
                $stmt = $pdo->prepare("INSERT INTO product_variants (product_id, flavour, weight, price, original_price, stock, image, status, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?)");
                foreach ($validWeights as $index => $weightData) {
                    $stmt->execute([$productId, $weightData['flavour'], $weightData['weight'], $weightData['price'], $weightData['original_price'], $weightData['stock'], $weightData['image'], $index]);
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
                                <label class="block text-sm font-medium text-gray-700 mb-1">Default Price (₹) *</label>
                                <input type="number" name="price" id="defaultPrice" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500" step="0.01" min="0" required
                                       value="<?php echo e($_POST['price'] ?? $product['price']); ?>">
                                <p class="text-xs text-gray-500 mt-1">Fallback price used when no variant is selected</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Default Original Price (₹) *</label>
                                <input type="number" name="original_price" id="defaultOriginalPrice" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500" step="0.01" min="0" required
                                       value="<?php echo e($_POST['original_price'] ?? $product['original_price']); ?>">
                                <p class="text-xs text-gray-500 mt-1">Fallback MRP used for discount display</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Total Stock Quantity *</label>
                                <input type="number" name="stock" id="defaultStock" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500" min="0" required
                                       value="<?php echo e($_POST['stock'] ?? $product['stock']); ?>">
                                <p class="text-xs text-gray-500 mt-1">With variants, this is saved as the total of all variant stock</p>
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
                            
                            <div id="weights-container" class="space-y-2">
                                <?php if (!empty($productWeights)): ?>
                                    <?php foreach ($productWeights as $index => $weight): ?>
                                        <div class="weight-entry grid grid-cols-1 md:grid-cols-7 gap-3 items-end p-4 border border-gray-200 rounded-lg bg-gray-50">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Flavor</label>
                                                <input type="text" name="weights[<?php echo $index; ?>][flavour]" value="<?php echo htmlspecialchars($weight['flavor'] ?? ''); ?>" 
                                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500" 
                                                       placeholder="e.g., Classic, Peri Peri">
                                            </div>
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
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Offer/MRP</label>
                                                <input type="number" name="weights[<?php echo $index; ?>][original_price]" value="<?php echo e($weight['original_price'] ?? ''); ?>" 
                                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500" 
                                                       step="0.01" min="0">
                                            </div>
                                            <div class="flex-1">
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Stock</label>
                                                <input type="number" name="weights[<?php echo $index; ?>][stock]" value="<?php echo $weight['stock']; ?>" 
                                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500" 
                                                       min="0" required>
                                            </div>
                                            <div class="flex-1">
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Image</label>
                                                <?php if (!empty($weight['image'])): ?>
                                                    <img src="<?php echo getImageUrl($weight['image'], 'products'); ?>" class="w-12 h-12 rounded-lg object-cover mb-2" alt="">
                                                <?php endif; ?>
                                                <input type="hidden" name="weights[<?php echo $index; ?>][existing_image]" value="<?php echo e($weight['image'] ?? ''); ?>">
                                                <input type="file" name="weights[<?php echo $index; ?>][image]"
                                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500"
                                                       accept="image/jpeg,image/png,image/webp">
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
                            <button type="submit" class="px-5 py-2.5 bg-primary text-white rounded-lg hover:bg-primary-700 transition-colors font-medium">
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
    weightEntry.className = 'weight-entry grid grid-cols-1 md:grid-cols-7 gap-3 items-end p-4 border border-gray-200 rounded-lg bg-gray-50';
    weightEntry.innerHTML = `
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Flavor</label>
            <input type="text" name="weights[${weightIndex}][flavour]" 
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500" 
                   placeholder="e.g., Classic, Peri Peri">
        </div>
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
            <label class="block text-sm font-medium text-gray-700 mb-1">Offer/MRP</label>
            <input type="number" name="weights[${weightIndex}][original_price]" 
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500" 
                   step="0.01" min="0">
        </div>
        <div class="flex-1">
            <label class="block text-sm font-medium text-gray-700 mb-1">Stock</label>
            <input type="number" name="weights[${weightIndex}][stock]" 
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500" 
                   min="0" required>
        </div>
        <div class="flex-1">
            <label class="block text-sm font-medium text-gray-700 mb-1">Image</label>
            <input type="hidden" name="weights[${weightIndex}][existing_image]" value="">
            <input type="file" name="weights[${weightIndex}][image]"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500"
                   accept="image/jpeg,image/png,image/webp">
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
    syncDefaultProductFields();
}

function syncDefaultProductFields() {
    const entries = Array.from(document.querySelectorAll('.weight-entry'));
    const defaultPrice = document.getElementById('defaultPrice');
    const defaultOriginalPrice = document.getElementById('defaultOriginalPrice');
    const defaultStock = document.getElementById('defaultStock');
    let totalStock = 0;
    let firstPrice = '';
    let firstOriginalPrice = '';
    let hasUsableVariant = false;

    entries.forEach(entry => {
        const weightInput = entry.querySelector('input[name$="[weight]"]');
        const priceInput = entry.querySelector('input[name$="[price]"]');
        const originalPriceInput = entry.querySelector('input[name$="[original_price]"]');
        const stockInput = entry.querySelector('input[name$="[stock]"]');

        if (!weightInput || weightInput.value.trim() === '') return;

        hasUsableVariant = true;
        totalStock += Math.max(0, parseInt(stockInput && stockInput.value ? stockInput.value : '0', 10) || 0);

        if (firstPrice === '' && priceInput && priceInput.value !== '') {
            firstPrice = priceInput.value;
        }
        if (firstOriginalPrice === '' && originalPriceInput && originalPriceInput.value !== '') {
            firstOriginalPrice = originalPriceInput.value;
        }
    });

    if (hasUsableVariant && defaultStock) {
        defaultStock.value = totalStock;
    }
    if (defaultPrice && defaultPrice.value === '' && firstPrice !== '') {
        defaultPrice.value = firstPrice;
    }
    if (defaultOriginalPrice && defaultOriginalPrice.value === '' && firstOriginalPrice !== '') {
        defaultOriginalPrice.value = firstOriginalPrice;
    }
}

document.getElementById('weights-container').addEventListener('input', syncDefaultProductFields);
syncDefaultProductFields();
</script>
