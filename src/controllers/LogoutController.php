<?php
session_start();
require_once '../../database.php';
require_once '../../src/models/Audit.php';

try {
    if (!empty($_SESSION['user_id'])) {
        $db = Database::getInstance()->getConnection();
        Audit::log($db, 'logout', 'user', (int)$_SESSION['user_id'], [ 'user_name' => ($_SESSION['user_name'] ?? null) ]);
    }
} catch (Throwable $e) { /* ignore */ }

session_destroy();
// header('Location: ' . BASE_URL . 'login.php');
header('Location: ../../login.php');
exit;
?>