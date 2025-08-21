<?php
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth.php';
require_admin();

$month = $_GET['month'] ?? date('Y-m');
$start = $month . "-01";
$end = date('Y-m-t', strtotime($start));

// all reports
$stmt = $pdo->prepare("
  SELECT pr.*, u.name
  FROM production_reports pr
  JOIN users u ON u.user_id = pr.user_id
  WHERE pr.report_date BETWEEN ? AND ?
  ORDER BY pr.report_date DESC, pr.report_id DESC
");
$stmt->execute([$start, $end]);
$rows = $stmt->fetchAll();

// daily shortfall: users with <2 entries today
$today = date('Y-m-d');
$short = $pdo->prepare("
  SELECT u.user_id, u.name, u.email, COUNT(pr.report_id) as c
  FROM users u
  LEFT JOIN production_reports pr ON pr.user_id = u.user_id AND pr.report_date = ?
  WHERE u.is_active = 1
  GROUP BY u.user_id
  HAVING c < 2
  ORDER BY u.name
");
$short->execute([$today]);
$shortRows = $short->fetchAll();

include __DIR__ . '/header.php';
?>

<div class="max-w-7xl mx-auto px-4 py-8 space-y-8">
  <!-- All Reports Card -->
  <div class="bg-white rounded-xl shadow-md overflow-hidden">
    <div class="p-6 md:p-8">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <h1 class="text-2xl font-bold text-gray-800">All Reports</h1>
        <form class="flex flex-col sm:flex-row items-center gap-2">
          <input type="month" name="month" value="<?php echo htmlspecialchars($month) ?>" 
                 class="px-3 py-2 w-full border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
          <button type="submit" class="px-4 py-2 w-full md:w-[68px] bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
            Filter
          </button>
        </form>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="text-left border-b border-gray-200">
              <th class="pb-3 font-medium text-gray-600">Date</th>
              <th class="pb-3 font-medium text-gray-600">Name</th>
              <th class="pb-3 font-medium text-gray-600">Type</th>
              <th class="pb-3 font-medium text-gray-600">Title</th>
              <th class="pb-3 font-medium text-gray-600">Status</th>
              <th class="pb-3 font-medium text-gray-600">Bukti</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <?php foreach($rows as $r): ?>
            <tr class="hover:bg-gray-50 transition">
              <td class="py-4 whitespace-nowrap text-sm text-gray-600">
                <?php echo htmlspecialchars($r['report_date']) ?>
              </td>
              <td class="py-4 whitespace-nowrap text-sm font-medium text-gray-800">
                <?php echo htmlspecialchars($r['name']) ?>
              </td>
              <td class="py-4 whitespace-nowrap text-sm text-gray-800">
                <?php echo htmlspecialchars($r['job_type']) ?>
              </td>
              <td class="py-4 text-sm text-gray-800">
                <?php echo htmlspecialchars($r['title']) ?>
              </td>
              <td class="py-4 whitespace-nowrap">
                <span class="px-2.5 py-1 rounded-full text-xs font-medium <?php echo $r['status']==='Selesai' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                  <?php echo htmlspecialchars($r['status']) ?>
                </span>
              </td>
              <td class="py-4 whitespace-nowrap">
              <?php if($r['proof_link'] || $r['proof_image']): ?>
                <button onclick="openModal(<?php echo $r['report_id'] ?>)" 
                        class="px-3 py-1 bg-indigo-100 text-indigo-700 rounded hover:bg-indigo-200 text-sm transition">
                  Detail
                </button>
              <?php else: ?>
                <span class="text-gray-400 text-sm">-</span>
              <?php endif; ?>
            </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Shortfall Card -->
  <div class="bg-white rounded-xl shadow-md overflow-hidden">
    <div class="p-6 md:p-8">
      <h2 class="text-base sm:text-xl font-bold text-gray-800 mb-6">Employees Less Than 2 Entries Today (<?php echo htmlspecialchars($today) ?>)</h2>
      
      <?php if(!$shortRows): ?>
        <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-center">
          <p class="text-green-800 font-medium">All staff have met the daily target!</p>
        </div>
      <?php else: ?>
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="text-left border-b border-gray-200">
                <th class="pb-3 font-medium text-gray-600">Name</th>
                <th class="pb-3 font-medium text-gray-600">Email</th>
                <th class="pb-3 font-medium text-gray-600">Amount</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <?php foreach($shortRows as $s): ?>
              <tr class="hover:bg-gray-50 transition">
                <td class="py-4 whitespace-nowrap text-sm font-medium text-gray-800">
                  <?php echo htmlspecialchars($s['name']) ?>
                </td>
                <td class="py-4 whitespace-nowrap text-sm text-gray-600">
                  <?php echo htmlspecialchars($s['email']) ?>
                </td>
                <td class="py-4 whitespace-nowrap text-sm font-medium <?php echo (int)$s['c'] === 0 ? 'text-red-600' : 'text-yellow-600' ?>">
                  <?php echo (int)$s['c'] ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>


<!-- Modal Popup -->
<div id="reportModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
  <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
    <div class="p-6">
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-xl font-bold text-gray-800">Detail Laporan</h3>
        <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700">&times;</button>
      </div>
      <div id="modalContent"></div>
    </div>
  </div>
</div>

<script>
function openModal(reportId) {
  fetch(`get_report_detail.php?id=${reportId}`)
    .then(response => response.text())
    .then(data => {
      document.getElementById('modalContent').innerHTML = data;
      document.getElementById('reportModal').classList.remove('hidden');
      document.body.style.overflow = 'hidden'; // Prevent scrolling
    });
}

function closeModal() {
  document.getElementById('reportModal').classList.add('hidden');
  document.body.style.overflow = 'auto';
}
</script>

<?php include __DIR__ . '/footer.php'; ?>