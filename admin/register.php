<?php
// register.php
session_start();
require 'koneksi.php';
require 'functions.php';

$username = $_POST['username'];
$email = $_POST['email'];
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);

$cek = mysqli_query($conn, "SELECT * FROM user WHERE username='$username'");
if (mysqli_num_rows($cek) > 0) {
    setFlashMessage('Username sudah digunakan!', 'error');
    header("Location: index.php");
    exit();
}

$query = mysqli_query($conn, "INSERT INTO user (username, email, password) VALUES ('$username', '$email', '$password')");

if ($query) {
    setFlashMessage('Akun berhasil didaftarkan! Silakan login.', 'success');
    header("Location: index.php");
} else {
    setFlashMessage('Pendaftaran gagal. Silakan coba lagi.', 'error');
    header("Location: index.php");
}
exit();
?>