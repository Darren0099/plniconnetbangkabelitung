

<?php
session_start();
include 'koneksi.php';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$username = 'User';
if (isset($_SESSION['user']['id'])) {
    $user_id = intval($_SESSION['user']['id']);
    $user_query = "SELECT username FROM user WHERE id = $user_id LIMIT 1";
    $user_result = mysqli_query($conn, $user_query);
    if ($user_result && mysqli_num_rows($user_result) > 0) {
        $user_row = mysqli_fetch_assoc($user_result);
        $username = $user_row['username'];
    }
} elseif (isset($_SESSION['user']['email'])) {
    $email = mysqli_real_escape_string($conn, $_SESSION['user']['email']);
    $user_query = "SELECT username FROM user WHERE email = '$email' LIMIT 1";
    $user_result = mysqli_query($conn, $user_query);
    if ($user_result && mysqli_num_rows($user_result) > 0) {
        $user_row = mysqli_fetch_assoc($user_result);
        $username = $user_row['username'];
    }
}

// Query for articles list (limited 5)
if ($search) {
    $query = "SELECT articles.*, user.username AS author_name FROM articles LEFT JOIN user ON articles.author_id = user.id WHERE articles.title LIKE '%" . mysqli_real_escape_string($conn, $search) . "%' ORDER BY articles.created_at DESC LIMIT 5";
} else {
    $query = "SELECT articles.*, user.username AS author_name FROM articles LEFT JOIN user ON articles.author_id = user.id ORDER BY articles.created_at DESC LIMIT 5";
}
$result = mysqli_query($conn, $query);
$articles = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Query total articles count (all time)
$total_articles_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM articles");
$total_articles_row = mysqli_fetch_assoc($total_articles_result);
$total_all = $total_articles_row['total'] ?? 0;

// Query total articles today
$today = date('Y-m-d');
$articles_today_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM articles WHERE DATE(created_at) = '$today'");
$articles_today_row = mysqli_fetch_assoc($articles_today_result);
$total_today = $articles_today_row['total'] ?? 0;

// Query total articles this month
$current_year = date('Y');
$current_month = date('m');
$articles_month_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM articles WHERE YEAR(created_at) = $current_year AND MONTH(created_at) = $current_month");
$articles_month_row = mysqli_fetch_assoc($articles_month_result);
$total_month = $articles_month_row['total'] ?? 0;

// Query total articles this year
$current_year = date('Y');
$articles_year_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM articles WHERE YEAR(created_at) = $current_year");
$articles_year_row = mysqli_fetch_assoc($articles_year_result);
$total_year = $articles_year_row['total'] ?? 0;

// Query total users count
$total_users_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM user");
$total_users_row = mysqli_fetch_assoc($total_users_result);
$total_users = $total_users_row['total'] ?? 0;
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
</style>


</head>
<body class="bg-gray-50">
    <!-- Burger Menu for Mobile -->
    <div class="md:hidden fixed top-4 right-4 z-40">
        <button onclick="toggleSidebar()" class="p-2 bg-white rounded-lg shadow-md border border-gray-200">
            <i class="ri-menu-line text-gray-600"></i>
        </button>
    </div>

    <!-- Welcome popup -->
    <div id="welcomePopup" class="welcome-popup" style="display:none;">
        Selamat datang, <?php echo htmlspecialchars($_SESSION['user']['username']); ?>!
    </div>

<div class="flex min-h-screen">

  <aside id="sidebar" class="fixed left-0 top-0 h-screen w-64 bg-white border-r border-gray-200 z-30 hidden md:block">
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
      <a href="user.php" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-50 rounded-lg">
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

  <div class="flex-1 ml-0 md:ml-64 overflow-x-hidden">
    <header class="bg-white shadow-sm border-b border-gray-200 px-4 md:px-6 py-4">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-4 flex-1">
            <form method="GET" action="dashboard.php" class="relative flex-1 max-w-md">
              <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Cari artikel..." class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg w-full text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-transparent" autocomplete="off">
              <div class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 flex items-center justify-center text-gray-400">
                <i class="ri-search-line text-sm"></i>
              </div>
            </form>
        </div>
        <div class="flex items-center gap-2 md:gap-4">
          <button class="relative p-2 text-gray-600 hover:text-primary hover:bg-primary/5 rounded-md transition-colors">
            <div class="w-5 h-5 flex items-center justify-center">
              <i class="ri-notification-line text-lg"></i>
            </div>
            <span class="absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full text-xs text-white flex items-center justify-center">3</span>
          </button>
          <div class="flex items-center space-x-2 md:space-x-3">
            <div class="w-8 h-8 md:w-10 md:h-10 bg-primary rounded-full flex items-center justify-center">
              <i class="ri-user-line text-white text-xs md:text-sm"></i>
            </div>
