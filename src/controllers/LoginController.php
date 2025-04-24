<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = $_POST['email'];
  $password = $_POST['password'];
  if ($email === 'admin@centralssat.com.br' && $password === '1234') {
    $_SESSION['logado'] = true;
    header('Location: ../../index.php');
    exit;
  } else {
    header('Location: ../../login.php?error=invalid_credentials');
    exit;
  }
}
