<?php
// Background email sending script
// This script runs asynchronously to send email notifications

require 'koneksi.php';
require 'functions.php';

// Prevent HTML error output
ini_set('display_errors', 0);
error_reporting(E_ALL & ~E_NOTICE);

// Get article data from command line argument
if ($argc < 2) {
    exit(1);
}

$articleDataJson = $argv[1];
$articleData = json_decode($articleDataJson, true);

if (!$articleData) {
    exit(1);
}

// Send the email notification
try {
    $logMessage = date('Y-m-d H:i:s') . " - Calling sendNewArticleNotification function\n";
    file_put_contents(__DIR__ . '/email_log.txt', $logMessage, FILE_APPEND);

    $emailSent = sendNewArticleNotification($articleData, $conn);

    // Log the result
    $logMessage = date('Y-m-d H:i:s') . " - Email notification result: " . ($emailSent ? 'SUCCESS' : 'FAILED') . "\n";
    file_put_contents(__DIR__ . '/email_log.txt', $logMessage, FILE_APPEND);

} catch (Exception $e) {
    // Log the error
    $logMessage = date('Y-m-d H:i:s') . " - Email notification failed - Error: " . $e->getMessage() . "\n";
    file_put_contents(__DIR__ . '/email_log.txt', $logMessage, FILE_APPEND);
}

exit(0);
?>
