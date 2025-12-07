<?php
session_start();
require_once 'koneksi.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user']['id'];
$error = '';
$success = '';



if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    
    if (empty($username)) {
        $error = 'Username tidak boleh kosong';
    } elseif (empty($email)) {
        $error = 'Email tidak boleh kosong';
    } else {
        
        $stmt = $conn->prepare("UPDATE user SET username = ?, email = ? WHERE id = ?");
        $stmt->bind_param("ssi", $username, $email, $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['user']['username'] = $username;
            $_SESSION['user']['email'] = $email;
            $success = 'Profil berhasil diperbarui';
        } else {
            $error = 'Gagal memperbarui profil: ' . $conn->error;
        }
    }
}



// Proses ubah password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validasi
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'Semua field password harus diisi';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Password baru dan konfirmasi password tidak cocok';
    } elseif (strlen($new_password) < 8) {
        $error = 'Password minimal 8 karakter';
    } else {
        // Verifikasi password saat ini
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if (password_verify($current_password, $user['password'])) {
            // Update password baru
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed_password, $user_id);
            
            if ($stmt->execute()) {
                $success = 'Password berhasil diubah';
            } else {
                $error = 'Gagal mengubah password';
            }
        } else {
            $error = 'Password saat ini salah';
        }
    }
}

