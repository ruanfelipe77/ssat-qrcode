<?php
session_start();
session_destroy();
// header('Location: ' . BASE_URL . 'login.php');
header('Location: ../../login.php');
exit;
?>