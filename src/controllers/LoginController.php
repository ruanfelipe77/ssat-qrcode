<?php
// Configurar sessão para durar 8 horas (28800 segundos)
ini_set('session.gc_maxlifetime', 28800);
session_set_cookie_params(28800);
session_start();
require_once '../../database.php';
require_once '../../src/models/Audit.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';

  try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare('SELECT id, name, email, password_hash, active FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && intval($user['active']) === 1 && password_verify($password, $user['password_hash'])) {
      // Autenticado
      $_SESSION['user_id'] = (int)$user['id'];
      $_SESSION['user_name'] = $user['name'];
      // Audit login
      try {
        $db = Database::getInstance()->getConnection();
        Audit::log($db, 'login', 'user', (int)$user['id'], [ 'email' => $user['email'] ]);
      } catch (Throwable $e) { /* ignore */ }
      header('Location: ../../index.php');
      exit;
    }
  } catch (Throwable $e) {
    // log optionally
  }

  header('Location: ../../login.php?error=invalid_credentials');
  exit;
}
