<?php
// index.php
session_start();
require 'functions.php';
if (isset($_SESSION['user'])) {
    header("Location: dashboard.php");
    exit();
}

$flash = isset($_SESSION['flash']) ? $_SESSION['flash'] : null;
unset($_SESSION['flash']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>PLNICON-AR</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>

    body {
      background-color: #f5f5f5;
      font-family: 'Segoe UI', sans-serif;
    }
    .auth-container {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .auth-box {
      background-color: white;
      border-radius: 1rem;
      box-shadow: 0 0 20px rgba(0,0,0,0.05);
      max-width: 1000px;
      width: 100%;
      display: flex;
      overflow: hidden;
    }
    .form-section {
      flex: 1;
      padding: 3rem;
    }
    .image-section {
      flex: 1;
      background-color: #eaf5ec;
      padding: 2rem;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-direction: column;
    }
    .form-control {
      border-radius: 999px;
    }
    .btn-black {
      background-color: #000;
      color: #fff;
      border-radius: 999px;
    }
    .toggle-link {
      color: #50c878;
      cursor: pointer;
    }
    .social-icons i {
      background-color: #000;
      color: white;
      padding: 10px;
      margin: 0 5px;
      border-radius: 50%;
      width: 44px;
      height: 44px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }
.alert {
    padding: 12px 20px;
    margin-bottom: 16px;
    border-radius: 6px;
    font-size: 15px;
    font-weight: 500;
    position: relative;
    animation: fadeIn 0.5s ease-in-out;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
    border-left: 4px solid #28a745;
}

.alert-danger {
    background-color: #f8d7da;
    color: #721c24;
    border-left: 4px solid #dc3545;
}

.alert-info {
    background-color: #d1ecf1;
    color: #0c5460;
    border-left: 4px solid #17a2b8;
}.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 15px 25px;
    border-radius: 10px;
    background-color: #8a2be2;
    color: white;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    z-index: 9999;
    animation: slideInRight 0.5s ease-out, fadeOut 0.5s ease-out 4.5s forwards;
    transform: translateX(120%);
    opacity: 0;
}

@keyframes slideInRight {
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@keyframes fadeOut {
    to {
        opacity: 0;
        transform: translateX(120%);
    }
}

.notification.success {
    background-color: #50c878;
}

.notification.error {
    background-color: #ff6b6b;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-5px); }
    to { opacity: 1; transform: translateY(0); }
}

    
    .d-none { display: none !important; }
    @media (max-width: 768px) {
      .auth-box {
        flex-direction: column;
      }
    }
    .alert {
    padding: 12px 20px;
    margin-bottom: 16px;
    border-radius: 6px;
    animation: fadeInOut 8s ease-in-out forwards;
}

@keyframes fadeInOut {
    0% { opacity: 0; transform: translateY(-20px); }
    10% { opacity: 1; transform: translateY(0); }
    90% { opacity: 1; transform: translateY(0); }
    100% { opacity: 0; transform: translateY(-20px); }
}
  </style>
</head>
<body>

<div id="notification-container"></div>
<div class="auth-container">
  <div class="auth-box">
    
    <div class="form-section">
      <!-- LOGIN FORM -->
      <div id="loginForm">
        <h2 class="fw-bold mb-3">Welcome back!</h2>
        <p class="text-muted">Login to continue using <strong>PLNICON-AR</strong>.</p>
        <form action="login.php" method="POST">
          <input type="text" name="username" class="form-control mb-3" placeholder="Username" required>
          <input type="password" name="password" class="form-control mb-3" placeholder="Password" required>
          <div class="d-flex justify-content-between mb-3">
            <small><a href="#" class="text-muted">Forgot Password?</a></small>
          </div>
          <button type="submit" class="btn btn-black w-100 mb-3">Login</button>
        </form>
        <div class="text-center text-muted mb-2">or continue with</div>
        <div class="text-center social-icons mb-3">
          <i class="bi bi-google"></i>
          <i class="bi bi-apple"></i>
          <i class="bi bi-facebook"></i>
        </div>
        <p class="text-center">Not a member? <span class="toggle-link" onclick="toggleForm()">Register now</span></p>
      </div>

      <!-- SIGNUP FORM -->
      <div id="signupForm" class="d-none">
        <h2 class="fw-bold mb-3">Create account</h2>
        <p class="text-muted">Register to use <strong>PLNICON-AR</strong>.</p>
        <form action="register.php" method="POST">
          <input type="text" name="username" class="form-control mb-3" placeholder="Username" required>
          <input type="email" name="email" class="form-control mb-3" placeholder="Email" required>
          <input type="password" name="password" class="form-control mb-3" placeholder="Password" required>
          <button type="submit" class="btn btn-black w-100 mb-3">Sign Up</button>
        </form>
        <p class="text-center">Already have an account? <span class="toggle-link" onclick="toggleForm()">Login here</span></p>
      </div>
    </div>

    <!-- IMAGE SECTION -->
    <div class="image-section">
      <img src="https://cdn-icons-png.flaticon.com/512/4433/4433904.png" alt="Illustration" style="width: 80%; max-width: 300px;">
      <p class="mt-4 fw-medium text-center">Make your work easier and organized<br> with <strong>PLNICON-AR</strong></p>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>document.addEventListener('DOMContentLoaded', function() {
    <?php if ($flash): ?>
        showNotification('<?= $flash['message'] ?>', '<?= $flash['type'] ?>');
    <?php endif; ?>

    function showNotification(message, type = 'success') {
        const container = document.getElementById('notification-container');
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        
        container.appendChild(notification);
        
        // Sesuaikan dengan total durasi animasi (0.5s slideIn + 4.5s delay + 0.5s fadeOut = 5.5s)
        setTimeout(() => {
            notification.remove();
        }, 5500); // 5.5 detik total
    }
    
    // Hapus fungsi toggleForm yang duplikat
    function toggleForm() {
        document.getElementById('loginForm').classList.toggle('d-none');
        document.getElementById('signupForm').classList.toggle('d-none');
    }
});
</script>
<script>
  function toggleForm() {
    document.getElementById('loginForm').classList.toggle('d-none');
    document.getElementById('signupForm').classList.toggle('d-none');
  }

  document.addEventListener('DOMContentLoaded', function() {
    const flashMessages = document.querySelectorAll('.flash-message');
    flashMessages.forEach(message => {
      message.addEventListener('animationend', function(e) {
        if (e.animationName === 'fadeOut') {
          message.remove();
        }
      });
    });
});
</script>
</body>
</html>