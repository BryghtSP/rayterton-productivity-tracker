<?php
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth.php';
require_admin();

$month = $_GET['month'] ?? date('Y-m');
$start = $month . "-01";
$end = date('Y-m-t', strtotime($start));

// === Pagination utk All Reports ===
$limit = 5;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// total data reports
$countStmt = $pdo->prepare("
  SELECT COUNT(*) 
  FROM production_reports pr
  JOIN users u ON u.user_id = pr.user_id
  LEFT JOIN work_force wf ON wf.workforce_id = pr.workforce_id
  WHERE pr.report_date BETWEEN ? AND ?
");
$countStmt->execute([$start, $end]);
$totalReports = (int)$countStmt->fetchColumn();
$totalPages = ceil($totalReports / $limit);

// Hitung startPage dan endPage untuk pagination
$startPage = max(1, $page - 2);
$endPage = min($totalPages, $startPage + 4);
if ($endPage - $startPage < 4) {
    $startPage = max(1, $endPage - 4);
}

// ambil data reports sesuai halaman
$stmt = $pdo->prepare("
  SELECT pr.*, u.name, wf.workforce_name
  FROM production_reports pr
  JOIN users u ON u.user_id = pr.user_id
  LEFT JOIN work_force wf ON wf.workforce_id = pr.workforce_id
  WHERE pr.report_date BETWEEN ? AND ?
  ORDER BY pr.report_date DESC, pr.report_id DESC
  LIMIT $limit OFFSET $offset
");
$stmt->execute([$start, $end]);
$rows = $stmt->fetchAll();

// === Pagination utk Shortfall ===
$today = date('Y-m-d');
$shortLimit = 5;
$shortPage = isset($_GET['short_page']) ? max(1, (int)$_GET['short_page']) : 1;
$shortOffset = ($shortPage - 1) * $shortLimit;

// total shortfall
$countShort = $pdo->prepare("
  SELECT COUNT(*) FROM (
    SELECT u.user_id
    FROM users u
    LEFT JOIN production_reports pr 
      ON pr.user_id = u.user_id AND pr.report_date = ?
    WHERE u.is_active = 1
    GROUP BY u.user_id
    HAVING COUNT(pr.report_id) < 2
  ) as t
");
$countShort->execute([$today]);
$totalShort = (int)$countShort->fetchColumn();
$totalShortPages = ceil($totalShort / $shortLimit);

// Hitung startPage dan endPage untuk shortfall pagination
$shortStartPage = max(1, $shortPage - 2);
$shortEndPage = min($totalShortPages, $shortStartPage + 4);
if ($shortEndPage - $shortStartPage < 4) {
    $shortStartPage = max(1, $shortEndPage - 4);
}

// ambil data shortfall sesuai halaman
$short = $pdo->prepare("
  SELECT u.user_id, u.name, u.email, COUNT(pr.report_id) as c
  FROM users u
  LEFT JOIN production_reports pr 
    ON pr.user_id = u.user_id AND pr.report_date = ?
  WHERE u.is_active = 1
  GROUP BY u.user_id
  HAVING c < 2
  ORDER BY u.name
  LIMIT $shortLimit OFFSET $shortOffset
");
$short->execute([$today]);
$shortRows = $short->fetchAll();

include __DIR__ . '/header.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Rayterton Prodtracker - admin Reports</title>
  <link rel="stylesheet" href="css/output.css">
</head>

<body>
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
                <th class="pb-3 font-medium text-gray-600">Work Force</th>
                <th class="pb-3 font-medium text-gray-600">Status</th>
                <th class="pb-3 font-medium text-gray-600">Proof</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <?php foreach ($rows as $r): ?>
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
                  <td class="py-4 text-sm text-gray-800">
                    <?php echo htmlspecialchars($r['workforce_name']) ?>
                  </td>
                  <td class="py-4 whitespace-nowrap">
                    <span class="px-2.5 py-1 rounded-full text-xs font-medium <?php echo $r['status'] === 'Selesai' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                      <?php echo htmlspecialchars($r['status']) ?>
                    </span>
                  </td>
                  <td class="py-4 whitespace-nowrap">
                    <?php if ($r): ?>
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

        <!-- Pagination All Reports -->
        <?php if ($totalPages > 1): ?>
          <div class="flex flex-col sm:flex-row justify-between items-center mt-6 gap-4">
            <div class="text-sm text-gray-600 whitespace-nowrap">
              Page <?= $page ?> of <?= $totalPages ?>
            </div>
            <nav class="flex flex-wrap justify-center gap-1">
              <!-- First Page Button -->
              <?php if ($page > 1): ?>
                <a href="?month=<?= htmlspecialchars($month) ?>&page=1&short_page=<?= $shortPage ?>"
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
              <?php if ($page > 1): ?>
                <a href="?month=<?= htmlspecialchars($month) ?>&page=<?= $page - 1 ?>&short_page=<?= $shortPage ?>"
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
                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                  <a href="?month=<?= htmlspecialchars($month) ?>&page=<?= $i ?>&short_page=<?= $shortPage ?>"
                    class="<?= $i === $page ? 'bg-indigo-600 text-white' : 'bg-white text-indigo-600 hover:bg-indigo-50' ?> px-3 py-2 border border-gray-300 rounded text-sm font-medium transition">
                    <?= $i ?>
                  </a>
                <?php endfor; ?>
              </div>

              <!-- Page indicator for mobile -->
              <div class="xs:hidden px-3 py-2 bg-indigo-600 text-white border border-gray-300 rounded text-sm font-medium">
                <?= $page ?>
              </div>

              <!-- Next Button -->
              <?php if ($page < $totalPages): ?>
                <a href="?month=<?= htmlspecialchars($month) ?>&page=<?= $page + 1 ?>&short_page=<?= $shortPage ?>"
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
              <?php if ($page < $totalPages): ?>
                <a href="?month=<?= htmlspecialchars($month) ?>&page=<?= $totalPages ?>&short_page=<?= $shortPage ?>"
                  class="px-2 py-2 sm:px-3 bg-white text-indigo-600 border border-gray-300 rounded hover:bg-gray-50 text-sm font-medium transition whitespace-nowrap">
                  <span class="hidden sm:inline">Last &gt;&gt;</span>
                  <span class="sm:hidden">Last</span>
                </a>
              <?php else: ?>
                <span class="px-2 py-2 sm:px-3 bg-gray-100 text-gray-400 border border-gray-300 rounded text-sm font-medium cursor-not-allowed whitespace-nowrap">
                  <span class="hidden sm:inline">Last &gt;&lt;</span>
                  <span class="sm:hidden">Last</span>
                </span>
              <?php endif; ?>
            </nav>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Shortfall Card -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
      <div class="p-6 md:p-8">
        <h2 class="text-base sm:text-xl font-bold text-gray-800 mb-6">
          Employees Less Than 2 Entries Today (<?php echo htmlspecialchars($today) ?>)
        </h2>

        <?php if (!$shortRows): ?>
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
                <?php foreach ($shortRows as $s): ?>
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

          <!-- Pagination Shortfall -->
          <?php if ($totalShortPages > 1): ?>
            <div class="flex flex-col sm:flex-row justify-between items-center mt-6 gap-4">
              <div class="text-sm text-gray-600 whitespace-nowrap">
                Page <?= $shortPage ?> of <?= $totalShortPages ?>
              </div>
              <nav class="flex flex-wrap justify-center gap-1">
                <!-- First Page Button -->
                <?php if ($shortPage > 1): ?>
                  <a href="?month=<?= htmlspecialchars($month) ?>&page=<?= $page ?>&short_page=1"
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
                <?php if ($shortPage > 1): ?>
                  <a href="?month=<?= htmlspecialchars($month) ?>&page=<?= $page ?>&short_page=<?= $shortPage - 1 ?>"
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
                  <?php for ($i = $shortStartPage; $i <= $shortEndPage; $i++): ?>
                    <a href="?month=<?= htmlspecialchars($month) ?>&page=<?= $page ?>&short_page=<?= $i ?>"
                      class="<?= $i === $shortPage ? 'bg-indigo-600 text-white' : 'bg-white text-indigo-600 hover:bg-indigo-50' ?> px-3 py-2 border border-gray-300 rounded text-sm font-medium transition">
                      <?= $i ?>
                    </a>
                  <?php endfor; ?>
                </div>

                <!-- Page indicator for mobile -->
                <div class="xs:hidden px-3 py-2 bg-indigo-600 text-white border border-gray-300 rounded text-sm font-medium">
                  <?= $shortPage ?>
                </div>

                <!-- Next Button -->
                <?php if ($shortPage < $totalShortPages): ?>
                  <a href="?month=<?= htmlspecialchars($month) ?>&page=<?= $page ?>&short_page=<?= $shortPage + 1 ?>"
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
                <?php if ($shortPage < $totalShortPages): ?>
                  <a href="?month=<?= htmlspecialchars($month) ?>&page=<?= $page ?>&short_page=<?= $totalShortPages ?>"
                    class="px-2 py-2 sm:px-3 bg-white text-indigo-600 border border-gray-300 rounded hover:bg-gray-50 text-sm font-medium transition whitespace-nowrap">
                    <span class="hidden sm:inline">Last &gt;&gt;</span>
                    <span class="sm:hidden">Last</span>
                  </a>
                <?php else: ?>
                  <span class="px-2 py-2 sm:px-3 bg-gray-100 text-gray-400 border border-gray-300 rounded text-sm font-medium cursor-not-allowed whitespace-nowrap">
                    <span class="hidden sm:inline">Last &gt;&lt;</span>
                    <span class="sm:hidden">Last</span>
                  </span>
                <?php endif; ?>
              </nav>
            </div>
          <?php endif; ?>
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
</body>

</html>

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