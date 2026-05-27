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
    
    $uploadVariantImage = function ($index) use (&$errors) {
        if (!isset($_FILES['weights']['error'][$index]['image'])) {
            return '';
        }

        $error = $_FILES['weights']['error'][$index]['image'];
        if ($error === UPLOAD_ERR_NO_FILE) {
            return '';
        }
        if ($error !== UPLOAD_ERR_OK) {
            $errors[] = 'Variant image upload failed (error code ' . $error . ')';
            return '';
        }

        $fileType = $_FILES['weights']['type'][$index]['image'] ?? '';
        $fileSize = (int)($_FILES['weights']['size'][$index]['image'] ?? 0);
        $tmpName = $_FILES['weights']['tmp_name'][$index]['image'] ?? '';
        $originalName = $_FILES['weights']['name'][$index]['image'] ?? 'variant-image';

        if (!in_array($fileType, ['image/jpeg', 'image/png', 'image/webp'])) {
            $errors[] = 'Only JPG, PNG, and WEBP variant images are allowed';
            return '';
        }
        if ($fileSize > 2 * 1024 * 1024) {
            $errors[] = 'Each variant image must be less than 2MB';
            return '';
        }
        if (!is_uploaded_file($tmpName)) {
            $errors[] = 'Variant image upload failed for ' . e($originalName);
            return '';
        }

        $fileName = time() . '_variant_' . (int)$index . '_' . preg_replace('/[^A-Za-z0-9\.\-_]/', '_', basename($originalName));
        if (move_uploaded_file($tmpName, PRODUCTS_PATH . $fileName)) {
            return $fileName;
        }

        $errors[] = 'Failed to upload variant image: ' . e($originalName);
        return '';
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
                $variantImage = $uploadVariantImage($index);
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
            $galleryJson = !empty($galleryImages) ? json_encode($galleryImages) : null;
            
            $stmt = $pdo->prepare("INSERT INTO products (name, description, ingredients, shipping_return, legal_mandatories, category_id, price, original_price, stock, weight, image, gallery, status, created_at) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())");
            $stmt->execute([$name, $description, $ingredients, $shippingReturn, $legalMandatories, $categoryId, $price, $originalPrice, $stock, $weight, $imageName, $galleryJson]);
            
            $productId = $pdo->lastInsertId();
            
            // Insert weights if provided
            if (!empty($validWeights)) {
                $stmt = $pdo->prepare("INSERT INTO product_variants (product_id, flavour, weight, price, original_price, stock, image, status, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?)");
                foreach ($validWeights as $index => $weightData) {
                    $stmt->execute([$productId, $weightData['flavour'], $weightData['weight'], $weightData['price'], $weightData['original_price'], $weightData['stock'], $weightData['image'], $index]);
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
                            <label class="block text-sm font-medium text-gray-700 mb-1">Default Price (₹) *</label>
                            <input type="number" name="price" id="defaultPrice" step="0.01" min="0" required
                                   value="<?php echo isset($_POST['price']) ? e($_POST['price']) : ''; ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition">
                            <p class="text-xs text-gray-500 mt-1">Fallback price used when no variant is selected</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Default Original Price (₹) *</label>
                            <input type="number" name="original_price" id="defaultOriginalPrice" step="0.01" min="0" required
                                   value="<?php echo isset($_POST['original_price']) ? e($_POST['original_price']) : ''; ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition">
                            <p class="text-xs text-gray-500 mt-1">Fallback MRP used for discount display</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Total Stock Quantity *</label>
                            <input type="number" name="stock" id="defaultStock" min="0" required
                                   value="<?php echo isset($_POST['stock']) ? e($_POST['stock']) : ''; ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition">
                            <p class="text-xs text-gray-500 mt-1">With variants, this is saved as the total of all variant stock</p>
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

function addWeight(weight = '', price = '', stock = '', flavour = '', originalPrice = '') {
    const container = document.getElementById('weightsContainer');
    const weightDiv = document.createElement('div');
    weightDiv.className = 'weight-entry bg-gray-50 p-4 rounded-lg mb-3';
    weightDiv.innerHTML = `
        <div class="grid grid-cols-1 md:grid-cols-7 gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Flavor</label>
                <input type="text" name="weights[${weightIndex}][flavour]" value="${flavour}" placeholder="e.g., Classic, Peri Peri"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition">
            </div>
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
                <label class="block text-sm font-medium text-gray-700 mb-1">Offer/MRP</label>
                <input type="number" name="weights[${weightIndex}][original_price]" value="${originalPrice}" step="0.01" min="0"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Stock *</label>
                <input type="number" name="weights[${weightIndex}][stock]" value="${stock}" min="0" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Image</label>
                <input type="file" name="weights[${weightIndex}][image]" accept="image/jpeg,image/png,image/webp"
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

document.getElementById('weightsContainer').addEventListener('input', syncDefaultProductFields);

// Add default weight option
addWeight();
</script>
