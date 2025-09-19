<?php
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth.php';
require_admin();

$recapType = $_GET['recap_type'] ?? 'daily'; // default daily
$date = $_GET['date'] ?? date('Y-m-d');
$month = $_GET['month'] ?? date('Y-m');

// Validasi notes sesuai format database: "Hadir: WFH", "Late: WFO", dll.
$validNotes = [
  '',
  'Hadir: Morning',
  'Hadir: Afternoon',
  'Hadir: WFH',
  'Hadir: WFO',
  'Hadir: WAC',
  'Hadir: WFA',
  'Late: Morning',
  'Late: Afternoon',
  'Late: WFH',
  'Late: WFO',
  'Late: WAC',
  'Late: WFA'
];
$notesFilter = in_array($_GET['notes'] ?? '', $validNotes) ? ($_GET['notes'] ?? '') : '';

// Fungsi bantu URL pagination daily
function buildDailyUrl($page, $date, $notes)
{
  return '?' . http_build_query([
    'recap_type' => 'daily',
    'date' => $date,
    'notes' => $notes,
    'daily_page' => $page
  ]);
}

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

// Hitung startPage dan endPage untuk daily pagination
$dailyStartPage = max(1, $dailyPage - 2);
$dailyEndPage = min($totalDailyPages, $dailyStartPage + 4);
if ($dailyEndPage - $dailyStartPage < 4) {
  $dailyStartPage = max(1, $dailyEndPage - 4);
}

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

// Hitung startPage dan endPage untuk monthly pagination
$monthlyStartPage = max(1, $monthlyPage - 2);
$monthlyEndPage = min($totalMonthlyPages, $monthlyStartPage + 4);
if ($monthlyEndPage - $monthlyStartPage < 4) {
  $monthlyStartPage = max(1, $monthlyEndPage - 4);
}

