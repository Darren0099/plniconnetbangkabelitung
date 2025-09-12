<?php

session_start();

// Pastikan file functions.php di-include sebelum memanggil requireAdmin()
require_once 'functions.php';
require_once 'koneksi.php';

// Periksa apakah user sudah login dan memiliki role admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$user = $_SESSION['user'];
$showWelcome = $_SESSION['show_welcome'] ?? false;
unset($_SESSION['show_welcome']);

$total_artikel = 0;
$query_total = "SELECT COUNT(*) as total FROM articles";
$result_total = mysqli_query($conn, $query_total);
if ($result_total) {
    $row = mysqli_fetch_assoc($result_total);
    $total_artikel = $row['total'];
}

$artikel_terbaru = [];
$author_ids = []; 
$query_artikel = "SELECT * FROM articles ORDER BY created_at DESC LIMIT 5";
$result_artikel = mysqli_query($conn, $query_artikel);

if ($result_artikel) {
    while ($row = mysqli_fetch_assoc($result_artikel)) {
        $artikel_terbaru[] = $row;
        if (!empty($row['author_id'])) {
            $author_ids[] = $row['author_id'];
        }
    }
}

$penulis_data = [];
if (!empty($author_ids)) {
    $placeholders = implode(',', array_fill(0, count($author_ids), '?'));
    $query_penulis = "SELECT id, username FROM user WHERE id IN ($placeholders)";
    
    $stmt = mysqli_prepare($conn, $query_penulis);
    if ($stmt) {
       
        $types = str_repeat('i', count($author_ids));
        mysqli_stmt_bind_param($stmt, $types, ...$author_ids);
        mysqli_stmt_execute($stmt);
        $result_penulis = mysqli_stmt_get_result($stmt);
        
        while ($row = mysqli_fetch_assoc($result_penulis)) {
            $penulis_data[$row['id']] = $row['username'];
        }
        mysqli_stmt_close($stmt);
    } else {
        error_log("Error preparing statement: " . mysqli_error($conn));
    }
}

$artikel_per_user = [];
$query_user_articles = "SELECT u.id, u.username, COUNT(a.id) as jumlah_artikel 
                       FROM user u 
                       LEFT JOIN articles a ON u.id = a.author_id 
                       GROUP BY u.id, u.username 
                       HAVING jumlah_artikel > 0 
                       ORDER BY jumlah_artikel DESC 
                       LIMIT 10";
$result_user_articles = mysqli_query($conn, $query_user_articles);

if ($result_user_articles) {
    while ($row = mysqli_fetch_assoc($result_user_articles)) {
        $artikel_per_user[] = $row;
    }
}

$active_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Admin - Kelola Artikel</title>
<script src="https://cdn.tailwindcss.com/3.4.16"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Pacifico&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/echarts/5.5.0/echarts.min.js"></script>
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
}   .welcome-popup {
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
            animation: slideIn 0.5s ease-out, fadeOut 0.5s ease-in 1.5s forwards;
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
    .swal2-popup {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        border-radius: 12px;
    }
    .swal2-title {
        font-size: 1.25rem;
        font-weight: 600;
    }
    .swal2-actions button {
        border-radius: 8px !important;
        padding: 0.5rem 1.5rem !important;
    }
    .swal2-confirm {
        background-color: #6366F1 !important;
    }
    .swal2-cancel {
        background-color: #EF4444 !important;
    }
#editModal {
    transition: opacity 0.3s ease, visibility 0.3s ease;
}
#editModal:not(.hidden) {
    opacity: 1;
    visibility: visible;
}
#editModal.hidden {
    opacity: 0;
    visibility: hidden;
}

#editModal .modal-content {
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    transform: translateY(0);
    transition: transform 0.3s ease;
}
#editModal.hidden .modal-content {
    transform: translateY(-20px);
}
</style>

