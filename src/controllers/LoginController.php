<?php
session_start();
require_once '../../database.php';

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
      header('Location: ../../index.php');
      exit;
    }
  } catch (Throwable $e) {
    // log optionally
  }

  header('Location: ../../login.php?error=invalid_credentials');
  exit;
}
