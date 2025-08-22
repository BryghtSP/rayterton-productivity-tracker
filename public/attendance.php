<?php
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth.php';
require_login();

// date_default_timezone_set(timezoneId: 'Asia/Jakarta'); // Pastikan timezone benar

$user_id = $_SESSION['user']['user_id'];
$today = date('Y-m-d');

//test commit

// Handle check-in/out/izin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['check_in'])) {
        $current_time = date('H:i:s');
        $status = 'Hadir';
        
        if ($current_time > '13:10:00') {
            $status = 'Alpa';
        } elseif ($current_time > '07:30:00') {
            $status = 'Telat';
        }
        
        $stmt = $pdo->prepare("INSERT INTO attendance (user_id, date, check_in, status) 
                              VALUES (?, ?, ?, ?)
                              ON DUPLICATE KEY UPDATE check_in = VALUES(check_in), status = VALUES(status)");
        $stmt->execute([$user_id, $today, $current_time, $status]);
    } 
    elseif (isset($_POST['check_out'])) {
        $stmt = $pdo->prepare("UPDATE attendance SET check_out = ? 
                              WHERE user_id = ? AND date = ?");
        $stmt->execute([date('H:i:s'), $user_id, $today]);
    }
    elseif (isset($_POST['submitIzin'])) {
        $notes = trim($_POST['notes']);
        $stmt = $pdo->prepare("INSERT INTO attendance (user_id, date, status, notes) 
                              VALUES (?, ?, 'Izin', ?)
                              ON DUPLICATE KEY UPDATE status='Izin', notes=VALUES(notes)");
        $stmt->execute([$user_id, $today, $notes]);
    }
    
    header("Location: attendance.php");
    exit;
}

// Get today's attendance
$stmt = $pdo->prepare("SELECT * FROM attendance WHERE user_id = ? AND date = ?");
$stmt->execute([$user_id, $today]);
$attendance = $stmt->fetch();

// Get monthly attendance
$month = $_GET['month'] ?? date('Y-m');
$start = $month . "-01";
$end = date('Y-m-t', strtotime($start));

$stmt = $pdo->prepare("SELECT date, check_in, check_out, status, notes
                      FROM attendance 
                      WHERE user_id = ? AND date BETWEEN ? AND ?
                      ORDER BY date DESC");
$stmt->execute([$user_id, $start, $end]);
$monthly = $stmt->fetchAll();

include __DIR__ . '/header.php';
?>

<div class="max-w-4xl mx-auto px-4 py-8 space-y-8">
  <!-- Today's Attendance Card -->
  <div class="bg-white rounded-xl shadow-md overflow-hidden">
    <div class="p-6 md:p-8">
      <h1 class=" text-2xl font-bold text-gray-800 mb-6">Absent Today (<?= date('d F Y') ?>)</h1>
      
      <div class="flex flex-col sm:flex-row gap-4 mb-6">
        <form method="post" class="flex-1">
           <button type="submit" name="check_in" 
          class="w-full py-3 px-4 <?= 
              ($attendance && ($attendance['check_in'] || $attendance['status'] === 'Izin')) 
                  ? 'bg-gray-300 cursor-not-allowed' 
                  : 'bg-green-600 hover:bg-green-700' ?> 
          text-white font-medium rounded-lg transition"
                <?= ($attendance && ($attendance['check_in'] || $attendance['status'] === 'Izin')) ? 'disabled' : '' ?>>
            <?= $attendance && $attendance['check_in'] 
                ? 'Sudah Check-in (' . $attendance['check_in'] . ')' 
                : ($attendance && $attendance['status'] === 'Izin' ? 'Sudah Izin' : 'Check-in') ?>
      </button>
        </form>
        
        <form method="post" class="flex-1">
          <button type="submit" name="check_out" 
                  class="w-full py-3 px-4 <?= !$attendance || !$attendance['check_in'] || $attendance['check_out'] ? 'bg-gray-300 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700' ?> 
                  text-white font-medium rounded-lg transition"
                  <?= !$attendance || !$attendance['check_in'] || $attendance['check_out'] ? 'disabled' : '' ?>>
            <?= $attendance && $attendance['check_out'] ? 'Sudah Check-out (' . $attendance['check_out'] . ')' : 'Check-out' ?>
          </button>
        </form>

        <!-- Tombol Izin -->
        <button id="btnIzin" type="button"
                class="flex-1 w-full py-3 px-4 <?= 
                    ($attendance && $attendance['check_in']) 
                        ? 'bg-gray-300 cursor-not-allowed' 
                        : 'bg-yellow-500 hover:bg-yellow-600' ?> 
                text-white font-medium rounded-lg transition"
                <?= ($attendance && $attendance['check_in']) ? 'disabled' : '' ?>>
        <?= ($attendance && $attendance['check_in']) ? 'Sudah Check-in' : 'Izin' ?>
        </button>
      </div>

      <!-- Modal Izin -->
      <div id="modalIzin" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden md:px-0 px-2">
        <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
          <h2 class="text-lg font-bold mb-4">Form Izin</h2>
          <form method="post">
            <label class="block text-sm font-medium mb-1">Alasan Izin:</label>
            <textarea name="notes" class="w-full border rounded-lg p-2 mb-4" rows="3" required></textarea>
            <div class="flex justify-end gap-2">
              <button type="button" id="closeModal" class="px-4 py-2 bg-gray-300 rounded-lg">Batal</button>
              <button type="submit" name="submitIzin" class="px-4 py-2 bg-yellow-500 text-white rounded-lg">Kirim</button>
            </div>
          </form>
        </div>
      </div>

      <script>
        document.getElementById('btnIzin').addEventListener('click', function() {
        <?php if ($attendance && $attendance['check_in']): ?>
            alert('Anda sudah Check-in, tidak bisa mengajukan izin.');
        <?php else: ?>
            document.getElementById('modalIzin').classList.remove('hidden');
        <?php endif; ?>
        });

        document.getElementById('btnIzin').addEventListener('click', function() {
          document.getElementById('modalIzin').classList.remove('hidden');
        });
        document.getElementById('closeModal').addEventListener('click', function() {
          document.getElementById('modalIzin').classList.add('hidden');
        });
      </script>
      
      <?php if ($attendance): ?>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
        <div class="bg-gray-50 p-4 rounded-lg">
          <p class="text-sm text-gray-500">Status</p>
          <p class="font-medium <?= $attendance['status'] === 'Hadir' ? 'text-green-600' : ($attendance['status'] === 'Telat' ? 'text-yellow-600' : 'text-red-600') ?>">
            <?= $attendance['status'] ?>
          </p>
        </div>
        <div class="bg-gray-50 p-4 rounded-lg">
          <p class="text-sm text-gray-500">Check-in</p>
          <p class="font-medium"><?= $attendance['check_in'] ?? '-' ?></p>
        </div>
        <div class="bg-gray-50 p-4 rounded-lg">
          <p class="text-sm text-gray-500">Check-out</p>
          <p class="font-medium"><?= $attendance['check_out'] ?? '-' ?></p>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Monthly Attendance Card -->
  <div class="bg-white rounded-xl shadow-md overflow-hidden">
    <div class="p-6 md:p-8">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <h2 class="text-xl font-bold text-gray-800">Monthly Recap</h2>
        <form class="flex flex-col sm:flex-row items-center gap-2">
          <input type="month" name="month" value="<?= htmlspecialchars($month) ?>" 
                 class="px-3 py-2 border w-full border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
          <button type="submit" class="w-full sm:w-[69px] px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
            Filter
          </button>
        </form>
      </div>
      
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="text-left border-b border-gray-200">
              <th class="text-sm sm:text-[16px] pb-3 font-medium text-gray-600">Date</th>
              <th class="text-sm sm:text-[16px] pb-3 font-medium text-gray-600">Check-in</th>
              <th class="text-sm sm:text-[16px] pb-3 font-medium text-gray-600">Check-out</th>
              <th class="text-sm sm:text-[16px] pb-3 font-medium text-gray-600">Status</th>
              <th class="text-sm sm:text-[16px] pb-3 font-medium text-gray-600">Notes</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <?php foreach($monthly as $record): ?>
            <tr class="hover:bg-gray-50 transition">
              <td class="py-4 whitespace-nowrap text-sm text-gray-600">
                <?= htmlspecialchars($record['date']) ?>
              </td>
              <td class="py-4 whitespace-nowrap text-sm font-medium">
                <?= $record['check_in'] ?? '-' ?>
              </td>
              <td class="py-4 whitespace-nowrap text-sm font-medium">
                <?= $record['check_out'] ?? '-' ?>
              </td>
              <td class="py-4 whitespace-nowrap">
                <span class="px-2.5 py-1 rounded-full text-xs font-medium 
                  <?= $record['status'] === 'Hadir' ? 'bg-green-100 text-green-800' : 
                     ($record['status'] === 'Telat' ? 'bg-yellow-100 text-yellow-800' : 
                     ($record['status'] === 'Izin' ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800')) ?>">
                  <?= htmlspecialchars($record['status']) ?>
                </span>
              </td>
              <td class="py-4 whitespace-nowrap text-sm text-gray-600">
                <?= htmlspecialchars($record['notes'] ?? '-') ?>
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
