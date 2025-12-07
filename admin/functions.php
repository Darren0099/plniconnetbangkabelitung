<?php

function isAdmin() {
    return isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin';
}

function requireAdmin() {
    if (!isAdmin()) {
        header("Location: index.php");
        exit();
    }
}
function setFlashMessage($message, $type = 'success') {
    $_SESSION['flash'] = [
        'message' => $message,
        'type' => $type // success, error, warning, info
    ];
}

function displayFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        $colorClasses = [
            'success' => 'bg-green-100 border-green-400 text-green-700',
            'error' => 'bg-red-100 border-red-400 text-red-700',
            'warning' => 'bg-yellow-100 border-yellow-400 text-yellow-700',
            'info' => 'bg-blue-100 border-blue-400 text-blue-700'
        ];
        $class = isset($colorClasses[$flash['type']]) ? $colorClasses[$flash['type']] : $colorClasses['info'];
        
        echo '<div class="flash-message fixed top-4 right-4 px-4 py-3 border rounded '.$class.'" role="alert">
                <div class="flex items-center gap-2">
                    <i class="'.getIconClass($flash['type']).'"></i>
                    <span>'.htmlspecialchars($flash['message']).'</span>
                </div>
              </div>';
        
        unset($_SESSION['flash']);
    }
}

function getIconClass($type) {
    $icons = [
        'success' => 'ri-checkbox-circle-fill',
        'error' => 'ri-close-circle-fill',
        'warning' => 'ri-error-warning-fill',
        'info' => 'ri-information-fill'
    ];
    return isset($icons[$type]) ? $icons[$type] : 'ri-information-fill';
}

/**
 * Get the correct URL path for article featured image.
 * @param string $imagePath The stored image path from DB.
 * @return string The correct URL to use in img src.
 */
function getArticleImagePath($imagePath) {
    if (empty($imagePath)) {
        return 'https://via.placeholder.com/600x400?text=No+Image';
    }
    $filename = basename($imagePath);
    // Always use admin/uploads/articles/ as base path for images
    return 'admin/uploads/articles/' . $filename;
}

/**
 * Resize image to specified dimensions while maintaining aspect ratio
 * @param string $filePath Path to the image file
 * @param int $maxWidth Maximum width
 * @param int $maxHeight Maximum height
 * @return mixed GD image resource or false on failure
 */
function resizeImage($filePath, $maxWidth, $maxHeight) {
    // Get image info
    $imageInfo = getimagesize($filePath);
    if (!$imageInfo) {
        return false;
    }

    $originalWidth = $imageInfo[0];
    $originalHeight = $imageInfo[1];
    $mimeType = $imageInfo['mime'];

    // Create image resource based on type
    switch ($mimeType) {
        case 'image/jpeg':
            $sourceImage = imagecreatefromjpeg($filePath);
            break;
        case 'image/png':
            $sourceImage = imagecreatefrompng($filePath);
            break;
        case 'image/gif':
            $sourceImage = imagecreatefromgif($filePath);
            break;
        case 'image/webp':
            $sourceImage = imagecreatefromwebp($filePath);
            break;
        default:
            return false;
    }

    if (!$sourceImage) {
        return false;
    }

    // Calculate new dimensions maintaining aspect ratio
    $aspectRatio = $originalWidth / $originalHeight;

    if ($originalWidth > $originalHeight) {
        // Landscape
        $newWidth = min($maxWidth, $originalWidth);
        $newHeight = $newWidth / $aspectRatio;
        if ($newHeight > $maxHeight) {
            $newHeight = $maxHeight;
            $newWidth = $newHeight * $aspectRatio;
        }
    } else {
        // Portrait or square
        $newHeight = min($maxHeight, $originalHeight);
        $newWidth = $newHeight * $aspectRatio;
        if ($newWidth > $maxWidth) {
            $newWidth = $maxWidth;
            $newHeight = $newWidth / $aspectRatio;
        }
    }

    // Create new image
    $resizedImage = imagecreatetruecolor($newWidth, $newHeight);

    // Preserve transparency for PNG/GIF
    if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
        imagecolortransparent($resizedImage, imagecolorallocatealpha($resizedImage, 0, 0, 0, 127));
        imagealphablending($resizedImage, false);
        imagesavealpha($resizedImage, true);
    }

    // Resize the image
    imagecopyresampled($resizedImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);

    // Free memory
    imagedestroy($sourceImage);

    return $resizedImage;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * General function to send email using PHPMailer
 * @param string $to Recipient email address
 * @param string $subject Email subject
 * @param string $body Email body (HTML)
 * @param string $fromEmail Sender email (default: artikelbangkabelitungpln@gmail.com)
 * @param string $fromName Sender name (default: PLN ICONNET)
 * @return bool True on success, false on failure
 */
