<?php
session_start();
require 'koneksi.php';
require 'functions.php';

header('Content-Type: application/json');

// Cek login
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Anda harus login']);
    exit();
}

// Validasi method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method tidak valid']);
    exit();
}

// Ambil data
$title = $_POST['title'] ?? '';
$category = $_POST['category'] ?? '';
$content = $_POST['content'] ?? '';
$tags = $_POST['tags'] ?? '';
$status = $_POST['status'] ?? 'published'; 
$author_id = $_SESSION['user']['id'];

// Validasi input
if (empty($title) || empty($category) || empty($content)) {
    echo json_encode(['success' => false, 'message' => 'Judul, kategori, dan konten harus diisi']);
    exit();
}

// Handle file upload
$featured_image = null;
if (isset($_FILES['featuredImage']) && $_FILES['featuredImage']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = 'uploads/articles/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $fileExtension = pathinfo($_FILES['featuredImage']['name'], PATHINFO_EXTENSION);
    $fileName = uniqid('article_') . '.' . $fileExtension;
    $targetPath = $uploadDir . $fileName;

    // Validasi file
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    $maxFileSize = 2 * 1024 * 1024; // 2MB

    if (!in_array(strtolower($fileExtension), $allowedExtensions)) {
        echo json_encode(['success' => false, 'message' => 'Format file tidak didukung']);
        exit();
    }

    if ($_FILES['featuredImage']['size'] > $maxFileSize) {
        echo json_encode(['success' => false, 'message' => 'Ukuran file terlalu besar']);
        exit();
    }

    if (!move_uploaded_file($_FILES['featuredImage']['tmp_name'], $targetPath)) {
        echo json_encode(['success' => false, 'message' => 'Gagal mengupload gambar']);
        exit();
    }
    
    $featured_image = $targetPath;
}

// Simpan ke database
try {
    $stmt = $conn->prepare("INSERT INTO articles (title, category, content, tags, featured_image, author_id, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssis", $title, $category, $content, $tags, $featured_image, $author_id, $status);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => $status === 'draft' ? 'Draft artikel berhasil disimpan!' : 'Artikel berhasil dipublikasikan!'
        ]);
    } else {
        throw new Exception("Gagal menyimpan artikel");
    }
} catch (Exception $e) {
    // Hapus file jika error
    if ($featured_image && file_exists($featured_image)) {
        unlink($featured_image);
    }
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan: ' . $e->getMessage()
    ]);
}
exit();
?>