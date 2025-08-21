<?php
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth.php';
require_login();

// Ambil user_id dari session
$user_id = $_SESSION['user']['user_id'] ?? 0;
$today = date('Y-m-d');

// Ambil status absensi hari ini
$stmt = $pdo->prepare("SELECT status FROM attendance WHERE user_id = ? AND date = ?");
$stmt->execute([$user_id, $today]);
$attendance = $stmt->fetch();
$current_status = $attendance['status'] ?? 'Belum Absen';

$badge_color = match ($current_status) {
    'Hadir' => 'text-green-800',
    'Telat' => 'text-yellow-800',
    'Izin' => 'text-blue-800',
    'Alpa' => 'text-red-800',
    default => 'text-gray-800'
};

// Tentukan awal dan akhir bulan ini
$first_day_of_month = date('Y-m-01');
$last_day_of_month = date('Y-m-t');

// Hitung total laporan bulan ini
$stmt1 = $pdo->prepare("
    SELECT COUNT(*) as total 
    FROM production_reports 
    WHERE user_id = ? 
      AND report_date >= ? 
      AND report_date <= ?
");
$stmt1->execute([$user_id, $first_day_of_month, $last_day_of_month]);
$monthly_report = $stmt1->fetch(PDO::FETCH_ASSOC);
$total_monthly = (int)$monthly_report['total'];

// Ambil aktivitas terbaru hari ini
$stmt2 = $pdo->prepare("
    SELECT title, job_type, status, created_at 
    FROM production_reports 
    WHERE user_id = ? AND report_date = ?
    ORDER BY created_at DESC 
    LIMIT 5
");
$stmt2->execute([$user_id, $today]);
$recent_activities_today = $stmt2->fetchAll(PDO::FETCH_ASSOC);

// Ambil name dan email
$stmt3 = $pdo->prepare("
    SELECT email, name
    FROM users
    WHERE user_id = ?
");
$stmt3->execute([$user_id]);
$user = $stmt3->fetch(PDO::FETCH_ASSOC);

$email = $user['email'];
$name = $user['name'];

// Hitung persentase (target 88 job = 100%)
$target = 88;
$percentage = $target > 0 ? min(100, ($total_monthly / $target) * 100) : 0;
$percentage = round($percentage, 2); // 2 angka di belakang koma

include __DIR__ . '/header.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rayterton Prodtracker - Profil</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body>
    <main class="min-h-screen flex items-center justify-center bg-gray-50">
        <!-- Profil Card -->
        <div class="bg-white rounded-lg shadow-lg p-6 w-full mx-auto max-w-lg text-center">
            <div class="bg-indigo-500 rounded-t-lg">
                <!-- Logo / Judul -->
                <div class="mb-6">
                    <h1 class="text-xl sm:text-2xl font-bold text-white">Rayterton Productivity Tracker</h1>
                </div>

                <!-- Foto Profil -->
                <div class="flex flex-col items-center mb-6">
                    <img src="/images/kabahkopter.jpg" alt="Profile Picture" class="w-20 h-20 rounded-full border-4 border-white mb-3">
                    <h2 class="text-xl font-semibold text-white"><?php echo $name; ?></h2>
                    <p class="text-white"><?php echo $email; ?></p>
                </div>
            </div>

            <!-- Statistik -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-10 text-sm">
                <div class="bg-blue-50 p-3 rounded flex flex-col justify-between text-center">
                    <p class="font-semibold text-blue-800">Job Total</p>
                    <p class="text-3xl font-bold text-blue-700"><?php echo $total_monthly; ?></p>
                    <p class="text-xs text-blue-600">of <?php echo $target; ?> targets</p>
                </div>
                <div class="bg-green-50 p-3 rounded-lg flex flex-col justify-between text-center">
                    <p class="font-semibold text-green-800 mb-2">Progress</p>
                    <div class="w-full bg-gray-200 rounded-full h-2.5 mb-1">
                        <div class="bg-green-600 h-2.5 rounded-full" style="width: <?php echo $percentage; ?>%"></div>
                    </div>
                    <p class="text-sm font-bold text-green-700 text-center"><?php echo $percentage; ?>%</p>
                    <p class="text-xs text-gray-500 text-center"><?php echo $total_monthly; ?>/<?php echo $target; ?> Job</p>
                </div>
                <div class="bg-purple-50 p-3 rounded flex flex-col items-center text-center">
                    <p class="font-semibold text-purple-800">Status</p>
                    <p class="mt-0 sm:mt-2 text-lg text-orange-600 font-medium <?php echo $badge_color; ?>">
                        <?php echo htmlspecialchars($current_status); ?>
                    </p>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Current Activity (<?php echo date('d M'); ?>) </h3>
                <ul class="space-y-3">
                    <?php if (empty($recent_activities_today)): ?>
                        <li class="text-gray-500 text-sm text-center py-2">There is no activity today.</li>
                    <?php else: ?>
                        <?php foreach ($recent_activities_today as $act): ?>
                            <li class="p-3 border border-gray-200 rounded-lg bg-white shadow-sm hover:shadow transition">
                                <a href="reports_my.php" class="flex justify-between items-center">
                                    <div class="flex-1">
                                        <p class="text-sm sm:text-base font-medium text-gray-800"><?php echo htmlspecialchars($act['title']); ?></p>
                                        <p class="text-xs sm:text-sm text-gray-600">
                                            <?php echo htmlspecialchars($act['job_type']); ?> •
                                            <span class="text-xs text-gray-500">
                                                <?php echo date('H:i', strtotime($act['created_at'])); ?>
                                            </span>
                                        </p>
                                    </div>
                                    <span class="ml-3 px-2 sm:px-2.5 py-1 text-xs font-medium rounded-full 
                            <?php echo $act['status'] === 'Selesai' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                        <?php echo htmlspecialchars($act['status']); ?>
                                    </span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Tombol Aksi -->
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <a href="change_password.php" class="px-5 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">Change Password</a>
                <a href="edit_profil.php" class="px-5 py-2 bg-yellow-500 text-white rounded hover:bg-yellow-600 transition">Edit Profil</a>
                <a href="logout.php" class="px-5 py-2 bg-red-500 text-white rounded hover:bg-red-600 transition">Log Out</a>
            </div>
        </div>
    </main>
</body>

</html>