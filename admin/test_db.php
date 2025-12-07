<?php
// Test database connection and table structure
require 'koneksi.php';

header('Content-Type: text/plain');

echo "Testing database connection...\n";

if (!$conn) {
    echo "❌ Database connection failed: " . mysqli_connect_error() . "\n";
    exit(1);
}

echo "✅ Database connection successful\n";

echo "\nTesting database selection...\n";
if (!mysqli_select_db($conn, 'plnicon_db')) {
    echo "❌ Database selection failed: " . mysqli_error($conn) . "\n";
    exit(1);
}

echo "✅ Database selection successful\n";

echo "\nTesting articles table...\n";
$result = mysqli_query($conn, "SHOW TABLES LIKE 'articles'");
if (!$result) {
    echo "❌ Table check failed: " . mysqli_error($conn) . "\n";
    exit(1);
}

if (mysqli_num_rows($result) == 0) {
    echo "❌ Articles table does not exist\n";
    exit(1);
}

echo "✅ Articles table exists\n";

echo "\nTesting table structure...\n";
$result = mysqli_query($conn, "DESCRIBE articles");
if (!$result) {
    echo "❌ Table structure check failed: " . mysqli_error($conn) . "\n";
    exit(1);
}

$columns = [];
while ($row = mysqli_fetch_assoc($result)) {
    $columns[] = $row['Field'];
}

$required_columns = ['id', 'title', 'slug', 'category', 'content', 'tags', 'featured_image', 'author_id', 'status'];
$missing_columns = [];

foreach ($required_columns as $col) {
    if (!in_array($col, $columns)) {
        $missing_columns[] = $col;
    }
}

if (!empty($missing_columns)) {
    echo "❌ Missing columns: " . implode(', ', $missing_columns) . "\n";
    exit(1);
}

echo "✅ All required columns exist\n";

echo "\nTesting user table...\n";
$result = mysqli_query($conn, "SHOW TABLES LIKE 'user'");
if (!$result) {
    echo "❌ User table check failed: " . mysqli_error($conn) . "\n";
    exit(1);
}

if (mysqli_num_rows($result) == 0) {
    echo "❌ User table does not exist\n";
    exit(1);
}

echo "✅ User table exists\n";

echo "\n🎉 All database tests passed!\n";

mysqli_close($conn);
?>
