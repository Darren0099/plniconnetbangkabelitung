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

// Fungsi untuk upload gambar
function uploadAvatar($file, $user_id) {
    $target_dir = "uploads/avatars/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $imageFileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $target_file = $target_dir . $user_id . '.' . $imageFileType;
    
    // Validasi gambar
    $check = getimagesize($file['tmp_name']);
    if ($check === false) {
        return ['status' => 'error', 'message' => 'File bukan gambar'];
    }
    
    // Cek ukuran file (max 2MB)
    if ($file['size'] > 2000000) {
        return ['status' => 'error', 'message' => 'Ukuran gambar terlalu besar (max 2MB)'];
    }
    
    // Format yang diizinkan
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($imageFileType, $allowed)) {
        return ['status' => 'error', 'message' => 'Hanya format JPG, JPEG, PNG & GIF yang diizinkan'];
    }
    
    // Upload file
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return ['status' => 'success', 'file_path' => $target_file];
    } else {
        return ['status' => 'error', 'message' => 'Gagal mengupload gambar'];
    }
}

// Proses update profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $username = trim($_POST['username']);
    
    if (empty($username)) {
        $error = 'Username tidak boleh kosong';
    } else {
        // Update username
        $stmt = $conn->prepare("UPDATE users SET username = ? WHERE id = ?");
        $stmt->bind_param("si", $username, $user_id);
        
        if ($stmt->execute()) {
            // Update session
            $_SESSION['user']['username'] = $username;
            $success = 'Profil berhasil diperbarui';
        } else {
            $error = 'Gagal memperbarui profil: ' . $conn->error;
        }
    }
}

// Proses upload avatar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_avatar'])) {
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $upload = uploadAvatar($_FILES['avatar'], $user_id);
        
        if ($upload['status'] === 'success') {
            // Update path avatar di database
            $stmt = $conn->prepare("UPDATE users SET avatar = ? WHERE id = ?");
            $stmt->bind_param("si", $upload['file_path'], $user_id);
            
            if ($stmt->execute()) {
                $_SESSION['user']['avatar'] = $upload['file_path'];
                $success = 'Avatar berhasil diupload';
            } else {
                $error = 'Gagal menyimpan path avatar';
            }
        } else {
            $error = $upload['message'];
        }
    } else {
        $error = 'Silakan pilih gambar untuk diupload';
    }
}