<script>
function confirmDelete(articleId) {
    Swal.fire({
        title: 'Apakah Anda yakin?',
        text: "Artikel yang dihapus tidak dapat dikembalikan!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Tidak, Batalkan',
        reverseButtons: true,
        customClass: {
            confirmButton: 'swal2-confirm',
            cancelButton: 'swal2-cancel'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `hapus_artikel.php?id=${articleId}`;
            
            Swal.fire({
                title: 'Menghapus...',
                html: 'Sedang menghapus artikel',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading()
                }
            });
        }
    });
}
</script>
</head>
<body class="bg-gray-50 overflow-x-hidden">
<div class="flex min-h-screen">
   <?php if ($showWelcome): ?>
    <div class="welcome-popup" id="welcomePopup">
        <i class="ri-checkbox-circle-fill"></i>
        <span>Selamat datang, <?php echo htmlspecialchars($user['username']); ?>!</span>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                const popup = document.getElementById('welcomePopup');
                if (popup) {
                    popup.addEventListener('animationend', function() {
                        popup.remove();
                    });
                }
            }, 1000);
        });
    </script>
    <?php endif; ?>
    
  <aside class="fixed left-0 top-0 h-screen w-64 bg-white border-r border-gray-200 z-30">
    <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-200">
      <div class="text-xl font-['Pacifico'] text-primary">logo</div>
      <span class="font-semibold text-gray-900">APLN</span>
    </div>
    <nav class="p-4 space-y-2">
      <a href="#" class="flex items-center gap-3 px-3 py-2 text-primary bg-primary/10 rounded-lg">
        <div class="w-5 h-5 flex items-center justify-center">
          <i class="ri-home-line"></i>
        </div>
        <span>Dashboard</span>
      </a>
      <a href="tambahartikel.php" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-50 rounded-lg">
        <div class="w-5 h-5 flex items-center justify-center">
          <i class="ri-article-line"></i>
        </div>
        <span>Buat Artikel</span>
      </a>
      <a href="#" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-50 rounded-lg">
        <div class="w-5 h-5 flex items-center justify-center">
          <i class="ri-team-line"></i>
        </div>
        <span>User Management</span>
      </a>
      <a href="profile.php" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-50 rounded-lg">
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

  <div class="flex-1 ml-64 overflow-x-hidden">
    <header class="bg-white shadow-sm border-b border-gray-200 px-6 py-4">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <div class="relative">
              <input id="searchInput" type="text" placeholder="Cari artikel, pengguna..." class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg w-80 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-transparent" autocomplete="off">
              <div class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 flex items-center justify-center text-gray-400">
                <i class="ri-search-line text-sm"></i>
              </div>
              <div id="searchResults" class="absolute z-50 bg-white border border-gray-300 rounded-lg shadow-lg mt-1 w-full max-h-96 overflow-y-auto hidden"></div>
            </div>
        </div>
        <div class="flex items-center gap-4">
          <button class="relative p-2 text-gray-600 hover:text-primary hover:bg-primary/5 rounded-md transition-colors">
            <div class="w-5 h-5 flex items-center justify-center">
              <i class="ri-notification-line text-lg"></i>
            </div>
            <span class="absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full text-xs text-white flex items-center justify-center">3</span>
          </button>
          <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-primary rounded-full flex items-center justify-center">
              <i class="ri-user-line text-white text-sm"></i>
            </div>
            <div class="hidden md:block">
              <div class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($_SESSION['user']['username'] ?? 'User'); ?></div>
              <div class="text-xs text-gray-500"><?php echo htmlspecialchars($_SESSION['user']['Admin'] ?? 'Admin'); ?></div>
            </div>
          </div>
        </div>
      </div>
    </header>

    <div class="px-6 py-4 bg-white border-b border-gray-200">
      <nav class="flex items-center gap-2 text-sm text-gray-600">
        <span>Dashboard</span>
        <div class="w-4 h-4 flex items-center justify-center">
          <i class="ri-arrow-right-s-line"></i>
        </div>
        <span class="text-gray-900 font-medium">Overview</span>
      </nav>
    </div>

    <main class="p-6">
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Selamat Datang, <?php echo htmlspecialchars($_SESSION['user']['username'] ?? 'User'); ?>!</h1>
        <p class="text-gray-600">Berikut adalah ringkasan aktivitas sistem artikel Anda hari ini.</p>
      </div>

      
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-600 text-sm mb-1">Total Artikel</p>
              <p class="text-3xl font-bold text-gray-900"><?php echo number_format($total_artikel); ?></p>
              <p class="text-green-600 text-sm mt-2">
                <span class="inline-flex items-center gap-1">
                  <div class="w-3 h-3 flex items-center justify-center">
                    <i class="ri-arrow-up-line text-xs"></i>
                  </div>
                  +<?php echo round(($total_artikel / max(1, $total_artikel - 5)) * 100); ?>%
                </span>
              </p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
              <i class="ri-article-line text-blue-600 text-xl"></i>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-sm border gap-6 mb-8 border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900">Artikel Terbaru</h3>
            <a href="tambahartikel.php" class="text-sm text-primary hover:text-secondary !rounded-button whitespace-nowrap">Lihat Semua</a>
          </div>
        </div>
        <div class="overflow-x-auto">
         <table class="w-full">
    <thead class="bg-gray-50">
        <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Judul Artikel</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Penulis</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kategori</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Views</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
        </tr>
    </thead>
    <tbody class="bg-white divide-y divide-gray-200">
        <?php foreach ($artikel_terbaru as $artikel): ?>
        <tr>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($artikel['title']); ?></div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center">
                        <i class="ri-user-line text-gray-600"></i>
                    </div>
                    <span class="text-sm text-gray-900">
                         <?php 
            if (isset($artikel['author_id']) && isset($penulis_data[$artikel['author_id']])) {
                echo htmlspecialchars($penulis_data[$artikel['author_id']]);
            } else {
                echo 'Unknown (ID: ' . htmlspecialchars($artikel['author_id'] ?? 'null') . ')';
            }
            ?>
                    </span>
                </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                    <?php echo htmlspecialchars(ucfirst($artikel['category'])); ?>
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                <?php echo date('d M Y', strtotime($artikel['created_at'])); ?>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $artikel['status'] == 'published' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                    <?php echo ucfirst($artikel['status']); ?>
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                <?php echo number_format($artikel['views'] ?? 0); ?>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <div class="flex items-center gap-2">
                    <button onclick="openEditModal(<?php echo $artikel['id']; ?>)" 
                class="text-primary hover:text-secondary !rounded-button whitespace-nowrap"
                title="Edit Artikel">
            <div class="w-4 h-4 flex items-center justify-center">
                <i class="ri-edit-line"></i>
            </div>
        </button>
                    <button onclick="confirmDelete(<?php echo $artikel['id']; ?>)" 
                            class="text-red-600 hover:text-red-800 !rounded-button whitespace-nowrap"
                            title="Hapus Artikel">
                        <div class="w-4 h-4 flex items-center justify-center">
                            <i class="ri-delete-bin-line"></i>
                        </div>
                    </button>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
        </div>
      </div>


      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-semibold text-gray-900">Kategori Artikel</h3>
    </div>
    <div id="pieChart" style="height: 300px;"></div>
