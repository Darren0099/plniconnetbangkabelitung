<?php
session_start();
include 'admin/koneksi.php';

function isImage($filename) {
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    return in_array(strtolower($ext), $imageExtensions);
}

$showWelcome = $_SESSION['show_welcome'] ?? false;
$username = $_SESSION['user']['username'] ?? null;
unset($_SESSION['show_welcome']);

// Query slider - tambahkan field slug
$slider_berita = mysqli_query($conn, "SELECT id, slug, title, featured_image, category, content, created_at FROM articles WHERE status = 'published' ORDER BY created_at DESC LIMIT 3");
$slider_data = mysqli_fetch_all($slider_berita, MYSQLI_ASSOC);

$slider_ids = array_column($slider_data, 'id');
$slider_ids_string = !empty($slider_ids) ? implode(',', $slider_ids) : '0';

// Query berita terbaru - tambahkan field slug
$berita_terbaru = mysqli_query($conn, "SELECT id, slug, title, featured_image, category, content, created_at FROM articles WHERE status = 'published' AND id NOT IN ($slider_ids_string) ORDER BY created_at DESC LIMIT 6");

$kategori_query = mysqli_query($conn, "SELECT DISTINCT category FROM articles WHERE status = 'published' LIMIT 3");
$kategori_list = [];
while ($row = mysqli_fetch_assoc($kategori_query)) {
    $kategori_list[] = $row['category'];
}
?>

<!-- Pada bagian headline: -->
<?php 
$headline = mysqli_query($conn, "SELECT id, slug, title, featured_image, category, content, created_at FROM articles WHERE status = 'published' ORDER BY created_at DESC LIMIT 1");
if ($headline_data = mysqli_fetch_assoc($headline)) : 
?>
<a href="artikel.php?slug=<?= urlencode($headline_data['slug']) ?>" class="block group">
    <!-- ... konten lainnya ... -->
</a>
<?php endif; ?>

<!-- Pada bagian berita terbaru: -->
<?php 
$berita_terbaru = mysqli_query($conn, "SELECT id, slug, title, featured_image, category, content, created_at FROM articles WHERE status = 'published' AND id != {$headline_data['id']} ORDER BY created_at DESC LIMIT 3");
while ($row = mysqli_fetch_assoc($berita_terbaru)) : 
?>
<a href="artikel.php?slug=<?= urlencode($row['slug']) ?>" class="block">
    <!-- ... konten lainnya ... -->
</a>
<?php endwhile; ?>

<!-- Pada bagian kategori: -->
<?php 
$kategori_query = mysqli_query($conn, "SELECT DISTINCT category FROM articles WHERE status = 'published' ORDER BY category ASC LIMIT 3");
while ($kategori = mysqli_fetch_assoc($kategori_query)) :
    $category = $kategori['category'];
    $artikel_kategori = mysqli_query($conn, "SELECT id, slug, title, featured_image, created_at FROM articles WHERE status = 'published' AND category = '$category' ORDER BY created_at DESC LIMIT 3");
    while ($row = mysqli_fetch_assoc($artikel_kategori)): 
?>
<a href="artikel.php?slug=<?= urlencode($row['slug']) ?>" class="no-underline">
    <!-- ... konten lainnya ... -->
</a>
<?php endwhile; endwhile; ?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PLN ICONNET</title>
    <script src="https://cdn.tailwindcss.com/3.4.16"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1A3B7A',
                        secondary: '#2563EB'
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Pacifico&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css" rel="stylesheet">
    <style>
        :where([class^="ri-"])::before { content: "\f3c2"; }
           .welcome-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: #4F46E5;
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 10px;
            z-index: 1000;
            animation: slideIn 0.5s ease-out, fadeOut 0.5s ease-in 3s forwards;
        }
        
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }
    </style>
