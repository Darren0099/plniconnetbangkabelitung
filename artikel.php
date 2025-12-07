<?php
include 'admin/koneksi.php';

// Fungsi untuk cek apakah file adalah gambar
function isImage($filename) {
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    return in_array(strtolower($ext), $imageExtensions);
}

// Fungsi untuk buat slug dari judul
function generateSlug($text) {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/i', '-', $text);
    $text = preg_replace('/-+/', '-', $text);
    return rtrim($text, '-');
}

// Ambil artikel berdasarkan slug
if (!isset($_GET['slug'])) {
    echo "Artikel tidak ditemukan!";
    exit;
}

$slug = mysqli_real_escape_string($conn, $_GET['slug']);

$query = mysqli_query($conn, "
    SELECT a.*, u.username AS author_name 
    FROM articles a
    LEFT JOIN user u ON a.author_id = u.id
    WHERE a.slug = '$slug' AND a.status = 'published'
");

if (mysqli_num_rows($query) === 0) {
    echo "Artikel tidak ditemukan atau belum dipublikasikan!";
    exit;
}

$data = mysqli_fetch_assoc($query);
$kategori = $data['category'];
$artikel_slug = generateSlug($data['title']);

// Rekomendasi artikel lain dari kategori sama
$rekomendasi = mysqli_query($conn, "
    SELECT id, title, slug, created_at FROM articles 
    WHERE category = '$kategori' AND slug != '$slug' AND status = 'published' 
    ORDER BY created_at DESC 
    LIMIT 4
");

// Previous post in same category
$previous_post = mysqli_query($conn, "
    SELECT id, title, slug FROM articles 
    WHERE category = '$kategori' AND created_at < '{$data['created_at']}' AND status = 'published' 
    ORDER BY created_at DESC 
    LIMIT 1
");

// Next post in same category
$next_post = mysqli_query($conn, "
    SELECT id, title, slug FROM articles 
    WHERE category = '$kategori' AND created_at > '{$data['created_at']}' AND status = 'published' 
    ORDER BY created_at ASC 
    LIMIT 1
");

$kategori_query = mysqli_query($conn, "
    SELECT DISTINCT category FROM articles WHERE status = 'published'
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($data['title']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet"/>

    <style>
       
        header {
            background-color: #ffffff;
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: relative;
            z-index: 1000;
        }
        
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
            flex-wrap: wrap;
        }
        
        .logo {
            flex: 0 0 auto;
        }
        
        .logo img {
            height: 40px;
            width: auto;
        }
        
        .search-container {
            flex: 1 1 auto;
            max-width: 500px;
            margin: 0 20px;
            position: relative;
        }
        
        .search-container input {
            width: 100%;
            padding: 8px 15px 8px 35px;
            border-radius: 20px;
            border: 1px solid #ddd;
            font-size: 14px;
            background-color: #f8f9fa;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            box-sizing: border-box;
            display: block;
        }

        /* Hide search container on small screens by default */
        @media (max-width: 768px) {
            .search-container {
                display: none;
                position: absolute;
                top: 60px;
                right: 15px;
                width: calc(100% - 30px);
                max-width: 300px;
                background: white;
                padding: 10px;
                border-radius: 8px;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                z-index: 1100;
            }
        }

        .search-container input:focus {
            border-color: #005baa;
            box-shadow: 0 0 0 2px rgba(0, 91, 170, 0.1);
            outline: none;
        }

        .search-container i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #777;
            pointer-events: none;
            font-size: 16px;
            line-height: 1;
            height: 100%;
            display: flex;
            align-items: center;
        }

        #searchResults {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #e5e7eb;
            border-top: none;
            border-radius: 0 0 8px 8px;
        }

        #searchResults a {
            padding: 12px 16px;
            display: block;
            text-decoration: none;
            color: #374151;
            border-bottom: 1px solid #f3f4f6;
            transition: background-color 0.2s ease;
        }

        #searchResults a:last-child {
            border-bottom: none;
        }

        #searchResults a:hover {
            background-color: #f9fafb;
            color: #005baa;
        }

        #searchResults .font-medium {
            font-weight: 500;
            margin-bottom: 4px;
        }

        #searchResults .text-xs {
            font-size: 12px;
            color: #6b7280;
        }
        
        .right-controls {
            display: flex;
            align-items: center;
            gap: 15px;
            flex: 0 0 auto;
        }
        
        .lang-buttons {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .lang-buttons button {
            background: none;
            border: none;
            color: #005baa;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            padding: 5px;
        }
        
        .lang-buttons span {
            color: #ccc;
        }
        
        #burger-btn {
            background: none;
            border: none;
            color: #005baa;
            font-size: 24px;
            cursor: pointer;
            /* display: none; */ /* Make burger button visible on all screen sizes */
            padding: 5px;
        }
        
        #mobile-menu {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 280px;
            background-color: white;
            box-shadow: 2px 0 8px rgba(0,0,0,0.15);
            border-radius: 0 0 5px 5px;
            z-index: 1200;
            padding: 15px;
            transform: translateX(-100%);
            transition: transform 0.3s ease;
        }
        
        #mobile-menu.open, #mobile-menu.mobile-menu-open {
            display: flex;
            transform: translateX(0);
        }
        
        #mobile-menu a {
            display: block;
            color: #333;
            text-decoration: none;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        
        #mobile-menu a:last-child {
            border-bottom: none;
        }
        
        /* Main Content */
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 15px;
        }
        
        .back-btn {
            margin-bottom: 20px;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .search-container {
                order: 3;
                width: 100%;
                max-width: 100%;
                margin: 15px 0 0 0;
            }
        }
        
        @media (max-width: 768px) {
            .header-container {
                gap: 10px;
            }
            
            .logo {
                order: 1;
            }
            
            .right-controls {
                order: 2;
                margin-left: auto;
            }
            
            .search-container {
                order: 3;
            }
            
            /* Remove display block for burger-btn here to keep it visible on all sizes */
            /* #burger-btn {
                display: block;
            } */
            
            .lang-buttons {
                display: none;
            }
        }

        /* Footer Styles */
        .footer {
            background: #f5f5f5;
            padding: 40px 20px;
            margin-top: 50px;
            border-top: 1px solid #ddd;
        }
        
        .social-icons i {
            font-size: 20px;
            margin: 0 10px;
            color: #333;
        }
        
        .footer .category-block {
            text-align: left;
        }
        
        .footer .category-block h6 {
            font-weight: bold;
            margin-bottom: 8px;
        }
        
        .footer .category-block ul {
            padding-left: 18px;
            margin-bottom: 20px;
        }
    </style>

    <style>
        .rounded-corners {
            border-radius: 8px;
        }
    </style>