// Ambil data user terbaru
$stmt = $conn->prepare("SELECT * FROM user WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();


// Update session dengan data terbaru
$_SESSION['user'] = $user;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Pengguna - APLN</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css" rel="stylesheet">
    <style>
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
        <button onclick="toggleSidebar()" class="p-2 bg-white rounded-lg shadow-md border border-gray-200 transition-all duration-300 hover:shadow-lg">
            <i class="ri-menu-line text-gray-600 transition-transform duration-300"></i>
        </button>
    </div>

    <!-- Sidebar -->
    <aside id="sidebar" class="fixed left-0 top-0 h-screen w-64 bg-white border-r border-gray-200 z-30 hidden md:block">
        <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-200">
            <div class="text-xl font-['Pacifico'] text-primary">logo</div>
            <span class="font-semibold text-gray-900">APLN</span>
        </div>
        <nav class="p-4 space-y-2">
            <a href="dashboard.php" class="flex items-center gap-3 px-3 py-2 rounded-lg <?= ($active_page == 'dashboard.php') ? 'text-primary bg-primary/10' : 'text-gray-600 hover:bg-gray-50' ?>">
                <div class="w-5 h-5 flex items-center justify-center">
                    <i class="ri-home-line"></i>
                </div>
                <span>Dashboard</span>
            </a>
            <a href="artikel.php" class="flex items-center gap-3 px-3 py-2 rounded-lg <?= ($active_page == 'artikel.php') ? 'text-primary bg-primary/10' : 'text-gray-600 hover:bg-gray-50' ?>">
                <div class="w-5 h-5 flex items-center justify-center">
                    <i class="ri-article-line"></i>
                </div>
                <span>Artikel</span>
            </a>
            <a href="user.php" class="flex items-center gap-3 px-3 py-2 rounded-lg <?= ($active_page == 'user.php') ? 'text-primary bg-primary/10' : 'text-gray-600 hover:bg-gray-50' ?>">
                <div class="w-5 h-5 flex items-center justify-center">
                    <i class="ri-team-line"></i>
                </div>
                <span>User Management</span>
            </a>
            <a href="profile.php" class="flex items-center gap-3 px-3 py-2 rounded-lg <?= ($active_page == 'profile.php') ? 'text-primary bg-primary/10' : 'text-gray-600 hover:bg-gray-50' ?>">
                <div class="w-5 h-5 flex items-center justify-center">
                    <i class="ri-user-settings-line"></i>
                </div>
                <span>My Profile</span>
            </a>
        </nav>
        <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-gray-200">
            <button onclick="confirmLogout()" class="flex items-center gap-3 px-3 py-2 w-full text-gray-600 hover:bg-red-50 hover:text-red-600 rounded-lg transition-colors">
                <div class="w-5 h-5 flex items-center justify-center">
                    <i class="ri-logout-box-line"></i>
                </div>
                <span>Keluar</span>
            </button>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 ml-0 md:ml-64">
        <div class="p-8">
            <div class="max-w-4xl mx-auto bg-white rounded-xl shadow-md overflow-hidden animate-fade-in-up" style="animation-delay: 0.1s;">
                <!-- Header -->
                <div class="bg-primary p-6 text-white animate-fade-in-left" style="animation-delay: 0.2s;">
                    <h1 class="text-2xl font-bold">Informasi Pribadi</h1>
                    <p class="text-primary-100">Perbarui dan kelola detail profil APLN Anda</p>
                </div>

                <!-- Notifikasi -->
                <?php if (!empty($error)): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mx-6 mt-4 animate-fade-in-right" style="animation-delay: 0.3s;">
                        <p><?php echo htmlspecialchars($error); ?></p>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mx-6 mt-4 animate-fade-in-right" style="animation-delay: 0.3s;">
                        <p><?php echo htmlspecialchars($success); ?></p>
                    </div>
                <?php endif; ?>

                <!-- Form Profil -->
                <div class="p-6 animate-fade-in-up" style="animation-delay: 0.4s;">
                    <form method="POST">
                         <h2 class="text-lg font-semibold mb-4">Ubah Username</h2>
                            <p class="text-gray-600 mb-6">Hi Selamat datang di Profile</p>

                        <!-- Informasi Profil -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8 animate-fade-in-up" style="animation-delay: 0.5s;">

                            <div class="animate-fade-in-left" style="animation-delay: 0.6s;">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Username *</label>
                                <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" required>
                            </div>

                            <div class="animate-fade-in-right" style="animation-delay: 0.7s;">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" required>
                            </div>

                            <div class="animate-fade-in-left" style="animation-delay: 0.8s;">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                                <input type="text" value="<?php echo htmlspecialchars(ucfirst($user['role'])); ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100" readonly>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 animate-fade-in-up" style="animation-delay: 0.9s;">
                            <!-- Tombol Atur Ulang -->
                            <button type="button" onclick="confirmResetForm()"
                                    class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition">
                                <i class="ri-arrow-go-back-line mr-2"></i> Atur Ulang
                            </button>

                            <!-- Tombol Simpan Perubahan (ditambahkan) -->
                           <button type="submit" name="update_profile"
                                    class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition">
                                <i class="ri-save-line mr-2"></i> Simpan Perubahan
                            </button>
                        </div>
                    </form>

                    <!-- Ubah Password -->
                    <div class="mt-12 pt-8 border-t border-gray-200 animate-fade-in-up" style="animation-delay: 1.0s;">
                        <h2 class="text-lg font-semibold mb-4">Ubah Kata Sandi</h2>
                        <p class="text-gray-600 mb-6">Pastikan kata sandi baru Anda kuat dan aman.</p>

                        <form id="passwordForm" method="POST">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 animate-fade-in-up" style="animation-delay: 1.1s;">
                                <div class="animate-fade-in-left" style="animation-delay: 1.2s;">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Password Sekarang *</label>
                                    <input type="password" name="current_password" placeholder="Masukkan password saat ini"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" required>
                                </div>

                                <div class="animate-fade-in-right" style="animation-delay: 1.3s;">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Password Baru *</label>
                                    <input type="password" name="new_password" placeholder="Masukkan password baru"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" required>
                                </div>

                                <div class="animate-fade-in-left" style="animation-delay: 1.4s;">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi Password Baru *</label>
                                    <input type="password" name="confirm_password" placeholder="Konfirmasi password baru"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" required>
                                </div>
                            </div>

                            <div class="flex justify-end gap-3 mt-6 animate-fade-in-up" style="animation-delay: 1.5s;">
                                <!-- Tombol Atur Ulang -->
                                <button type="button" onclick="confirmResetPasswordForm()"
                                        class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition">
                                    <i class="ri-arrow-go-back-line mr-2"></i> Atur Ulang
                                </button>

                                <!-- Tombol Simpan Perubahan (ditambahkan) -->
                                <button type="submit" name="change_password"
                                        class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition">
                                    <i class="ri-save-line mr-2"></i> Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- SweetAlert JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    // Konfirmasi Logout
    function confirmLogout() {
        Swal.fire({
            title: 'Yakin ingin keluar?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Keluar',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'logout.php';
            }
        });
    }

    // Konfirmasi Update Profil
    function confirmProfileUpdate() {
        Swal.fire({
            title: 'Simpan Perubahan Profil?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Simpan',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                document.querySelector('form').submit();
            }
        });
    }

    // Konfirmasi Ubah Password
    function confirmPasswordChange() {
        const form = document.getElementById('passwordForm');
        const newPassword = form.querySelector('input[name="new_password"]').value;
        const confirmPassword = form.querySelector('input[name="confirm_password"]').value;
        
        if (newPassword !== confirmPassword) {
            Swal.fire('Error!', 'Password baru dan konfirmasi tidak cocok', 'error');
            return;
        }
        
        if (newPassword.length < 8) {
            Swal.fire('Error!', 'Password minimal 8 karakter', 'error');
            return;
        }
        
        Swal.fire({
            title: 'Yakin ubah password?',
            text: 'Anda harus login kembali setelah password diubah',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Ubah',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    }



    // Konfirmasi Reset Form
    function confirmResetForm() {
        Swal.fire({
            title: 'Reset Form?',
            text: 'Semua perubahan yang belum disimpan akan hilang',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Reset',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                document.querySelector('form').reset();
                Swal.fire('Direset!', 'Form telah dikembalikan ke nilai awal', 'success');
            }
        });
    }

    // Konfirmasi Reset Password Form
    function confirmResetPasswordForm() {
        Swal.fire({
            title: 'Reset Form Password?',
            text: 'Semua perubahan password yang belum disimpan akan hilang',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Reset',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('passwordForm').reset();
                Swal.fire('Direset!', 'Form password telah dikembalikan', 'success');
            }
        });
    }
    </script>
    <script>
        // Fungsi untuk handle submit form profil
document.querySelector('form').addEventListener('submit', function(e) {
    if (e.submitter.name === 'update_profile') {
        e.preventDefault();
        Swal.fire({
            title: 'Simpan Perubahan Profil?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Simpan',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                this.submit();
            }
        });
    }
});

