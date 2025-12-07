<?php
session_start();
require_once 'koneksi.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$id = $_GET['id'];

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $category = $_POST['category'];
    $slug = $_POST['slug'];
    $use_old_image = isset($_POST['use_old_image']) ? true : false;

    // Handle image upload
    $featured_image = $artikel['featured_image']; // Keep existing image by default

    if (!$use_old_image && isset($_FILES['featuredImage']) && $_FILES['featuredImage']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/articles/';
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0755, true)) {
                $error = "Gagal membuat folder upload.";
            }
        }

        $file_extension = strtolower(pathinfo($_FILES['featuredImage']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $maxFileSize = 5 * 1024 * 1024; // 5MB

        if (!isset($error)) {
            if ($_FILES['featuredImage']['size'] > $maxFileSize) {
                $error = "Ukuran file terlalu besar. Maksimal 5MB";
            } elseif (!in_array($file_extension, $allowed_extensions)) {
                $error = "Format gambar tidak didukung. Gunakan JPG, PNG, GIF, atau WebP";
            } else {
                $new_filename = 'article_' . uniqid() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;

                if (move_uploaded_file($_FILES['featuredImage']['tmp_name'], $upload_path)) {
                    // Delete old image if it exists
                    if (!empty($artikel['featured_image'])) {
                        $old_image_path = __DIR__ . '/' . $artikel['featured_image'];
                        if (file_exists($old_image_path)) {
                            unlink($old_image_path);
                        }
                    }
                    // Save relative path for DB
                    $featured_image = 'admin/uploads/articles/' . $new_filename;
                } else {
                    $error = "Gagal mengupload gambar.";
                }
            }
        }
    }

    // Debugging: Log the featured image path to verify
    error_log("Featured image to save: " . $featured_image);

    if (!isset($error)) {
        $update_query = "UPDATE articles SET
                        title = ?,
                        slug = ?,
                        content = ?,
                        category = ?,
                        featured_image = ?,
                        updated_at = NOW()
                        WHERE id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, 'sssssi', $title, $slug, $content, $category, $featured_image, $id);
        $result = mysqli_stmt_execute($stmt);

        if ($result) {
            $_SESSION['success_message'] = "Artikel berhasil diperbarui";
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Gagal memperbarui artikel: " . mysqli_error($conn);
        }
    } else {
        // Jika ada error, simpan pesan error di session dan jangan update DB
        $_SESSION['error_message'] = $error;
    }
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Artikel - Admin</title>
<script src="https://cdn.tailwindcss.com/3.4.16"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Pacifico&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    .swal2-popup {
      font-family: 'Segoe UI', sans-serif;
    }

    /* Sidebar Animation */
    #sidebar {
        transform: translateX(-100%);
        transition: transform 0.3s ease-in-out;
    }

    #sidebar:not(.hidden) {
        transform: translateX(0);
    }

    /* Burger Menu Animation */
    .burger-menu {
        transition: transform 0.3s ease;
    }

    .burger-menu.active i {
        transform: rotate(90deg);
    }

    /* Navigation Link Animations */
    nav a {
        transition: all 0.2s ease;
        position: relative;
    }

    nav a:hover {
        transform: translateX(4px);
    }

    nav a::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        height: 100%;
        width: 0;
        background: rgba(99, 102, 241, 0.1);
        transition: width 0.3s ease;
        border-radius: 8px;
    }

    nav a:hover::before {
        width: 100%;
    }

    /* Button Animations */
    button {
        transition: all 0.2s ease;
    }

    button:hover {
        transform: translateY(-1px);
    }

    button:active {
        transform: translateY(0);
    }

    /* Form Input Animations */
    input, select, textarea {
        transition: all 0.3s ease;
    }

    input:focus, select:focus, textarea:focus {
        transform: scale(1.01);
    }

    /* Card Animation */
    .card {
        animation: slideInUp 0.5s ease-out;
    }

    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Page Load Animations */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes fadeInLeft {
        from {
            opacity: 0;
            transform: translateX(-30px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    @keyframes fadeInRight {
        from {
            opacity: 0;
            transform: translateX(30px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    .animate-fade-in-up {
        animation: fadeInUp 0.6s ease-out forwards;
        opacity: 0;
    }

    .animate-fade-in-left {
        animation: fadeInLeft 0.6s ease-out forwards;
        opacity: 0;
    }

    .animate-fade-in-right {
        animation: fadeInRight 0.6s ease-out forwards;
        opacity: 0;
    }

    /* Loading Animation */
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    .animate-spin {
        animation: spin 1s linear infinite;
    }

    /* Pulse Animation for Notifications */
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }

    .animate-pulse {
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }
  </style>
<script>
tailwind.config = {
  theme: {
    extend: {
      colors: {
        primary: '#6366F1',
        secondary: '#4F46E5'
      },
      borderRadius: {
        'none': '0px',
        'sm': '4px',
        DEFAULT: '8px',
        'md': '12px',
        'lg': '16px',
        'xl': '20px',
        '2xl': '24px',
        '3xl': '32px',
        'full': '9999px',
        'button': '8px'
      }
    }
  }
}
</script>
<style>
:where([class^="ri-"])::before {
  content: "\f3c2";
}
</style>
</head>
<body class="bg-gray-50">
<div class="flex min-h-screen">
  <!-- Burger Menu for Mobile -->
  <div class="md:hidden fixed top-4 right-4 z-40">
    <button onclick="toggleSidebar()" class="p-2 bg-white rounded-lg shadow-md border border-gray-200">
      <i class="ri-menu-line text-gray-600"></i>
    </button>
  </div>

  <!-- Navigation Bar -->
  <aside id="sidebar" class="fixed left-0 top-0 h-screen w-64 bg-white border-r border-gray-200 z-30 hidden md:block">
    <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-200">
      <div class="text-xl font-['Pacifico'] text-primary">logo</div>
      <span class="font-semibold text-gray-900">APLN</span>
    </div>
    <nav class="p-4 space-y-2">
      <a href="dashboard.php" class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-600 hover:bg-gray-50">
        <div class="w-5 h-5 flex items-center justify-center">
          <i class="ri-home-line"></i>
        </div>
        <span>Dashboard</span>
      </a>
      <a href="tambahartikel.php" class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-600 hover:bg-gray-50">
        <div class="w-5 h-5 flex items-center justify-center">
          <i class="ri-article-line"></i>
        </div>
        <span>Buat Artikel</span>
      </a>
      <a href="user.php" class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-600 hover:bg-gray-50">
        <div class="w-5 h-5 flex items-center justify-center">
          <i class="ri-team-line"></i>
        </div>
        <span>User Management</span>
      </a>
      <a href="profile.php" class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-600 hover:bg-gray-50">
        <div class="w-5 h-5 flex items-center justify-center">
          <i class="ri-user-settings-line"></i>
        </div>
        <span>My Profile</span>
      </a>
    </nav>
    <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-gray-200">
      <a href="logout.php" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-red-50 hover:text-red-600 rounded-lg transition-colors">
        <div class="w-5 h-5 flex items-center justify-center">
          <i class="ri-logout-box-line"></i>
        </div>
        <span>Keluar</span>
      </a>
    </div>
  </aside>

  <!-- Main Content -->
  <div class="flex-1 ml-0 md:ml-64">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b border-gray-200 px-6 py-4">
      <div class="flex items-center justify-between">

        <div class="flex items-center gap-4">
          <button class="relative p-2 text-gray-600 hover:text-primary hover:bg-primary/5 rounded-md transition-colors">
            <div class="w-5 h-5 flex items-center justify-center">
              <i class="ri-notification-line text-lg"></i>
            </div>
            <span class="absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full text-xs text-white flex items-center justify-center">3</span>
          </button>
          <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-primary rounded-full flex items-center justify-center">
              <span class="text-white font-medium"><?php echo strtoupper(substr($_SESSION['user']['username'], 0, 2)); ?></span>
            </div>
            <div class="hidden md:block">
              <div class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($_SESSION['user']['username']); ?></div>
              <div class="text-xs text-gray-500">admin</div>
            </div>
          </div>
        </div>
      </div>
    </header>
            
            <main class="p-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
                    <h2 class="text-2xl font-bold mb-6">Edit Artikel</h2>
                    
                    <?php if (isset($error)): ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-4">
                            <label class="block text-gray-700 mb-2" for="title">Judul Artikel</label>
                            <input type="text" id="title" name="title"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                   value="<?php echo htmlspecialchars($artikel['title']); ?>" required>
                            <input type="hidden" id="slug" name="slug" value="<?php echo htmlspecialchars($artikel['slug']); ?>">
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

                        <div class="mb-4">
                            <label class="block text-gray-700 mb-2">Gambar Utama</label>
<?php if (!empty($artikel['featured_image'])): ?>
    <div id="previewContainer" class="mb-2">
        <p class="text-sm text-gray-600">Gambar saat ini:</p>
        <img id="imagePreview" src="<?php echo htmlspecialchars(getArticleImagePath($artikel['featured_image'])); ?>" alt="Current Image" class="max-w-xs h-auto border border-gray-300 rounded">
    </div>
<?php else: ?>
    <div id="previewContainer" class="mb-2 hidden">
        <img id="imagePreview" alt="Image Preview" class="max-w-xs h-auto border border-gray-300 rounded">
    </div>
<?php endif; ?>
                            <label class="inline-flex items-center mt-2 mb-2">
                                <input type="checkbox" name="use_old_image" id="useOldImage" checked class="form-checkbox text-primary">
                                <span class="ml-2 text-gray-700">Gunakan gambar lama</span>
                            </label>
                            <input type="file" id="featuredImage" name="featuredImage" accept="image/*" class="w-full px-3 py-2 border border-gray-300 rounded-lg" onchange="previewImage(event)">
                            <p class="text-sm text-gray-500 mt-1">Pilih gambar baru untuk mengganti gambar saat ini (opsional)</p>
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
<script>
function generateSlug(text) {
    return text
        .toString()
        .normalize('NFD')                   
        .replace(/[\u0300-\u036f]/g, '')   
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9 -]/g, '')        
        .replace(/\s+/g, '-')               
        .replace(/-+/g, '-');               
}

document.getElementById('title').addEventListener('input', function () {
    const slug = generateSlug(this.value);
    document.getElementById('slug').value = slug;
});
</script>

<script>
document.getElementById('featuredImage').addEventListener('change', function(event) {
    const previewContainer = document.getElementById('previewContainer');
    const imagePreview = document.getElementById('imagePreview');
    const file = event.target.files[0];

    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            imagePreview.src = e.target.result;
            previewContainer.classList.remove('hidden');
        };
        reader.readAsDataURL(file);
    } else {
        imagePreview.src = '';
        previewContainer.classList.add('hidden');
    }
});
</script>

<script>
function previewImage(event) {
    const previewContainer = document.getElementById('previewContainer');
    const imagePreview = document.getElementById('imagePreview');
    const useOldImageCheckbox = document.getElementById('useOldImage');
    const file = event.target.files[0];

    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            imagePreview.src = e.target.result;
            previewContainer.classList.remove('hidden');
            // Uncheck "use old image" when new file is selected
            useOldImageCheckbox.checked = false;
        };
        reader.readAsDataURL(file);
    } else {
        imagePreview.src = '';
        previewContainer.classList.add('hidden');
        // Re-check "use old image" if no file selected
        useOldImageCheckbox.checked = true;
    }
}
</script>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('hidden');
}

