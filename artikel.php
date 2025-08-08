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
    SELECT id, title, slug FROM articles 
    WHERE category = '$kategori' AND slug != '$slug' AND status = 'published' 
    ORDER BY created_at DESC 
    LIMIT 4
");

// Ambil kategori untuk footer
$kategori_query = mysqli_query($conn, "
    SELECT DISTINCT category FROM articles WHERE status = 'published'
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($data['title']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Script untuk ubah URL (hanya tampilan) -->
    <script>
    const params = new URLSearchParams(window.location.search);
    const slug = params.get("slug");

    if (slug) {
        const newUrl = `/plniconsumsel/artikel.php?slug=${slug}`;
        history.replaceState(null, '', newUrl);
    }
    </script>

    <style>
        .footer {
            background: #f5f5f5;
            padding: 40px 20px;
            margin-top: 50px;
            border-top: 1px solid #ddd;
        }
        .back-btn {
            margin-bottom: 20px;
        }
        .navbar-brand img {
            height: 40px;
        }
        .nav-item .nav-link {
            color: #fff !important;
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
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container d-flex justify-content-between align-items-center">
        <a class="navbar-brand" href="index.php">
            <img src="../logo/Logo%20PLN%20ICON.png" alt="Logo">
        </a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="#">Kesehatan</a></li>
                <li class="nav-item"><a class="nav-link" href="#">Teknologi</a></li>
                <li class="nav-item"><a class="nav-link" href="#">Bisnis</a></li>
                <li class="nav-item"><a class="nav-link" href="#">Lifestyle</a></li>
                <li class="nav-item"><a class="nav-link" href="#">Pendidikan</a></li>
            </ul>
        </div>
    </div>
</nav>

<!-- Konten Artikel -->
<div class="container mt-4">
    <a href="index.php" class="btn btn-outline-secondary back-btn">← Kembali ke Beranda</a>

    <h1><?= htmlspecialchars($data['title']) ?></h1>
    <p class="text-muted">
        Ditulis oleh <strong><?= htmlspecialchars($data['author_name']) ?></strong> |
        Kategori: <?= htmlspecialchars($data['category']) ?> |
        <?= date('d M Y H:i', strtotime($data['created_at'])) ?>
    </p>

    <?php if (!empty($data['featured_image']) && isImage($data['featured_image'])): ?>
        <img src="admin/uploads/articles/<?= htmlspecialchars(basename($data['featured_image'])) ?>" class="img-fluid my-3">
    <?php endif; ?>

    <div class="mb-5" style="white-space: pre-line;">
        <?= nl2br(htmlspecialchars($data['content'])) ?>
    </div>

    <!-- Artikel Terkait -->
    <h4>Berita Terkait Lainnya</h4>
    <div class="row">
        <?php while ($row = mysqli_fetch_assoc($rekomendasi)) : ?>
            <div class="col-md-6 col-lg-3 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <h6 class="card-title"><?= htmlspecialchars($row['title']) ?></h6>
                        <a href="artikel.php?slug=<?= urlencode($row['slug']) ?>">Baca Selengkapnya</a>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<!-- Footer -->
<div class="footer text-center">
    <div class="container">
        <div class="row">
            <?php while ($kategori = mysqli_fetch_assoc($kategori_query)) :
                $kategori_nama = $kategori['category'];
                $artikel_kat = mysqli_query($conn, "
                    SELECT id, title, slug FROM articles 
                    WHERE category = '$kategori_nama' AND status = 'published' 
                    ORDER BY RAND() LIMIT 3
                ");
            ?>
                <div class="col-md-4 category-block">
                    <h6><?= htmlspecialchars($kategori_nama) ?></h6>
                    <ul class="list-unstyled">
                        <?php while ($row = mysqli_fetch_assoc($artikel_kat)) : ?>
                            <li><a href="artikel.php?slug=<?= urlencode($row['slug']) ?>">
                                <?= htmlspecialchars($row['title']) ?>
                            </a></li>
                        <?php endwhile; ?>
                    </ul>
                </div>
            <?php endwhile; ?>
        </div>

        <hr>
        <div class="social-icons mb-2">
            <a href="#"><i class="bi bi-facebook"></i></a>
            <a href="#"><i class="bi bi-instagram"></i></a>
            <a href="#"><i class="bi bi-twitter-x"></i></a>
            <a href="#"><i class="bi bi-youtube"></i></a>
            <a href="#"><i class="bi bi-tiktok"></i></a>
        </div>
        <p>&copy; <?= date('Y') ?> Portal Berita. Semua Hak Dilindungi.</p>
        <p><a href="index.php">Beranda</a> · <a href="#">Tentang</a> · <a href="#">Kontak</a></p>
    </div>
</div>

</body>
</html>
