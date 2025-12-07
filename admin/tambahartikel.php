<?php
session_start();
require 'functions.php';

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

$user = $_SESSION['user'];
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Buat Artikel - Admin</title>
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
      <a href="dashboard.php" class="flex items-center gap-3 px-3 py-2 rounded-lg <?= ($current_page == 'dashboard.php') ? 'text-primary bg-primary/10' : 'text-gray-600 hover:bg-gray-50' ?>">
        <div class="w-5 h-5 flex items-center justify-center">
          <i class="ri-home-line"></i>
        </div>
        <span>Dashboard</span>
      </a>
      <a href="tambahartikel.php" class="flex items-center gap-3 px-3 py-2 rounded-lg <?= ($current_page == 'tambahartikel.php') ? 'text-primary bg-primary/10' : 'text-gray-600 hover:bg-gray-50' ?>">
        <div class="w-5 h-5 flex items-center justify-center">
          <i class="ri-article-line"></i>
        </div>
        <span>Buat Artikel</span>
      </a>
      <a href="user.php" class="flex items-center gap-3 px-3 py-2 rounded-lg <?= ($current_page == 'user.php') ? 'text-primary bg-primary/10' : 'text-gray-600 hover:bg-gray-50' ?>">
        <div class="w-5 h-5 flex items-center justify-center">
          <i class="ri-team-line"></i>
        </div>
        <span>User Management</span>
      </a>
      <a href="profile.php" class="flex items-center gap-3 px-3 py-2 rounded-lg <?= ($current_page == 'profile.php') ? 'text-primary bg-primary/10' : 'text-gray-600 hover:bg-gray-50' ?>">
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
              <span class="text-white font-medium"><?= strtoupper(substr($user['username'], 0, 2)) ?></span>
            </div>
            <div class="hidden md:block">
              <div class="text-sm font-medium text-gray-800"><?= htmlspecialchars($user['username']) ?></div>
              <div class="text-xs text-gray-500">admin</div>
            </div>
          </div>
        </div>
      </div>
    </header>

    <!-- Main Content Area -->
    <main class="p-8">
      <!-- Header -->
      <header class="flex items-center justify-between mb-8">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Buat Artikel</h1>
          <p class="text-gray-600 mt-1">Buat Artikel/Berita terbaru</p>
        </div>
      </header>

      <!-- Article Form -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <form id="articleForm" action="save_article.php" method="POST" enctype="multipart/form-data">
  <!-- Title Input -->
  <div class="mb-6">
    <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Judul Artikel</label>
    <input type="text" id="title" name="title" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary" placeholder="Masukkan judul artikel" required maxlength="100" oninput="generateSlug()">
</div>

<!-- Slug (akan diisi otomatis) -->
<div class="mb-6">
    <label for="slug" class="block text-sm font-medium text-gray-700 mb-2">Slug URL</label>
    <input type="text" id="slug" name="slug" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary bg-gray-50" placeholder="Slug akan dibuat otomatis" readonly>
  </div>

  <!-- Category Selection -->
  <div class="mb-6">
    <label for="category" class="block text-sm font-medium text-gray-700 mb-2">Kategori</label>
    <div class="relative">
      <select id="category" name="category" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary appearance-none pr-10" required>
        <option value="">Pilih kategori</option>
        <option value="teknologi">Teknologi</option>
        <option value="bisnis">Bisnis</option>
        <option value="lifestyle">Lifestyle</option>
        <option value="kesehatan">Kesehatan</option>
        <option value="pendidikan">Pendidikan</option>
      </select>
      <div class="absolute right-3 top-1/2 transform -translate-y-1/2 w-4 h-4 flex items-center justify-center pointer-events-none">
        <i class="ri-arrow-down-s-line text-gray-400"></i>
      </div>
    </div>
  </div>

  <!-- Featured Image -->
  <div class="mb-6">
    <label class="block text-sm font-medium text-gray-700 mb-2">Gambar Utama</label>
    <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-primary transition-colors">
      <div id="previewContainer" class="hidden mb-4">
        <img id="imagePreview" class="max-h-48 mx-auto rounded-lg" alt="Preview">
      </div>
      <div id="uploadPrompt">
        <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center mx-auto mb-4">
          <i class="ri-image-add-line text-gray-500 text-2xl"></i>
        </div>
        <p class="text-sm text-gray-600 mb-2">Drag & drop gambar atau</p>
        <input type="file" id="featuredImage" name="featuredImage" accept="image/*" class="hidden">
        <button type="button" onclick="document.getElementById('featuredImage').click()" class="text-primary font-medium text-sm hover:underline">pilih file</button>
        <p class="text-xs text-gray-500 mt-2">PNG, JPG, GIF (Max. 10MB)</p>
      </div>
    </div>
  </div>

  <!-- Article Content -->
  <div class="mb-6">
    <label for="content" class="block text-sm font-medium text-gray-700 mb-2">Isi Artikel</label>
    <textarea id="content" name="content" rows="12" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary resize-none" placeholder="Tulis isi artikel di sini..." required maxlength="5000"></textarea>
    <div class="mt-2 text-right">
      <span id="charCount" class="text-sm text-gray-500">0/5000 karakter</span>
    </div>
  </div>

  <!-- Tags Input -->
  <div class="mb-6">
    <label for="tags" class="block text-sm font-medium text-gray-700 mb-2">Tags</label>
    <input type="text" id="tags" name="tags" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary" placeholder="Masukkan tags (pisahkan dengan koma)">
  </div>

  <!-- Submit Buttons -->
