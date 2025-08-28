<?php
// lib/db.php
// Update these credentials for your server
$DB_HOST = getenv('DB_HOST') ?: 'raytertonapps.com';
$DB_NAME = getenv('DB_NAME') ?: 'raytert2_prodtracker';
$DB_USER = getenv('DB_USER') ?: 'raytert2_prodtracker';
$DB_PASS = getenv('DB_PASS') ?: 'RTNprodtracker';
date_default_timezone_set('Asia/Jakarta');

try {
  $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
} catch (PDOException $e) {
  die("Database connection failed: " . htmlspecialchars($e->getMessage()));
}
?>
