# ⚡ Website Artikel PLN Bangka Belitung

Website portal informasi dan artikel resmi untuk menyajikan berita, pembaruan layanan, tips kelistrikan, serta edukasi publik seputar layanan PT PLN (Persero) Wilayah Bangka Belitung.

---

## 📌 Fitur Utama

* **Portal Artikel & Berita:** Publikasi artikel terkini seputar kelistrikan, program promo, dan kegiatan PLN di wilayah Bangka Belitung.
* **Kategori Artikel:** Pengelompokan konten (Edukasi, Promo, Berita Daerah, Pemeliharaan, Tips Hemat Energi).
* **Pencarian & Filter:** Pencarian artikel berdasarkan kata kunci atau kategori.
* **Tampilan Responsif:** Optimal di perangkat *mobile*, *tablet*, maupun *desktop*.
* **Panel Admin (CMS):** Pengelolaan artikel (Tambah, Edit, Hapus) dan manajemen media/gambar.

---

## 🛠️ Teknologi yang Digunakan

* **PHP** (92.4%) – Logika server, pemrosesan data, dan integrasi database.
* **HTML5** (7.1%) – Struktur dan tata letak halaman web.
* **CSS3** (0.5%) – Penataan gaya tampilan visual.
* **Database:** MySQL / MariaDB

---

## 🚀 Panduan Instalasi (Lokal)

Ikuti langkah-langkah berikut untuk menjalankan proyek di server lokal (XAMPP / Laragon):

### 1. Klon / Unduh Repositori
Pindahkan direktori proyek ke dalam folder server web Anda (`htdocs` untuk XAMPP atau `www` untuk Laragon):
```bash
cd C:/xampp/htdocs/
git clone [https://github.com/username/website-artikel-pln-babel.git](https://github.com/username/website-artikel-pln-babel.git) 

### 2. Import Database
Buka phpMyAdmin (http://localhost/phpmyadmin).

Buat database baru, misalnya dengan nama db_pln_babel.

Import file database yang berada di dalam folder proyek (contoh: database/db_pln_babel.sql).

3. Konfigurasi Koneksi Database
Sesuaikan kredensial database pada file konfigurasi (misalnya config/koneksi.php atau config/database.php):

PHP
$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_pln_babel";
4. Jalankan Aplikasi
Buka browser dan akses URL berikut:

Plaintext
http://localhost/website-artikel-pln-babel
