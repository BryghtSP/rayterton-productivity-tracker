<?php
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth.php';
require_login();

$user_id = $_SESSION['user']['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Akses ditolak.");
}

// Ambil data form
$report_date = $_POST['report_date'] ?? date('Y-m-d');
$job_type_id = (int)$_POST['job_type'];
$status = $_POST['status'] ?? 'Progress';
$title = trim($_POST['title']);
$description = trim($_POST['description']) ?: null;
$proof_link = trim($_POST['proof_link']) ?: null;

// Validasi input wajib
if (empty($title) || empty($report_date) || $job_type_id <= 0) {
    die("Data tidak lengkap.");
}

// Upload & Kompres Gambar
$proof_image_path = null;
$max_size = 1048576; // 1 MB

if (isset($_FILES['proof_image']) && $_FILES['proof_image']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['proof_image'];

    // 1. Cek ukuran file
    if ($file['size'] > $max_size) {
        die("Ukuran file gambar tidak boleh lebih dari 1 MB.");
    }

    // 2. Cek tipe file
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    $mime = mime_content_type($file['tmp_name']);
    if (!in_array($mime, $allowed_types)) {
        die("Format gambar tidak didukung. Gunakan JPG, PNG, atau WebP.");
    }

    // 3. Folder upload
    $upload_dir = __DIR__ . '/../uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // 4. Buat nama file unik
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_filename = 'report_' . time() . '_' . uniqid() . '.' . ($mime === 'image/webp' ? 'webp' : ($mime === 'image/png' ? 'png' : 'jpg'));
    $target_path = $upload_dir . $new_filename;

    // 5. Kompres & Simpan
    $success = false;
    switch ($mime) {
        case 'image/jpeg':
        case 'image/jpg':
            $image = imagecreatefromjpeg($file['tmp_name']);
            $success = imagejpeg($image, $target_path, 80); // 80% quality
            break;
        case 'image/png':
            $image = imagecreatefrompng($file['tmp_name']);
            // Hilangkan background transparan jika perlu
            $bg = imagecreatetruecolor(imagesx($image), imagesy($image));
            imagefill($bg, 0, 0, imagecolorallocate($bg, 255, 255, 255));
            imagecopy($bg, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
            $success = imagejpeg($bg, $target_path, 80); // Konversi ke JPG untuk hemat ukuran
            imagedestroy($bg);
            break;
        case 'image/webp':
            $image = imagecreatefromwebp($file['tmp_name']);
            $success = imagejpeg($image, $target_path, 80);
            break;
    }

    if ($image) imagedestroy($image);

    if (!$success) {
        die("Gagal memproses gambar.");
    }

    $proof_image_path = '/uploads/' . $new_filename; // Simpan path relatif
}

// Simpan ke database
try {
    $stmt = $pdo->prepare("
        INSERT INTO production_reports 
        (user_id, report_date, job_type_id, title, description, status, proof_link, proof_image)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $user_id,
        $report_date,
        $job_type_id,
        $title,
        $description,
        $status,
        $proof_link,
        $proof_image_path
    ]);

    header("Location: reports_my.php?success=report_saved");
    exit;
} catch (PDOException $e) {
    die("Gagal menyimpan laporan: " . $e->getMessage());
}