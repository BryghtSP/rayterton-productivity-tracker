<?php
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth.php';
require_login();

$user_id = $_SESSION['user']['user_id'];
$today = date('Y-m-d');

// Ambil posisi karyawan sekali di awal
$stmt = $pdo->prepare("SELECT position FROM employees WHERE user_id = ?");
$stmt->execute([$user_id]);
$employee = $stmt->fetch();

// Tentukan apakah user adalah Internship
$isIntern = false;
if ($employee) {
  $position = strtolower(trim($employee['position']));
  $isIntern = in_array($position, ['internship', 'intern', 'magang']);
}

// Handle check-in/out/izin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['submitCheckIn'])) {
    $current_time = date('H:i:s');
    $location = trim($_POST['location']);
    $shift = $_POST['shift'] ?? null;

    // Validasi shift berdasarkan waktu
    if ($shift === 'Pagi' && $current_time >= '12:00:00') {
      $shift = 'Siang'; // Auto-correct
    } elseif ($shift === 'Siang' && $current_time < '12:00:00') {
      $shift = 'Pagi'; // Auto-correct
    }

    // Fallback otomatis jika tidak pilih shift
    if (!$shift) {
      $shift = $current_time < '12:00:00' ? 'Pagi' : 'Siang';
    }

    // Tentukan status
    $status = 'Hadir';

    if ($isIntern) {
      if ($shift === 'Pagi' && $current_time > '07:45:00') {
        $status = 'Telat';
      } elseif ($shift === 'Siang' && $current_time > '13:10:00') {
        $status = 'Telat';
      }
    } else {
      if ($current_time > '09:00:00') {
        $status = 'Telat';
      }
    }

    // Simpan data absensi
    $stmt = $pdo->prepare("INSERT INTO attendance (user_id, date, check_in, status, location, shift) 
                              VALUES (?, ?, ?, ?, ?, ?)
                              ON DUPLICATE KEY UPDATE 
                              check_in = VALUES(check_in), 
                              status = VALUES(status), 
                              location = VALUES(location),
                              shift = VALUES(shift)");
    $stmt->execute([$user_id, $today, $current_time, $status, $location, $shift]);

    header("Location: attendance.php");
    exit;
  } elseif (isset($_POST['check_out'])) {
    $stmt = $pdo->prepare("UPDATE attendance SET check_out = ? 
                              WHERE user_id = ? AND date = ?");
    $stmt->execute([date('H:i:s'), $user_id, $today]);

    header("Location: attendance.php");
    exit;
  } elseif (isset($_POST['submitIzin'])) {
    $notes = trim($_POST['notes']);
    $stmt = $pdo->prepare("INSERT INTO attendance (user_id, date, status, notes) 
                              VALUES (?, ?, 'Izin', ?)
                              ON DUPLICATE KEY UPDATE status='Izin', notes=VALUES(notes)");
    $stmt->execute([$user_id, $today, $notes]);

    header("Location: attendance.php");
    exit;
  }
}

// Get today's attendance
$stmt = $pdo->prepare("SELECT * FROM attendance WHERE user_id = ? AND date = ?");
$stmt->execute([$user_id, $today]);
$attendance = $stmt->fetch();

// Get monthly attendance
$month = $_GET['month'] ?? date('Y-m');
$start = $month . "-01";
$end = date('Y-m-t', strtotime($start));

$stmt = $pdo->prepare("SELECT date, check_in, check_out, status, location, notes, shift
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
      <div class="flex justify-between">        
        <h1 class="text-2xl font-bold text-gray-800 mb-6">Absent Today (<?= date('d F Y') ?>)</h1>
        <!-- Tampilkan tipe karyawan -->
        <div class="mb-4 text-center">
          <span class="inline-block px-4 py-2 bg-gray-100 text-gray-800 text-sm font-medium rounded-full">
            Anda:
            <strong><?= $isIntern ? 'Internship' : 'Full-time Employee' ?></strong>
          </span>
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <!-- Tombol Check-in (buka modal) -->
        <button
          type="button"
          id="btnCheckIn"
          data-can-checkin="<?= !($attendance && ($attendance['check_in'] || $attendance['status'] === 'Izin')) ? 'true' : 'false' ?>"
          class="w-full py-3 px-4 
            <?= ($attendance && ($attendance['check_in'] || $attendance['status'] === 'Izin'))
              ? 'bg-gray-300 cursor-not-allowed'
              : 'bg-green-600 hover:bg-green-700' ?> 
            text-white font-medium rounded-lg transition"
          <?= ($attendance && ($attendance['check_in'] || $attendance['status'] === 'Izin')) ? 'disabled' : '' ?>>
          <?= $attendance && $attendance['check_in']
            ? 'Sudah Check-in (' . $attendance['check_in'] . ')'
            : ($attendance && $attendance['status'] === 'Izin' ? 'Sudah Izin' : 'Check-in') ?>
        </button>

        <!-- Check-out -->
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

      <!-- Modal Check-in -->
      <div id="modalCheckIn" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
          <h2 class="text-lg font-bold mb-4">Konfirmasi Check-in</h2>
          <form method="post">
            <!-- Info Shift -->
            <p class="text-sm text-gray-600 mb-3">
              <strong>Shift Pagi:</strong> 00:00 - 11:59 |
              <strong>Shift Siang:</strong> 12:00 - 23:59
            </p>

            <!-- Shift -->
            <div class="mb-4">
              <label class="block text-sm font-medium mb-1">Shift</label>
              <select name="shift" required class="w-full border rounded-lg p-2">
                <option value="">-- Pilih Shift --</option>
                <option value="Pagi">Pagi</option>
                <option value="Siang">Siang</option>
              </select>
            </div>

            <!-- Lokasi -->
            <div class="mb-4">
              <label class="block text-sm font-medium mb-1">Lokasi</label>
              <input type="text"
                name="location"
                placeholder="Kantor, WFH, Meeting"
                class="w-full border rounded-lg p-2"
                required>
            </div>

            <div class="flex justify-end gap-2">
              <button type="button" id="closeModalCheckIn" class="px-4 py-2 bg-gray-300 rounded-lg">Batal</button>
              <button type="submit" name="submitCheckIn" class="px-4 py-2 bg-green-600 text-white rounded-lg">Submit</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Modal Izin -->
      <div id="modalIzin" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
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

      <!-- Tampilkan Status Hari Ini -->
      <?php if ($attendance): ?>
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mt-6">
          <div class="bg-gray-50 p-4 rounded-lg">
            <p class="text-sm text-gray-500">Status</p>
            <p class="font-medium <?= $attendance['status'] === 'Hadir' ? 'text-green-600' : ($attendance['status'] === 'Telat' ? 'text-yellow-600' : 'text-red-600') ?>">
              <?= $attendance['status'] ?>
            </p>
          </div>
          <div class="bg-gray-50 p-4 rounded-lg">
            <p class="text-sm text-gray-500">Shift</p>
            <p class="font-medium"><?= htmlspecialchars($attendance['shift'] ?? '-') ?></p>
          </div>
          <div class="bg-gray-50 p-4 rounded-lg">
            <p class="text-sm text-gray-500">Check-in</p>
            <p class="font-medium"><?= $attendance['check_in'] ?? '-' ?></p>
          </div>
          <div class="bg-gray-50 p-4 rounded-lg">
            <p class="text-sm text-gray-500">Check-out</p>
            <p class="font-medium"><?= $attendance['check_out'] ?? '-' ?></p>
          </div>
          <div class="bg-gray-50 p-4 rounded-lg">
            <p class="text-sm text-gray-500">Lokasi</p>
            <p class="font-medium"><?= htmlspecialchars($attendance['location'] ?? '-') ?></p>
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
              <th class="text-sm sm:text-[16px] pb-3 font-medium text-gray-600">Shift</th>
              <th class="text-sm sm:text-[16px] pb-3 font-medium text-gray-600">Check-in</th>
              <th class="text-sm sm:text-[16px] pb-3 font-medium text-gray-600">Check-out</th>
              <th class="text-sm sm:text-[16px] pb-3 font-medium text-gray-600">Status</th>
              <th class="text-sm sm:text-[16px] pb-3 font-medium text-gray-600">Location</th>
              <th class="text-sm sm:text-[16px] pb-3 font-medium text-gray-600">Notes</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <?php foreach ($monthly as $record): ?>
              <tr class="hover:bg-gray-50 transition">
                <td class="py-4 whitespace-nowrap text-sm text-gray-600">
                  <?= htmlspecialchars($record['date']) ?>
                </td>
                <td class="py-4 whitespace-nowrap text-sm font-medium">
                  <?= htmlspecialchars($record['shift'] ?? '-') ?>
                </td>
                <td class="py-4 whitespace-nowrap text-sm font-medium">
                  <?= $record['check_in'] ?? '-' ?>
                </td>
                <td class="py-4 whitespace-nowrap text-sm font-medium">
                  <?= $record['check_out'] ?? '-' ?>
                </td>
                <td class="py-4 whitespace-nowrap">
                  <span class="px-2.5 py-1 rounded-full text-xs font-medium 
                    <?= $record['status'] === 'Hadir' ? 'bg-green-100 text-green-800' : ($record['status'] === 'Telat' ? 'bg-yellow-100 text-yellow-800' : ($record['status'] === 'Izin' ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800')) ?>">
                    <?= htmlspecialchars($record['status']) ?>
                  </span>
                </td>
                <td class="py-4 whitespace-nowrap text-sm font-medium">
                  <?= htmlspecialchars($record['location'] ?? '-') ?>
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

<!-- JavaScript -->
<script>
  document.addEventListener("DOMContentLoaded", function() {
    const btnCheckIn = document.getElementById('btnCheckIn');
    const modalCheckIn = document.getElementById('modalCheckIn');
    const closeModalCheckIn = document.getElementById('closeModalCheckIn');

    const btnIzin = document.getElementById('btnIzin');
    const modalIzin = document.getElementById('modalIzin');
    const closeModal = document.getElementById('closeModal');

    // Handle Check-in
    if (btnCheckIn && modalCheckIn) {
      btnCheckIn.addEventListener('click', function() {
        const canCheckIn = this.getAttribute('data-can-checkin') === 'true';
        if (!canCheckIn) {
          alert('Anda sudah melakukan check-in atau izin hari ini.');
        } else {
          modalCheckIn.classList.remove('hidden');
        }
      });
    }

    if (closeModalCheckIn && modalCheckIn) {
      closeModalCheckIn.addEventListener('click', function() {
        modalCheckIn.classList.add('hidden');
      });
    }

    // Handle Izin
    if (btnIzin && modalIzin) {
      btnIzin.addEventListener('click', function() {
        const attendanceCheckIn = <?= json_encode($attendance && $attendance['check_in']) ?>;
        if (attendanceCheckIn) {
          alert('Anda sudah Check-in, tidak bisa mengajukan izin.');
        } else {
          modalIzin.classList.remove('hidden');
        }
      });
    }

    if (closeModal && modalIzin) {
      closeModal.addEventListener('click', function() {
        modalIzin.classList.add('hidden');
      });
    }
  });
</script>

<?php include __DIR__ . '/footer.php'; ?>