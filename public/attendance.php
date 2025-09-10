<?php
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth.php';
require_login();

$user_id = $_SESSION['user']['user_id'];
$today = date('Y-m-d');

// Get employee position
$stmt = $pdo->prepare("SELECT position FROM employees WHERE user_id = ?");
$stmt->execute([$user_id]);
$employee = $stmt->fetch();

$isIntern = false;
if ($employee) {
    $position = strtolower(trim($employee['position']));
    $isIntern = in_array($position, ['internship', 'intern', 'magang']);
}

// Handle Leave Request
if (isset($_POST['submitLeave'])) {
    $leave_type = $_POST['leave_type'] ?? '';
    $explanation = trim($_POST['explanation']);

    if (empty($leave_type) || empty($explanation)) {
        header("Location: attendance.php?error=missing_data");
        exit;
    }

    // Cek apakah sudah check-in
    $stmt = $pdo->prepare("SELECT check_in FROM attendance WHERE user_id = ? AND date = ?");
    $stmt->execute([$user_id, $today]);
    $existing = $stmt->fetch();

    if ($existing && $existing['check_in']) {
        header("Location: attendance.php?error=already_checked_in");
        exit;
    }

    // Tentukan status
    $status = match ($leave_type) {
        'Sick' => 'Sick',
        'Leave' => 'Leave',
        'Others' => 'Others',
        default => 'Leave'
    };

    $notes = "$status request";

    // Simpan ke database
    $stmt = $pdo->prepare("INSERT INTO attendance (user_id, date, status, notes, explanation) 
                          VALUES (?, ?, ?, ?, ?)
                          ON DUPLICATE KEY UPDATE 
                          status = VALUES(status), 
                          notes = VALUES(notes), 
                          explanation = VALUES(explanation)");

    $stmt->execute([$user_id, $today, $status, $notes, $explanation]);

    header("Location: attendance.php");
    exit;
}

// Handle Check-in
if (isset($_POST['submitCheckIn'])) {
    $current_time = date('H:i:s');
    $location = trim($_POST['location']);
    $shift = $_POST['shift'] ?? 'WFO';
    $explanation = trim($_POST['explanation'] ?? '');

    // Cek apakah sudah request leave
    $stmt = $pdo->prepare("SELECT status FROM attendance WHERE user_id = ? AND date = ?");
    $stmt->execute([$user_id, $today]);
    $existing = $stmt->fetch();

    if ($existing && in_array($existing['status'], ['Leave', 'Sick', 'Others'])) {
        header("Location: attendance.php?error=leave_already_submitted");
        exit;
    }

    $status = 'Present';
    $notes = "Hadir: $shift";
    $save_explanation = null;

    // Cek keterlambatan
    $is_late = false;
    if ($shift === 'Morning' && $current_time > '08:30:59') {
        $is_late = true;
    } elseif ($shift === 'Afternoon' && $current_time > '13:30:59') {
        $is_late = true;
    } elseif (in_array($shift, ['WFO', 'WAC', 'WFH', 'WFA']) && $current_time > '09:30:59') {
        $is_late = true;
    }

    if ($is_late) {
        $status = 'Late';
        $notes = "Late: $shift";

        if (empty($explanation) || strlen($explanation) < 10) {
            header("Location: attendance.php?error=explanation_required");
            exit;
        }
        $save_explanation = $explanation;
    }

    // Simpan ke database
    $stmt = $pdo->prepare("INSERT INTO attendance (user_id, date, check_in, status, location, notes, explanation) 
                          VALUES (?, ?, ?, ?, ?, ?, ?)
                          ON DUPLICATE KEY UPDATE 
                          check_in = VALUES(check_in), 
                          status = VALUES(status), 
                          location = VALUES(location),
                          notes = VALUES(notes),
                          explanation = VALUES(explanation)");

    $stmt->execute([
        $user_id,
        $today,
        $current_time,
        $status,
        $location,
        $notes,
        $save_explanation
    ]);

    header("Location: attendance.php");
    exit;
}

// Handle Check-out
if (isset($_POST['check_out'])) {
    $current_time = date('H:i:s');
    $stmt = $pdo->prepare("UPDATE attendance SET check_out = ? WHERE user_id = ? AND date = ?");
    $stmt->execute([$current_time, $user_id, $today]);
    header("Location: attendance.php");
    exit;
}

// Get today's attendance
$stmt = $pdo->prepare("SELECT * FROM attendance WHERE user_id = ? AND date = ?");
$stmt->execute([$user_id, $today]);
$attendance = $stmt->fetch();

// Cek apakah sudah request leave
$is_leave = $attendance && in_array($attendance['status'], ['Leave', 'Sick', 'Others']);

// Get monthly attendance
$month = $_GET['month'] ?? date('Y-m');
$start = $month . "-01";
$end = date('Y-m-t', strtotime($start));
$stmt = $pdo->prepare("SELECT date, check_in, check_out, status, location, notes, explanation
                      FROM attendance 
                      WHERE user_id = ? AND date BETWEEN ? AND ?
                      ORDER BY date DESC");
$stmt->execute([$user_id, $start, $end]);
$monthly = $stmt->fetchAll();

include __DIR__ . '/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rayterton Productivity Tracker - Attendance</title>
    <link rel="stylesheet" href="css/output.css">
</head>
<body>
    <div class="max-w-4xl mx-auto px-4 py-8 space-y-8">

        <!-- Today's Attendance -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="p-6 md:p-8">
                <div class="flex justify-between">
                    <h1 class="text-2xl font-bold text-gray-800 mb-6">Attendance Today (<?= date('d F Y') ?>)</h1>
                    <div class="mb-4 text-center">
                        <span class="inline-block px-4 py-2 bg-gray-100 text-gray-800 text-sm font-medium rounded-full">
                            You are: <strong><?= $isIntern ? 'Internship' : 'Full-time Employee' ?></strong>
                        </span>
                    </div>
                </div>

                <!-- Error Messages -->
                <?php if (isset($_GET['error'])): ?>
                    <div class="mb-6 p-4 bg-red-50 text-red-700 rounded-lg text-sm">
                        <?= htmlspecialchars(
                            $_GET['error'] === 'missing_data' ? 'Data tidak lengkap.' :
                            ($_GET['error'] === 'already_checked_in' ? 'Anda sudah check-in, tidak bisa request leave.' :
                            ($_GET['error'] === 'leave_already_submitted' ? 'Anda sudah request leave, tidak bisa check-in.' :
                            ($_GET['error'] === 'explanation_required' ? 'Alasan keterlambatan wajib diisi (min. 10 karakter).' : 'Error tidak diketahui.')))
                        )?>
                    </div>
                <?php endif; ?>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                    <!-- Check-in -->
                    <button type="button" id="btnCheckIn"
                        <?= ($attendance && $attendance['check_in']) || $is_leave ? 'disabled' : '' ?>
                        class="w-full py-3 px-4 
                        <?= ($attendance && $attendance['check_in']) || $is_leave ? 'bg-gray-300 cursor-not-allowed' : 'bg-green-600 hover:bg-green-700' ?>
                        text-white font-medium rounded-lg transition">
                        <?= $attendance && $attendance['check_in'] ? 'Checked-in (' . $attendance['check_in'] . ')' : ($is_leave ? 'Leave Requested' : 'Check-in') ?>
                    </button>

                    <!-- Checkout Button -->
                    <form method="post" id="checkoutForm" class="flex-1">
                        <button type="button" id="btnCheckout"
                            class="w-full py-3 px-4 
                            <?= !$attendance || !$attendance['check_in'] || $attendance['check_out'] ? 'bg-gray-300 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700' ?>
                            text-white font-medium rounded-lg transition"
                            <?= !$attendance || !$attendance['check_in'] || $attendance['check_out'] ? 'disabled' : '' ?>>
                            <?= $attendance && $attendance['check_out'] ? 'Checked-out (' . $attendance['check_out'] . ')' : 'Check-out' ?>
                        </button>
                        <input type="hidden" name="check_out" value="1">
                    </form>

                    <!-- Modal Checkout -->
                    <div id="modalCheckout" class="fixed inset-0 bg-white/30 backdrop-blur-sm flex items-center justify-center z-50 hidden">
                        <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
                            <h2 class="text-lg font-bold mb-4">Konfirmasi Check-out</h2>
                            <p class="mb-4">Apakah kamu yakin ingin melakukan check-out sekarang?</p>
                            <div class="flex justify-end gap-2">
                                <button type="button" id="closeModalCheckout" class="px-4 py-2 bg-gray-300 rounded-lg">Batal</button>
                                <button type="button" id="confirmCheckout" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Ya, Checkout</button>
                            </div>
                        </div>
                    </div>

                    <script>
                        document.addEventListener("DOMContentLoaded", function() {
                            const btnCheckout = document.getElementById('btnCheckout');
                            const modalCheckout = document.getElementById('modalCheckout');
                            const closeModalCheckout = document.getElementById('closeModalCheckout');
                            const confirmCheckout = document.getElementById('confirmCheckout');
                            const checkoutForm = document.getElementById('checkoutForm');

                            btnCheckout?.addEventListener('click', () => {
                                modalCheckout.classList.remove('hidden');
                            });

                            closeModalCheckout?.addEventListener('click', () => {
                                modalCheckout.classList.add('hidden');
                            });

                            confirmCheckout?.addEventListener('click', () => {
                                checkoutForm.submit();
                            });
                        });
                    </script>

                    <!-- Leave -->
                    <button id="btnLeave" type="button"
                        <?= ($attendance && $attendance['check_in']) || $is_leave ? 'disabled' : '' ?>
                        class="w-full py-3 px-4 
                        <?= ($attendance && $attendance['check_in']) || $is_leave ? 'bg-gray-300 cursor-not-allowed' : 'bg-yellow-500 hover:bg-yellow-600' ?>
                        text-white font-medium rounded-lg transition">
                        <?= ($attendance && $attendance['check_in']) || $is_leave ? 'Already Action Taken' : 'Request Leave' ?>
                    </button>
                </div>

                <!-- Modal Check-in -->
                <div id="modalCheckIn" class="fixed inset-0 bg-white/30 backdrop-blur-sm flex items-center justify-center z-50 hidden">
                    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
                        <h2 class="text-lg font-bold mb-4">Check-in Confirmation</h2>
                        <form method="post" id="checkInForm">
                            <p class="text-sm text-gray-600 mb-3">
                                <strong>Morning:</strong> 07:30 - 11:59 | <strong>Afternoon:</strong> 13:00 - 17:30
                            </p>

                            <div class="mb-4">
                                <label class="block text-sm font-medium mb-1">Shift</label>
                                <select name="shift" required class="w-full border rounded-lg p-2" id="shiftSelect">
                                    <option value="">-- Select Shift --</option>
                                    <option value="Morning">Pagi</option>
                                    <option value="Afternoon">Siang</option>
                                    <option value="WFO">Whole Day at Office (WFO)</option>
                                    <option value="WAC">Working at Client (WAC)</option>
                                    <option value="WFH">Working from Home (WFH)</option>
                                    <option value="WFA">Working from Anywhere (WFA)</option>
                                </select>
                                <p id="shiftHint" class="mt-1 text-xs text-gray-500 italic">Please select your shift.</p>
                            </div>

                            <div class="mb-4">
                                <label class="block text-sm font-medium mb-1">Location</label>
                                <input type="text" name="location" placeholder="Office, WFH, Meeting" class="w-full border rounded-lg p-2" required>
                            </div>

                            <div class="mb-4 hidden" id="explanationContainer">
                                <label class="block text-sm font-medium mb-1">Explanation / Reason for Late</label>
                                <textarea name="explanation" class="w-full border rounded-lg p-2" rows="3" placeholder="e.g., Traffic jam, health issue, family emergency"></textarea>
                            </div>

                            <div class="flex justify-end gap-2">
                                <button type="button" id="closeModalCheckIn" class="px-4 py-2 bg-gray-300 rounded-lg">Cancel</button>
                                <button type="submit" name="submitCheckIn" class="px-4 py-2 bg-green-600 text-white rounded-lg">Submit</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Modal Leave -->
                <div id="modalLeave" class="fixed inset-0 bg-white/30 backdrop-blur-sm flex items-center justify-center z-50 hidden">
                    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
                        <h2 class="text-lg font-bold mb-4">Leave Form</h2>
                        <form method="post">
                            <div class="mb-4">
                                <label class="block text-sm font-medium mb-1">Type of Leave</label>
                                <select name="leave_type" required class="w-full border rounded-lg p-2">
                                    <option value="">-- Select Type --</option>
                                    <option value="Sick">Sick / Illness</option>
                                    <option value="Leave">Leave / Cuti</option>
                                    <option value="Others">Others</option>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label class="block text-sm font-medium mb-1">Explanation / Reason</label>
                                <textarea name="explanation" class="w-full border rounded-lg p-2" rows="3" placeholder="e.g., Demam tinggi, acara keluarga" required></textarea>
                            </div>

                            <div class="flex justify-end gap-2">
                                <button type="button" id="closeModalLeave" class="px-4 py-2 bg-gray-300 rounded-lg">Cancel</button>
                                <button type="submit" name="submitLeave" class="px-4 py-2 bg-yellow-500 text-white rounded-lg">Submit</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Today's Details -->
                <?php if ($attendance): ?>
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mt-6">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-sm text-gray-500">Status</p>
                            <p class="font-medium 
                                <?= $attendance['status'] === 'Present' ? 'text-green-600' : 
                                   ($attendance['status'] === 'Late' ? 'text-yellow-600' : 'text-red-600') ?>">
                                <?= htmlspecialchars($attendance['status']) ?>
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
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-sm text-gray-500">Location</p>
                            <p class="font-medium"><?= htmlspecialchars($attendance['location'] ?? '-') ?></p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-sm text-gray-500">Explanation</p>
                            <p class="font-medium"><?= htmlspecialchars($attendance['explanation'] ?? '-') ?></p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Monthly Recap -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="p-6 md:p-8">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                    <h2 class="text-xl font-bold text-gray-800">Monthly Recap</h2>
                    <form class="flex flex-col sm:flex-row items-center gap-2">
                        <input type="month" name="month" value="<?= htmlspecialchars($month) ?>" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 transition">
                        <button type="submit" class="w-full sm:w-auto px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">Filter</button>
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="border-b border-gray-200">
                            <tr>
                                <th class="pb-3 font-medium text-gray-600 text-left">Date</th>
                                <th class="pb-3 font-medium text-gray-600 text-left">Check-in</th>
                                <th class="pb-3 font-medium text-gray-600 text-left">Check-out</th>
                                <th class="pb-3 font-medium text-gray-600 text-left">Status</th>
                                <th class="pb-3 font-medium text-gray-600 text-left">Location</th>
                                <th class="pb-3 font-medium text-gray-600 text-left">Notes</th>
                                <th class="pb-3 font-medium text-gray-600 text-left">Explanation</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($monthly as $record): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="py-4"><?= htmlspecialchars($record['date']) ?></td>
                                    <td class="py-4"><?= $record['check_in'] ?? '-' ?></td>
                                    <td class="py-4"><?= $record['check_out'] ?? '-' ?></td>
                                    <td class="py-4">
                                        <span class="px-2.5 py-1 rounded-full text-xs font-medium
                                            <?= $record['status'] === 'Present' ? 'bg-green-100 text-green-800' : 
                                               ($record['status'] === 'Late' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') ?>">
                                            <?= htmlspecialchars($record['status']) ?>
                                        </span>
                                    </td>
                                    <td class="py-4"><?= htmlspecialchars($record['location'] ?? '-') ?></td>
                                    <td class="py-4"><?= htmlspecialchars($record['notes'] ?? '-') ?></td>
                                    <td class="py-4"><?= htmlspecialchars($record['explanation'] ?? '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const btnCheckIn = document.getElementById('btnCheckIn');
            const modalCheckIn = document.getElementById('modalCheckIn');
            const closeModalCheckIn = document.getElementById('closeModalCheckIn');

            const btnLeave = document.getElementById('btnLeave');
            const modalLeave = document.getElementById('modalLeave');
            const closeModalLeave = document.getElementById('closeModalLeave');

            const shiftSelect = document.getElementById('shiftSelect');
            const explanationContainer = document.getElementById('explanationContainer');
            const checkInForm = document.getElementById('checkInForm');

            function getCurrentTime() {
                const now = new Date();
                return `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}:00`;
            }

            function isLate(shift, timeStr) {
                const [h, m] = timeStr.split(':').map(Number);
                const minutes = h * 60 + m;
                if (shift === 'Morning' && minutes > 7 * 60 + 59) return true;
                if (shift === 'Afternoon' && minutes > 13 * 60 + 29) return true;
                if (['WFO', 'WAC', 'WFH', 'WFA'].includes(shift) && minutes > 8 * 60 + 30) return true;
                return false;
            }

            shiftSelect?.addEventListener('change', function() {
                const currentTime = getCurrentTime();
                if (isLate(this.value, currentTime)) {
                    explanationContainer.classList.remove('hidden');
                } else {
                    explanationContainer.classList.add('hidden');
                }
            });

            checkInForm?.addEventListener('submit', function(e) {
                const shift = shiftSelect.value;
                const currentTime = getCurrentTime();
                const explanation = checkInForm.querySelector('textarea[name="explanation"]')?.value.trim();

                if (isLate(shift, currentTime) && (!explanation || explanation.length < 10)) {
                    e.preventDefault();
                    alert('Alasan keterlambatan wajib diisi (minimal 10 karakter).');
                }
            });

            btnCheckIn?.addEventListener('click', () => {
                if (btnCheckIn.disabled) {
                    alert('Aksi tidak bisa dilakukan.');
                } else {
                    modalCheckIn.classList.remove('hidden');
                }
            });

            closeModalCheckIn?.addEventListener('click', () => modalCheckIn.classList.add('hidden'));

            btnLeave?.addEventListener('click', () => {
                if (btnLeave.disabled) {
                    alert('Aksi tidak bisa dilakukan.');
                } else {
                    modalLeave.classList.remove('hidden');
                }
            });

            closeModalLeave?.addEventListener('click', () => modalLeave.classList.add('hidden'));
        });
    </script>

    <?php include __DIR__ . '/footer.php'; ?>
</body>
</html>