<?php
include 'koneksi.php';

if (isset($_POST['submit'])) {
    $judul = $_POST['judul'];
    $isi   = $_POST['isi'];

 
    $namaFile = $_FILES['gambar']['name'];
    $tmpName  = $_FILES['gambar']['tmp_name'];
    $folder   = "admin/uploads/articles/";

    move_uploaded_file($tmpName, $folder . $namaFile);

    
    $sql = "INSERT INTO artikel (judul, isi, gambar) VALUES ('$judul', '$isi', 'admin/uploads/articles/$namaFile')";
    $query = mysqli_query($koneksi, $sql);

    if ($query) {
        echo "Artikel berhasil diupload. <a href='index.php'>Upload lagi</a>";
    } else {
        echo "Gagal upload: " . mysqli_error($koneksi);
    }
}
?>