</head>
<body class="bg-white">
    <?php if ($showWelcome && $username): ?>
    <div class="welcome-notification" id="welcomeNotification">
        <i class="ri-checkbox-circle-fill"></i>
        <span>Selamat datang, <?= htmlspecialchars($username) ?>!</span>
    </div>
    <header class="w-full bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center">
                        <div class="w-12 h-12 flex items-center justify-center">
                            <i class="ri-graduation-cap-fill text-primary text-2xl"></i>
                        </div>
                        <span class="ml-3 text-xl font-bold text-primary">PLN-ICONNET</span>
                    </div>
                    <nav class="hidden md:ml-10 md:flex md:space-x-8">
                        <a href="#" class="text-gray-700 hover:text-primary px-3 py-2 text-sm font-medium">Beranda</a>
                        <div class="relative group">
                            <button class="text-gray-700 hover:text-primary px-3 py-2 text-sm font-medium flex items-center">
                                Tentang ICONNET
                                <div class="w-4 h-4 ml-1 flex items-center justify-center">
                                    <i class="ri-arrow-down-s-line text-sm"></i>
                                </div>
                            </button>
                            <div class="absolute left-0 mt-2 w-48 bg-white rounded shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200">
                                <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Sejarah</a>
                                <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Visi & Misi</a>
                                <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Pimpinan</a>
                            </div>
                        </div>
                        <a href="#" class="text-gray-700 hover:text-primary px-3 py-2 text-sm font-medium">Akademik</a>
                    </nav>
                </div>
                <div class="flex items-center space-x-4">
                   <div class="flex items-center space-x-4">
    <div class="relative">
        <input type="text" placeholder="Cari..." class="pl-10 pr-4 py-2 border border-gray-300 rounded-full text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
        <div class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 flex items-center justify-center">
            <i class="ri-search-line text-gray-400 text-sm"></i>
        </div>
    </div>

<div class="user-section">
    <?php if (isset($_SESSION['user'])): ?>
        <span class="username"><?= htmlspecialchars($_SESSION['user']['username']) ?></span>
        <a href="admin/logout.php" class="logout-btn">Logout</a>
    <?php else: ?>
        <a href="admin/index.php" class="login-btn">Login</a>
    <?php endif; ?>
</div>
    
    <div class="flex items-center space-x-2">
        <button class="px-2 py-1 text-sm text-primary font-medium">ID</button>
        <span class="text-gray-300">|</span>
        <button class="px-2 py-1 text-sm text-gray-500">EN</button>
    </div>
    <button class="md:hidden w-8 h-8 flex items-center justify-center">
        <i class="ri-menu-line text-xl"></i>
    </button>
</div>
                </div>
            </div>
        </div>
    </header>

    <section class="relative h-[600px] overflow-hidden">
        <div class="slider-container relative h-full">
            <?php foreach ($slider_data as $index => $slide): ?>
            <div class="slide absolute inset-0 <?= $index === 0 ? 'opacity-100' : 'opacity-0' ?> transition-opacity duration-500" 
                 style="background-image: url('admin/uploads/articles/<?= htmlspecialchars(basename($slide['featured_image'])) ?>'); background-size: cover; background-position: center;">
                <div class="absolute inset-0 bg-gradient-to-t from-primary via-primary/60 to-transparent"></div>
                <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-full flex items-end pb-24">
                    <div class="text-white max-w-3xl">
                        <div class="text-sm font-medium mb-2 opacity-90"><?= htmlspecialchars($slide['category']) ?></div>
                        <h1 class="text-4xl md:text-5xl font-bold mb-4"><?= htmlspecialchars($slide['title']) ?></h1>
                        <p class="text-lg opacity-90 mb-6"><?= substr(strip_tags(htmlspecialchars($slide['content'])), 0, 150) ?>...</p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <button class="absolute left-4 top-1/2 transform -translate-y-1/2 w-12 h-12 flex items-center justify-center bg-white bg-opacity-30 hover:bg-opacity-50 rounded-full text-white transition-all z-10" id="prevSlide">
            <i class="ri-arrow-left-s-line text-2xl"></i>
        </button>
        <button class="absolute right-4 top-1/2 transform -translate-y-1/2 w-12 h-12 flex items-center justify-center bg-white bg-opacity-30 hover:bg-opacity-50 rounded-full text-white transition-all z-10" id="nextSlide">
            <i class="ri-arrow-right-s-line text-2xl"></i>
        </button>
        <div class="absolute bottom-4 left-1/2 transform -translate-x-1/2 flex space-x-2 z-10">
            <?php foreach ($slider_data as $index => $slide): ?>
            <div class="w-3 h-3 rounded-full cursor-pointer <?= $index === 0 ? 'bg-white' : 'bg-white bg-opacity-50' ?>" data-slide="<?= $index ?>"></div>
            <?php endforeach; ?>
        </div>
    </section>