// Add page load animation
document.addEventListener('DOMContentLoaded', function() {
    // Animate burger menu
    const burgerMenu = document.querySelector('.md\\:hidden');
    if (burgerMenu) {
        burgerMenu.classList.add('animate-fade-in-right');
        setTimeout(() => burgerMenu.style.animationDelay = '0s', 100);
    }

    // Animate sidebar
    const sidebar = document.getElementById('sidebar');
    if (sidebar) {
        sidebar.classList.add('animate-fade-in-left');
        setTimeout(() => sidebar.style.animationDelay = '0.2s', 100);
    }

    // Animate header
    const header = document.querySelector('header');
    if (header) {
        header.classList.add('animate-fade-in-up');
        setTimeout(() => header.style.animationDelay = '0.4s', 100);
    }

    // Animate main title
    const mainTitle = document.querySelector('main h2');
    if (mainTitle) {
        mainTitle.classList.add('animate-fade-in-up');
        setTimeout(() => mainTitle.style.animationDelay = '0.6s', 100);
    }

    // Animate form container
    const formContainer = document.querySelector('.bg-white.rounded-xl');
    if (formContainer) {
        formContainer.classList.add('animate-fade-in-up');
        setTimeout(() => formContainer.style.animationDelay = '0.8s', 100);
    }

    // Animate form elements
    const formElements = document.querySelectorAll('form > div');
    formElements.forEach((element, index) => {
        element.classList.add('animate-fade-in-up');
        setTimeout(() => element.style.animationDelay = `${1.0 + index * 0.1}s`, 100);
    });

    // Animate buttons
    const buttons = document.querySelectorAll('button, a');
    buttons.forEach((button, index) => {
        button.classList.add('animate-fade-in-up');
        setTimeout(() => button.style.animationDelay = `${1.4 + index * 0.1}s`, 100);
    });
});
</script>
</html>