</div>

<script id="pieChartScript">
document.addEventListener('DOMContentLoaded', function() {
    // Ambil data kategori dari PHP
    const kategoriData = [
        <?php
        // Query untuk menghitung artikel per kategori
        $query_kategori = "SELECT category, COUNT(*) as jumlah FROM articles 
                          WHERE category IN ('teknologi', 'bisnis', 'lifestyle', 'kesehatan', 'pendidikan')
                          GROUP BY category";
        $result_kategori = mysqli_query($conn, $query_kategori);
        
        $data_kategori = [];
        while ($row = mysqli_fetch_assoc($result_kategori)) {
            $data_kategori[] = $row;
        }
        
        // Warna untuk setiap kategori
        $colors = [
            'teknologi' => 'rgba(87, 181, 231, 1)',
            'bisnis' => 'rgba(141, 211, 199, 1)',
            'lifestyle' => 'rgba(251, 191, 114, 1)',
            'kesehatan' => 'rgba(252, 141, 98, 1)',
            'pendidikan' => 'rgba(190, 144, 212, 1)'
        ];
        
        // Format data untuk ECharts
        foreach ($data_kategori as $kategori) {
            echo "{
                value: {$kategori['jumlah']},
                name: '" . ucfirst($kategori['category']) . "',
                itemStyle: { color: '{$colors[$kategori['category']]}' }
            },";
        }
        ?>
    ];

    const pieChart = echarts.init(document.getElementById('pieChart'));
    const pieOption = {
        animation: false,
        series: [{
            type: 'pie',
            radius: ['40%', '70%'],
            data: kategoriData,
            label: { 
                show: true, 
                formatter: function(params) {
                    return params.name + ': ' + params.value + ' (' + params.percent + '%)';
                }
            },
            itemStyle: { borderRadius: 8 }
        }],
        tooltip: {
            trigger: 'item',
            backgroundColor: 'rgba(255, 255, 255, 0.9)',
            borderColor: '#e5e7eb',
            textStyle: { color: '#1f2937' },
            formatter: function(params) {
                return `
                    <strong>${params.name}</strong><br/>
                    Jumlah: ${params.value} artikel<br/>
                    Persentase: ${params.percent}%
                `;
            }
        },
        legend: {
            orient: 'vertical',
            right: 10,
            top: 'center'
        }
    };
    pieChart.setOption(pieOption);
    
    // Responsive chart
    window.addEventListener('resize', function() {
        pieChart.resize();
    });
});
</script>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-lg font-semibold text-gray-900">Artikel per user</h3>
          <button class="text-sm text-primary hover:text-secondary !rounded-button whitespace-nowrap">Lihat Semua</button>
        </div>
        <div id="barChart" style="height: 300px;"></div>
      </div>

       
    </main>
  </div>