</head>
<body>

<header>
    <div class="header-container">
        <div class="logo">
            <a href="index.php">
                <img src="logo/Logo PLN ICON.png" alt="PLN ICONNET Logo" />
            </a>
        </div>

        <div class="search-container" id="search-container">
            <input id="searchInput" type="text" placeholder="Cari artikel...">
            <i class="ri-search-line"></i>
            <div id="searchResults" class="absolute top-full left-0 w-full bg-white shadow-lg rounded-md mt-1 hidden z-50">
                <!-- hasil pencarian muncul di sini -->
            </div>
        </div>

        <div class="right-controls">
            <div class="lang-buttons">
                <button id="btn-id">ID</button>
                <span>|</span>
                <button id="btn-en">EN</button>
            </div>

            <button id="burger-btn" aria-label="Toggle Menu" style="margin-right: 10px;">
                <i class="ri-menu-line"></i>
            </button>
            <button id="search-toggle-btn" aria-label="Toggle Search"
                    style="display:none; background:none; border:none; color:#005baa; font-size:24px; cursor:pointer; padding:5px; margin-left:10px; z-index: 1101; position: relative;">
                <i class="ri-more-2-fill"></i>
            </button>
        </div>
</header>

<nav id="mobile-menu" class="mobile-menu-closed">
    <div class="mobile-menu-header d-flex align-items-center justify-content-between p-3 border-bottom">
        <a href="index.php" class="mobile-menu-logo d-flex align-items-center">
            <img src="logo/Logo PLN ICON.png" alt="PLN ICONNET Logo" style="height: 40px; width: auto;">
        </a>
        <button id="mobile-menu-close-btn" aria-label="Close Menu" style="background:none; border:none; font-size:24px; cursor:pointer;">
            <i class="ri-close-line"></i>
        </button>
    </div>
    <div class="mobile-menu-lang p-3 border-bottom d-flex gap-2">
        <button id="btn-id-mobile" class="btn btn-link p-0">ID</button>
        <span>|</span>
        <button id="btn-en-mobile" class="btn btn-link p-0">EN</button>
    </div>
    <ul class="mobile-menu-list list-unstyled p-3 mb-0">
        <li><a href="index.php" class="d-block py-2">Beranda</a></li>
        <li><a href="#" class="d-block py-2">Tentang ICONNET</a></li>
        <li><a href="#" class="d-block py-2">Sejarah</a></li>
        <li><a href="#" class="d-block py-2">Visi & Misi</a></li>
        <li><a href="#" class="d-block py-2">Pimpinan</a></li>
    </ul>
