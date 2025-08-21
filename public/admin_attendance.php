<?php
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth.php';
require_admin();

$date = $_GET['date'] ?? date('Y-m-d');
$month = $_GET['month'] ?? date('Y-m');

// Daily attendance
$stmt = $pdo->prepare("
    SELECT a.*, u.name, u.email 
    FROM attendance a
    JOIN users u ON a.user_id = u.user_id
    WHERE a.date = ?
    ORDER BY a.status, u.name
");
$stmt->execute([$date]);
$daily = $stmt->fetchAll();

// Monthly summary
$start = $month . "-01";
$end = date('Y-m-t', strtotime($start));

$stmt = $pdo->prepare("
    SELECT u.user_id, u.name, 
           SUM(CASE WHEN TIME(a.check_in) <= '07:30:00' THEN 1 ELSE 0 END) as hadir_shift_pagi,
           SUM(CASE WHEN TIME(a.check_in) > '07:30:00' AND TIME(a.check_in) <= '13:10:00' THEN 1 ELSE 0 END) as hadir_shift_siang,
           SUM(CASE WHEN TIME(a.check_in) > '13:10:00' AND a.check_in IS NOT NULL THEN 1 ELSE 0 END) as hadir_invalid,
           SUM(CASE WHEN a.status = 'Telat' THEN 1 ELSE 0 END) as telat,
           SUM(CASE WHEN a.status = 'Izin' THEN 1 ELSE 0 END) as izin,
           SUM(CASE WHEN a.status = 'Sakit' THEN 1 ELSE 0 END) as sakit,
           SUM(CASE WHEN a.status = 'Alpa' THEN 1 ELSE 0 END) as alpa
    FROM users u
    LEFT JOIN attendance a ON a.user_id = u.user_id AND a.date BETWEEN ? AND ?
    WHERE u.is_active = 1
    GROUP BY u.user_id
    ORDER BY u.name
");

$stmt->execute([$start, $end]);
$monthly = $stmt->fetchAll();

include __DIR__ . '/header.php';
?>

<div class="max-w-7xl mx-auto px-4 py-8 space-y-8">
  <!-- Daily Attendance Card -->
  <div class="bg-white rounded-xl shadow-md overflow-hidden">
    <div class="p-6 md:p-8">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Daily Attendance</h1>
        <form class="flex flex-col sm:flex-row items-center gap-2">
          <input type="date" name="date" value="<?= htmlspecialchars($date) ?>" 
                 class="px-3 py-2 w-full border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
          <button type="submit" class="px-4 py-2 w-full sm:w-[68px] bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
            Filter
          </button>
        </form>
      </div>
      
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="text-left border-b border-gray-200">
              <th class="pb-3 font-medium text-gray-600">Name</th>
              <th class="pb-3 font-medium text-gray-600">Email</th>
              <th class="pb-3 font-medium text-gray-600">Check-in</th>
              <th class="pb-3 font-medium text-gray-600">Check-out</th>
              <th class="pb-3 font-medium text-gray-600">Status</th>
              <th class="pb-3 font-medium text-gray-600">Notes</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <?php foreach($daily as $record): ?>
            <tr class="hover:bg-gray-50 transition">
              <td class="py-4 whitespace-nowrap text-sm font-medium text-gray-800">
                <?= htmlspecialchars($record['name']) ?>
              </td>
              <td class="py-4 whitespace-nowrap text-sm text-gray-600">
                <?= htmlspecialchars($record['email']) ?>
              </td>
              <td class="py-4 whitespace-nowrap text-sm">
                <?= $record['check_in'] ?? '-' ?>
              </td>
              <td class="py-4 whitespace-nowrap text-sm">
                <?= $record['check_out'] ?? '-' ?>
              </td>
              <td class="py-4 whitespace-nowrap">
                <span class="px-2.5 py-1 rounded-full text-xs font-medium 
                  <?= $record['status'] === 'Hadir' ? 
                     (($record['check_in'] <= '07:30:00') ? 'bg-green-100 text-green-800' : 
                      (($record['check_in'] <= '13:10:00') ? 'bg-blue-100 text-blue-800' : 'bg-orange-100 text-orange-800')) :
                     ($record['status'] === 'Telat' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') ?>">
                  <?= htmlspecialchars($record['status']) ?>
                  <?php if($record['status'] === 'Hadir'): ?>
                    (<?= $record['check_in'] <= '07:30:00' ? 'Pagi' : 
                       ($record['check_in'] <= '13:10:00' ? 'Siang' : 'Invalid') ?>)
                  <?php endif; ?>
                </span>
              </td>
              <td class="py-4 whitespace-nowrap text-sm text-gray-700">
                <?= !empty($record['notes']) ? htmlspecialchars($record['notes']) : '-' ?>
            </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Monthly Summary Card -->
  <div class="bg-white rounded-xl shadow-md overflow-hidden">
    <div class="p-6 md:p-8">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <h2 class="text-xl font-bold text-gray-800">Monthly Recap</h2>
        <form class="flex flex-col sm:flex-row items-center gap-2">
          <input type="month" name="month" value="<?= htmlspecialchars($month) ?>" 
                 class="px-3 py-2 w-full border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
          <button type="submit" class="px-4 py-2 w-full sm:w-[68px] bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
            Filter
          </button>
        </form>
      </div>
      
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="text-left border-b border-gray-200">
              <th class="pb-3 font-medium text-gray-600">Name</th>
              <th class="pb-3 font-medium text-gray-600 text-center">Shift Pagi</th>
              <th class="pb-3 font-medium text-gray-600 text-center">Shift Siang</th>
              <th class="pb-3 font-medium text-gray-600 text-center">Invalid</th>
              <th class="pb-3 font-medium text-gray-600 text-center">Telat</th>
              <th class="pb-3 font-medium text-gray-600 text-center">Izin</th>
              <th class="pb-3 font-medium text-gray-600 text-center">Sakit</th>
              <th class="pb-3 font-medium text-gray-600 text-center">Alpa</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <?php foreach($monthly as $record): ?>
            <tr class="hover:bg-gray-50 transition">
              <td class="py-4 whitespace-nowrap text-sm font-medium text-gray-800">
                <?= htmlspecialchars($record['name']) ?>
              </td>
              <td class="py-4 whitespace-nowrap text-sm text-center font-medium text-green-600">
                <?= $record['hadir_shift_pagi'] ?? 0 ?>
              </td>
              <td class="py-4 whitespace-nowrap text-sm text-center font-medium text-blue-600">
                <?= $record['hadir_shift_siang'] ?? 0 ?>
              </td>
              <td class="py-4 whitespace-nowrap text-sm text-center font-medium text-orange-600">
                <?= $record['hadir_invalid'] ?? 0 ?>
              </td>
              <td class="py-4 whitespace-nowrap text-sm text-center font-medium text-yellow-600">
                <?= $record['telat'] ?? 0 ?>
              </td>
              <td class="py-4 whitespace-nowrap text-sm text-center font-medium text-indigo-600">
                <?= $record['izin'] ?? 0 ?>
              </td>
              <td class="py-4 whitespace-nowrap text-sm text-center font-medium text-purple-600">
                <?= $record['sakit'] ?? 0 ?>
              </td>
              <td class="py-4 whitespace-nowrap text-sm text-center font-medium text-red-600">
                <?= $record['alpa'] ?? 0 ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>