$stmt = $pdo->prepare("
    SELECT u.user_id, u.name, 
           SUM(CASE 
               WHEN a.status = 'Hadir' AND a.notes LIKE 'Hadir:%' AND TIME(a.check_in) <= '07:45:00' THEN 1 
               ELSE 0 
           END) as hadir_shift_pagi,
           SUM(CASE 
               WHEN a.status = 'Hadir' AND a.notes LIKE 'Hadir:%' AND TIME(a.check_in) > '07:45:00' AND TIME(a.check_in) <= '13:10:00' THEN 1 
               ELSE 0 
           END) as hadir_shift_siang,
           SUM(CASE 
               WHEN a.status = 'Hadir' AND a.notes LIKE 'Hadir:%' AND TIME(a.check_in) > '13:10:00' THEN 1 
               ELSE 0 
           END) as hadir_invalid,
           SUM(CASE 
               WHEN a.notes LIKE 'Late:%' THEN 1 
               ELSE 0 
           END) as telat,
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
            <input type="hidden" name="recap_type" value="<?= $recapType ?>">

            <select name="recap_type"
              onchange="this.form.submit()"
              class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
              <option value="daily" <?= $recapType === 'daily' ? 'selected' : '' ?>>Daily</option>
              <option value="monthly" <?= $recapType === 'monthly' ? 'selected' : '' ?>>Monthly</option>
            </select>

            <?php if ($recapType === 'daily'): ?>
              <!-- Daily filter: Date -->
              <input type="date" name="date"
                value="<?= htmlspecialchars($date) ?>"
                class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">

              <!-- Daily filter: Notes -->
              <!-- Daily filter: Notes -->
              <select name="notes"
                onchange="this.form.submit()"
                class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                <option value="">-- All Notes --</option>
                <option value="Hadir: Morning" <?= $notesFilter === 'Hadir: Morning' ? 'selected' : '' ?>>Hadir: Morning</option>
                <option value="Hadir: Afternoon" <?= $notesFilter === 'Hadir: Afternoon' ? 'selected' : '' ?>>Hadir: Afternoon</option>
                <option value="Hadir: WFH" <?= $notesFilter === 'Hadir: WFH' ? 'selected' : '' ?>>Hadir: WFH</option>
                <option value="Hadir: WFO" <?= $notesFilter === 'Hadir: WFO' ? 'selected' : '' ?>>Hadir: WFO</option>
                <option value="Hadir: WAC" <?= $notesFilter === 'Hadir: WAC' ? 'selected' : '' ?>>Hadir: WAC</option>
                <option value="Hadir: WFA" <?= $notesFilter === 'Hadir: WFA' ? 'selected' : '' ?>>Hadir: WFA</option>
                <option value="Late: Morning" <?= $notesFilter === 'Late: Morning' ? 'selected' : '' ?>>Late: Morning</option>
                <option value="Late: Afternoon" <?= $notesFilter === 'Late: Afternoon' ? 'selected' : '' ?>>Late: Afternoon</option>
                <option value="Late: WFH" <?= $notesFilter === 'Late: WFH' ? 'selected' : '' ?>>Late: WFH</option>
                <option value="Late: WFO" <?= $notesFilter === 'Late: WFO' ? 'selected' : '' ?>>Late: WFO</option>
                <option value="Late: WAC" <?= $notesFilter === 'Late: WAC' ? 'selected' : '' ?>>Late: WAC</option>
                <option value="Late: WFA" <?= $notesFilter === 'Late: WFA' ? 'selected' : '' ?>>Late: WFA</option>
              </select>
            <?php else: ?>
              <!-- Monthly filter: Month -->
              <input type="month" name="month"
                value="<?= htmlspecialchars($month) ?>"
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
                  <th class="pb-3 font-medium text-gray-600">Explanation</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <?php if (count($daily) > 0): ?>
                  <?php foreach ($daily as $record): ?>
                    <tr class="hover:bg-gray-50 transition">
                      <td class="py-4 text-sm font-medium text-gray-800"><?= htmlspecialchars($record['name']) ?></td>
                      <td class="py-4 text-sm text-gray-600"><?= htmlspecialchars($record['email']) ?></td>
                      <td class="py-4 text-sm"><?= $record['check_in'] ?? '-' ?></td>
                      <td class="py-4 text-sm"><?= $record['check_out'] ?? '-' ?></td>
                      <td class="py-4 text-sm"><?= htmlspecialchars($record['status']) ?></td>
                      <td class="py-4 text-sm"><?= htmlspecialchars($record['location'] ?? '-') ?></td>
                      <td class="py-4 text-sm"><?= !empty($record['notes']) ? htmlspecialchars($record['notes']) : '-' ?></td>
                      <td class="py-4 text-sm"><?= htmlspecialchars($record['explanation'] ?? '-') ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="7" class="py-4 text-center text-gray-500">No attendance records found for this date.</td>
                  </tr>
                <?php endif; ?>
              </tbody>

            <?php else: ?>
              <!-- Monthly Table -->
              <thead>
                <tr class="text-left border-b border-gray-200">
                  <th class="pb-3 font-medium text-gray-600">Name</th>
                  <th class="pb-3 font-medium text-gray-600 text-center">Total Hadir</th>
                  <th class="pb-3 font-medium text-gray-600 text-center">Telat</th>
                  <th class="pb-3 font-medium text-gray-600 text-center">Izin</th>
                  <th class="pb-3 font-medium text-gray-600 text-center">Sakit</th>
                  <th class="pb-3 font-medium text-gray-600 text-center">Total Absen</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <?php if (count($monthly) > 0): ?>
                  <?php foreach ($monthly as $record):
                    $totalHadir = ($record['hadir_shift_pagi'] ?? 0) + ($record['hadir_shift_siang'] ?? 0);
                    $totalAbsen = ($record['telat'] ?? 0) + ($record['izin'] ?? 0) + ($record['sakit'] ?? 0);
                  ?>
                    <tr class="hover:bg-gray-50 transition">
                      <td class="py-4 text-sm font-medium text-gray-800"><?= htmlspecialchars($record['name']) ?></td>
                      <td class="py-4 text-sm text-center font-bold text-green-700"><?= $totalHadir ?></td>
                      <td class="py-4 text-sm text-center text-yellow-600"><?= $record['telat'] ?? 0 ?></td>
                      <td class="py-4 text-sm text-center text-indigo-600"><?= $record['izin'] ?? 0 ?></td>
                      <td class="py-4 text-sm text-center text-purple-600"><?= $record['sakit'] ?? 0 ?></td>
                      <td class="py-4 text-sm text-center font-bold text-red-600"><?= $totalAbsen ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="6" class="py-4 text-center text-gray-500">No monthly records found.</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            <?php endif; ?>
          </table>
        </div>

        <!-- Pagination Daily -->
        <?php if ($recapType === 'daily' && $totalDailyPages > 1): ?>
          <div class="flex flex-col sm:flex-row justify-between items-center mt-6 gap-4">
            <div class="text-sm text-gray-600 whitespace-nowrap">
              Page <?= $dailyPage ?> of <?= $totalDailyPages ?>
            </div>
            <nav class="flex flex-wrap justify-center gap-1">
              <!-- First Page Button -->
              <?php if ($dailyPage > 1): ?>
                <a href="?recap_type=daily&date=<?= htmlspecialchars($date) ?>&notes=<?= htmlspecialchars($notesFilter) ?>&daily_page=1"
                  class="px-2 py-2 sm:px-3 bg-white text-indigo-600 border border-gray-300 rounded hover:bg-gray-50 text-sm font-medium transition whitespace-nowrap">
                  <span class="hidden sm:inline">&lt;&lt; First</span>
                  <span class="sm:hidden">First</span>
                </a>
              <?php else: ?>
                <span class="px-2 py-2 sm:px-3 bg-gray-100 text-gray-400 border border-gray-300 rounded text-sm font-medium cursor-not-allowed whitespace-nowrap">
                  <span class="hidden sm:inline">&lt;&lt; First</span>
                  <span class="sm:hidden">First</span>
                </span>
              <?php endif; ?>

              <!-- Previous Button -->
              <?php if ($dailyPage > 1): ?>
                <a href="?recap_type=daily&date=<?= htmlspecialchars($date) ?>&notes=<?= htmlspecialchars($notesFilter) ?>&daily_page=<?= $dailyPage - 1 ?>"
                  class="px-2 py-2 sm:px-3 bg-white text-indigo-600 border border-gray-300 rounded hover:bg-gray-50 text-sm font-medium transition whitespace-nowrap">
                  <span class="hidden sm:inline">&lt; Prev</span>
                  <span class="sm:hidden">&lt;</span>
                </a>
              <?php else: ?>
                <span class="px-2 py-2 sm:px-3 bg-gray-100 text-gray-400 border border-gray-300 rounded text-sm font-medium cursor-not-allowed whitespace-nowrap">
                  <span class="hidden sm:inline">&lt; Prev</span>
                  <span class="sm:hidden">&lt;</span>
                </span>
              <?php endif; ?>

              <!-- Page Numbers -->
              <div class="hidden xs:flex gap-1">
                <?php for ($i = $dailyStartPage; $i <= $dailyEndPage; $i++): ?>
                  <a href="?recap_type=daily&date=<?= htmlspecialchars($date) ?>&notes=<?= htmlspecialchars($notesFilter) ?>&daily_page=<?= $i ?>"
                    class="<?= $i === $dailyPage ? 'bg-indigo-600 text-white' : 'bg-white text-indigo-600 hover:bg-indigo-50' ?> px-3 py-2 border border-gray-300 rounded text-sm font-medium transition">
                    <?= $i ?>
                  </a>
                <?php endfor; ?>
              </div>

              <!-- Page indicator for mobile -->
              <div class="xs:hidden px-3 py-2 bg-indigo-600 text-white border border-gray-300 rounded text-sm font-medium">
                <?= $dailyPage ?>
              </div>

              <!-- Next Button -->
              <?php if ($dailyPage < $totalDailyPages): ?>
                <a href="?recap_type=daily&date=<?= htmlspecialchars($date) ?>&notes=<?= htmlspecialchars($notesFilter) ?>&daily_page=<?= $dailyPage + 1 ?>"
                  class="px-2 py-2 sm:px-3 bg-white text-indigo-600 border border-gray-300 rounded hover:bg-gray-50 text-sm font-medium transition whitespace-nowrap">
                  <span class="hidden sm:inline">Next &gt;</span>
                  <span class="sm:hidden">&gt;</span>
                </a>
              <?php else: ?>
                <span class="px-2 py-2 sm:px-3 bg-gray-100 text-gray-400 border border-gray-300 rounded text-sm font-medium cursor-not-allowed whitespace-nowrap">
                  <span class="hidden sm:inline">Next &gt;</span>
                  <span class="sm:hidden">&gt;</span>
                </span>
              <?php endif; ?>

              <!-- Last Page Button -->
              <?php if ($dailyPage < $totalDailyPages): ?>
                <a href="?recap_type=daily&date=<?= htmlspecialchars($date) ?>&notes=<?= htmlspecialchars($notesFilter) ?>&daily_page=<?= $totalDailyPages ?>"
                  class="px-2 py-2 sm:px-3 bg-white text-indigo-600 border border-gray-300 rounded hover:bg-gray-50 text-sm font-medium transition whitespace-nowrap">
                  <span class="hidden sm:inline">Last &gt;&gt;</span>
                  <span class="sm:hidden">Last</span>
                </a>
              <?php else: ?>
                <span class="px-2 py-2 sm:px-3 bg-gray-100 text-gray-400 border border-gray-300 rounded text-sm font-medium cursor-not-allowed whitespace-nowrap">
                  <span class="hidden sm:inline">Last &gt;&gt;</span>
                  <span class="sm:hidden">Last</span>
                </span>
              <?php endif; ?>
            </nav>
          </div>
        <?php endif; ?>

        <!-- Pagination Monthly -->
        <?php if ($recapType === 'monthly' && $totalMonthlyPages > 1): ?>
          <div class="flex flex-col sm:flex-row justify-between items-center mt-6 gap-4">
            <div class="text-sm text-gray-600 whitespace-nowrap">
              Page <?= $monthlyPage ?> of <?= $totalMonthlyPages ?>
            </div>
            <nav class="flex flex-wrap justify-center gap-1">
              <!-- First Page Button -->
              <?php if ($monthlyPage > 1): ?>
                <a href="?recap_type=monthly&month=<?= htmlspecialchars($month) ?>&monthly_page=1"
                  class="px-2 py-2 sm:px-3 bg-white text-indigo-600 border border-gray-300 rounded hover:bg-gray-50 text-sm font-medium transition whitespace-nowrap">
                  <span class="hidden sm:inline">&lt;&lt; First</span>
                  <span class="sm:hidden">First</span>
                </a>
              <?php else: ?>
                <span class="px-2 py-2 sm:px-3 bg-gray-100 text-gray-400 border border-gray-300 rounded text-sm font-medium cursor-not-allowed whitespace-nowrap">
                  <span class="hidden sm:inline">&lt;&lt; First</span>
                  <span class="sm:hidden">First</span>
                </span>
              <?php endif; ?>

              <!-- Previous Button -->
              <?php if ($monthlyPage > 1): ?>
                <a href="?recap_type=monthly&month=<?= htmlspecialchars($month) ?>&monthly_page=<?= $monthlyPage - 1 ?>"
                  class="px-2 py-2 sm:px-3 bg-white text-indigo-600 border border-gray-300 rounded hover:bg-gray-50 text-sm font-medium transition whitespace-nowrap">
                  <span class="hidden sm:inline">&lt; Prev</span>
                  <span class="sm:hidden">&lt;</span>
                </a>
              <?php else: ?>
                <span class="px-2 py-2 sm:px-3 bg-gray-100 text-gray-400 border border-gray-300 rounded text-sm font-medium cursor-not-allowed whitespace-nowrap">
                  <span class="hidden sm:inline">&lt; Prev</span>
                  <span class="sm:hidden">&lt;</span>
                </span>
              <?php endif; ?>

              <!-- Page Numbers -->
              <div class="hidden xs:flex gap-1">
                <?php for ($i = $monthlyStartPage; $i <= $monthlyEndPage; $i++): ?>
                  <a href="?recap_type=monthly&month=<?= htmlspecialchars($month) ?>&monthly_page=<?= $i ?>"
                    class="<?= $i === $monthlyPage ? 'bg-indigo-600 text-white' : 'bg-white text-indigo-600 hover:bg-indigo-50' ?> px-3 py-2 border border-gray-300 rounded text-sm font-medium transition">
                    <?= $i ?>
                  </a>
                <?php endfor; ?>
              </div>

              <!-- Page indicator for mobile -->
              <div class="xs:hidden px-3 py-2 bg-indigo-600 text-white border border-gray-300 rounded text-sm font-medium">
                <?= $monthlyPage ?>
              </div>

              <!-- Next Button -->
              <?php if ($monthlyPage < $totalMonthlyPages): ?>
                <a href="?recap_type=monthly&month=<?= htmlspecialchars($month) ?>&monthly_page=<?= $monthlyPage + 1 ?>"
                  class="px-2 py-2 sm:px-3 bg-white text-indigo-600 border border-gray-300 rounded hover:bg-gray-50 text-sm font-medium transition whitespace-nowrap">
                  <span class="hidden sm:inline">Next &gt;</span>
                  <span class="sm:hidden">&gt;</span>
                </a>
              <?php else: ?>
                <span class="px-2 py-2 sm:px-3 bg-gray-100 text-gray-400 border border-gray-300 rounded text-sm font-medium cursor-not-allowed whitespace-nowrap">
                  <span class="hidden sm:inline">Next &gt;</span>
                  <span class="sm:hidden">&gt;</span>
                </span>
              <?php endif; ?>

              <!-- Last Page Button -->
              <?php if ($monthlyPage < $totalMonthlyPages): ?>
                <a href="?recap_type=monthly&month=<?= htmlspecialchars($month) ?>&monthly_page=<?= $totalMonthlyPages ?>"
                  class="px-2 py-2 sm:px-3 bg-white text-indigo-600 border border-gray-300 rounded hover:bg-gray-50 text-sm font-medium transition whitespace-nowrap">
                  <span class="hidden sm:inline">Last &gt;&gt;</span>
                  <span class="sm:hidden">Last</span>
                </a>
              <?php else: ?>
                <span class="px-2 py-2 sm:px-3 bg-gray-100 text-gray-400 border border-gray-300 rounded text-sm font-medium cursor-not-allowed whitespace-nowrap">
                  <span class="hidden sm:inline">Last &gt;&gt;</span>
                  <span class="sm:hidden">Last</span>
                </span>
              <?php endif; ?>
            </nav>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</body>

</html>

<?php include __DIR__ . '/footer.php'; ?>