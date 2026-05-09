<?php
/**
 * Admin - Manage Hero Features Images
 */

$pageTitle = 'Manage Hero Features';
require_once __DIR__ . '/../includes/db_connect.php';
requireAdmin();

$errors = [];

$infoImageUploadDir = IMAGES_PATH . 'info_image/';
$infoImageUploadUrl = IMAGES_URL . 'info_image/';

if (!is_dir($infoImageUploadDir)) {
    mkdir($infoImageUploadDir, 0777, true);
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['form_action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $featureId = isset($_POST['feature_id']) ? (int)$_POST['feature_id'] : 0;

        $existingImages = [];
        if (!empty($_POST['existing_images_json'])) {
            $decoded = json_decode($_POST['existing_images_json'], true);
            if (is_array($decoded)) {
                $existingImages = $decoded;
            }
        }

        $removeImages = $_POST['remove_images'] ?? [];
        if (!is_array($removeImages)) {
            $removeImages = [];
        }

        $keepImages = [];
        foreach ($existingImages as $img) {
            if (!in_array($img, $removeImages, true)) {
                $keepImages[] = $img;
            }
        }

        $newImages = [];
        if (isset($_FILES['images']) && isset($_FILES['images']['name']) && is_array($_FILES['images']['name'])) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];

            foreach ($_FILES['images']['name'] as $idx => $name) {
                if (!isset($_FILES['images']['error'][$idx])) {
                    continue;
                }

                if ($_FILES['images']['error'][$idx] === UPLOAD_ERR_NO_FILE) {
                    continue;
                }

                if ($_FILES['images']['error'][$idx] !== UPLOAD_ERR_OK) {
                    $errors[] = 'Image upload failed (error code ' . $_FILES['images']['error'][$idx] . ')';
                    continue;
                }

                $tmpName = $_FILES['images']['tmp_name'][$idx] ?? '';
                $fileType = $_FILES['images']['type'][$idx] ?? '';
                $fileSize = (int)($_FILES['images']['size'][$idx] ?? 0);

                if (!in_array($fileType, $allowedTypes, true)) {
                    $errors[] = 'Only JPG, PNG, and WEBP images are allowed';
                    continue;
                }

                if ($fileSize > 2 * 1024 * 1024) {
                    $errors[] = 'Image size must be less than 2MB';
                    continue;
                }

                if (!is_uploaded_file($tmpName)) {
                    $errors[] = 'Uploaded file is invalid';
                    continue;
                }

                $safeBaseName = preg_replace('/[^A-Za-z0-9\.\-_]/', '_', basename($name));
                $fileName = time() . '_' . $idx . '_' . $safeBaseName;
                $targetPath = $infoImageUploadDir . $fileName;

                if (move_uploaded_file($tmpName, $targetPath)) {
                    $newImages[] = $fileName;
                } else {
                    $errors[] = 'Failed to upload image';
                }
            }
        }

        // Delete removed files from disk only when editing (since they existed on disk already)
        if ($action === 'edit' && empty($errors) && !empty($removeImages)) {
            foreach ($removeImages as $img) {
                $imgPath = $infoImageUploadDir . basename($img);
                if (file_exists($imgPath)) {
                    unlink($imgPath);
                }
            }
        }

        $finalImages = array_values(array_merge($keepImages, $newImages));

        if (empty($finalImages)) {
            $errors[] = 'Please upload at least 1 image';
        }

        if (empty($errors)) {
            try {
                $imagesJson = json_encode($finalImages);

                if ($action === 'add') {
                    $stmt = $pdo->prepare('INSERT INTO hero_features (images, created_at) VALUES (?, NOW())');
                    $stmt->execute([$imagesJson]);
                    setFlash('Hero feature created successfully!', 'success');
                } else {
                    $stmt = $pdo->prepare('UPDATE hero_features SET images = ? WHERE id = ?');
                    $stmt->execute([$imagesJson, $featureId]);
                    setFlash('Hero feature updated successfully!', 'success');
                }
            } catch (PDOException $e) {
                setFlash('Error saving hero feature', 'danger');
            }
        } else {
            setFlash(implode(' ', $errors), 'danger');
        }

        redirect(BASE_URL . 'admin/manage_hero_features.php');
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $featureId = (int)$_GET['delete'];

    try {
        $stmt = $pdo->prepare('SELECT images FROM hero_features WHERE id = ?');
        $stmt->execute([$featureId]);
        $row = $stmt->fetch();

        if ($row) {
            $images = json_decode($row['images'], true);
            if (is_array($images)) {
                foreach ($images as $img) {
                    $imgPath = $infoImageUploadDir . basename($img);
                    if (file_exists($imgPath)) {
                        unlink($imgPath);
                    }
                }
            }
        }

        $stmt = $pdo->prepare('DELETE FROM hero_features WHERE id = ?');
        $stmt->execute([$featureId]);
        setFlash('Hero feature deleted successfully', 'success');
    } catch (PDOException $e) {
        setFlash('Error deleting hero feature', 'danger');
    }

    redirect(BASE_URL . 'admin/manage_hero_features.php');
}

