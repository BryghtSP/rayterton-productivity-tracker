<?php
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth.php';
require_login();

$user_id = $_SESSION['user']['user_id'];

// Ambil employee_id dari users -> employees
$stmt = $pdo->prepare("SELECT employee_id FROM employees WHERE user_id = ?");
$stmt->execute([$user_id]);
$employee = $stmt->fetch();

if (!$employee) {
  die("Data karyawan tidak ditemukan.");
}

$employee_id = $employee['employee_id'];

// Ambil semua work_force yang terhubung dengan employee ini
$stmt = $pdo->prepare("
    SELECT wf.workforce_id, wf.workforce_name
    FROM work_force wf
    JOIN employees_workforce ew ON wf.workforce_id = ew.workforce_id
    WHERE ew.employee_id = ?
    ORDER BY wf.workforce_name
");
$stmt->execute([$employee_id]);
$work_forces = $stmt->fetchAll(PDO::FETCH_ASSOC);

//colong table job_type
$stmt = $pdo->query("SELECT job_type_id, name FROM job_type ORDER BY name");
$job_types = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/header.php';
?>

<div class="max-w-4xl mx-auto px-4 py-8">
  <div class="bg-white rounded-xl shadow-md overflow-hidden">
    <div class="p-6 md:p-8">
      <h1 class="text-2xl font-bold text-gray-800 mb-6">Daily Report Input</h1>

      <form action="save_report.php" method="POST" enctype="multipart/form-data" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Date Input -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
            <input type="date" name="report_date" value="<?php echo date('Y-m-d'); ?>"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
              required>
          </div>

          <!-- Job Type Select -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Job Type</label>
            <select name="job_type"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
              required>
              <option value="">-- Pilih Job Type --</option>
              <?php foreach ($job_types as $jt): ?>
                <option value="<?= $jt['job_type_id'] ?>">
                  <?= htmlspecialchars($jt['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <?php if (empty($job_types)): ?>
              <p class="text-red-500 text-sm mt-1">Belum ada job type yang terdaftar.</p>
            <?php endif; ?>
          </div>

          <!-- Title Input -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Judul/Menu/Layar</label>
            <input type="text" name="title"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
              required>
          </div>

          <!-- Work Force Dropdown -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Work Force</label>
            <select name="workforce_id"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
              required>
              <option value="">-- Pilih Work Force --</option>
              <?php foreach ($work_forces as $wf): ?>
                <option value="<?= $wf['workforce_id'] ?>">
                  <?= htmlspecialchars($wf['workforce_name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <?php if (empty($work_forces)): ?>
              <p class="text-red-500 text-sm mt-1">Anda belum terdaftar di work force manapun.</p>
            <?php endif; ?>
          </div>

          <!-- Description Textarea -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea name="description" rows="4"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"></textarea>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Status Select -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
              <select name="status"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                <option value="Progress">Progress</option>
                <option value="Selesai">Selesai</option>
              </select>
            </div>

            <!-- Proof Link Input -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Bukti (URL repo/screenshot)</label>
              <input type="url" name="proof_link" placeholder="https://..."
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
            </div>
          </div>
          <!-- Proof Image Upload -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Bukti (Foto)</label>
            <input type="file" name="proof_image" accept="image/*"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
            <p class="text-xs text-gray-500 mt-1">Upload gambar (jpg, png, jpeg)</p>
          </div>
          <!-- Submit Button -->
          <div class="pt-4">
            <button type="submit"
              class="w-full md:w-auto px-6 py-3 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition">
              Save Report
            </button>
          </div>
      </form>

      <p class="mt-6 text-sm text-gray-500">
        Policy: Minimum 2 items per day, monthly target 50–88 items.
      </p>
    </div>
  </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>