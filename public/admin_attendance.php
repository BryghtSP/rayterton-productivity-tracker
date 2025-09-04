<?php
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth.php';
require_admin();

$recapType = $_GET['recap_type'] ?? 'daily'; // default daily
$date = $_GET['date'] ?? date('Y-m-d');
$month = $_GET['month'] ?? date('Y-m');
$notesFilter = $_GET['notes'] ?? ''; // ambil filter notes

// === DAILY LOGIC ===
$limitDaily = 10;
$dailyPage = isset($_GET['daily_page']) ? max(1, (int)$_GET['daily_page']) : 1;
$dailyOffset = ($dailyPage - 1) * $limitDaily;

// Hitung total daily
$sqlCountDaily = "SELECT COUNT(*) FROM attendance a WHERE a.date = ?";
$paramsDaily = [$date];
if (!empty($notesFilter)) {
  $sqlCountDaily .= " AND a.notes = ?";
  $paramsDaily[] = $notesFilter;
}
$countDaily = $pdo->prepare($sqlCountDaily);
$countDaily->execute($paramsDaily);
$totalDaily = (int)$countDaily->fetchColumn();
$totalDailyPages = ceil($totalDaily / $limitDaily);

// Ambil data daily
$sqlDaily = "
    SELECT a.*, u.name, u.email 
    FROM attendance a
    JOIN users u ON a.user_id = u.user_id
    WHERE a.date = ?
";
$paramsDaily = [$date];
if (!empty($notesFilter)) {
  $sqlDaily .= " AND a.notes = ?";
  $paramsDaily[] = $notesFilter;
}
$sqlDaily .= " ORDER BY a.status, u.name
    LIMIT $limitDaily OFFSET $dailyOffset";

$stmt = $pdo->prepare($sqlDaily);
$stmt->execute($paramsDaily);
$daily = $stmt->fetchAll();

// === MONTHLY LOGIC ===
$start = $month . "-01";
$end = date('Y-m-t', strtotime($start));

$limitMonthly = 10;
$monthlyPage = isset($_GET['monthly_page']) ? max(1, (int)$_GET['monthly_page']) : 1;
$monthlyOffset = ($monthlyPage - 1) * $limitMonthly;

$countMonthly = $pdo->query("SELECT COUNT(*) FROM users WHERE is_active = 1");
$totalMonthly = (int)$countMonthly->fetchColumn();
$totalMonthlyPages = ceil($totalMonthly / $limitMonthly);

