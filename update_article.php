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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit();
}

if (!isset($_POST['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'ID artikel diperlukan']);
    exit();
}

$id = (int)$_POST['id'];
$title = trim($_POST['title']);
$content = trim($_POST['content']);
$category = $_POST['category'];

// Validasi input
if (empty($title) || empty($content) || empty($category)) {
    echo json_encode(['status' => 'error', 'message' => 'Semua field harus diisi']);
    exit();
}

if (!in_array($category, ['teknologi', 'bisnis', 'lifestyle', 'kesehatan', 'pendidikan'])) {
    echo json_encode(['status' => 'error', 'message' => 'Kategori tidak valid']);
    exit();
}

// Generate slug dari title
function generateSlug($text) {
    return preg_replace('/-+/', '-', preg_replace('/[^a-z0-9-]/', '', strtolower(str_replace(' ', '-', trim($text)))));
}

$slug = generateSlug($title);

// Cek apakah slug sudah ada (kecuali untuk artikel ini sendiri)
$query_check = "SELECT id FROM articles WHERE slug = ? AND id != ?";
$stmt_check = mysqli_prepare($conn, $query_check);
mysqli_stmt_bind_param($stmt_check, 'si', $slug, $id);
mysqli_stmt_execute($stmt_check);
$result_check = mysqli_stmt_get_result($stmt_check);

if (mysqli_num_rows($result_check) > 0) {
    // Jika slug sudah ada, tambahkan angka di akhir
    $counter = 1;
    $original_slug = $slug;
    do {
        $slug = $original_slug . '-' . $counter;
        mysqli_stmt_bind_param($stmt_check, 'si', $slug, $id);
        mysqli_stmt_execute($stmt_check);
        $result_check = mysqli_stmt_get_result($stmt_check);
        $counter++;
    } while (mysqli_num_rows($result_check) > 0);
}

// Update artikel
$query = "UPDATE articles SET title = ?, slug = ?, content = ?, category = ?, updated_at = NOW() WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'sssssi', $title, $slug, $content, $category, $id);
$result = mysqli_stmt_execute($stmt);

if ($result) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Artikel berhasil diperbarui'
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Gagal memperbarui artikel: ' . mysqli_error($conn)
    ]);
}
?>
