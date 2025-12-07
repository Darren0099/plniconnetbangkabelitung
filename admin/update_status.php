<?php
session_start();
require_once 'koneksi.php';

// Cek apakah user sudah login dan memiliki role admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['id']) || !isset($input['status'])) {
    echo json_encode(['status' => 'error', 'message' => 'ID dan status diperlukan']);
    exit();
}

$id = (int)$input['id'];
$status = $input['status'];

if (!in_array($status, ['published', 'draft'])) {
    echo json_encode(['status' => 'error', 'message' => 'Status tidak valid']);
    exit();
}

$query = "UPDATE articles SET status = ?, updated_at = NOW() WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'si', $status, $id);
$result = mysqli_stmt_execute($stmt);

if ($result) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Status artikel berhasil diperbarui'
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Gagal memperbarui status artikel: ' . mysqli_error($conn)
    ]);
}
?>