// Proses hapus avatar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_avatar'])) {
    if (!empty($_SESSION['user']['avatar']) && file_exists($_SESSION['user']['avatar'])) {
        unlink($_SESSION['user']['avatar']);
    }
    
    $stmt = $conn->prepare("UPDATE users SET avatar = NULL WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['user']['avatar'] = '';
        $success = 'Avatar berhasil dihapus';
    } else {
        $error = 'Gagal menghapus avatar';
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
        .main-content {
            margin-left: 16rem; /* Sesuai lebar sidebar */
        }
        .avatar-preview {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 3px solid #6366F1;
            background-color: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .avatar-upload input[type="file"] {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Sidebar -->
    <aside class="fixed left-0 top-0 h-screen w-64 bg-white border-r border-gray-200 z-30">
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
            <a href="user-management.php" class="flex items-center gap-3 px-3 py-2 rounded-lg <?= ($active_page == 'user-management.php') ? 'text-primary bg-primary/10' : 'text-gray-600 hover:bg-gray-50' ?>">
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
    <div class="main-content">
        <div class="p-8">
            <div class="max-w-4xl mx-auto bg-white rounded-xl shadow-md overflow-hidden">
                <!-- Header -->
                <div class="bg-primary p-6 text-white">
                    <h1 class="text-2xl font-bold">Informasi Pribadi</h1>
                    <p class="text-primary-100">Perbarui dan kelola detail profil APLN Anda</p>
                </div>
                
                <!-- Notifikasi -->
                <?php if (!empty($error)): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mx-6 mt-4">
                        <p><?php echo htmlspecialchars($error); ?></p>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mx-6 mt-4">
                        <p><?php echo htmlspecialchars($success); ?></p>
                    </div>
                <?php endif; ?>
                
                <!-- Form Profil -->
                <div class="p-6">
                    <form method="POST" enctype="multipart/form-data">
                        <!-- Avatar Section -->
                        <div class="mb-8 text-center">
                            <h2 class="text-lg font-semibold mb-4">Profile Picture</h2>
                            <div class="flex flex-col items-center">
                                <div class="avatar-preview mb-4">
                                    <?php if (!empty($user['avatar']) && file_exists($user['avatar'])): ?>
                                        <img src="<?php echo htmlspecialchars($user['avatar']); ?>" alt="Avatar">
                                    <?php else: ?>
                                        <i class="ri-user-fill text-6xl text-gray-400"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="flex gap-3">
                                    <label class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-secondary cursor-pointer">
                                        <i class="ri-upload-line mr-2"></i> Unggah Avatar
                                        <input type="file" name="avatar" accept="image/*" class="hidden" onchange="confirmAvatarUpload(this)">
                                    </label>
                                    <?php if (!empty($user['avatar'])): ?>
                                        <button type="button" onclick="confirmDeleteAvatar()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                                            <i class="ri-delete-bin-line mr-2"></i> Hapus
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Informasi Profil -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Username *</label>
                                <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" required>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100" readonly>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                                <input type="text" value="<?php echo htmlspecialchars(ucfirst($user['role'])); ?>" 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100" readonly>
                            </div>
                        </div>
                        
                        <div class="flex justify-end gap-3">
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
                    <div class="mt-12 pt-8 border-t border-gray-200">
                        <h2 class="text-lg font-semibold mb-4">Ubah Kata Sandi</h2>
                        <p class="text-gray-600 mb-6">Pastikan kata sandi baru Anda kuat dan aman.</p>
                        
                        <form id="passwordForm" method="POST">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Password Sekarang *</label>
                                    <input type="password" name="current_password" placeholder="Masukkan password saat ini" 
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" required>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Password Baru *</label>
                                    <input type="password" name="new_password" placeholder="Masukkan password baru" 
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" required>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi Password Baru *</label>
                                    <input type="password" name="confirm_password" placeholder="Konfirmasi password baru" 
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" required>
                                </div>
                            </div>
                            
                            <div class="flex justify-end gap-3 mt-6">
                                <!-- Tombol Atur Ulang -->
                                <button type="button" onclick="confirmResetPasswordForm()" 
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

    // Konfirmasi Hapus Avatar
    function confirmDeleteAvatar() {
        Swal.fire({
            title: 'Hapus Avatar?',
            text: 'Avatar akan dihapus permanen',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="delete_avatar" value="1">';
                document.body.appendChild(form);
                form.submit();
            }
        });
    }

    // Konfirmasi Upload Avatar
    function confirmAvatarUpload(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
            
            if (!validTypes.includes(file.type)) {
                Swal.fire('Error!', 'Hanya format JPG, PNG, dan GIF yang diizinkan', 'error');
                input.value = '';
                return;
            }
            
            if (file.size > 2000000) {
                Swal.fire('Error!', 'Ukuran gambar maksimal 2MB', 'error');
                input.value = '';
                return;
            }
            
            Swal.fire({
                title: 'Upload Avatar?',
                html: `Anda yakin ingin mengupload <strong>${file.name}</strong> sebagai avatar baru?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Upload',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.enctype = 'multipart/form-data';
                    
                    const inputClone = document.createElement('input');
                    inputClone.type = 'file';
                    inputClone.name = 'avatar';
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(file);
                    inputClone.files = dataTransfer.files;
                    
                    form.appendChild(inputClone);
                    document.body.appendChild(form);
                    form.submit();
                } else {
                    input.value = '';
                }
            });
        }
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
</body>
</html>