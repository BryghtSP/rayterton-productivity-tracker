<?php
// lib/auth.php
session_start();

function require_login() {
  if (!isset($_SESSION['user'])) {
    header("Location: /index.php");
    exit;
  }
}

function require_admin() {
  require_login();
  if ($_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    echo "Forbidden";
    exit;
  }
}
?>