function sendEmail($to, $subject, $body, $fromEmail = 'artikelbangkabelitungpln@gmail.com', $fromName = 'PLN ICONNET') {
    require_once __DIR__ . '/../PHPMailer/PHPMailer/PHPMailer.php';
    require_once __DIR__ . '/../PHPMailer/PHPMailer/SMTP.php';
    require_once __DIR__ . '/../PHPMailer/PHPMailer/Exception.php';

    $mail = new PHPMailer(true);

    try {
        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'artikelbangkabelitungpln@gmail.com';
        $mail->Password = 'trde qhni whaz rqdn';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        // Email Identity
        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;

        return $mail->send();
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}

function sendNewArticleNotification($articleData, $conn) {
    require_once __DIR__ . '/../PHPMailer/PHPMailer/PHPMailer.php';
    require_once __DIR__ . '/../PHPMailer/PHPMailer/SMTP.php';
    require_once __DIR__ . '/../PHPMailer/PHPMailer/Exception.php';

    $mail = new PHPMailer(true);

    try {
        // =========================
        // CONFIGURASI SMTP GMAIL
        // =========================
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;

        // Email kamu (harus aktif APP PASSWORD)
        $mail->Username = 'artikelbangkabelitungpln@gmail.com';
        $mail->Password = 'trde qhni whaz rqdn';

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        // Add timeout to prevent hanging
        $mail->Timeout = 10; // 10 seconds timeout
        $mail->SMTPKeepAlive = false;

        // =========================
        // IDENTITAS EMAIL
        // =========================
        $mail->setFrom('artikelbangkabelitungpln@gmail.com', 'PLN ICONNET');
        $mail->isHTML(true);

        // Judul Email
        $mail->Subject = 'Artikel Baru: ' . $articleData['title'];

        $logMessage = date('Y-m-d H:i:s') . " - Email subject set: {$mail->Subject}\n";
        file_put_contents(__DIR__ . '/email_log.txt', $logMessage, FILE_APPEND);

        // =========================
        // ISI EMAIL
        // =========================
        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
        $article_url = 'http://' . $host . '/artikel.php?slug=' . $articleData['slug'];

        $mail->Body = "
        <div style='font-family: Arial; padding: 20px;'>
            <h2 style='color:#007bff'>{$articleData['title']}</h2>
            <p><strong>Kategori:</strong> {$articleData['category']}</p>
            <p>Ada artikel baru yang baru saja dipublikasikan di website PLN ICONNET.</p>
            <a href='{$article_url}'
               style='display:inline-block; padding:10px 20px; background:#28a745; color:white; text-decoration:none; border-radius:5px;'>
               Baca Artikel
            </a>
            <br><br>
            <p>Terima kasih telah mengikuti berita dari kami.</p>
        </div>
        ";

        $logMessage = date('Y-m-d H:i:s') . " - Email body set\n";
        file_put_contents(__DIR__ . '/email_log.txt', $logMessage, FILE_APPEND);

        // =========================
        // AMBIL SEMUA USER EMAIL
        // =========================
        $query = "SELECT email FROM user WHERE email IS NOT NULL AND email != ''";
        $result = mysqli_query($conn, $query);

        if (!$result) {
            $logMessage = date('Y-m-d H:i:s') . " - Database query failed: " . mysqli_error($conn) . "\n";
            file_put_contents(__DIR__ . '/email_log.txt', $logMessage, FILE_APPEND);
            return false;
        }

        $emailCount = mysqli_num_rows($result);
        $logMessage = date('Y-m-d H:i:s') . " - Found {$emailCount} email addresses\n";
        file_put_contents(__DIR__ . '/email_log.txt', $logMessage, FILE_APPEND);

        if ($emailCount == 0) {
            $logMessage = date('Y-m-d H:i:s') . " - No email addresses found, skipping email send\n";
            file_put_contents(__DIR__ . '/email_log.txt', $logMessage, FILE_APPEND);
            return false;
        }

        while ($row = mysqli_fetch_assoc($result)) {
            $mail->addBCC($row['email']); // Kirim massal pakai BCC (lebih cepat)
            $logMessage = date('Y-m-d H:i:s') . " - Added BCC: {$row['email']}\n";
            file_put_contents(__DIR__ . '/email_log.txt', $logMessage, FILE_APPEND);
        }

        // =========================
        // KIRIM EMAIL
        // =========================
        $logMessage = date('Y-m-d H:i:s') . " - Attempting to send email...\n";
        file_put_contents(__DIR__ . '/email_log.txt', $logMessage, FILE_APPEND);

        if ($mail->send()) {
            $logMessage = date('Y-m-d H:i:s') . " - Email sent successfully!\n";
            file_put_contents(__DIR__ . '/email_log.txt', $logMessage, FILE_APPEND);
            return true;
        } else {
            $logMessage = date('Y-m-d H:i:s') . " - Email send failed: {$mail->ErrorInfo}\n";
            file_put_contents(__DIR__ . '/email_log.txt', $logMessage, FILE_APPEND);
            return false;
        }

    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}
?>
