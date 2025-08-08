<?php
function isAdmin() {
    return isset($_SESSION['user']) && $_SESSION['user']['role'] == 'admin';
}

function isUser() {
    return isset($_SESSION['user']) && $_SESSION['user']['role'] == 'user';
}

function requireAdmin() {
    if (!isAdmin()) {
        header("Location: ../index.php");
        exit();
    }
}

function requireUser() {
    if (!isUser()) {
        header("Location: ../index.php");
        exit();
    }
}
?>