// Get edit feature
$editFeature = null;
$editImages = [];
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    try {
        $stmt = $pdo->prepare('SELECT * FROM hero_features WHERE id = ?');
        $stmt->execute([$editId]);
        $editFeature = $stmt->fetch();
        if ($editFeature) {
            $decoded = json_decode($editFeature['images'], true);
            if (is_array($decoded)) {
                $editImages = $decoded;
            }
        }
    } catch (PDOException $e) {
        $editFeature = null;
        $editImages = [];
    }
}

// Fetch all hero features
$heroFeatures = [];
try {
    $stmt = $pdo->query('SELECT * FROM hero_features ORDER BY id DESC');
    $heroFeatures = $stmt->fetchAll();
} catch (PDOException $e) {
    $heroFeatures = [];
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="bg-white mt-20">
    <div class="h-[calc(100vh-80px)] flex">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <div class="flex-1 overflow-y-auto p-6 md:p-8">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-gray-900">Manage Hero Features</h2>
            </div>

            <div class="bg-white md:border md:rounded-lg md:shadow-sm md:p-6">
                <h5 class="font-bold text-gray-900 mb-4"><?php echo $editFeature ? 'Edit Hero Feature' : 'Add Hero Feature'; ?></h5>

                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                    <input type="hidden" name="form_action" value="<?php echo $editFeature ? 'edit' : 'add'; ?>">
                    <?php if ($editFeature): ?>
                        <input type="hidden" name="feature_id" value="<?php echo (int)$editFeature['id']; ?>">
                        <input type="hidden" name="existing_images_json" value="<?php echo e(json_encode($editImages)); ?>">
                    <?php endif; ?>

                    <?php if ($editFeature && !empty($editImages)): ?>
                        <div>
                            <div class="text-sm font-semibold text-gray-700 mb-2">Existing Images</div>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                <?php foreach ($editImages as $img): ?>
                                    <label class="border rounded-lg p-2 flex flex-col gap-2">
                                        <img src="<?php echo $infoImageUploadUrl . e($img); ?>" class="w-full h-24 object-cover rounded" alt="Hero">
                                        <div class="flex items-center gap-2 text-xs">
                                            <input type="checkbox" name="remove_images[]" value="<?php echo e($img); ?>">
                                            <span class="text-gray-600">Remove</span>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Upload Images</label>
                        <input type="file" name="images[]" multiple accept="image/jpeg,image/png,image/webp"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg outline-none focus:border-accent transition">
                        <p class="text-xs text-gray-500 mt-1">JPG / PNG / WEBP, max 2MB each</p>
                    </div>

                    <div class="flex gap-3 text-sm">
                        <button type="submit" class="bg-primary-500 hover:bg-primary-600 text-white font-semibold py-3 px-6 rounded-lg transition">
                            <?php echo $editFeature ? 'Update' : 'Create'; ?>
                        </button>
                        <?php if ($editFeature): ?>
                            <a href="<?php echo BASE_URL; ?>admin/manage_hero_features.php" class="border-2 border-gray-300 hover:bg-gray-50 text-gray-700 font-medium py-3 px-6 rounded-lg transition">
                                Cancel
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="mt-8">
                <h5 class="font-semibold text-gray-900 mb-4">Saved Hero Features</h5>

                <?php if (empty($heroFeatures)): ?>
                    <div class="text-center py-12 bg-white border rounded-lg shadow-sm">
                        <p class="text-gray-500">No features found.</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        <?php foreach ($heroFeatures as $feature): ?>
                            <?php
                                $imgs = json_decode($feature['images'], true);
                                if (!is_array($imgs)) {
                                    $imgs = [];
                                }
                            ?>
                            <div class="bg-white rounded-lg shadow-sm p-5 border">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <div class="font-bold text-gray-900">Feature #<?php echo (int)$feature['id']; ?></div>
                                        <div class="text-xs text-gray-500">Images: <?php echo count($imgs); ?></div>
                                    </div>
                                    <div class="flex gap-2">
                                        <a href="<?php echo BASE_URL; ?>admin/manage_hero_features.php?edit=<?php echo (int)$feature['id']; ?>" class="inline-flex items-center border-2 border-primary-500 text-primary-600 hover:bg-primary-500 hover:text-white font-medium py-2 px-4 rounded-lg transition text-sm">
                                            Edit
                                        </a>
                                        <a href="<?php echo BASE_URL; ?>admin/manage_hero_features.php?delete=<?php echo (int)$feature['id']; ?>" onclick="return confirm('Delete this hero feature?')" class="inline-flex items-center border-2 border-red-500 text-red-500 hover:bg-red-500 hover:text-white font-medium py-2 px-4 rounded-lg transition text-sm">
                                            Delete
                                        </a>
                                    </div>
                                </div>

                                <?php if (!empty($imgs)): ?>
                                    <div class="grid grid-cols-3 gap-2 mt-4">
                                        <?php foreach (array_slice($imgs, 0, 6) as $img): ?>
                                            <img src="<?php echo $infoImageUploadUrl . e($img); ?>" class="w-full h-20 object-contain rounded" alt="Hero">
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>