// Fungsi untuk handle submit form password
document.getElementById('passwordForm').addEventListener('submit', function(e) {
    if (e.submitter.name === 'change_password') {
        e.preventDefault();
        
        // Validasi password
        const newPassword = this.querySelector('input[name="new_password"]').value;
        const confirmPassword = this.querySelector('input[name="confirm_password"]').value;
        
        if (newPassword !== confirmPassword) {
            Swal.fire('Error!', 'Password baru dan konfirmasi tidak cocok', 'error');
            return;
        }
        
        if (newPassword.length < 8) {
            Swal.fire('Error!', 'Password minimal 8 karakter', 'error');
            return;
        }
        
        Swal.fire({
            title: 'Yakin ubah password?',
            text: 'Anda harus login kembali setelah password diubah',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Ubah',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                this.submit();
            }
        });
    }
});
    </script>

<script>
    // Show SweetAlert when email is changed
    document.querySelector('input[name="email"]').addEventListener('change', function() {
        Swal.fire({
            icon: 'info',
            title: 'Email diubah',
            text: 'Anda telah mengubah email Anda.',
            confirmButtonText: 'OK'
        });
    });
</script>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const burgerBtn = document.querySelector('.md\\:hidden button');

        sidebar.classList.toggle('hidden');

        // Add burger menu animation
        if (burgerBtn) {
            burgerBtn.classList.toggle('active');
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

        // Animate main content
        const mainContent = document.querySelector('.flex-1');
        if (mainContent) {
            mainContent.classList.add('animate-fade-in-up');
            setTimeout(() => mainContent.style.animationDelay = '0.4s', 100);
        }

        // Animate header
        const header = document.querySelector('.bg-primary');
        if (header) {
            header.classList.add('animate-fade-in-up');
            setTimeout(() => header.style.animationDelay = '0.6s', 100);
        }

        // Animate form sections
        const formSections = document.querySelectorAll('form, .mt-12');
        formSections.forEach((section, index) => {
            section.classList.add('animate-fade-in-up');
            setTimeout(() => section.style.animationDelay = `${0.8 + index * 0.2}s`, 100);
        });

        // Animate buttons
        const buttons = document.querySelectorAll('button');
        buttons.forEach((button, index) => {
            button.classList.add('animate-fade-in-up');
            setTimeout(() => button.style.animationDelay = `${1.2 + index * 0.1}s`, 100);
        });
    });
</script>
</body>
</html>