<div class="hidden lg:block">
  <div class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($_SESSION['user']['username']); ?></div>
  <div class="text-xs text-gray-500">Admin</div>
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
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Selamat Datang, <?php echo htmlspecialchars($_SESSION['user']['username']); ?></h1>
        <p class="text-gray-600">Berikut adalah ringkasan aktivitas sistem artikel Anda hari ini.</p>
      </div>

      
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between mb-4">
            <div>
              <p class="text-gray-600 text-sm mb-1">Total Artikel</p>
              <p id="article-count" class="text-3xl font-bold text-gray-900"><?php echo $total_all; ?></p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
              <i class="ri-article-line text-blue-600 text-xl"></i>
            </div>
          </div>
          <div class="mt-2 flex gap-1">
            <select id="filterMonth" class="px-2 py-1 text-xs border border-gray-300 rounded">
              <option value="">Bulan</option>
              <option value="01">Januari</option>
              <option value="02">Februari</option>
              <option value="03">Maret</option>
              <option value="04">April</option>
              <option value="05">Mei</option>
              <option value="06">Juni</option>
              <option value="07">Juli</option>
              <option value="08">Agustus</option>
              <option value="09">September</option>
              <option value="10">Oktober</option>
              <option value="11">November</option>
              <option value="12">Desember</option>
            </select>
            <select id="filterYear" class="px-2 py-1 text-xs border border-gray-300 rounded">
              <option value="">Tahun</option>
              <option value="2023">2023</option>
              <option value="2024">2024</option>
              <option value="2025">2025</option>
            </select>
            <button onclick="filterByMonthYear()" class="px-2 py-1 text-xs bg-gray-100 text-gray-700 rounded hover:bg-gray-200 transition">Filter</button>
          </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-600 text-sm mb-1">Artikel Bulan Ini</p>
              <p class="text-3xl font-bold text-gray-900"><?php echo $total_month; ?></p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
              <i class="ri-calendar-line text-green-600 text-xl"></i>
            </div>
          </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-600 text-sm mb-1">Total User</p>
              <p class="text-3xl font-bold text-gray-900"><?php echo $total_users; ?></p>
            </div>
            <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
              <i class="ri-user-line text-yellow-600 text-xl"></i>
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
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
        </tr>
    </thead>
    <tbody class="bg-white divide-y divide-gray-200">
        <?php if (count($articles) > 0): ?>
            <?php foreach ($articles as $article): ?>
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($article['title']); ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center">
                                <i class="ri-user-line text-gray-600"></i>
                            </div>
                            <span class="text-sm text-gray-900">
                                <?php echo htmlspecialchars($article['author_name'] ?? 'Unknown'); ?>
                            </span>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                            <?php echo htmlspecialchars($article['category'] ?? 'Uncategorized'); ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <?php echo htmlspecialchars(date('d M Y', strtotime($article['created_at']))); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                            <?php echo htmlspecialchars($article['status'] ?? 'Published'); ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <div class="flex items-center gap-2">
                            <a href="../artikel.php?slug=<?php echo urlencode($article['slug']); ?>" target="_blank"
                                class="text-blue-600 hover:text-blue-800 !rounded-button whitespace-nowrap"
                                title="Lihat Artikel">
                                <div class="w-4 h-4 flex items-center justify-center">
                                    <i class="ri-eye-line"></i>
                                </div>
                            </a>
                            <button onclick="copyArticleLink('<?php echo urlencode($article['slug']); ?>')"
                                class="text-green-600 hover:text-green-800 !rounded-button whitespace-nowrap"
                                title="Salin Link Artikel">
                                <div class="w-4 h-4 flex items-center justify-center">
                                    <i class="ri-link-line"></i>
                                </div>
                            </button>
                            <button onclick="openEditModal(<?php echo $article['id']; ?>)"
                                class="text-primary hover:text-secondary !rounded-button whitespace-nowrap"
                                title="Edit Artikel">
                                <div class="w-4 h-4 flex items-center justify-center">
                                    <i class="ri-edit-line"></i>
                                </div>
                            </button>
                            <button onclick="confirmDelete(<?php echo $article['id']; ?>)"
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
        <?php else: ?>
            <tr>
                <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                    Tidak ada artikel ditemukan.
                </td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
        </div>
      </div>


      <div class="grid grid-cols-1 gap-6 mb-8">
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
        {
                value: 1,
                name: 'Bisnis',
                itemStyle: { color: 'rgba(141, 211, 199, 1)' }
            },{
                value: 2,
                name: 'Kesehatan',
                itemStyle: { color: 'rgba(252, 141, 98, 1)' }
            },{
                value: 2,
                name: 'Pendidikan',
                itemStyle: { color: 'rgba(190, 144, 212, 1)' }
            },{
                value: 2,
                name: 'Teknologi',
                itemStyle: { color: 'rgba(87, 181, 231, 1)' }
            },    ];

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
                    <label class="block text-sm font-medium text-gray-700 mb-2">Gambar Utama</label>
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-primary transition-colors">
                        <div id="editPreviewContainer" class="hidden mb-4">
                            <img id="editImagePreview" class="max-h-48 mx-auto rounded-lg" alt="Preview">
                        </div>
                        <div id="editUploadPrompt">
                            <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center mx-auto mb-4">
                                <i class="ri-image-add-line text-gray-500 text-2xl"></i>
                            </div>
                            <p class="text-sm text-gray-600 mb-2">Drag & drop gambar atau</p>
                            <input type="file" id="editFeaturedImage" name="featuredImage" accept="image/*" class="hidden">
                            <button type="button" onclick="document.getElementById('editFeaturedImage').click()" class="text-primary font-medium text-sm hover:underline">pilih file</button>
                            <p class="text-xs text-gray-500 mt-2">PNG, JPG, GIF (Max. 10MB)</p>
                        </div>
                    </div>
                    <div id="currentPhotoPreview" class="mt-2">
                        <!-- Current photo preview will be inserted here -->
                    </div>
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

