-- Database setup for PLN ICON CMS
-- Run this script in phpMyAdmin or MySQL command line

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS plnicon_db;
USE plnicon_db;

-- Create user table
CREATE TABLE IF NOT EXISTS user (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create articles table
CREATE TABLE IF NOT EXISTS articles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    category VARCHAR(100) NOT NULL,
    content TEXT NOT NULL,
    tags VARCHAR(500),
    featured_image VARCHAR(500),
    author_id INT NOT NULL,
    status ENUM('draft', 'published') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES user(id) ON DELETE CASCADE
);

-- Insert default admin user (password: admin123)
INSERT IGNORE INTO user (username, email, password, role) VALUES
('admin', 'admin@plnicon.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Insert sample articles
INSERT IGNORE INTO articles (title, slug, category, content, tags, author_id, status) VALUES
('Selamat Datang di PLN ICONNET', 'selamat-datang-di-pln-iconnet', 'teknologi',
'<p>Selamat datang di website resmi PLN ICONNET. Kami berkomitmen untuk memberikan pelayanan listrik terbaik kepada masyarakat.</p>
<h2>Visi Kami</h2>
<p>Menjadi perusahaan listrik terdepan dalam inovasi dan pelayanan pelanggan.</p>
<h2>Misi Kami</h2>
<ul>
<li>Menyediakan listrik berkualitas tinggi</li>
<li>Mengembangkan teknologi terbaru</li>
<li>Meningkatkan pelayanan pelanggan</li>
</ul>',
'teknologi, pln, iconnet', 1, 'published'),

('Tips Hemat Listrik di Rumah', 'tips-hemat-listrik-di-rumah', 'lifestyle',
'<p>Berikut adalah beberapa tips praktis untuk menghemat penggunaan listrik di rumah:</p>
<h2>1. Matikan Peralatan yang Tidak Digunakan</h2>
<p>Selalu matikan lampu dan peralatan elektronik ketika tidak digunakan.</p>
<h2>2. Gunakan Lampu LED</h2>
<p>Lampu LED lebih hemat energi dibandingkan lampu pijar.</p>
<h2>3. Manfaatkan Cahaya Alami</h2>
<p>Buka tirai jendela untuk memanfaatkan cahaya matahari di siang hari.</p>',
'hemat, listrik, tips', 1, 'published');

-- Create indexes for better performance
CREATE INDEX idx_articles_slug ON articles(slug);
CREATE INDEX idx_articles_status ON articles(status);
CREATE INDEX idx_articles_category ON articles(category);
CREATE INDEX idx_articles_author ON articles(author_id);
CREATE INDEX idx_user_email ON user(email);
