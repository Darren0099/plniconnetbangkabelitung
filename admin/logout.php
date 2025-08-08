<?php
// logout.php
session_start();
require 'functions.php';

setFlashMessage('Anda berhasil logout', 'success');
session_unset();
session_destroy();
header("Location: index.php");
exit();
?>