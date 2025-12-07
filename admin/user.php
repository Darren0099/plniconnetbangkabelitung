<?php
session_start();
require 'koneksi.php';
require 'functions.php';

requireAdmin();

// Handle delete user
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $userId = $_GET['delete'];
    
    // Prevent deleting own account
    if ($userId == $_SESSION['user']['id']) {
        setFlashMessage('Anda tidak dapat menghapus akun sendiri!', 'error');
        header("Location: user.php");
        exit();
    }
    
    // Check if user exists and is not the last admin
    $checkQuery = "SELECT role FROM user WHERE id = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Check if this is the last admin
        if ($user['role'] == 'admin') {
            $adminCountQuery = "SELECT COUNT(*) as admin_count FROM user WHERE role = 'admin'";
            $adminResult = mysqli_query($conn, $adminCountQuery);
            $adminRow = mysqli_fetch_assoc($adminResult);
            if ($adminRow['admin_count'] <= 1) {
                setFlashMessage('Tidak dapat menghapus admin terakhir!', 'error');
                header("Location: user.php");
                exit();
            }
        }
        
        // Delete user
        $deleteQuery = "DELETE FROM user WHERE id = ?";
        $stmt = $conn->prepare($deleteQuery);
        $stmt->bind_param("i", $userId);
        
        if ($stmt->execute()) {
            setFlashMessage('User berhasil dihapus!', 'success');
        } else {
            setFlashMessage('Gagal menghapus user!', 'error');
        }
    } else {
        setFlashMessage('User tidak ditemukan!', 'error');
    }
    
    header("Location: user.php");
    exit();
}

// Handle update user
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update') {
    $userId = $_POST['user_id'];
    $newRole = $_POST['role'];
    $newPassword = !empty($_POST['new_password']) ? password_hash($_POST['new_password'], PASSWORD_DEFAULT) : null;
    
    $updates = [];
    $types = "";
    $params = [];
    
    if ($newRole) {
        $updates[] = "role = ?";
        $types .= "s";
        $params[] = $newRole;
    }
    
    if ($newPassword) {
        $updates[] = "password = ?";
        $types .= "s";
        $params[] = $newPassword;
    }
    
    if (!empty($updates)) {
        $updateQuery = "UPDATE user SET " . implode(", ", $updates) . " WHERE id = ?";
        $types .= "i";
        $params[] = $userId;
        
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            setFlashMessage('User berhasil diperbarui!', 'success');
        } else {
            setFlashMessage('Gagal memperbarui user!', 'error');
        }
    } else {
        setFlashMessage('Tidak ada perubahan yang dilakukan!', 'info');
    }
    
    header("Location: user.php");
    exit();
}

// Handle search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchTerm = '%' . mysqli_real_escape_string($conn, $search) . '%';

// Query users
$query = "SELECT id, username, role FROM user WHERE username LIKE ? OR role LIKE ? ORDER BY id";
$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $searchTerm, $searchTerm);
$stmt->execute();
$result = $stmt->get_result();
$users = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>User Management - Admin</title>
<script src="https://cdn.tailwindcss.com/3.4.16"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Pacifico&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