<section class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Berita Utama (Headline) - Gambar di Kiri, Teks di Kanan -->
        <?php 
        $headline = mysqli_query($conn, "SELECT * FROM articles WHERE status = 'published' ORDER BY created_at DESC LIMIT 1");
        if ($headline_data = mysqli_fetch_assoc($headline)) : 
            $image_path = !empty($headline_data['featured_image']) && isImage($headline_data['featured_image']) ? 
                'admin/uploads/articles/'.htmlspecialchars(basename($headline_data['featured_image'])) : 
                'https://via.placeholder.com/1200x600?text=No+Image';
        ?>
        <div class="mb-12 border-b border-gray-200 pb-8">
            <span class="text-sm font-medium text-gray-500 mb-2 block"><?= htmlspecialchars($headline_data['category']) ?></span>
            <a href="artikel.php?slug=<?= urlencode($headline_data['slug']) ?>" class="block group">
                <div class="flex flex-col md:flex-row gap-8">
                    <?php if (!empty($headline_data['featured_image']) && isImage($headline_data['featured_image'])): ?>
                    <div class="md:w-1/3">
                        <img src="<?= $image_path ?>" 
                             alt="<?= htmlspecialchars($headline_data['title']) ?>" 
                             class="w-full h-64 object-cover rounded-lg hover:opacity-90 transition-opacity">
                    </div>
                    <?php endif; ?>
                    <div class="md:w-2/3">
                        <h1 class="text-3xl font-bold text-gray-900 mb-4 group-hover:text-primary transition-colors">
                            <?= htmlspecialchars($headline_data['title']) ?>
                        </h1>
                        <div class="flex items-center text-sm text-gray-500 mb-4">
                            <span><?= date('d F Y, H:i', strtotime($headline_data['created_at'])) ?></span>
                        </div>
                        <p class="text-gray-700 text-lg leading-relaxed">
                            <?= substr(strip_tags(htmlspecialchars($headline_data['content'])), 0, 200) ?>...
                        </p>
                    </div>
                </div>
            </a>
        </div>
        <?php endif; ?>

        <div class="flex items-center justify-between mb-8">
            <h2 class="text-2xl font-bold text-gray-900">Berita Terbaru</h2>
            <a href="berita.php" class="text-primary hover:text-blue-700 font-medium flex items-center">
                Lihat Semua
                <i class="ri-arrow-right-line ml-1"></i>
            </a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <?php 
            $berita_terbaru = mysqli_query($conn, "SELECT * FROM articles WHERE status = 'published' AND id != {$headline_data['id']} ORDER BY created_at DESC LIMIT 3");
            while ($row = mysqli_fetch_assoc($berita_terbaru)) : 
                $image_path = !empty($row['featured_image']) && isImage($row['featured_image']) ? 
                    'admin/uploads/articles/'.htmlspecialchars(basename($row['featured_image'])) : 
                    'https://via.placeholder.com/600x400?text=No+Image';
            ?>
            <div class="group">
                <a href="artikel.php?slug=<?= urlencode($row['slug']) ?>" class="block">
                    <?php if (!empty($row['featured_image']) && isImage($row['featured_image'])): ?>
                    <div class="relative overflow-hidden rounded-lg mb-4 h-48">
                        <img src="<?= $image_path ?>" 
                             alt="<?= htmlspecialchars($row['title']) ?>" 
                             class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105">
                    </div>
                    <?php endif; ?>
                    <div class="space-y-3">
                        <span class="text-sm font-medium text-gray-500"><?= htmlspecialchars($row['category']) ?></span>
                        <h3 class="text-xl font-semibold text-gray-900 group-hover:text-primary transition-colors">
                            <?= htmlspecialchars($row['title']) ?>
                        </h3>
                        <p class="text-gray-600">
                            <?= substr(strip_tags(htmlspecialchars($row['content'])), 0, 100) ?>...
                        </p>
                        <span class="text-sm text-gray-500 block">
                            <?= date('d F Y, H:i', strtotime($row['created_at'])) ?>
                        </span>
                    </div>
                </a>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</section>

