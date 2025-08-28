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

// Handle check-in/out/leave
if (isset($_POST['submitCheckIn'])) {
    $current_time = date('H:i:s');
    $location = trim($_POST['location']);
    $shift = $_POST['shift'] ?? null;

    // Default ke WFO kalau tidak diisi
    if (!$shift) $shift = 'WFO';

    // Set status Hadir untuk semua jenis shift
    $status = 'Hadir';
    $notes = $shift; // notes = WFO/WAC/WFH/WFA sesuai shift

    $stmt = $pdo->prepare("INSERT INTO attendance (user_id, date, check_in, status, location, shift, notes) 
                          VALUES (?, ?, ?, ?, ?, ?, ?)
                          ON DUPLICATE KEY UPDATE 
                          check_in = VALUES(check_in), 
                          status = VALUES(status), 
                          location = VALUES(location),
                          shift = VALUES(shift),
                          notes = VALUES(notes)");
    $stmt->execute([$user_id, $today, $current_time, $status, $location, $shift, $notes]);

    header("Location: attendance.php");
    exit;
}

// Handle check-in/out/leave
if (isset($_POST['submitCheckIn'])) {
    $current_time = date('H:i:s');
    $location = trim($_POST['location']);
    $shift = $_POST['shift'] ?? null;

    // Default ke WFO kalau tidak diisi
    if (!$shift) $shift = 'WFO';

    // Set status Present untuk semua jenis shift
    $status = 'Present';
    $notes = $shift; // notes = WFO/WAC/WFH/WFA sesuai shift

    $stmt = $pdo->prepare("INSERT INTO attendance (user_id, date, check_in, status, location, shift, notes) 
                          VALUES (?, ?, ?, ?, ?, ?, ?)
                          ON DUPLICATE KEY UPDATE 
                          check_in = VALUES(check_in), 
                          status = VALUES(status), 
                          location = VALUES(location),
                          shift = VALUES(shift),
                          notes = VALUES(notes)");
    $stmt->execute([$user_id, $today, $current_time, $status, $location, $shift, $notes]);

    header("Location: attendance.php");
    exit;
}

// Check-out handler
if (isset($_POST['check_out'])) {
    $current_time = date('H:i:s');
    $stmt = $pdo->prepare("UPDATE attendance SET check_out = ? WHERE user_id = ? AND date = ?");
    $stmt->execute([$current_time, $user_id, $today]);
    header("Location: attendance.php");
    exit;
}

// Leave handler
if (isset($_POST['submitLeave'])) {
    $notes = trim($_POST['notes']);
    $stmt = $pdo->prepare("INSERT INTO attendance (user_id, date, status, notes) 
                          VALUES (?, ?, 'Leave', ?)
                          ON DUPLICATE KEY UPDATE status='Leave', notes=VALUES(notes)");
    $stmt->execute([$user_id, $today, $notes]);
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
$stmt = $pdo->prepare("SELECT date, check_in, check_out, status, location, notes, shift
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

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
<!-- Check-in -->
<button type="button" id="btnCheckIn" data-can-checkin="<?= !($attendance && ($attendance['check_in'] || $attendance['status'] === 'Leave')) ? 'true' : 'false' ?>"
class="w-full py-3 px-4 <?= ($attendance && ($attendance['check_in'] || $attendance['status'] === 'Leave')) ? 'bg-gray-300 cursor-not-allowed' : 'bg-green-600 hover:bg-green-700' ?> text-white font-medium rounded-lg transition"
<?= ($attendance && ($attendance['check_in'] || $attendance['status'] === 'Leave')) ? 'disabled' : '' ?>>
<?= $attendance && $attendance['check_in'] ? 'Checked-in (' . $attendance['check_in'] . ')' : ($attendance && $attendance['status']==='Leave' ? 'Leave Taken' : 'Check-in') ?>
</button>

<!-- Check-out -->
<form method="post" class="flex-1">
<button type="submit" name="check_out"
class="w-full py-3 px-4 <?= !$attendance || !$attendance['check_in'] || $attendance['check_out'] ? 'bg-gray-300 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700' ?> text-white font-medium rounded-lg transition"
<?= !$attendance || !$attendance['check_in'] || $attendance['check_out'] ? 'disabled' : '' ?>>
<?= $attendance && $attendance['check_out'] ? 'Checked-out (' . $attendance['check_out'] . ')' : 'Check-out' ?>
</button>
</form>

<!-- Leave -->
<button id="btnLeave" type="button" class="flex-1 w-full py-3 px-4 <?= ($attendance && $attendance['check_in']) ? 'bg-gray-300 cursor-not-allowed' : 'bg-yellow-500 hover:bg-yellow-600' ?> text-white font-medium rounded-lg transition"
<?= ($attendance && $attendance['check_in']) ? 'disabled' : '' ?>>
<?= ($attendance && $attendance['check_in']) ? 'Already Checked-in' : 'Request Leave' ?>
</button>
</div>

<!-- Modal Check-in -->
<div id="modalCheckIn" class="fixed inset-0 bg-white/30 backdrop-blur-sm flex items-center justify-center z-50 hidden">
<div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
<h2 class="text-lg font-bold mb-4">Check-in Confirmation</h2>
<form method="post">
<p class="text-sm text-gray-600 mb-3">
<strong>Morning:</strong> 00:00 - 11:59 | <strong>Afternoon:</strong> 12:00 - 23:59
</p>

<div class="mb-4">
<label class="block text-sm font-medium mb-1">Shift</label>
<select name="shift" required class="w-full border rounded-lg p-2" id="shiftSelect">
<option value="">-- Select Shift --</option>
<option value="Morning" data-hint="Morning shift: 00:00 - 11:59">Morning</option>
<option value="Afternoon" data-hint="Afternoon shift: 12:00 - 23:59">Afternoon</option>
<option value="WFO" data-hint="Whole Day at Office: work at office, present all day">WFO</option>
<option value="WAC" data-hint="Working at Client: on-site at client location">WAC</option>
<option value="WFH" data-hint="Working from Home: work remotely from home">WFH</option>
<option value="WFA" data-hint="Working from Anywhere: work remotely from any location">WFA</option>
</select>
<p id="shiftHint" class="mt-1 text-xs text-gray-500 italic">Please select your shift.</p>
</div>

<div class="mb-4">
<label class="block text-sm font-medium mb-1">Location</label>
<input type="text" name="location" placeholder="Office, WFH, Meeting" class="w-full border rounded-lg p-2" required>
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
<label class="block text-sm font-medium mb-1">Reason for Leave:</label>
<textarea name="notes" class="w-full border rounded-lg p-2 mb-4" rows="3" required></textarea>
<div class="flex justify-end gap-2">
<button type="button" id="closeModalLeave" class="px-4 py-2 bg-gray-300 rounded-lg">Cancel</button>
<button type="submit" name="submitLeave" class="px-4 py-2 bg-yellow-500 text-white rounded-lg">Submit</button>
</div>
</form>
</div>
</div>

<?php if ($attendance): ?>
<div class="grid grid-cols-1 md:grid-cols-5 gap-4 mt-6">
<div class="bg-gray-50 p-4 rounded-lg">
<p class="text-sm text-gray-500">Status</p>
<p class="font-medium <?= $attendance['status']==='Present' ? 'text-green-600' : ($attendance['status']==='Late' ? 'text-yellow-600' : 'text-red-600') ?>">
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
<p class="text-sm text-gray-500">Location</p>
<p class="font-medium"><?= htmlspecialchars($attendance['location'] ?? '-') ?></p>
</div>
</div>
<?php endif; ?>

<!-- Monthly Attendance -->
<div class="bg-white rounded-xl shadow-md overflow-hidden">
<div class="p-6 md:p-8">
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
<h2 class="text-xl font-bold text-gray-800">Monthly Recap</h2>
<form class="flex flex-col sm:flex-row items-center gap-2">
<input type="month" name="month" value="<?= htmlspecialchars($month) ?>" class="px-3 py-2 border w-full border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
<button type="submit" class="w-full sm:w-[69px] px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">Filter</button>
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
<td class="py-4 whitespace-nowrap text-sm text-gray-600"><?= htmlspecialchars($record['date']) ?></td>
<td class="py-4 whitespace-nowrap text-sm font-medium"><?= htmlspecialchars($record['shift'] ?? '-') ?></td>
<td class="py-4 whitespace-nowrap text-sm font-medium"><?= $record['check_in'] ?? '-' ?></td>
<td class="py-4 whitespace-nowrap text-sm font-medium"><?= $record['check_out'] ?? '-' ?></td>
<td class="py-4 whitespace-nowrap">
<span class="px-2.5 py-1 rounded-full text-xs font-medium 
<?= $record['status']==='Present' ? 'bg-green-100 text-green-800' : ($record['status']==='Late' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') ?>">
<?= htmlspecialchars($record['status']) ?>
</span>
</td>
<td class="py-4 whitespace-nowrap text-sm font-medium"><?= htmlspecialchars($record['location'] ?? '-') ?></td>
<td class="py-4 whitespace-nowrap text-sm text-gray-600"><?= htmlspecialchars($record['notes'] ?? '-') ?></td>
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

btnCheckIn?.addEventListener('click', () => {
  if (btnCheckIn.dataset.canCheckin==='true') modalCheckIn.classList.remove('hidden');
  else alert('You have already checked-in or requested leave today.');
});
closeModalCheckIn?.addEventListener('click', () => modalCheckIn.classList.add('hidden'));

btnLeave?.addEventListener('click', () => modalLeave.classList.remove('hidden'));
closeModalLeave?.addEventListener('click', () => modalLeave.classList.add('hidden'));

// Shift hint tooltip
const shiftSelect = document.getElementById('shiftSelect');
const shiftHint = document.getElementById('shiftHint');
shiftSelect?.addEventListener('change', function() {
  const selectedOption = this.options[this.selectedIndex];
  shiftHint.textContent = selectedOption.dataset.hint || 'Please select your shift.';
});
});
</script>
<?php include __DIR__ . '/footer.php'; ?>
