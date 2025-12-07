<?php
// Test script that mimics save_article.php behavior
session_start();
require 'koneksi.php';
require 'functions.php';

// Simulate login session
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = ['id' => 1, 'username' => 'testuser'];
}

header('Content-Type: application/json');

echo "Testing save_article.php simulation...\n\n";

// Test database connection
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . mysqli_connect_error()]);
    exit();
}

echo "✅ Database connection successful\n";

// Test session
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Session not set']);
    exit();
}

echo "✅ Session is set\n";

// Simulate POST data
$_POST = [
    'title' => 'Test Article',
    'category' => 'teknologi',
    'content' => 'This is a test article content.',
    'slug' => 'test-article',
    'tags' => 'test, article',
    'status' => 'published'
];

// Test method validation
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not POST']);
    exit();
}

echo "✅ Method is POST\n";

// Test data extraction
$title = isset($_POST['title']) ? $_POST['title'] : '';
$category = isset($_POST['category']) ? $_POST['category'] : '';
$content = isset($_POST['content']) ? $_POST['content'] : '';
$slug = isset($_POST['slug']) ? $_POST['slug'] : '';
$tags = isset($_POST['tags']) ? $_POST['tags'] : '';
$status = isset($_POST['status']) ? $_POST['status'] : 'published';
$author_id = $_SESSION['user']['id'];

echo "✅ Data extracted successfully\n";

// Test input validation
if (empty($title) || empty($category) || empty($content)) {
    echo json_encode(['success' => false, 'message' => 'Validation failed']);
    exit();
}

echo "✅ Input validation passed\n";

// Test slug generation
function generateUniqueSlug($title, $conn) {
    $slug = strtolower(trim($title));
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    $slug = preg_replace('/\s+/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');

    if (empty($slug)) {
        $slug = 'artikel-' . time();
    }

    $original_slug = $slug;
    $counter = 1;

    while (true) {
        $query = "SELECT id FROM articles WHERE slug = ?";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            echo "❌ Prepare failed: " . $conn->error . "\n";
            return $slug;
        }
        $stmt->bind_param("s", $slug);
        if (!$stmt->execute()) {
            echo "❌ Execute failed: " . $stmt->error . "\n";
            return $slug;
        }
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            break;
        }

        $slug = $original_slug . '-' . $counter;
        $counter++;
    }

    return $slug;
}

$slug = generateUniqueSlug($title, $conn);
echo "✅ Slug generated: $slug\n";

// Test database insertion
try {
    $stmt = $conn->prepare("INSERT INTO articles (title, slug, category, content, tags, featured_image, author_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
        exit();
    }

    $featured_image = null;
    $stmt->bind_param("ssssssis", $title, $slug, $category, $content, $tags, $featured_image, $author_id, $status);

    if ($stmt->execute()) {
        echo "✅ Article inserted successfully\n";
        echo json_encode([
            'success' => true,
            'message' => 'Test article saved successfully!'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Execute failed: ' . $stmt->error]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Exception: ' . $e->getMessage()
    ]);
}

mysqli_close($conn);
?>
