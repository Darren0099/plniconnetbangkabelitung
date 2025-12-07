<?php
session_start();
include 'koneksi.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$month = isset($_GET['month']) ? trim($_GET['month']) : '';
$year = isset($_GET['year']) ? trim($_GET['year']) : '';

if (empty($month) || empty($year)) {
    echo json_encode(['status' => 'error', 'message' => 'Bulan dan tahun tidak valid']);
    exit;
}

// Validate month (01-12) and year (numeric)
if (!preg_match('/^(0[1-9]|1[0-2])$/', $month) || !preg_match('/^\d{4}$/', $year)) {
    echo json_encode(['status' => 'error', 'message' => 'Format bulan atau tahun tidak valid']);
    exit;
}

$query = "SELECT COUNT(*) AS total FROM articles WHERE YEAR(created_at) = '$year' AND MONTH(created_at) = '$month'";
$result = mysqli_query($conn, $query);

if ($result) {
    $row = mysqli_fetch_assoc($result);
    echo json_encode(['status' => 'success', 'count' => $row['total']]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil data']);
}
?>