<div class="flex items-center justify-end space-x-4">
  <!-- Tombol Simpan Draft -->
  <button type="button" class="px-6 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50 transition-colors !rounded-button whitespace-nowrap">
    Simpan Draft
  </button>

  <!-- Tombol Publikasikan -->
  <button type="submit" class="px-6 py-2 bg-primary text-white rounded-lg text-sm hover:bg-primary/90 transition-colors !rounded-button whitespace-nowrap">
    Publikasikan
  </button>
</div>
</form>
      </div>
    </main>
  </div>
</div>
 <script>
  function generateSlug() {
    const title = document.getElementById('title').value;
    // Simple slug generation (you can enhance this)
    let slug = title.toLowerCase()
                   .replace(/[^\w\s]/gi, '')
                   .replace(/\s+/g, '-')
                   .replace(/-+/g, '-')
                   .trim();
    document.getElementById('slug').value = slug;
}
    document.addEventListener('DOMContentLoaded', function() {
  // Form elements
  const form = document.getElementById('articleForm');
  const uploadZone = document.querySelector('.border-dashed');
  const imageInput = document.getElementById('featuredImage');
  const previewContainer = document.getElementById('previewContainer');
  const imagePreview = document.getElementById('imagePreview');
  const uploadPrompt = document.getElementById('uploadPrompt');
  const contentTextarea = document.getElementById('content');
  const charCount = document.getElementById('charCount');
  const draftButton = document.querySelector('button[type="button"]'); // Tombol Simpan Draft
  const publishButton = document.querySelector('button[type="submit"]'); // Tombol Publikasikan

  // Image upload handling
  uploadZone.addEventListener('dragover', function(e) {
    e.preventDefault();
    this.classList.add('border-primary', 'bg-primary/5');
  });

  uploadZone.addEventListener('dragleave', function(e) {
    e.preventDefault();
    this.classList.remove('border-primary', 'bg-primary/5');
  });

  uploadZone.addEventListener('drop', function(e) {
    e.preventDefault();
    this.classList.remove('border-primary', 'bg-primary/5');
    const files = e.dataTransfer.files;
    if (files.length > 0 && files[0].type.startsWith('image/')) {
      handleImageUpload(files[0]);
    }
  });

  imageInput.addEventListener('change', function(e) {
    if (this.files && this.files[0]) {
      handleImageUpload(this.files[0]);
    }
  });

  function handleImageUpload(file) {
    if (file.size > 10 * 1024 * 1024) {
      Swal.fire({
        icon: 'error',
        title: 'Ukuran file terlalu besar',
        text: 'Maksimal ukuran file 10MB'
      });
      return;
    }

    const reader = new FileReader();
    reader.onload = function(e) {
      imagePreview.src = e.target.result;
      previewContainer.classList.remove('hidden');
      uploadPrompt.classList.add('hidden');
    };
    reader.readAsDataURL(file);
  }

  // Character count for content
  contentTextarea.addEventListener('input', function() {
    const count = this.value.length;
    charCount.textContent = `${count}/5000 karakter`;
    if (count > 5000) {
      this.value = this.value.substring(0, 5000);
      charCount.textContent = "5000/5000 karakter";
    }
  });

  // Handle draft button click
  draftButton.addEventListener('click', function(e) {
    e.preventDefault(); // Mencegah form submit default

    // Validasi minimal
    if (!form.querySelector('#title').value || !form.querySelector('#category').value) {
      Swal.fire({
        icon: 'error',
        title: 'Form tidak lengkap',
        text: 'Judul dan kategori harus diisi untuk menyimpan draft'
      });
      return;
    }

    // Konfirmasi simpan draft
    Swal.fire({
      title: 'Simpan Draft?',
      text: "Apakah Anda yakin ingin menyimpan draft artikel ini?",
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#4F46E5',
      cancelButtonColor: '#6B7280',
      confirmButtonText: 'Ya, Simpan Draft',
      cancelButtonText: 'Batal'
    }).then((result) => {
      if (result.isConfirmed) {
        submitForm('draft', draftButton);
      }
    });
  });

  // Handle publish button click
  publishButton.addEventListener('click', function(e) {
    e.preventDefault(); // Mencegah form submit default

    // Validasi lengkap
    if (!form.querySelector('#title').value ||
        !form.querySelector('#category').value ||
        !form.querySelector('#content').value) {
      Swal.fire({
        icon: 'error',
        title: 'Form tidak lengkap',
        text: 'Judul, kategori, dan konten harus diisi untuk publikasi'
      });
      return;
    }

    submitForm('published', publishButton);
  });

  // Fungsi untuk submit form
  function submitForm(status, buttonElement) {
    const formData = new FormData(form);
    formData.append('status', status);

    const originalButtonText = buttonElement.innerHTML;

    // Tampilkan loading state
    buttonElement.disabled = true;
    buttonElement.innerHTML = '<i class="ri-loader-4-line animate-spin"></i> Memproses...';

    // Kirim data ke server
    fetch('save_article.php', {
      method: 'POST',
      body: formData
    })
    .then(response => {
      if (!response.ok) {
        throw new Error('Network response was not ok');
      }
      return response.json();
    })
    .then(data => {
      if (data.success) {
        Swal.fire({
          icon: 'success',
          title: 'Berhasil!',
          text: status === 'draft' ? 'Draft berhasil disimpan' : 'Artikel berhasil dipublikasikan',
          timer: 2000,
          showConfirmButton: false
        }).then(() => {
          resetForm();
          window.location.href = 'dashboard.php';
        });
      } else {
        throw new Error(data.message || 'Gagal menyimpan artikel');
      }
    })
    .catch(error => {
      Swal.fire({
        icon: 'error',
        title: 'Gagal!',
        text: error.message || 'Terjadi kesalahan saat menyimpan artikel'
      });
    })
    .finally(() => {
      // Kembalikan tombol ke state semula
      buttonElement.disabled = false;
      buttonElement.innerHTML = originalButtonText;
    });
  }

  // Reset form
  function resetForm() {
    form.reset();
    previewContainer.classList.add('hidden');
    uploadPrompt.classList.remove('hidden');
    charCount.textContent = "0/5000 karakter";
    imagePreview.src = '';
  }
});
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
    const mainTitle = document.querySelector('main h1');
    if (mainTitle) {
        mainTitle.classList.add('animate-fade-in-up');
        setTimeout(() => mainTitle.style.animationDelay = '0.6s', 100);
    }

    // Animate main description
    const mainDesc = document.querySelector('main p');
    if (mainDesc) {
        mainDesc.classList.add('animate-fade-in-up');
        setTimeout(() => mainDesc.style.animationDelay = '0.8s', 100);
    }

    // Animate form container
    const formContainer = document.querySelector('.bg-white.rounded-lg');
    if (formContainer) {
        formContainer.classList.add('animate-fade-in-up');
        setTimeout(() => formContainer.style.animationDelay = '1.0s', 100);
    }

    // Animate form elements
    const formElements = document.querySelectorAll('form > div');
    formElements.forEach((element, index) => {
        element.classList.add('animate-fade-in-up');
        setTimeout(() => element.style.animationDelay = `${1.2 + index * 0.1}s`, 100);
    });

    // Animate buttons
    const buttons = document.querySelectorAll('button');
    buttons.forEach((button, index) => {
        button.classList.add('animate-fade-in-up');
        setTimeout(() => button.style.animationDelay = `${1.6 + index * 0.1}s`, 100);
    });
});
</script>
</body>
</html>