<script id="barChartScript">
document.addEventListener('DOMContentLoaded', function() {
  const barChart = echarts.init(document.getElementById('barChart'));
  
  // Data dari PHP
  const userData = [
    ['Raffli']  ];
  
  const articleData = [
    7  ];
  
  const barOption = {
    animation: false,
    grid: { top: 20, right: 20, bottom: 40, left: 80 },
    xAxis: {
      type: 'category',
      data: userData,
      axisLine: { show: false },
      axisTick: { show: false },
      axisLabel: {
        rotate: userData.length > 5 ? 45 : 0,
        fontSize: 12
      }
    },
    yAxis: {
      type: 'value',
      axisLine: { show: false },
      axisTick: { show: false },
      splitLine: { lineStyle: { color: '#f3f4f6' } }
    },
    series: [{
      data: articleData,
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
      textStyle: { color: '#1f2937' },
      formatter: function(params) {
        return `<strong>${params[0].name}</strong><br/>Jumlah Artikel: ${params[0].value}`;
      }
    }
  };
  barChart.setOption(barOption);
  
  // Responsive chart
  window.addEventListener('resize', function() {
    barChart.resize();
  });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const navLinks = document.querySelectorAll('nav a');
  navLinks.forEach(link => {
    link.addEventListener('click', function() {
      navLinks.forEach(l => l.classList.remove('text-primary', 'bg-primary/10'));
      this.classList.add('text-primary', 'bg-primary/10');
    });
  });

  const burgerMenu = document.querySelector('.burger-menu');
  const sidebar = document.querySelector('.sidebar');
  
  burgerMenu.addEventListener('click', function(e) {
    e.stopPropagation(); 
    sidebar.classList.toggle('collapsed');
  });

  document.addEventListener('click', function() {
    if (window.innerWidth < 768) {
      sidebar.classList.add('collapsed');
    }
  });
});
</script>
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
            // Tampilkan loading indicator
            Swal.fire({
                title: 'Menghapus...',
                html: 'Sedang menghapus artikel',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading()
                }
            });

            // Kirim request AJAX untuk hapus artikel
            fetch(`hapus_artikel.php?id=${articleId}`)
                .then(response => response.json())
                .then(data => {
                    Swal.close();
                    if (data.status === 'success') {
                        Swal.fire({
                            title: 'Berhasil!',
                            text: data.message,
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            // Refresh halaman setelah berhasil hapus
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            title: 'Gagal!',
                            text: data.message,
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                })
                .catch(error => {
                    Swal.close();
                    Swal.fire({
                        title: 'Error!',
                        text: 'Terjadi kesalahan saat menghapus artikel',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                });
        }
    });
}
</script>
<script>
  // Fungsi untuk modal edit
let currentArticleId = null;

function openEditModal(articleId) {
    currentArticleId = articleId;
    
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

                    // Show current photo preview if available
                    const photoPreviewDiv = document.getElementById('currentPhotoPreview');
                    photoPreviewDiv.innerHTML = '';
                    if (data.article.featured_image) {
                        const img = document.createElement('img');
                        img.src = data.article.featured_image;
                        img.alt = 'Foto Artikel';
                        img.className = 'max-w-xs max-h-40 rounded-md border border-gray-300';
                        photoPreviewDiv.appendChild(img);
                    }
                    
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
<script>
function copyArticleLink(slug) {
    const link = window.location.origin + '/artikel.php?slug=' + slug;
    navigator.clipboard.writeText(link).then(function() {
        // Show success message
        Swal.fire({
            title: 'Berhasil!',
            text: 'Link artikel berhasil disalin ke clipboard',
            icon: 'success',
            timer: 2000,
            showConfirmButton: false
        });
    }).catch(function(err) {
        console.error('Could not copy text: ', err);
        Swal.fire({
            title: 'Gagal!',
            text: 'Gagal menyalin link artikel',
            icon: 'error'
        });
    });
}

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('hidden');
}

function filterByMonthYear() {
    const monthSelect = document.getElementById('filterMonth');
    const yearSelect = document.getElementById('filterYear');
    const selectedMonth = monthSelect.value;
    const selectedYear = yearSelect.value;

    if (!selectedMonth || !selectedYear) {
        Swal.fire({
            title: 'Pilih Bulan dan Tahun',
            text: 'Silakan pilih bulan dan tahun terlebih dahulu',
            icon: 'warning'
        });
        return;
    }

    // Tampilkan loading
    Swal.fire({
        title: 'Memuat...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    // Kirim request AJAX untuk mendapatkan jumlah artikel pada bulan dan tahun tersebut
    fetch(`get_article_count.php?month=${selectedMonth}&year=${selectedYear}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // Update tampilan jumlah artikel
                document.getElementById('article-count').textContent = data.count;
                const monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                const monthName = monthNames[parseInt(selectedMonth) - 1];
                Swal.fire({
                    title: 'Berhasil!',
                    text: `Jumlah artikel pada ${monthName} ${selectedYear}: ${data.count}`,
                    icon: 'success',
                    timer: 3000,
                    showConfirmButton: false
                });

                // Load filtered articles
                loadFilteredArticles(selectedMonth, selectedYear);
            } else {
                Swal.close();
                Swal.fire({
                    title: 'Error!',
                    text: data.message,
                    icon: 'error'
                });
            }
        })
        .catch(error => {
            Swal.close();
            Swal.fire({
                title: 'Error!',
                text: 'Gagal memuat data artikel',
                icon: 'error'
            });
        });
}

function loadFilteredArticles(month, year) {
    fetch(`get_filtered_articles.php?month=${month}&year=${year}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                updateArticlesTable(data.articles);
            } else {
                console.error('Failed to load filtered articles:', data.message);
            }
        })
        .catch(error => {
            console.error('Error loading filtered articles:', error);
        });
}

function updateArticlesTable(articles) {
    const tbody = document.querySelector('tbody');
    tbody.innerHTML = '';

    if (articles.length > 0) {
        articles.forEach(article => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900">${article.title}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center">
                            <i class="ri-user-line text-gray-600"></i>
                        </div>
                        <span class="text-sm text-gray-900">
                            ${article.author_name || 'Unknown'}
                        </span>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                        ${article.category || 'Uncategorized'}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    ${new Date(article.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                        ${article.status || 'Published'}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <div class="flex items-center gap-2">
                        <a href="../artikel.php?slug=${encodeURIComponent(article.slug)}" target="_blank"
                            class="text-blue-600 hover:text-blue-800 !rounded-button whitespace-nowrap"
                            title="Lihat Artikel">
                            <div class="w-4 h-4 flex items-center justify-center">
                                <i class="ri-eye-line"></i>
                            </div>
                        </a>
                        <button onclick="copyArticleLink('${encodeURIComponent(article.slug)}')"
                            class="text-green-600 hover:text-green-800 !rounded-button whitespace-nowrap"
                            title="Salin Link Artikel">
                            <div class="w-4 h-4 flex items-center justify-center">
                                <i class="ri-link-line"></i>
                            </div>
                        </button>
                        <button onclick="openEditModal(${article.id})"
                            class="text-primary hover:text-secondary !rounded-button whitespace-nowrap"
                            title="Edit Artikel">
                            <div class="w-4 h-4 flex items-center justify-center">
                                <i class="ri-edit-line"></i>
                            </div>
                        </button>
                        <button onclick="confirmDelete(${article.id})"
                            class="text-red-600 hover:text-red-800 !rounded-button whitespace-nowrap"
                            title="Hapus Artikel">
                            <div class="w-4 h-4 flex items-center justify-center">
                                <i class="ri-delete-bin-line"></i>
                            </div>
                        </button>
                    </div>
                </td>
            `;
            tbody.appendChild(row);
        });
    } else {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                Tidak ada artikel ditemukan untuk filter ini.
            </td>
        `;
        tbody.appendChild(row);
    }
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

    // Animate breadcrumb
    const breadcrumb = document.querySelector('.px-6.py-4.bg-white.border-b');
    if (breadcrumb) {
        breadcrumb.classList.add('animate-fade-in-up');
        setTimeout(() => breadcrumb.style.animationDelay = '0.6s', 100);
    }

    // Animate main title
    const mainTitle = document.querySelector('main h1');
    if (mainTitle) {
        mainTitle.classList.add('animate-fade-in-up');
        setTimeout(() => mainTitle.style.animationDelay = '0.8s', 100);
    }

    // Animate main description
    const mainDesc = document.querySelector('main p');
    if (mainDesc) {
        mainDesc.classList.add('animate-fade-in-up');
        setTimeout(() => mainDesc.style.animationDelay = '1.0s', 100);
    }

    // Animate stats cards
    const statCards = document.querySelectorAll('.grid.grid-cols-1.md\\:grid-cols-2.lg\\:grid-cols-4 .bg-white.rounded-xl');
    statCards.forEach((card, index) => {
        card.classList.add('animate-fade-in-up');
        setTimeout(() => card.style.animationDelay = `${1.2 + index * 0.1}s`, 100);
    });

    // Animate articles table container
    const tableContainer = document.querySelector('.bg-white.rounded-xl.shadow-sm.border');
    if (tableContainer) {
        tableContainer.classList.add('animate-fade-in-up');
        setTimeout(() => tableContainer.style.animationDelay = '1.6s', 100);
    }

    // Animate chart containers
    const chartContainers = document.querySelectorAll('.bg-white.rounded-xl.shadow-sm.border.border-gray-200.p-6');
    chartContainers.forEach((container, index) => {
        container.classList.add('animate-fade-in-up');
        setTimeout(() => container.style.animationDelay = `${1.8 + index * 0.2}s`, 100);
    });
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const welcomePopup = document.getElementById('welcomePopup');
    if (welcomePopup) {
        welcomePopup.style.display = 'flex';
        setTimeout(() => {
            welcomePopup.style.display = 'none';
        }, 2000); // Show for 2 seconds
    }
});
</script>
</body>
</html>
