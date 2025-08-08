<?php
session_start();
require 'koneksi.php';
require 'functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $query = "SELECT * FROM user WHERE username = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            // Simpan data user ke session
            $_SESSION['user'] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role']
            ];
            $_SESSION['show_welcome'] = true;
            
            // Redirect berdasarkan role - PERBAIKAN DI SINI
            if ($user['role'] == 'admin') {
                header("Location: dashboard.php"); // Sudah berada di folder admin
            } else {
                header("Location: ../index.php"); // Kembali ke halaman utama
            }
            exit();
        } else {
            setFlashMessage('Password salah', 'error');
            header("Location: index.php");
            exit();
        }
    } else {
        setFlashMessage('Username tidak ditemukan', 'error');
        header("Location: index.php");
        exit();
    }
}
?>