</div>
<!-- Modal Edit Artikel -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-xl shadow-lg w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gray-800">Edit Artikel</h3>
                <button onclick="closeEditModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="ri-close-line text-2xl"></i>
                </button>
            </div>
            
            <form id="editForm" class="space-y-4" enctype="multipart/form-data">
                <input type="hidden" id="editArticleId" name="id">
                
                <div>
                    <label for="editTitle" class="block text-sm font-medium text-gray-700 mb-1">Judul Artikel</label>
                    <input type="text" id="editTitle" name="title" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>
                
                <div>
                    <label for="editCategory" class="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
                    <select id="editCategory" name="category" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="teknologi">Teknologi</option>
                        <option value="bisnis">Bisnis</option>
                        <option value="lifestyle">Lifestyle</option>
                        <option value="kesehatan">Kesehatan</option>
                        <option value="pendidikan">Pendidikan</option>
                    </select>
                </div>
                
                <div>
                    <label for="editContent" class="block text-sm font-medium text-gray-700 mb-1">Isi Artikel</label>
                    <textarea id="editContent" name="content" rows="8"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"></textarea>
                </div>

                <div>
                    <label for="editImage" class="block text-sm font-medium text-gray-700 mb-1">Ganti Foto Artikel</label>
                    <input type="file" id="editImage" name="image" accept="image/*" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <div id="imagePreview" class="mt-2"></div>
                </div>
                
                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" onclick="closeEditModal()" 
                            class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition">
                        Batal
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-secondary transition flex items-center">
                        <i class="ri-save-line mr-2"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('editImage').addEventListener('change', function(event) {
    const preview = document.getElementById('imagePreview');
    preview.innerHTML = '';
    const file = event.target.files[0];
    if (file) {
        const img = document.createElement('img');
        img.src = URL.createObjectURL(file);
        img.classList.add('max-w-xs', 'h-auto', 'border', 'border-gray-300', 'rounded');
        preview.appendChild(img);
    }
});
</script>


