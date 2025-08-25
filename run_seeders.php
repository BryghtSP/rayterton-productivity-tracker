<?php
// run_seeders.php

require_once __DIR__ . '/lib/db.php';

echo "Menjalankan seeder...\n";

// Ambil semua file seeder
$seederFiles = glob(__DIR__ . '/seeders/*.php');
sort($seederFiles);

foreach ($seederFiles as $file) {
    echo "Menjalankan seeder: " . basename($file) . "...\n";
    $seeder = require $file;

    try {
        $seeder['run']($pdo);
    } catch (Exception $e) {
        echo "Gagal menjalankan seeder: " . $e->getMessage() . "\n";
    }
}

echo "Semua seeder selesai dijalankan.\n";
