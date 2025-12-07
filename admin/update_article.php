<?php
session_start();
require_once 'koneksi.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit();
}

$id = $_POST['id'] ?? null;
$title = $_POST['title'] ?? '';
$content = $_POST['content'] ?? '';
$category = $_POST['category'] ?? '';

if (empty($id) || empty($title) || empty($content) || empty($category)) {
    echo json_encode(['status' => 'error', 'message' => 'Semua field harus diisi']);
    exit();
}

$photo = null;

// Get current article to handle old image deletion
$query = "SELECT featured_image FROM articles WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$currentArticle = $result->fetch_assoc();
$oldImage = $currentArticle['featured_image'];

// Handle photo upload
if (isset($_FILES['featuredImage']) && $_FILES['featuredImage']['error'] === UPLOAD_ERR_OK) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 5 * 1024 * 1024; // 5MB

    if (!in_array($_FILES['featuredImage']['type'], $allowedTypes)) {
        echo json_encode(['status' => 'error', 'message' => 'Tipe file tidak didukung. Hanya JPEG, PNG, GIF, dan WebP yang diperbolehkan.']);
        exit();
    }

    if ($_FILES['featuredImage']['size'] > $maxSize) {
        echo json_encode(['status' => 'error', 'message' => 'Ukuran file terlalu besar. Maksimal 5MB.']);
        exit();
    }

    $uploadDir = __DIR__ . '/uploads/articles/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $fileExtension = pathinfo($_FILES['featuredImage']['name'], PATHINFO_EXTENSION);
    $filename = uniqid('article_', true) . '.' . $fileExtension;
    $uploadPath = $uploadDir . $filename;
    $photo = 'admin/uploads/articles/' . $filename; // Store full relative path

    if (move_uploaded_file($_FILES['featuredImage']['tmp_name'], $uploadPath)) {
        // Resize image to standard dimensions (800x600)
        $resizedImage = resizeImage($uploadPath, 800, 600);
        if ($resizedImage) {
            // Replace original with resized image
            $fileExtension = pathinfo($_FILES['featuredImage']['name'], PATHINFO_EXTENSION);
            if (strtolower($fileExtension) === 'png') {
                imagepng($resizedImage, $uploadPath, 8); // PNG quality 0-9, 8 is good balance
            } elseif (strtolower($fileExtension) === 'gif') {
                imagegif($resizedImage, $uploadPath);
            } elseif (strtolower($fileExtension) === 'webp') {
                imagewebp($resizedImage, $uploadPath, 85);
            } else {
                imagejpeg($resizedImage, $uploadPath, 85); // 85% quality for JPEG
            }
            imagedestroy($resizedImage);
        }

        // Delete old image if exists
        if (!empty($oldImage)) {
            $oldImagePath = __DIR__ . '/' . $oldImage;
            if (file_exists($oldImagePath)) {
                unlink($oldImagePath);
            }
        }
    } else {
        error_log('Failed to move uploaded file to ' . $uploadPath);
        echo json_encode(['status' => 'error', 'message' => 'Gagal mengupload foto']);
        exit();
    }
}

try {
    if ($photo) {
        // Update with new photo
        $stmt = $conn->prepare("UPDATE articles SET 
                              title = ?, 
                              content = ?, 
                              category = ?, 
                              featured_image = ?,
                              updated_at = NOW() 
                              WHERE id = ?");
        $stmt->bind_param("ssssi", $title, $content, $category, $photo, $id);
    } else {
        // Update without changing photo
        $stmt = $conn->prepare("UPDATE articles SET 
                              title = ?, 
                              content = ?, 
                              category = ?, 
                              updated_at = NOW() 
                              WHERE id = ?");
        $stmt->bind_param("sssi", $title, $content, $category, $id);
    }
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Artikel berhasil diperbarui']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui artikel']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
