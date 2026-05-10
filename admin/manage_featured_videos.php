<?php
/**
 * Admin - Manage Featured Product Videos
 */

$pageTitle = 'Manage Featured Videos';
require_once __DIR__ . '/../includes/db_connect.php';
requireAdmin();

$errors = [];

$videosUploadDir = UPLOADS_PATH;
$videosUploadUrl = UPLOADS_URL;

if (!is_dir($videosUploadDir)) {
    mkdir($videosUploadDir, 0777, true);
}

// Helper function to extract YouTube video ID
function getYouTubeVideoId($url) {
    $pattern = '/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([^&\s?]+)/';
    if (preg_match($pattern, $url, $matches)) {
        return $matches[1];
    }
    return null;
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['form_action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $videoId = isset($_POST['video_id']) ? (int)$_POST['video_id'] : 0;
        $badge = isset($_POST['badge']) ? trim($_POST['badge']) : 'Healthy Snack';
        $videoType = isset($_POST['video_type']) ? $_POST['video_type'] : 'upload';
        $youtubeUrl = isset($_POST['youtube_url']) ? trim($_POST['youtube_url']) : '';
        $existingFilePath = isset($_POST['existing_file_path']) ? trim($_POST['existing_file_path']) : '';

        // Validation
        if (empty($badge)) {
            $errors[] = 'Badge text is required';
        }

        $filePath = $existingFilePath;

        if ($videoType === 'youtube') {
            // Handle YouTube URL
            if (empty($youtubeUrl)) {
                $errors[] = 'YouTube URL is required';
            } else {
                $youtubeId = getYouTubeVideoId($youtubeUrl);
                if (!$youtubeId) {
                    $errors[] = 'Invalid YouTube URL. Please use a valid YouTube video link.';
                } else {
                    $filePath = 'https://www.youtube.com/embed/' . $youtubeId;
                }
            }
        } else {
            // Handle file upload
            if (isset($_FILES['video']) && $_FILES['video']['error'] !== UPLOAD_ERR_NO_FILE) {
                if ($_FILES['video']['error'] !== UPLOAD_ERR_OK) {
                    $errors[] = 'Video upload failed (error code ' . $_FILES['video']['error'] . ')';
                } else {
                    $allowedTypes = ['video/mp4', 'video/webm', 'video/ogg'];
                    $fileType = $_FILES['video']['type'] ?? '';
                    $fileSize = (int)($_FILES['video']['size'] ?? 0);
                    $tmpName = $_FILES['video']['tmp_name'] ?? '';

                    if (!in_array($fileType, $allowedTypes, true)) {
                        $errors[] = 'Only MP4, WEBM, and OGG videos are allowed';
                    }

                    if ($fileSize > 50 * 1024 * 1024) { // 50MB limit
                        $errors[] = 'Video size must be less than 50MB';
                    }

                    if (!is_uploaded_file($tmpName)) {
                        $errors[] = 'Uploaded file is invalid';
                    }

                    if (empty($errors)) {
                        $safeBaseName = preg_replace('/[^A-Za-z0-9\._-]/', '_', basename($_FILES['video']['name']));
                        $fileName = time() . '_' . $safeBaseName;
                        $targetPath = $videosUploadDir . $fileName;

                        if (move_uploaded_file($tmpName, $targetPath)) {
                            // Delete old file if editing
                            if ($action === 'edit' && !empty($existingFilePath) && strpos($existingFilePath, 'http') !== 0) {
                                $oldPath = $videosUploadDir . basename($existingFilePath);
                                if (file_exists($oldPath)) {
                                    unlink($oldPath);
                                }
                            }
                            $filePath = $fileName;
                        } else {
                            $errors[] = 'Failed to upload video';
                        }
                    }
                }
            } elseif ($action === 'add') {
                $errors[] = 'Please upload a video file';
            }
        }

        if (empty($errors)) {
            try {
                if ($action === 'add') {
                    $stmt = $pdo->prepare('INSERT INTO featured_products_video (badge, file_path, created_at) VALUES (?, ?, NOW())');
                    $stmt->execute([$badge, $filePath]);
                    setFlash('Featured video added successfully!', 'success');
                } else {
                    $stmt = $pdo->prepare('UPDATE featured_products_video SET badge = ?, file_path = ? WHERE id = ?');
                    $stmt->execute([$badge, $filePath, $videoId]);
                    setFlash('Featured video updated successfully!', 'success');
                }
            } catch (PDOException $e) {
                setFlash('Error saving featured video', 'danger');
            }
        } else {
            setFlash(implode(' ', $errors), 'danger');
        }

        redirect(BASE_URL . 'admin/manage_featured_videos.php');
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $videoId = (int)$_GET['delete'];

    try {
        $stmt = $pdo->prepare('SELECT file_path FROM featured_products_video WHERE id = ?');
        $stmt->execute([$videoId]);
        $row = $stmt->fetch();

        if ($row && !empty($row['file_path'])) {
            $filePath = $videosUploadDir . basename($row['file_path']);
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        $stmt = $pdo->prepare('DELETE FROM featured_products_video WHERE id = ?');
        $stmt->execute([$videoId]);
        setFlash('Featured video deleted successfully', 'success');
    } catch (PDOException $e) {
        setFlash('Error deleting featured video', 'danger');
    }

    redirect(BASE_URL . 'admin/manage_featured_videos.php');
}

// Get edit video
$editVideo = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    try {
        $stmt = $pdo->prepare('SELECT * FROM featured_products_video WHERE id = ?');
        $stmt->execute([$editId]);
        $editVideo = $stmt->fetch();
    } catch (PDOException $e) {
        $editVideo = null;
    }
}

// Fetch all featured videos
$featuredVideos = [];
try {
    $stmt = $pdo->query('SELECT * FROM featured_products_video ORDER BY id DESC');
    $featuredVideos = $stmt->fetchAll();
} catch (PDOException $e) {
    $featuredVideos = [];
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="bg-white mt-20">
    <div class="h-[calc(100vh-80px)] flex">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <div class="flex-1 overflow-y-auto p-6 md:p-8">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-gray-900">Manage Featured Videos</h2>
            </div>

            <div class="bg-white md:border md:rounded-lg md:shadow-sm md:p-6">
                <h5 class="font-bold text-gray-900 mb-4"><?php echo $editVideo ? 'Edit Featured Video' : 'Add Featured Video'; ?></h5>

                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                    <input type="hidden" name="form_action" value="<?php echo $editVideo ? 'edit' : 'add'; ?>">
                    <?php if ($editVideo): ?>
                        <input type="hidden" name="video_id" value="<?php echo (int)$editVideo['id']; ?>">
                        <input type="hidden" name="existing_file_path" value="<?php echo e($editVideo['file_path']); ?>">
                    <?php endif; ?>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Badge Text *</label>
                        <input type="text" name="badge" required maxlength="20"
                               value="<?php echo $editVideo ? e($editVideo['badge']) : 'Healthy Snack'; ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition"
                               placeholder="e.g., Best Seller, Hot Deal">
                        <p class="text-xs text-gray-500 mt-1">Max 20 characters. Appears as a badge on the video.</p>
                    </div>

                    <?php
                    $isYouTube = $editVideo && strpos($editVideo['file_path'], 'youtube.com/embed') !== false;
                    $youtubeUrl = '';
                    if ($isYouTube) {
                        preg_match('/embed\/([^?]+)/', $editVideo['file_path'], $matches);
                        $youtubeId = $matches[1] ?? '';
                        $youtubeUrl = 'https://youtu.be/' . $youtubeId;
                    }
                    ?>

                    <!-- Video Type Selection -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Video Source *</label>
                        <div class="flex gap-4">
                            <label class="flex items-center cursor-pointer">
                                <input type="radio" name="video_type" value="upload" 
                                       <?php echo (!$editVideo || !$isYouTube) ? 'checked' : ''; ?> 
                                       onchange="toggleVideoSource('upload')"
                                       class="w-4 h-4 text-primary-500 border-gray-300 focus:ring-primary-500">
                                <span class="ml-2 text-sm text-gray-700">Upload File</span>
                            </label>
                            <label class="flex items-center cursor-pointer">
                                <input type="radio" name="video_type" value="youtube" 
                                       <?php echo ($isYouTube) ? 'checked' : ''; ?> 
                                       onchange="toggleVideoSource('youtube')"
                                       class="w-4 h-4 text-primary-500 border-gray-300 focus:ring-primary-500">
                                <span class="ml-2 text-sm text-gray-700">YouTube URL</span>
                            </label>
                        </div>
                    </div>

                    <!-- File Upload Section -->
                    <div id="uploadSection" style="display: <?php echo (!$editVideo || !$isYouTube) ? 'block' : 'none'; ?>;">
                        <?php if ($editVideo && !empty($editVideo['file_path']) && !$isYouTube): ?>
                            <div class="mb-3">
                                <div class="text-sm font-semibold text-gray-700 mb-2">Current Video</div>
                                <video src="<?php echo $videosUploadUrl . e($editVideo['file_path']); ?>" 
                                       class="w-full max-w-md h-48 object-cover rounded-lg" controls muted loop>
                                </video>
                            </div>
                        <?php endif; ?>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                <?php echo $editVideo ? 'Replace Video (optional)' : 'Upload Video *'; ?>
                            </label>
                            <input type="file" name="video" accept="video/mp4,video/webm,video/ogg"
                                   <?php echo (!$editVideo || $isYouTube) ? 'required' : ''; ?>
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg outline-none focus:border-accent transition"
                                   id="videoFileInput">
                            <p class="text-xs text-gray-500 mt-1">MP4, WEBM, OGG format. Max 50MB. Recommended: 9:16 portrait format.</p>
                        </div>
                    </div>

                    <!-- YouTube URL Section -->
                    <div id="youtubeSection" style="display: <?php echo ($isYouTube) ? 'block' : 'none'; ?>;">
                        <label class="block text-sm font-medium text-gray-700 mb-1">YouTube URL *</label>
                        <input type="url" name="youtube_url" 
                               value="<?php echo $isYouTube ? e($youtubeUrl) : ''; ?>"
                               placeholder="https://youtu.be/VIDEO_ID or https://youtube.com/watch?v=VIDEO_ID"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition"
                               id="youtubeUrlInput"
                               <?php echo ($isYouTube) ? 'required' : ''; ?>>
                        <p class="text-xs text-gray-500 mt-1">Paste YouTube video URL. Examples: youtu.be/VIDEO_ID or youtube.com/watch?v=VIDEO_ID</p>
                        
                        <?php if ($editVideo && $isYouTube): ?>
                            <div class="mt-3">
                                <div class="text-sm font-semibold text-gray-700 mb-2">Current YouTube Video</div>
                                <iframe 
                                    src="<?php echo e($editVideo['file_path']); ?>" 
                                    class="w-full max-w-md h-48 rounded-lg"
                                    frameborder="0"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                    allowfullscreen>
                                </iframe>
                            </div>
                        <?php endif; ?>
                    </div>

                    <script>
                        function toggleVideoSource(type) {
                            const uploadSection = document.getElementById('uploadSection');
                            const youtubeSection = document.getElementById('youtubeSection');
                            const videoFileInput = document.getElementById('videoFileInput');
                            const youtubeUrlInput = document.getElementById('youtubeUrlInput');
                            
                            if (type === 'upload') {
                                uploadSection.style.display = 'block';
                                youtubeSection.style.display = 'none';
                                videoFileInput.required = <?php echo !$editVideo ? 'true' : 'false'; ?>;
                                youtubeUrlInput.required = false;
                            } else {
                                uploadSection.style.display = 'none';
                                youtubeSection.style.display = 'block';
                                videoFileInput.required = false;
                                youtubeUrlInput.required = true;
                            }
                        }
                    </script>

                    <div class="flex gap-3 text-sm">
                        <button type="submit" class="bg-primary-500 hover:bg-primary-600 text-white font-semibold py-3 px-6 rounded-lg transition">
                            <?php echo $editVideo ? 'Update' : 'Add Video'; ?>
                        </button>
                        <?php if ($editVideo): ?>
                            <a href="<?php echo BASE_URL; ?>admin/manage_featured_videos.php" class="border-2 border-gray-300 hover:bg-gray-50 text-gray-700 font-medium py-3 px-6 rounded-lg transition">
                                Cancel
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="mt-8">
                <h5 class="font-semibold text-gray-900 mb-4">Saved Featured Videos (<?php echo count($featuredVideos); ?>)</h5>

                <?php if (empty($featuredVideos)): ?>
                    <div class="text-center py-12 bg-white border rounded-lg shadow-sm">
                        <p class="text-gray-500">No featured videos found. Add your first video above.</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php foreach ($featuredVideos as $video): 
                            $videoIsYouTube = strpos($video['file_path'], 'youtube.com/embed') !== false;
                        ?>
                            <div class="bg-white rounded-lg shadow-sm p-4 border">
                                <div class="relative">
                                    <?php if ($videoIsYouTube): ?>
                                        <iframe 
                                            src="<?php echo e($video['file_path']); ?>" 
                                            class="w-full h-48 rounded-lg bg-gray-100"
                                            frameborder="0"
                                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                            allowfullscreen>
                                        </iframe>
                                        <div class="absolute top-2 right-2">
                                            <span class="bg-red-600 text-white text-xs font-bold px-2 py-1 rounded">
                                                <i class="fab fa-youtube mr-1"></i>YouTube
                                            </span>
                                        </div>
                                    <?php else: ?>
                                        <video src="<?php echo $videosUploadUrl . e($video['file_path']); ?>" 
                                               class="w-full h-48 object-cover rounded-lg bg-gray-100" controls muted loop>
                                        </video>
                                        <div class="absolute top-2 right-2">
                                            <span class="bg-blue-600 text-white text-xs font-bold px-2 py-1 rounded">
                                                <i class="fas fa-file-video mr-1"></i>File
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="absolute top-2 left-2">
                                        <span class="bg-white/90 backdrop-blur-sm text-accent text-xs font-bold px-3 py-1 rounded-full border">
                                            <?php echo e($video['badge']); ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="mt-3 flex items-center justify-between">
                                    <div class="text-sm text-gray-500">
                                        Added: <?php echo date('M d, Y', strtotime($video['created_at'])); ?>
                                    </div>
                                    <div class="flex gap-2">
                                        <a href="<?php echo BASE_URL; ?>admin/manage_featured_videos.php?edit=<?php echo (int)$video['id']; ?>" 
                                           class="inline-flex items-center border-2 border-primary-500 text-primary-600 hover:bg-primary-500 hover:text-white font-medium py-1 px-3 rounded-lg transition text-sm">
                                            <i class="fas fa-edit mr-1"></i> Edit
                                        </a>
                                        <a href="<?php echo BASE_URL; ?>admin/manage_featured_videos.php?delete=<?php echo (int)$video['id']; ?>" 
                                           onclick="return confirm('Delete this featured video?')" 
                                           class="inline-flex items-center border-2 border-red-500 text-red-500 hover:bg-red-500 hover:text-white font-medium py-1 px-3 rounded-lg transition text-sm">
                                            <i class="fas fa-trash mr-1"></i> Delete
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>
</body>
</html>