<section class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <?php 
            $kategori_query = mysqli_query($conn, "SELECT DISTINCT category FROM articles WHERE status = 'published' ORDER BY category ASC LIMIT 3");
            
            while ($kategori = mysqli_fetch_assoc($kategori_query)) :
                $category = $kategori['category'];
                $artikel_kategori = mysqli_query($conn, "SELECT * FROM articles WHERE status = 'published' AND category = '$category' ORDER BY created_at DESC LIMIT 3");
            ?>
            <div>
                <h2 class="text-xl font-bold text-primary border-b-2 border-primary inline-block mb-6">
                    <?= htmlspecialchars($category) ?>
                </h2>
                
                <div class="space-y-6">
                    <?php 
                    $count = 0;
                    while ($row = mysqli_fetch_assoc($artikel_kategori)): 
                        $count++;
                        $image_path = !empty($row['featured_image']) && isImage($row['featured_image']) ? 
                            'admin/uploads/articles/'.htmlspecialchars(basename($row['featured_image'])) : 
                            'https://via.placeholder.com/600x400?text=No+Image';
                    ?>
                    <?php if ($count === 1): ?>
                    <article>
                        <a href="artikel.php?slug=<?= urlencode($row['slug']) ?>" class="no-underline">
                            <img src="<?= $image_path ?>" 
                                 alt="<?= htmlspecialchars($row['title']) ?>" 
                                 class="w-full h-48 object-cover rounded-lg mb-4 hover:opacity-90 transition-opacity">
                            <h3 class="font-semibold text-gray-900 mb-2 hover:text-primary transition-colors">
                                <?= htmlspecialchars($row['title']) ?>
                            </h3>
                            <span class="text-sm text-gray-500">
                                <?= date('d M Y, H:i', strtotime($row['created_at'])) ?>
                            </span>
                        </a>
                    </article>
                    <?php else: ?>
                    <article class="flex items-start space-x-4 border-t pt-4">
                        <a href="artikel.php?id=<?= $row['id'] ?>" class="flex items-start space-x-4 w-full no-underline">
                            <img src="<?= $image_path ?>" 
                                 alt="<?= htmlspecialchars($row['title']) ?>" 
                                 class="w-20 h-16 object-cover rounded hover:opacity-90 transition-opacity">
                            <div class="flex-1">
                                <h3 class="text-sm font-medium text-gray-900 hover:text-primary transition-colors">
                                    <?= htmlspecialchars(substr($row['title'], 0, 60)) ?>...
                                </h3>
                                <span class="text-xs text-gray-500">
                                    <?= date('d M Y, H:i', strtotime($row['created_at'])) ?>
                                </span>
                            </div>
                        </a>
                    </article>
                    <?php endif; ?>
                    <?php endwhile; ?>
                </div>
                <a href="kategori.php?category=<?= urlencode($category) ?>" class="mt-4 inline-flex items-center text-primary hover:text-primary/80 transition-colors no-underline">
                    Lihat Semua
                    <i class="ri-arrow-right-line ml-1"></i>
                </a>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</section>

    <footer class="bg-primary text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 flex items-center justify-center">
                            <i class="ri-graduation-cap-fill text-white text-2xl"></i>
                        </div>
                        <span class="ml-3 text-xl font-bold">PLN ICONNET</span>
                    </div>
                    <p class="text-blue-100 mb-4">Kualitas dan kecepatan yang terus bertambah menjadi kebutuhan baik di rumah, kantor, dan banyak tempat lainnya membuat kami memberikan layanan terbaik untuk anda.</p>
                    <div class="flex space-x-4">
                        <a href="#" class="w-8 h-8 flex items-center justify-center bg-white bg-opacity-20 rounded hover:bg-opacity-30 transition-colors">
                            <i class="ri-facebook-fill"></i>
                        </a>
                        <a href="#" class="w-8 h-8 flex items-center justify-center bg-white bg-opacity-20 rounded hover:bg-opacity-30 transition-colors">
                            <i class="ri-twitter-fill"></i>
                        </a>
                        <a href="#" class="w-8 h-8 flex items-center justify-center bg-white bg-opacity-20 rounded hover:bg-opacity-30 transition-colors">
                            <i class="ri-instagram-fill"></i>
                        </a>
                        <a href="#" class="w-8 h-8 flex items-center justify-center bg-white bg-opacity-20 rounded hover:bg-opacity-30 transition-colors">
                            <i class="ri-youtube-fill"></i>
                        </a>
                    </div>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4">Kontak</h3>
                    <div class="space-y-3 text-blue-100">
                        <div class="flex items-start space-x-3">
                            <div class="w-5 h-5 flex items-center justify-center mt-0.5">
                                <i class="ri-map-pin-line text-sm"></i>
                            </div>
                            <span class="text-sm">Jl. Demang Lebar Daun No.375, 20 Ilir D. IV, Kec. Ilir Tim. I, Kota Palembang, Sumatera Selatan 30131</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <div class="w-5 h-5 flex items-center justify-center">
                                <i class="ri-phone-line text-sm"></i>
                            </div>
                            <span class="text-sm">(0274) 588688</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <div class="w-5 h-5 flex items-center justify-center">
                                <i class="ri-mail-line text-sm"></i>
                            </div>
                            <span class="text-sm">info@PLN.ac.id</span>
                        </div>
                    </div>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4">Tautan Cepat</h3>
                    <ul class="space-y-2 text-blue-100">
                        <li><a href="#" class="text-sm hover:text-white transition-colors">Portal Akademik</a></li>
                        <li><a href="#" class="text-sm hover:text-white transition-colors">E-Learning</a></li>
                        <li><a href="#" class="text-sm hover:text-white transition-colors">Perpustakaan Digital</a></li>
                        <li><a href="#" class="text-sm hover:text-white transition-colors">Jurnal Elektronik</a></li>
                        <li><a href="#" class="text-sm hover:text-white transition-colors">Sistem Informasi</a></li>
                        <li><a href="#" class="text-sm hover:text-white transition-colors">Career Center</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4">Newsletter</h3>
                    <p class="text-blue-100 text-sm mb-4">Dapatkan informasi terbaru dari UGM langsung di email Anda</p>
                    <div class="flex">
                        <input type="email" placeholder="Email Anda" class="flex-1 px-4 py-2 text-gray-900 rounded-l focus:outline-none focus:ring-2 focus:ring-white">
                        <button class="bg-white text-primary px-4 py-2 rounded-r hover:bg-gray-100 transition-colors !rounded-button whitespace-nowrap">
                            <div class="w-5 h-5 flex items-center justify-center">
                                <i class="ri-send-plane-line"></i>
                            </div>
                        </button>
                    </div>
                </div>
            </div>
            <div class="border-t border-blue-600 mt-8 pt-8 flex flex-col md:flex-row justify-between items-center">
                <p class="text-blue-100 text-sm">© 2025 PLN ICONNET. All Right Reserved.</p>
                <div class="flex space-x-6 mt-4 md:mt-0">
                    <a href="#" class="text-blue-100 text-sm hover:text-white transition-colors">Kebijakan Privasi</a>
                    <a href="#" class="text-blue-100 text-sm hover:text-white transition-colors">Syarat & Ketentuan</a>
                    <a href="#" class="text-blue-100 text-sm hover:text-white transition-colors">Sitemap</a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
           
            const menuButton = document.querySelector('[class*="md:hidden"]');
            const mobileMenu = document.createElement('div');
            mobileMenu.className = 'md:hidden fixed inset-0 bg-white z-50 transform translate-x-full transition-transform duration-300';
            mobileMenu.innerHTML = `
                <div class="p-4">
                    <div class="flex justify-between items-center mb-8">
                        <span class="text-xl font-bold text-primary">UGM</span>
                        <button id="close-menu" class="w-8 h-8 flex items-center justify-center">
                            <i class="ri-close-line text-xl"></i>
                        </button>
                    </div>
                    <nav class="space-y-4">
                        <a href="#" class="block text-gray-700 hover:text-primary py-2">Beranda</a>
                        <a href="#" class="block text-gray-700 hover:text-primary py-2">Tentang UGM</a>
                        <a href="#" class="block text-gray-700 hover:text-primary py-2">Akademik</a>
                        <a href="#" class="block text-gray-700 hover:text-primary py-2">Penelitian</a>
                        <a href="#" class="block text-gray-700 hover:text-primary py-2">Kemahasiswaan</a>
                        <a href="#" class="block text-gray-700 hover:text-primary py-2">Alumni</a>
                    </nav>
                </div>
            `;
            document.body.appendChild(mobileMenu);
            
            menuButton.addEventListener('click', function() {
                mobileMenu.classList.remove('translate-x-full');
            });
            
            document.getElementById('close-menu').addEventListener('click', function() {
                mobileMenu.classList.add('translate-x-full');
            });

            const slides = document.querySelectorAll('.slide');
            const indicators = document.querySelectorAll('[data-slide]');
            const prevButton = document.getElementById('prevSlide');
            const nextButton = document.getElementById('nextSlide');
            let currentSlide = 0;
            let isAnimating = false;
            
            function updateSlider(newIndex) {
                if (isAnimating) return;
                isAnimating = true;
                
                slides[currentSlide].classList.remove('opacity-100');
                slides[currentSlide].classList.add('opacity-0');
                indicators[currentSlide].classList.add('bg-opacity-50');
                indicators[currentSlide].classList.remove('bg-opacity-100');
                
                currentSlide = newIndex;
                
                slides[currentSlide].classList.remove('opacity-0');
                slides[currentSlide].classList.add('opacity-100');
                indicators[currentSlide].classList.remove('bg-opacity-50');
                indicators[currentSlide].classList.add('bg-opacity-100');
                
                setTimeout(() => {
                    isAnimating = false;
                }, 500);
            }
            
            function nextSlide() {
                const newIndex = (currentSlide + 1) % slides.length;
                updateSlider(newIndex);
            }
            
            function prevSlide() {
                const newIndex = (currentSlide - 1 + slides.length) % slides.length;
                updateSlider(newIndex);
            }
            
            prevButton.addEventListener('click', prevSlide);
            nextButton.addEventListener('click', nextSlide);
            
            indicators.forEach((indicator, index) => {
                indicator.addEventListener('click', () => {
                    if (currentSlide !== index) {
                        updateSlider(index);
                    }
                });
            });
            
            let autoplayInterval = setInterval(nextSlide, 5000);
            
            const sliderContainer = document.querySelector('.slider-container');
            sliderContainer.addEventListener('mouseenter', () => {
                clearInterval(autoplayInterval);
            });
            
            sliderContainer.addEventListener('mouseleave', () => {
                autoplayInterval = setInterval(nextSlide, 5000);
            });
        });
    </script>

      <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Hilangkan notifikasi setelah animasi selesai
            const notification = document.getElementById('welcomeNotification');
            if (notification) {
                notification.addEventListener('animationend', function(e) {
                    if (e.animationName === 'fadeOut') {
                        notification.remove();
                    }
                });
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>