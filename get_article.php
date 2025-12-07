<?php
session_start();
require_once 'admin/koneksi.php';

// Cek apakah user sudah login dan memiliki role admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'ID artikel diperlukan']);
    exit();
}

$id = (int)$_GET['id'];

$query = "SELECT * FROM articles WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$article = mysqli_fetch_assoc($result);

if ($article) {
    echo json_encode([
        'status' => 'success',
        'article' => $article
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Artikel tidak ditemukan'
    ]);
}
?>