<script id="barChartScript">
document.addEventListener('DOMContentLoaded', function() {
  const barChart = echarts.init(document.getElementById('barChart'));
  const barOption = {
    animation: false,
    grid: { top: 20, right: 20, bottom: 40, left: 80 },
    xAxis: {
      type: 'category',
      data: [
        <?php
        $userNames = [];
        foreach ($artikel_per_user as $user) {
            $userNames[] = "'" . addslashes($user['username']) . "'";
        }
        echo implode(',', $userNames);
        ?>
      ],
      axisLine: { show: false },
      axisTick: { show: false }
    },
    yAxis: {
      type: 'value',
      axisLine: { show: false },
      axisTick: { show: false },
      splitLine: { lineStyle: { color: '#f3f4f6' } }
    },
    series: [{
      data: [
        <?php
        $articleCounts = [];
        foreach ($artikel_per_user as $user) {
            $articleCounts[] = $user['jumlah_artikel'];
        }
        echo implode(',', $articleCounts);
        ?>
      ],
      type: 'bar',
      itemStyle: {
        color: 'rgba(87, 181, 231, 1)',
        borderRadius: [4, 4, 0, 0]
      },
      barWidth: '60%'
    }],
    tooltip: {
      trigger: 'axis',
      backgroundColor: 'rgba(255, 255, 255, 0.9)',
      borderColor: '#e5e7eb',
      textStyle: { color: '#1f2937' }
    }
  };
  barChart.setOption(barOption);
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const searchInput = document.getElementById('searchInput');
  const searchResults = document.getElementById('searchResults');

  let timeout = null;

  searchInput.addEventListener('input', function() {
    clearTimeout(timeout);
    const query = this.value.trim();

    if (query.length < 2) {
      searchResults.innerHTML = '';
      searchResults.classList.add('hidden');
      return;
    }

    timeout = setTimeout(() => {
          fetch(`search.php?query=${encodeURIComponent(query)}`)
            .then(res => res.json())
            .then(data => {
              if (data.length > 0) {
                let html = `
                  <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                      <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Judul Artikel</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Penulis</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kategori</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Views</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                      </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                `;
                data.forEach(article => {
                  html += `
                    <tr>
                      <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">${article.title}</td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${article.author}</td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                          ${article.category.charAt(0).toUpperCase() + article.category.slice(1)}
                        </span>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${new Date(article.created_at).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })}</td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ${article.status === 'published' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'}">
                          ${article.status.charAt(0).toUpperCase() + article.status.slice(1)}
                        </span>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${article.views.toLocaleString()}</td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm font-medium flex gap-2">
                        <button onclick="openEditModalBySlug('${article.slug}')" title="Edit" class="text-primary hover:text-secondary">
                          <i class="ri-edit-line"></i>
                        </button>
                        <button onclick="confirmDeleteBySlug('${article.slug}')" title="Hapus" class="text-red-600 hover:text-red-800">
                          <i class="ri-delete-bin-line"></i>
                        </button>
                        <button onclick="toggleStatusBySlug('${article.slug}')" title="Ubah Status" class="text-yellow-600 hover:text-yellow-800">
                          <i class="ri-refresh-line"></i>
                        </button>
                        <button onclick="openArticleBySlug('${article.slug}')" title="Tinjau Artikel" class="text-gray-600 hover:text-gray-800">
                          <i class="ri-link-m"></i>
                        </button>
                      </td>
                    </tr>
                  `;
                });
                html += '</tbody></table>';
                searchResults.innerHTML = html;
                searchResults.classList.remove('hidden');
              } else {
                searchResults.innerHTML = `<div class="px-4 py-2 text-gray-500">Tidak ada hasil</div>`;
                searchResults.classList.remove('hidden');
              }
            })
            .catch(err => {
              console.error(err);
              searchResults.innerHTML = `<div class="px-4 py-2 text-red-500">Terjadi kesalahan</div>`;
              searchResults.classList.remove('hidden');
            });
    }, 300);
  });

  document.addEventListener('click', function(e) {
    if (!searchResults.contains(e.target) && e.target !== searchInput) {
      searchResults.classList.add('hidden');
    }
  });
});

