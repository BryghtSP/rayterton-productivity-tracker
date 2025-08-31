<?php
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth.php';
require_admin();

$today = date('Y-m-d');

// Konfigurasi pagination
$limit = 10;
$page = (int)($_GET['page'] ?? 1);
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Ambil user yang:
// - bukan admin (dari tabel users)
// - punya data di employees (untuk ambil position)
// - belum absen hari ini
$stmt = $pdo->prepare("
    SELECT 
        u.user_id,
        u.name,
        u.email,
        e.position
    FROM users u
    JOIN employees e ON u.user_id = e.user_id
    LEFT JOIN attendance a ON u.user_id = a.user_id AND a.date = ?
    WHERE u.role != 'admin'
      AND a.user_id IS NULL
    ORDER BY e.position, u.name
    LIMIT ? OFFSET ?
");
$stmt->bindValue(1, $today, PDO::PARAM_STR);
$stmt->bindValue(2, $limit, PDO::PARAM_INT);
$stmt->bindValue(3, $offset, PDO::PARAM_INT);
$stmt->execute();
$users_not_checked_in = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung total data untuk pagination
$totalStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM users u
    JOIN employees e ON u.user_id = e.user_id
    LEFT JOIN attendance a ON u.user_id = a.user_id AND a.date = ?
    WHERE u.role != 'admin'
      AND a.user_id IS NULL
");
$totalStmt->execute([$today]);
$total_not_checked_in = $totalStmt->fetchColumn();
$totalPages = max(1, ceil($total_not_checked_in / $limit));

// Hitung total karyawan (yang bukan admin dan ada di employees)
$totalEmpStmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM users u
    JOIN employees e ON u.user_id = e.user_id
    WHERE u.role != 'admin'
");
$totalEmpStmt->execute();
$total_employees = $totalEmpStmt->fetchColumn();

// Hitung yang sudah absen (hanya yang bukan admin)
$presentStmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM attendance a
    JOIN users u ON u.user_id = a.user_id
    JOIN employees e ON u.user_id = e.user_id
    WHERE a.date = ? 
      AND u.role != 'admin'
");
$presentStmt->execute([$today]);
$present_count = $presentStmt->fetchColumn();

$absent_count = $total_not_checked_in;

include __DIR__ . '/header.php';
?>

<div class="max-w-4xl mx-auto px-4 py-8">
  <div class="bg-white rounded-xl shadow-md overflow-hidden">
    <div class="p-6 md:p-8">
      <h1 class="text-2xl font-bold text-gray-800 mb-2">Today's Attendance Report</h1>
      <p class="text-sm text-gray-600 mb-6"><?= htmlspecialchars($today) ?></p>

      <!-- Statistics -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8 text-center">
        <div class="bg-blue-50 p-4 rounded-lg">
          <p class="font-semibold text-blue-800">Total Employees</p>
          <p class="text-2xl font-bold text-blue-700"><?= $total_employees ?></p>
        </div>
        <div class="bg-green-50 p-4 rounded-lg">
          <p class="font-semibold text-green-800">Present</p>
          <p class="text-2xl font-bold text-green-700"><?= $present_count ?></p>
        </div>
        <div class="bg-red-50 p-4 rounded-lg">
          <p class="font-semibold text-red-800">Not Checked In</p>
          <p class="text-2xl font-bold text-red-700"><?= $absent_count ?></p>
        </div>
      </div>

      <?php if (empty($users_not_checked_in)): ?>
        <div class="text-center py-6">
          <p class="text-green-700 font-medium">All employees have checked in today. 🎉</p>
        </div>
      <?php else: ?>
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Employees Not Checked In</h2>
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="text-left border-b border-gray-200">
                <th class="pb-3 font-medium text-gray-600">Name</th>
                <th class="pb-3 font-medium text-gray-600">Email</th>
                <th class="pb-3 font-medium text-gray-600">Position</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <?php foreach ($users_not_checked_in as $user): ?>
                <tr class="hover:bg-gray-50 transition">
                  <td class="py-4 text-sm font-medium text-gray-800">
                    <?= htmlspecialchars($user['name']) ?>
                  </td>
                  <td class="py-4 text-sm text-gray-600">
                    <?= htmlspecialchars($user['email']) ?>
                  </td>
                  <td class="py-4 text-sm">
                    <span class="px-2.5 py-1 bg-indigo-100 text-indigo-800 text-xs font-medium rounded-full">
                      <?= htmlspecialchars($user['position'] ?? 'Unknown') ?>
                    </span>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
          <div class="flex flex-col sm:flex-row justify-between items-center mt-6 gap-4">
            <div class="text-sm text-gray-600 whitespace-nowrap">
              Page <?= $page ?> of <?= $totalPages ?>
            </div>
            <nav class="flex flex-wrap justify-center gap-1">
              <!-- First Page Button -->
              <?php if ($page > 1): ?>
                <a href="?page=1"
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
                <a href="?page=<?= $page - 1 ?>"
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

              <!-- Page Numbers - Hidden on mobile if many pages -->
              <div class="hidden xs:flex gap-1">
                <?php 
                // Determine page range to display
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $startPage + 4);
                
                // Adjust if at the end
                if ($endPage - $startPage < 4) {
                    $startPage = max(1, $endPage - 4);
                }
                
                for ($i = $startPage; $i <= $endPage; $i++): 
                ?>
                  <a href="?page=<?= $i ?>"
                     class="<?= $i === $page ? 'bg-indigo-600 text-white' : 'bg-white text-indigo-600 hover:bg-indigo-50' ?>
                      px-3 py-2 border border-gray-300 rounded text-sm font-medium transition">
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
                <a href="?page=<?= $page + 1 ?>"
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
                <a href="?page=<?= $totalPages ?>"
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
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>