</nav>

<style>
    /* Mobile menu styles */
    #mobile-menu {
        position: fixed;
        top: 0;
        left: 0;
        height: 100vh;
        width: 280px;
        background: white;
        box-shadow: 2px 0 8px rgba(0,0,0,0.15);
        transform: translateX(-100%);
        transition: transform 0.3s ease;
        z-index: 1200;
        display: flex;
        flex-direction: column;
    }
    #mobile-menu.mobile-menu-open {
        transform: translateX(0);
    }
    /* Slide main content when menu open */
    .content-shifted {
        transform: translateX(280px);
        transition: transform 0.3s ease;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const burgerBtn = document.getElementById('burger-btn');
    const mobileMenu = document.getElementById('mobile-menu');
    const mobileMenuCloseBtn = document.getElementById('mobile-menu-close-btn');
    const mainContent = document.querySelector('.container.mt-4');

    burgerBtn.addEventListener('click', () => {
        if (mobileMenu.classList.contains('mobile-menu-open')) {
            mobileMenu.classList.remove('mobile-menu-open');
            if (mainContent) {
                mainContent.classList.remove('content-shifted');
            }
        } else {
            mobileMenu.classList.add('mobile-menu-open');
            if (mainContent) {
                mainContent.classList.add('content-shifted');
            }
        }
    });

    mobileMenuCloseBtn.addEventListener('click', () => {
        mobileMenu.classList.remove('mobile-menu-open');
        if (mainContent) {
            mainContent.classList.remove('content-shifted');
        }
    });

    // Language buttons in mobile menu
    const btnIdMobile = document.getElementById('btn-id-mobile');
    const btnEnMobile = document.getElementById('btn-en-mobile');

    if (btnIdMobile) btnIdMobile.addEventListener('click', () => setLanguage('id'));
    if (btnEnMobile) btnEnMobile.addEventListener('click', () => setLanguage('en'));
});
</script>


<script>
function getTld(hostname) {
  const parts = hostname.split('.');
  if (parts.length <= 2) return hostname;         
  return parts.slice(parts.length - 2).join('.');   
}

function setGoogleTranslateCookie(src, target) {
  const cookieVal = '/' + src + '/' + target;
  document.cookie = 'googtrans=' + cookieVal + ';path=/';
  try {
    const domain = getTld(location.hostname);
    document.cookie = 'googtrans=' + cookieVal + ';path=/;domain=' + domain;
  } catch (e) {
  }
}

(function preapplyLangFromStorage() {
  const lang = localStorage.getItem('site_lang'); 
  if (!lang) return;
  const src = 'id'; 
  const target = (lang === 'en') ? 'en' : 'id';
  setGoogleTranslateCookie(src, target);
})();

function setLanguage(lang) {
  if (!['id','en'].includes(lang)) lang = 'id';
  localStorage.setItem('site_lang', lang);

  const src = 'id'; 
  const target = (lang === 'en') ? 'en' : 'id';

  setGoogleTranslateCookie(src, target);

  setTimeout(function() {
    location.reload();
  }, 150);
}

document.addEventListener('DOMContentLoaded', function() {
  const btnId = document.getElementById('btn-id');
  const btnEn = document.getElementById('btn-en');

  if (btnId) btnId.addEventListener('click', ()=> setLanguage('id'));
  if (btnEn) btnEn.addEventListener('click', ()=> setLanguage('en'));

  const current = localStorage.getItem('site_lang') || 'id';
  if (current === 'en' && btnEn) btnEn.classList.add('font-semibold');
  if (current === 'id' && btnId) btnId.classList.add('font-semibold');

  // Search toggle button for small screens
  const searchToggleBtn = document.getElementById('search-toggle-btn');
  const searchContainer = document.getElementById('search-container');

  function updateSearchToggleVisibility() {
    if (window.innerWidth <= 768) {
      searchToggleBtn.style.display = 'inline-block';
      searchContainer.style.display = 'none';
    } else {
      searchToggleBtn.style.display = 'none';
      searchContainer.style.display = 'block';
    }
  }

  searchToggleBtn.addEventListener('click', () => {
    if (searchContainer.style.display === 'none' || searchContainer.style.display === '') {
      searchContainer.style.display = 'block';
    } else {
      searchContainer.style.display = 'none';
    }
  });

  window.addEventListener('resize', updateSearchToggleVisibility);
  updateSearchToggleVisibility();
});
</script>