// Functions for actions by slug
function openEditModalBySlug(slug) {
  // Fetch article id by slug then open modal
  fetch(`admin/get_article.php?slug=${encodeURIComponent(slug)}`)
    .then(res => res.json())
    .then(data => {
      if (data.status === 'success') {
        const article = data.article;
        document.getElementById('editArticleId').value = article.id;
        document.getElementById('editTitle').value = article.title;
        document.getElementById('editCategory').value = article.category;
        document.getElementById('editContent').value = article.content;
        // TODO: Load current image preview if needed
        document.getElementById('editModal').classList.remove('hidden');
      } else {
        Swal.fire('Error', data.message, 'error');
      }
    })
    .catch(() => Swal.fire('Error', 'Gagal memuat data artikel', 'error'));
}

function confirmDeleteBySlug(slug) {
  // Fetch article id by slug then confirm delete
  fetch(`admin/get_article.php?slug=${encodeURIComponent(slug)}`)
    .then(res => res.json())
    .then(data => {
      if (data.status === 'success') {
        confirmDelete(data.article.id);
      } else {
        Swal.fire('Error', data.message, 'error');
      }
    })
    .catch(() => Swal.fire('Error', 'Gagal memuat data artikel', 'error'));
}

function toggleStatusBySlug(slug) {
  // Fetch article id by slug then toggle status
  fetch(`admin/get_article.php?slug=${encodeURIComponent(slug)}`)
    .then(res => res.json())
    .then(data => {
      if (data.status === 'success') {
        const article = data.article;
        const newStatus = article.status === 'published' ? 'draft' : 'published';
        fetch('admin/update_status.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({id: article.id, status: newStatus})
        })
        .then(res => res.json())
        .then(resp => {
          if (resp.status === 'success') {
            Swal.fire('Berhasil', resp.message, 'success').then(() => {
              window.location.reload();
            });
          } else {
            Swal.fire('Error', resp.message, 'error');
          }
        })
        .catch(() => Swal.fire('Error', 'Gagal mengubah status', 'error'));
      } else {
        Swal.fire('Error', data.message, 'error');
      }
    })
    .catch(() => Swal.fire('Error', 'Gagal memuat data artikel', 'error'));
}

function openArticleBySlug(slug) {
  window.open(`artikel.php?slug=${encodeURIComponent(slug)}`, '_blank');
}

// Modal functions
function openEditModal(articleId) {
    // Tampilkan loading
    Swal.fire({
        title: 'Memuat...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    // Ambil data artikel via AJAX
    fetch(`get_article.php?id=${articleId}`)
        .then(response => response.json())
        .then(data => {
            Swal.close();
            if (data.status === 'success') {
                // Isi form dengan data artikel
                document.getElementById('editArticleId').value = data.article.id;
                document.getElementById('editTitle').value = data.article.title;
                document.getElementById('editCategory').value = data.article.category;
                document.getElementById('editContent').value = data.article.content;

                // Tampilkan modal
                document.getElementById('editModal').classList.remove('hidden');
            } else {
                Swal.fire('Error!', data.message, 'error');
            }
        })
        .catch(error => {
            Swal.fire('Error!', 'Gagal memuat data artikel', 'error');
        });
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
    document.getElementById('editForm').reset();
}

// Handle form submit
document.getElementById('editForm').addEventListener('submit', function(e) {
    e.preventDefault();

    // Tampilkan konfirmasi SweetAlert
    Swal.fire({
        title: 'Simpan Perubahan?',
        text: "Anda yakin ingin menyimpan perubahan artikel ini?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Simpan!',
        cancelButtonText: 'Batal',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Kirim data via AJAX
            const formData = new FormData(document.getElementById('editForm'));

            // Tampilkan loading
            Swal.fire({
                title: 'Menyimpan...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            fetch('update_article.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        title: 'Berhasil!',
                        text: data.message,
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        closeEditModal();
                        window.location.reload();
                    });
                } else {
                    Swal.fire('Error!', data.message, 'error');
                }
            })
            .catch(error => {
                Swal.fire('Error!', 'Gagal menyimpan perubahan', 'error');
            });
        }
    });
});
</script>
</body>
</html>
