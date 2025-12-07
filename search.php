<?php
header('Content-Type: application/json');
require 'admin/koneksi.php'; // ganti sesuai koneksi database kamu

$query = isset($_GET['query']) ? trim($_GET['query']) : '';

if ($query === '') {
    echo json_encode([]);
    exit;
}

$sql = "SELECT title, slug, category, created_at FROM articles WHERE title LIKE ? ORDER BY created_at DESC LIMIT 10";
$stmt = $conn->prepare($sql);
$searchTerm = "%" . $query . "%";
$stmt->bind_param("s", $searchTerm);
$stmt->execute();
$result = $stmt->get_result();

$articles = [];
while ($row = $result->fetch_assoc()) {
    $articles[] = [
        'title' => $row['title'],
        'slug'  => $row['slug'],
        'category' => $row['category'],
        'created_at' => $row['created_at']
    ];
}

echo json_encode($articles);