<div class="flex min-h-screen">

  <aside id="sidebar" class="fixed left-0 top-0 h-screen w-64 bg-white border-r border-gray-200 z-30 hidden md:block">
    <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-200">
      <div class="text-xl font-['Pacifico'] text-primary">logo</div>
      <span class="font-semibold text-gray-900">APLN</span>
    </div>
    <nav class="p-4 space-y-2">
      <a href="dashboard.php" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-50 rounded-lg">
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
      <a href="user.php" class="flex items-center gap-3 px-3 py-2 text-primary bg-primary/10 rounded-lg">
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

  <!-- Main Content -->
  <div class="flex-1 ml-0 md:ml-64">
    <header class="bg-white shadow-sm border-b border-gray-200 px-6 py-4">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
          <div class="text-sm text-gray-600">Dashboard / User Management</div>
        </div>
        <div class="flex items-center gap-4">
          <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-primary rounded-full flex items-center justify-center">
              <i class="ri-user-line text-white text-sm"></i>
            </div>
            <div class="hidden md:block">
              <div class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($_SESSION['user']['username']); ?></div>
              <div class="text-xs text-gray-500">Admin</div>
            </div>
          </div>
        </div>
      </div>
    </header>

    <main class="p-6">
      <?php displayFlashMessage(); ?>
      
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">User Management</h1>
        <p class="text-gray-600">Kelola akun pengguna yang terdaftar di sistem.</p>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
          <h3 class="text-lg font-semibold text-gray-900">Daftar Pengguna</h3>
          <form method="GET" class="flex items-center space-x-2">
            <input type="text" name="search" placeholder="Cari pengguna..." value="<?php echo htmlspecialchars($search); ?>" 
                   class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" />
            <button type="submit" 
                    class="px-3 py-2 bg-primary text-white rounded-lg hover:bg-secondary transition">
              <i class="ri-search-line"></i>
            </button>
          </form>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Pengguna</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <?php if (count($users) > 0): ?>
                <?php $no = 1; foreach ($users as $user): ?>
                  <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $no++; ?></td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center">
                          <i class="ri-user-line text-gray-600"></i>
                        </div>
                        <span class="text-sm text-gray-900"><?php echo htmlspecialchars($user['username']); ?></span>
                      </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                        <?php echo $user['role'] == 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'; ?>">
                        <?php echo htmlspecialchars(ucfirst($user['role'] ?? 'user')); ?>
                      </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                      <div class="flex items-center gap-2">
                        <button onclick="openEditModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>', '<?php echo $user['role']; ?>')" 
                          class="text-primary hover:text-secondary rounded-button whitespace-nowrap"
                          title="Edit User">
                          <div class="w-4 h-4 flex items-center justify-center">
                            <i class="ri-edit-line"></i>
                          </div>
                        </button>
                        <?php if ($user['id'] != $_SESSION['user']['id']): ?>
                          <button onclick="confirmDelete(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')" 
                            class="text-red-600 hover:text-red-800 rounded-button whitespace-nowrap"
                            title="Hapus User">
                            <div class="w-4 h-4 flex items-center justify-center">
                              <i class="ri-delete-bin-line"></i>
                            </div>
                          </button>
                        <?php endif; ?>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                    Tidak ada pengguna ditemukan.
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</div>

<!-- Edit User Modal -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
  <div class="bg-white rounded-xl shadow-lg w-full max-w-md max-h-[90vh] overflow-y-auto">
    <div class="p-6">
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-xl font-bold text-gray-800">Edit User</h3>
        <button onclick="closeEditModal()" class="text-gray-500 hover:text-gray-700">
          <i class="ri-close-line text-2xl"></i>
        </button>
      </div>
      
      <form id="editForm" method="POST" class="space-y-4">
        <input type="hidden" name="action" value="update">
        <input type="hidden" id="editUserId" name="user_id">
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
          <input type="text" id="editUsername" class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100" readonly>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
          <select id="editRole" name="role" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
            <option value="user">User</option>
            <option value="admin">Admin</option>
          </select>
        </div>
        
        <div>
          <label for="newPassword" class="block text-sm font-medium text-gray-700 mb-1">Password Baru (kosongkan jika tidak diubah)</label>
          <input type="password" id="newPassword" name="new_password" 
                 class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                 placeholder="Masukkan password baru">
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
function confirmDelete(userId, username) {
  Swal.fire({
    title: 'Apakah Anda yakin?',
    text: `User "${username}" akan dihapus secara permanen!`,
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
      window.location.href = `user.php?delete=${userId}`;
    }
  });
}

function openEditModal(userId, username, role) {
  document.getElementById('editUserId').value = userId;
  document.getElementById('editUsername').value = username;
  document.getElementById('editRole').value = role;
  document.getElementById('newPassword').value = '';
  document.getElementById('editModal').classList.remove('hidden');
}

function closeEditModal() {
  document.getElementById('editModal').classList.add('hidden');
  document.getElementById('editForm').reset();
}

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

    // Animate table container
    const tableContainer = document.querySelector('.bg-white.rounded-xl');
    if (tableContainer) {
        tableContainer.classList.add('animate-fade-in-up');
        setTimeout(() => tableContainer.style.animationDelay = '1.0s', 100);
    }

    // Animate table rows
    const tableRows = document.querySelectorAll('tbody tr');
    tableRows.forEach((row, index) => {
        row.classList.add('animate-fade-in-up');
        setTimeout(() => row.style.animationDelay = `${1.2 + index * 0.1}s`, 100);
    });
});
</script>
</body>
</html>