<script>
    const burgerBtn = document.getElementById('burger-btn');
    const mobileMenu = document.getElementById('mobile-menu');

    burgerBtn.addEventListener('click', () => {
        mobileMenu.classList.toggle('open');
    });
</script>

<!-- Konten Artikel -->
<div class="container mt-4">

<style>

.page-title {
    position: relative;
    left: 50%;
    margin-left: -50vw;
    width: 100vw;
    background-color: #1A3B7A;
    background-image: url('pattern.png'); /* opsional */
    background-repeat: repeat;
    background-size: contain;
    text-align: center;
    padding: 50px 0;
    overflow-x: hidden; /* cegah overflow horizontal */
    z-index: 0;
}

.page-title h1 {
    color: white;
    font-size: 2rem;
    font-weight: bold;
    margin: 0;
    position: relative;
    z-index: 1;
}

.container.mt-4 {
    margin-top: 0; /* reset margin-top */
}
</style>

<style>
.related-post-link:hover,
.related-post-link:active,
.related-post-link:focus {
    color: #00008B !important;
}
</style>


<div class="page-title">
    <h1>
        <?= htmlspecialchars($data['category']) ?>
    </h1>
</div>


    <p class="text-muted">
        Ditulis oleh <strong><?= htmlspecialchars($data['author_name']) ?></strong> |
        Kategori: <?= htmlspecialchars($data['category']) ?> |
        <?= date('d M Y H:i', strtotime($data['created_at'])) ?>
    </p>

    <?php if (!empty($data['featured_image']) && isImage($data['featured_image'])): ?>
        <img src="admin/uploads/articles/<?= htmlspecialchars(basename($data['featured_image'])) ?>" class="img-fluid my-3 rounded-corners" style="max-width: 100%; height: auto; max-height: 400px; object-fit: cover;">
    <?php endif; ?>

    <div class="mb-5" style="white-space: pre-line;">
        <?= nl2br(htmlspecialchars($data['content'])) ?>
    </div>


<!-- Previous and Next Post Navigation -->
<div class="d-flex justify-content-between mb-4 px-3" style="gap: 1rem;">
    <div style="flex: 1; border-right: 1px solid #ccc; padding-right: 1rem; text-align: left; font-size: 1.25rem;">
        <strong>Previous Post</strong><br>
        <?php if ($prev = mysqli_fetch_assoc($previous_post)) : ?>
            <a href="artikel.php?slug=<?= urlencode($prev['slug']) ?>" class="text-decoration-none fw-bold text-muted">
                <?= htmlspecialchars($prev['title']) ?>
            </a>
        <?php else: ?>
            <span class="text-muted">No previous post</span>
        <?php endif; ?>
    </div>
    <div style="flex: 1; padding-left: 1rem; text-align: right; font-size: 1.25rem;">
        <strong>Next Post</strong><br>
        <?php if ($next = mysqli_fetch_assoc($next_post)) : ?>
            <a href="artikel.php?slug=<?= urlencode($next['slug']) ?>" class="text-decoration-none fw-bold text-muted">
                <?= htmlspecialchars($next['title']) ?>
            </a>
        <?php else: ?>
            <span class="text-muted">No next post</span>
        <?php endif; ?>
    </div>
</div>

<!-- Related Posts -->
<h4 style="border-bottom: 1px solid #ccc; padding-bottom: 0.5rem; font-size: 1.25rem;">Related Posts</h4>
<div class="d-flex gap-4 overflow-auto pb-3 mb-4" style="scroll-behavior: smooth;">
    <?php while ($row = mysqli_fetch_assoc($rekomendasi)) : ?>
        <div style="min-width: 250px;">
            <div class="mb-1 text-muted" style="font-size: 0.85rem;">
                <?= date('d/m/Y', strtotime($row['created_at'])) ?>
            </div>
            <a href="artikel.php?slug=<?= urlencode($row['slug']) ?>" class="text-decoration-none fw-bold text-dark related-post-link">
                <?= htmlspecialchars($row['title']) ?>
            </a>
        </div>
    <?php endwhile; ?>
</div>

<!-- Scroll to Top Button -->
<button id="scrollUpBtn" title="Scroll to top" style="
    position: fixed;
    bottom: 20px;
    right: 20px;
    width: 48px;
    height: 48px;
    border: none;
    border-radius: 50%;
    background-color: #1A3B7A;
    color: white;
    font-size: 24px;
    display: none;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 1000;
    box-shadow: 0 4px 6px rgba(0,0,0,0.3);
    transition: transform 0.3s ease, opacity 0.3s ease;