$stmt = $pdo->prepare("
    SELECT u.user_id, u.name, 
           SUM(CASE 
               WHEN a.status = 'Hadir' AND TIME(a.check_in) <= '07:45:00' THEN 1 
               ELSE 0 
           END) as hadir_shift_pagi,
           SUM(CASE 
               WHEN a.status = 'Hadir' AND TIME(a.check_in) > '07:45:00' AND TIME(a.check_in) <= '13:10:00' THEN 1 
               ELSE 0 
           END) as hadir_shift_siang,
           SUM(CASE 
               WHEN a.status = 'Hadir' AND TIME(a.check_in) > '13:10:00' THEN 1 
               ELSE 0 
           END) as hadir_invalid,
           SUM(CASE WHEN a.status = 'Telat' THEN 1 ELSE 0 END) as telat,
           SUM(CASE WHEN a.status = 'Izin' THEN 1 ELSE 0 END) as izin,
           SUM(CASE WHEN a.status = 'Sakit' THEN 1 ELSE 0 END) as sakit
    FROM users u
    LEFT JOIN attendance a ON a.user_id = u.user_id AND a.date BETWEEN ? AND ?
    WHERE u.is_active = 1
    GROUP BY u.user_id
    ORDER BY u.name
    LIMIT $limitMonthly OFFSET $monthlyOffset
");
$stmt->execute([$start, $end]);
$monthly = $stmt->fetchAll();



include __DIR__ . '/header.php';
?>


<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Rayterton Prodtracker - Attendance Recap</title>
  <link rel="stylesheet" href="css/output.css">
</head>

<body>
  <div class="max-w-7xl mx-auto px-4 py-8 space-y-8">

    <!-- Recap Switcher Card -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
      <div class="p-6 md:p-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
          <h1 class="text-2xl font-bold text-gray-800">Attendance Recap</h1>


          <!-- Filter Form -->
          <form class="flex flex-col sm:flex-row items-center gap-2" method="get">
            <select name="recap_type"
              onchange="this.form.submit()"
              class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
              <option value="daily" <?= $recapType === 'daily' ? 'selected' : '' ?>>Daily</option>
              <option value="monthly" <?= $recapType === 'monthly' ? 'selected' : '' ?>>Monthly</option>
            </select>

            <?php if ($recapType === 'daily'): ?>
              <!-- Daily filter: Notes -->
              <select name="notes"
                onchange="this.form.submit()"
                class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                <option value="">-- All Notes --</option>
                <option value="Morning" <?= $notesFilter === 'Morning' ? 'selected' : '' ?>>Pagi</option>
                <option value="Afternoon" <?= $notesFilter === 'Afternoon' ? 'selected' : '' ?>>Siang</option>
                <option value="WFO" <?= $notesFilter === 'WFO' ? 'selected' : '' ?>>Whole Day at Office (WFO)</option>
                <option value="WAC" <?= $notesFilter === 'WAC' ? 'selected' : '' ?>>Working at Client (WAC)</option>
                <option value="WFH" <?= $notesFilter === 'WFH' ? 'selected' : '' ?>>Working from Home (WFH)</option>
                <option value="WFA" <?= $notesFilter === 'WFA' ? 'selected' : '' ?>>Working from Anywhere (WFA)</option>
              </select>
            <?php else: ?>
              <!-- Monthly filter: Month -->
              <input type="month" name="month"
                value="<?= htmlspecialchars($month) ?>"
                onchange="this.form.submit()"
                class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
            <?php endif; ?>

            <button type="submit"
              class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
              Filter
            </button>
            <a href="export_excel.php?recap_type=<?= urlencode($recapType) ?>&date=<?= urlencode($date) ?>&month=<?= urlencode($month) ?>&notes=<?= urlencode($notesFilter) ?>"
              class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
              Export Excel
            </a>


          </form>





        </div>

        <div class="overflow-x-auto">
          <table class="w-full">
            <?php if ($recapType === 'daily'): ?>
              <!-- Daily Table -->
              <thead>
                <tr class="text-left border-b border-gray-200">
                  <th class="pb-3 font-medium text-gray-600">Name</th>
                  <th class="pb-3 font-medium text-gray-600">Email</th>
                  <th class="pb-3 font-medium text-gray-600">Check-in</th>
                  <th class="pb-3 font-medium text-gray-600">Check-out</th>
                  <th class="pb-3 font-medium text-gray-600">Status</th>
                  <th class="pb-3 font-medium text-gray-600">Location</th>
                  <th class="pb-3 font-medium text-gray-600">Notes</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <?php foreach ($daily as $record): ?>
                  <tr class="hover:bg-gray-50 transition">
                    <td class="py-4 text-sm font-medium text-gray-800"><?= htmlspecialchars($record['name']) ?></td>
                    <td class="py-4 text-sm text-gray-600"><?= htmlspecialchars($record['email']) ?></td>
                    <td class="py-4 text-sm"><?= $record['check_in'] ?? '-' ?></td>
                    <td class="py-4 text-sm"><?= $record['check_out'] ?? '-' ?></td>
                    <td class="py-4 text-sm"><?= htmlspecialchars($record['status']) ?></td>
                    <td class="py-4 text-sm"><?= htmlspecialchars($record['location'] ?? '-') ?></td>
                    <td class="py-4 text-sm"><?= !empty($record['notes']) ? htmlspecialchars($record['notes']) : '-' ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>


            <?php else: ?>
              <!-- Monthly Table -->
              <thead>
                <tr class="text-left border-b border-gray-200">
                  <th class="pb-3 font-medium text-gray-600">Name</th>
                  <th class="pb-3 font-medium text-gray-600 text-center">Telat</th>
                  <th class="pb-3 font-medium text-gray-600 text-center">Izin</th>
                  <th class="pb-3 font-medium text-gray-600 text-center">Sakit</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <?php foreach ($monthly as $record): ?>
                  <tr class="hover:bg-gray-50 transition">
                    <td class="py-4 text-sm font-medium text-gray-800"><?= htmlspecialchars($record['name']) ?></td>
                    <td class="py-4 text-sm text-center text-yellow-600"><?= $record['telat'] ?? 0 ?></td>
                    <td class="py-4 text-sm text-center text-indigo-600"><?= $record['izin'] ?? 0 ?></td>
                    <td class="py-4 text-sm text-center text-purple-600"><?= $record['sakit'] ?? 0 ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            <?php endif; ?>
          </table>
        </div>

        <!-- Pagination -->
        <?php if ($recapType === 'daily' && $totalDailyPages > 1): ?>
          <div class="mt-4 flex gap-2">
            <?php for ($i = 1; $i <= $totalDailyPages; $i++): ?>
              <a href="?recap_type=daily&date=<?= urlencode($date) ?>&daily_page=<?= $i ?>"
                class="px-3 py-1 rounded <?= $i == $dailyPage ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700' ?>">
                <?= $i ?>
              </a>
            <?php endfor; ?>
          </div>
        <?php elseif ($recapType === 'monthly' && $totalMonthlyPages > 1): ?>
          <div class="mt-4 flex gap-2">
            <?php for ($i = 1; $i <= $totalMonthlyPages; $i++): ?>
              <a href="?recap_type=monthly&month=<?= urlencode($month) ?>&monthly_page=<?= $i ?>"
                class="px-3 py-1 rounded <?= $i == $monthlyPage ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700' ?>">
                <?= $i ?>
              </a>
            <?php endfor; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</body>

</html>

<?php include __DIR__ . '/footer.php'; ?>