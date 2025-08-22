<?php
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth.php';
require_login();

$user_id = $_SESSION['user']['user_id'] ?? 0;
$error = '';
$success = '';

// Ambil data pengguna saat ini
$stmt = $pdo->prepare("
    SELECT u.email, e.name
    FROM users u 
    LEFT JOIN employees e ON u.user_id = e.user_id
    WHERE u.user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    die("Pengguna tidak ditemukan.");
}

$name = $user['name'];
$email = $user['email'];

// Proses saat form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);

    // Validasi
    if (empty($name) || empty($email)) {
        $error = "Semua kolom harus diisi.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid.";
    } else {
        // Cek apakah email sudah digunakan oleh user lain
        $check = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
        $check->execute([$email, $user_id]);
        if ($check->fetch()) {
            $error = "Email sudah digunakan oleh pengguna lain.";
        } else {
            // Update ke database
            $update = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE user_id = ?");
            $update->execute([$name, $email, $user_id]);

            // Perbarui session
            $_SESSION['user']['name'] = $name;
            $_SESSION['user']['email'] = $email;

            $success = "Profil berhasil diperbarui!";
        }
    }
}

include __DIR__ . '/header.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rayterton Prodtracker - Edit Profil</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" integrity="sha512-1ycn6IcaQQ40/MKBW2W4Rhis/DbILU74C1vSrLJxCq57o941Ym01SwNsOMqvEBFlcgUa6xLiPY/NS5R+E6ztJQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>

<body>
    <div class="max-w-lg mx-auto bg-white rounded-lg shadow-lg p-6 mt-10">
        <div class="flex items-center gap-3 mb-6">
            <a href="profil.php"
                class="text-black font-medium">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h2 class="text-2xl font-bold text-gray-800">Edit Profil</h2>
        </div>

        <?php if ($error): ?>
            <div class="mb-4 p-3 bg-red-100 text-red-800 text-sm rounded">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="mb-4 p-3 bg-green-100 text-green-800 text-sm rounded">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-6">
            <!-- Nama -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Full Name</label>
                <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>"
                    class="w-full px-4 py-2 border-b border-gray-300 outline-none focus:border-indigo-500 focus:ring-0 transition"
                    required>
            </div>

            <!-- Email -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>"
                    class="w-full px-4 py-2 border-b border-gray-300 outline-none focus:border-indigo-500 focus:ring-0 transition"
                    required>
            </div>

            <!-- Tombol -->
            <div class="flex gap-3 pt-4">
                <button type="submit"
                    class="px-6 py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition">
                    Save Change
                </button>
                <a href="profil.php"
                    class="px-6 py-2 bg-gray-400 text-white font-medium rounded-lg hover:bg-gray-500 transition">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</body>

</html>