">
    <i class="bi bi-arrow-up"></i>
</button>

<script>
    const scrollUpBtn = document.getElementById('scrollUpBtn');

    window.addEventListener('scroll', () => {
        if (window.scrollY > 200) {
            scrollUpBtn.style.display = 'flex';
            scrollUpBtn.style.opacity = '1';
            scrollUpBtn.style.transform = 'translateY(0)';
        } else {
            scrollUpBtn.style.opacity = '0';
            scrollUpBtn.style.transform = 'translateY(100px)';
            setTimeout(() => {
                scrollUpBtn.style.display = 'none';
            }, 300);
        }
    });

    scrollUpBtn.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
</script>
</div>

<!-- Footer -->
<footer class="footer text-white pt-5 pb-4" style="background-color: #1A3B7A;">
    <div class="container">
        <div class="row">
            <!-- Contact Info -->
            <div class="col-md-6 mb-4">
                <h5 class="fw-bold">Our Contact</h5>
                <p><i class="bi bi-geo-alt-fill me-2"></i>Jl. Demang Lebar Daun No.375, 20 Ilir D. IV<br> Kec. Ilir Tim. I, Kota Palembang,<br>Sumatera Selatan 30131</p>
                <p><i class="bi bi-telephone-fill me-2"></i>(0274) 588688</p>
                <p><i class="bi bi-envelope-fill me-2"></i>info@PLN.ac.id</p>
                <div class="d-flex gap-3 mt-3">
                    <a href="#" class="text-white fs-4"><i class="bi bi-x-lg"></i></a>
                    <a href="#" class="text-white fs-4"><i class="bi bi-facebook"></i></a>
                    <a href="https://www.instagram.com/pln.iconplus_sumbagsel?utm_source=ig_web_button_share_sheet&igsh=d3p2Y2l4ZHk1YWlo" class="text-white fs-4"><i class="bi bi-instagram"></i></a>
                    <a href="#" class="text-white fs-4"><i class="bi bi-linkedin"></i></a>
                    <a href="https://www.youtube.com/@PLN.IconPlus" class="text-white fs-4"><i class="bi bi-youtube"></i></a>
                    <a href="#" class="text-white fs-4"><i class="bi bi-tiktok"></i></a>
                </div>
            </div>

            <!-- Logos -->
            <div class="col-md-6 d-flex align-items-center justify-content-end gap-4">
                <img src="logo/Logo PLN ICON.png" alt="Logo 1" style="height: 50px; object-fit: contain; margin-right: 20px;">
                <img src="logo/Logo PLN ICON.png" alt="Logo 2" style="height: 50px; object-fit: contain;">
            </div>
        </div>
        <hr class="border-light">
        <p class="text-center mb-0">&copy; <?= date('Y') ?> Portal Berita. Semua Hak Dilindungi.</p>
    </div>
</footer>

   <script>
        document.addEventListener("DOMContentLoaded", function () {
        const searchInput = document.querySelector("#searchInput");
        const searchResults = document.querySelector("#searchResults");

        let timeout = null;

        searchInput.addEventListener("input", function () {
            clearTimeout(timeout);
            const query = this.value.trim();

            if (query.length < 2) {
                searchResults.innerHTML = "";
                searchResults.classList.add("hidden");
                return;
            }

            timeout = setTimeout(() => {
                fetch(`search.php?query=${encodeURIComponent(query)}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.length > 0) {
                            let html = "";
                            data.forEach(article => {
                                html += `
                                    <a href="artikel.php?slug=${encodeURIComponent(article.slug)}"
                                       class="block px-4 py-2 hover:bg-gray-100 border-b border-gray-200 last:border-b-0 no-underline text-gray-900 hover:text-primary">
                                        <div class="font-medium text-gray-900">${article.title}</div>
                                        <div class="text-xs text-gray-500">${article.category} • ${new Date(article.created_at).toLocaleDateString('id-ID')}</div>
                                    </a>
                                `;
                            });
                            searchResults.innerHTML = html;
                            searchResults.classList.remove("hidden");
                        } else {
                            searchResults.innerHTML = `<div class="px-4 py-2 text-gray-500">Tidak ada hasil</div>`;
                            searchResults.classList.remove("hidden");
                        }
                    })
                    .catch(err => console.error(err));
            }, 300); // delay 300ms
        });

        // klik di luar -> sembunyikan popup
        document.addEventListener("click", function (e) {
            if (!searchResults.contains(e.target) && e.target !== searchInput) {
                searchResults.classList.add("hidden");
            }
        });
    });

    </script>
</body>
</html>
