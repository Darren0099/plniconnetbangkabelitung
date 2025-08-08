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

try {
    $stmt = $conn->prepare("UPDATE articles SET 
                          title = ?, 
                          content = ?, 
                          category = ?, 
                          updated_at = NOW() 
                          WHERE id = ?");
    $stmt->bind_param("sssi", $title, $content, $category, $id);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Artikel berhasil diperbarui']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui artikel']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>