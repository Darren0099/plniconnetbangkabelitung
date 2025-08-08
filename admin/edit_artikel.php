<?php
session_start();
require_once 'koneksi.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

// Cek apakah parameter id ada
if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$id = $_GET['id'];

// Ambil data artikel yang akan diedit
$query = "SELECT * FROM articles WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$artikel = mysqli_fetch_assoc($result);

if (!$artikel) {
    $_SESSION['error_message'] = "Artikel tidak ditemukan";
    header("Location: dashboard.php");
    exit();
}

// Jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $category = $_POST['category'];
    
    $update_query = "UPDATE articles SET 
                    title = ?, 
                    content = ?, 
                    category = ?, 
                    updated_at = NOW() 
                    WHERE id = ?";
    $stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($stmt, 'sssi', $title, $content, $category, $id);
    $result = mysqli_stmt_execute($stmt);
    
    if ($result) {
        $_SESSION['success_message'] = "Artikel berhasil diperbarui";
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Gagal memperbarui artikel: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <!-- Head section sama seperti dashboard -->
</head>
<body class="bg-gray-50">
    <div class="flex min-h-screen">
        <!-- Sidebar sama seperti dashboard -->
        
        <div class="flex-1 ml-64 overflow-x-hidden">
            <!-- Header sama seperti dashboard -->
            
            <main class="p-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
                    <h2 class="text-2xl font-bold mb-6">Edit Artikel</h2>
                    
                    <?php if (isset($error)): ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-4">
                            <label class="block text-gray-700 mb-2" for="title">Judul Artikel</label>
                            <input type="text" id="title" name="title" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg" 
                                   value="<?php echo htmlspecialchars($artikel['title']); ?>" required>
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-gray-700 mb-2" for="category">Kategori</label>
                            <select id="category" name="category" class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
                                <option value="teknologi" <?php echo $artikel['category'] == 'teknologi' ? 'selected' : ''; ?>>Teknologi</option>
                                <option value="bisnis" <?php echo $artikel['category'] == 'bisnis' ? 'selected' : ''; ?>>Bisnis</option>
                                <option value="lifestyle" <?php echo $artikel['category'] == 'lifestyle' ? 'selected' : ''; ?>>Lifestyle</option>
                                <option value="kesehatan" <?php echo $artikel['category'] == 'kesehatan' ? 'selected' : ''; ?>>Kesehatan</option>
                                <option value="pendidikan" <?php echo $artikel['category'] == 'pendidikan' ? 'selected' : ''; ?>>Pendidikan</option>
                            </select>
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-gray-700 mb-2" for="content">Isi Artikel</label>
                            <textarea id="content" name="content" rows="10" 
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg" required><?php echo htmlspecialchars($artikel['content']); ?></textarea>
                        </div>
                        
                        <div class="flex justify-end gap-3">
                            <a href="dashboard.php" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300">
                                Batal
                            </a>
                            <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-secondary">
                                Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>
</body>
</html>