<?php
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth.php';
require_login();

$user_id = $_SESSION['user']['user_id'];
$report_date = $_POST['report_date'] ?? date('Y-m-d');
$job_type = $_POST['job_type'] ?? '';
$title = $_POST['title'] ?? '';
$description = $_POST['description'] ?? '';
$status = $_POST['status'] ?? 'Progress';
$workforce_id = (int)$_POST['workforce_id'];

// Fungsi untuk mendapatkan nama user
function getUserName($pdo, $user_id)
{
    $stmt = $pdo->prepare("SELECT name FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    return $user ? $user['name'] : 'unknown';
}

// Report form WF
// Ambil employee_id
$stmt = $pdo->prepare("SELECT employee_id FROM employees WHERE user_id = ?");
$stmt->execute([$user_id]);
$employee = $stmt->fetch();
$employee_id = $employee['employee_id'];

// Validasi: pastikan workforce_id dimiliki oleh employee ini
// $check = $pdo->prepare("
//         SELECT 1 FROM employees_workforce 
//         WHERE employee_id = ? AND workforce_id = ?
//     ");
// $check->execute([$employee_id, $workforce_id]);
// if (!$check->fetch()) {
//     die("Anda tidak memiliki akses ke work_force ini.");
// }

// Handle file upload dengan penamaan otomatis (tanpa kompresi)
$proof_image_path = null;
if (isset($_FILES['proof_image']) && $_FILES['proof_image']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = '../uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Dapatkan nama user
    $user_name = getUserName($pdo, $user_id);
    $user_name_clean = preg_replace('/[^a-zA-Z0-9]/', '_', $user_name); // Clean username

    // Buat nama file otomatis
    $timestamp = date('Ymd_His'); // Format: 20240115_143022
    $original_filename = basename($_FILES['proof_image']['name']);
    $file_extension = pathinfo($original_filename, PATHINFO_EXTENSION);

    // Buat nama file sesuai format: NAMAUSER_JOBTYPE_TANGGAL.format
    $safe_job_type = preg_replace('/[^a-zA-Z0-9]/', '_', $job_type);
    $new_filename = "{$user_name_clean}_{$safe_job_type}_{$timestamp}.{$file_extension}";
    $target_file = $upload_dir . $new_filename;

    // Cek apakah file adalah gambar
    $image_info = getimagesize($_FILES["proof_image"]["tmp_name"]);
    if ($image_info !== false) {
        // Jika file adalah gambar, simpan
        if (move_uploaded_file($_FILES["proof_image"]["tmp_name"], $target_file)) {
            $proof_image_path = $target_file;
        }
    } else {
        // Jika bukan gambar, proses biasa
        if (move_uploaded_file($_FILES["proof_image"]["tmp_name"], $target_file)) {
            $proof_image_path = $target_file;
        }
    }
}

// Simpan ke database
$stmt = $pdo->prepare("INSERT INTO production_reports (user_id, report_date, job_type, title, description, status, proof_image, workforce_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->execute([$user_id, $report_date, $job_type, $title, $description, $status, $proof_image_path, $workforce_id]);

header("Location: reports_my.php");
exit;
