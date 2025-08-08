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
        $class = $colorClasses[$flash['type']] ?? $colorClasses['info'];
        
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
    return $icons[$type] ?? 'ri-information-fill';
}
?>