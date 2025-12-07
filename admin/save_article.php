// Prevent any output before headers
ob_start();

<?php
session_start();
require 'koneksi.php';
require 'functions.php';

// Prevent HTML error output that corrupts JSON
ini_set('display_errors', 0);
error_reporting(E_ALL & ~E_NOTICE);

header('Content-Type: application/json');

// Clear any buffered output
ob_clean();

// Cek login
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Anda harus login']);
    exit();
}

// Test database connection
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Validasi method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method tidak valid']);
    exit();
}

// Ambil data
$title = isset($_POST['title']) ? $_POST['title'] : '';
$category = isset($_POST['category']) ? $_POST['category'] : '';
$content = isset($_POST['content']) ? $_POST['content'] : '';
$slug = isset($_POST['slug']) ? $_POST['slug'] : '';
$tags = isset($_POST['tags']) ? $_POST['tags'] : '';
$status = isset($_POST['status']) ? $_POST['status'] : 'published';
$author_id = $_SESSION['user']['id'];

// Validasi input
if (empty($title) || empty($category) || empty($content)) {
    echo json_encode(['success' => false, 'message' => 'Judul, kategori, dan konten harus diisi']);
    exit();
}

// Generate unique slug server-side
function generateUniqueSlug($title, $conn) {
    // Create base slug from title
    $slug = strtolower(trim($title));
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug); // Remove special chars except spaces and hyphens
    $slug = preg_replace('/\s+/', '-', $slug); // Replace spaces with hyphens
    $slug = preg_replace('/-+/', '-', $slug); // Replace multiple hyphens with single
    $slug = trim($slug, '-'); // Trim hyphens from start/end

    if (empty($slug)) {
        $slug = 'artikel-' . time();
    }

    $original_slug = $slug;
    $counter = 1;

    // Check if slug exists and make it unique
    while (true) {
        $query = "SELECT id FROM articles WHERE slug = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $slug);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            break; // Slug is unique
        }

        $slug = $original_slug . '-' . $counter;
        $counter++;
    }

    return $slug;
}

// Generate unique slug
$slug = generateUniqueSlug($title, $conn);

// Handle file upload
$featured_image = null;
if (isset($_FILES['featuredImage']) && $_FILES['featuredImage']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = 'admin/uploads/articles/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $fileExtension = pathinfo($_FILES['featuredImage']['name'], PATHINFO_EXTENSION);
    $fileName = uniqid('article_') . '.' . $fileExtension;
    $targetPath = $uploadDir . $fileName;

    // Validasi file
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $maxFileSize = 5 * 1024 * 1024; // 5MB

    if (!in_array(strtolower($fileExtension), $allowedExtensions)) {
        echo json_encode(['success' => false, 'message' => 'Format file tidak didukung']);
        exit();
    }

    if ($_FILES['featuredImage']['size'] > $maxFileSize) {
        echo json_encode(['success' => false, 'message' => 'Ukuran file terlalu besar']);
        exit();
    }

    if (move_uploaded_file($_FILES['featuredImage']['tmp_name'], $targetPath)) {
        $featured_image = 'admin/uploads/articles/' . $fileName; // sudah "admin/uploads/articles/namafile.jpg"
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal mengupload gambar']);
        exit();
    }
}

// Simpan ke database
try {
   $stmt = $conn->prepare("INSERT INTO articles (title, slug, category, content, tags, featured_image, author_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssssis", $title, $slug, $category, $content, $tags, $featured_image, $author_id, $status);
 
    if ($stmt->execute()) {
        // Send email notification asynchronously if article is published
        if ($status === 'published') {
            $articleData = [
                'title' => $title,
                'slug' => $slug,
                'category' => $category,
                'content' => $content,
                'tags' => $tags,
                'featured_image' => $featured_image,
                'author_id' => $author_id,
                'status' => $status
            ];

            // Send email in background without blocking the response
            $emailScript = __DIR__ . '/send_email_background.php';
            if (file_exists($emailScript)) {
                // Use exec to run email sending in background (Windows compatible)
                $command = "start /B php \"$emailScript\" \"" . addslashes(json_encode($articleData)) . "\" >nul 2>&1";
                exec($command);
            }
        }

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