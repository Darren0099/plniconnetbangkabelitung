<?php
// Test email functionality directly
require 'koneksi.php';
require 'functions.php';

// Test data
$testArticleData = [
    'title' => 'Test Article for Email',
    'slug' => 'test-article-email',
    'category' => 'teknologi',
    'content' => 'This is a test article to check email functionality.',
    'tags' => 'test, email',
    'featured_image' => null,
    'author_id' => 1,
    'status' => 'published'
];

echo "Testing email functionality...\n\n";

echo "Test article data:\n";
echo json_encode($testArticleData, JSON_PRETTY_PRINT) . "\n\n";

echo "Calling sendNewArticleNotification...\n";

$result = sendNewArticleNotification($testArticleData, $conn);

echo "Result: " . ($result ? "SUCCESS" : "FAILED") . "\n\n";

echo "Check email_log.txt for detailed logs.\n";

mysqli_close($conn);
?>
