<?php
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth.php';
require_login();

$user_id = $_SESSION['user']['user_id'];
$month = $_GET['month'] ?? date('Y-m');
$start = $month . "-01";
$end = date('Y-m-t', strtotime($start));

// get list
$stmt = $pdo->prepare("SELECT 
        pr.*,
        wf.workforce_name
    FROM production_reports pr
    LEFT JOIN work_force wf ON wf.workforce_id = pr.workforce_id
    WHERE pr.user_id = ? 
      AND pr.report_date BETWEEN ? AND ?
    ORDER BY pr.report_date DESC, pr.report_id DESC");
$stmt->execute([$user_id, $start, $end]);
$rows = $stmt->fetchAll();

// counts
$stmt2 = $pdo->prepare("SELECT DATE(report_date) d, COUNT(*) c 
                        FROM production_reports 
                        WHERE user_id = ? AND report_date BETWEEN ? AND ? 
                        GROUP BY DATE(report_date)");
$stmt2->execute([$user_id, $start, $end]);
$daily = $stmt2->fetchAll();

$total = 0;
$labels = [];
$data = [];
foreach ($daily as $r) {
  $labels[] = $r['d'];
  $data[] = (int)$r['c'];
  $total += (int)$r['c'];
}

include __DIR__ . '/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Rayterton Prodtracker - Report My</title>
  <link rel="stylesheet" href="css/output.css">
</head>
<body>
  <div class="max-w-7xl mx-auto px-4 py-8 space-y-8">
  <!-- Summary Card -->
  <div class="bg-white rounded-xl shadow-md overflow-hidden">
    <div class="p-6 md:p-8">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
          <h1 class="text-lg sm:text-xl md:text-2xl font-bold text-gray-800">My Reports</h1>
          <div class="flex items-center gap-4 mt-2">
            <span class="text-base md:text-lg font-semibold text-indigo-600"><?php echo htmlspecialchars($month) ?></span>
            <span class="px-3 py-1 bg-indigo-100 text-indigo-800 text-sm font-medium rounded-full">
              Total: <?php echo (int)$total ?> item
            </span>
          </div>
        </div>
        <form class="flex flex-col sm:flex-row items-center gap-2">
          <input type="month" name="month" value="<?php echo htmlspecialchars($month) ?>"
            class="px-3 py-2 w-full border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
          <button type="submit" class="px-4 py-2 w-full md:w-[69px] bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
            Filter
          </button>
        </form>
      </div>

      <div class="bg-indigo-50 p-4 rounded-lg mb-6">
        <p class="text-sm text-indigo-800">
          <span class="font-medium">Monthly Target:</span> 50–88 items (minimum 2 items per day)
        </p>
      </div>

      <div class="h-64">
        <canvas id="chart"></canvas>
      </div>
    </div>
  </div>

  <!-- Reports Table -->
  <div class="bg-white rounded-xl shadow-md overflow-hidden">
    <div class="p-6 md:p-8">
      <h2 class="text-xl font-bold text-gray-800 mb-6">Entry Details</h2>

      <div class="overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="text-left border-b border-gray-200">
              <th class="pb-3 font-medium text-gray-600">Date</th>
              <th class="pb-3 font-medium text-gray-600">Type</th>
              <th class="pb-3 font-medium text-gray-600">Title</th>
              <th class="pb-3 font-medium text-gray-600">Work Force</th>
              <th class="pb-3 font-medium text-gray-600">Status</th>
              <th class="pb-3 font-medium text-gray-600">Proof</th>
              <th class="pb-3 font-medium text-gray-600">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <?php foreach ($rows as $r): ?>
              <tr class="hover:bg-gray-50 transition" id="row-<?php echo $r['report_id']; ?>">
                <td class="py-4 whitespace-nowrap text-sm text-gray-600">
                  <?php echo htmlspecialchars($r['report_date']) ?>
                </td>
                <td class="py-4 whitespace-nowrap text-sm font-medium text-gray-800">
                  <?php echo htmlspecialchars($r['job_type']) ?>
                </td>
                <td class="py-4 whitespace-nowrap text-sm text-gray-800">
                  <?php echo htmlspecialchars($r['title']) ?>
                </td>
                <td class="py-4 whitespace-nowrap text-[10px] sm:text-sm font-medium text-gray-800">
                  <?php echo htmlspecialchars($r['workforce_name'] ?? '-'); ?>
                </td>
                <td class="py-4 whitespace-nowrap">
                  <span
                    class="status-badge px-2.5 py-1 rounded-full text-xs font-medium 
      <?php echo $r['status'] === 'Selesai' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>"
                    id="status-<?php echo $r['report_id']; ?>">
                    <?php echo htmlspecialchars($r['status']); ?>
                  </span>
                </td>

                <td class="py-4 whitespace-nowrap">
                  <?php if ($r['proof_link']): ?>
                    <a href="<?php echo htmlspecialchars($r['proof_link']) ?>" target="_blank"
                      class="text-indigo-600 hover:text-indigo-800 text-sm font-medium hover:underline transition">
                      Lihat
                    </a>
                  <?php elseif ($r['proof_image']): ?>
                    <button onclick="openModal(<?php echo $r['report_id'] ?>)"
                      class="px-3 py-1 bg-green-100 text-green-700 rounded hover:bg-green-200 text-sm transition">
                      See Picture
                    </button>
                  <?php else: ?>
                    <span class="text-gray-400 text-sm">-</span>
                  <?php endif; ?>
                </td>

                <td class="py-4 whitespace-nowrap space-x-1">
                  <?php if ($r['status'] === 'Progress'): ?>
                    <button
                      onclick="markAsDone(<?php echo $r['report_id']; ?>, this)"
                      class="px-3 py-1 bg-blue-600 text-white text-sm rounded hover:bg-blue-700 transition">
                      Tandai Selesai
                    </button>
                  <?php else: ?>
                    <span class="text-gray-400 text-sm">Selesai</span>
                  <?php endif; ?>

                  <button
                    onclick="deleteReport(<?php echo $r['report_id']; ?>, this)"
                    class="px-3 py-1 bg-red-600 text-white text-sm rounded hover:bg-red-700 transition"
                    title="Hapus laporan">
                    Hapus
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
</body>
</html>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  // Chart.js
  const labels = <?php echo json_encode($labels); ?>;
  const data = <?php echo json_encode($data); ?>;
  new Chart(document.getElementById('chart'), {
    type: 'bar',
    data: {
      labels,
      datasets: [{
        label: 'Item per Hari',
        data,
        backgroundColor: 'rgba(79, 70, 229, 0.7)',
        borderColor: 'rgba(79, 70, 229, 1)',
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
      plugins: { legend: { display: false } }
    }
  });

  // Tandai selesai
  function markAsDone(reportId, btn) {
    Swal.fire({
      title: 'Yakin?',
      text: "Ingin menandai laporan ini sebagai selesai?",
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Ya, tandai!',
      cancelButtonText: 'Batal'
    }).then((res) => {
      if (res.isConfirmed) {
        const originalText = btn.textContent;
        btn.textContent = 'Processing...';
        btn.disabled = true;

        fetch('update_status_ajax.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded',
              'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: 'report_id=' + reportId + '&action=mark_done'
          })
          .then(r => r.json())
          .then(d => {
            if (d.success) {
              const statusEl = document.getElementById('status-' + reportId);
              statusEl.textContent = 'Selesai';
              statusEl.className = 'status-badge px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800';
              btn.textContent = 'Selesai';
              btn.classList.remove('bg-blue-600', 'hover:bg-blue-700');
              btn.classList.add('bg-gray-400', 'text-gray-700', 'cursor-not-allowed');
              Swal.fire('Berhasil!', 'Status laporan diperbarui.', 'success');
            } else {
              Swal.fire('Gagal!', d.message, 'error');
              btn.textContent = originalText;
              btn.disabled = false;
            }
          })
          .catch(e => {
            console.error(e);
            Swal.fire('Error', 'Terjadi kesalahan koneksi.', 'error');
            btn.textContent = originalText;
            btn.disabled = false;
          });
      }
    });
  }

  // Hapus laporan
  function deleteReport(reportId, btn) {
    Swal.fire({
      title: 'Yakin hapus?',
      text: "Laporan ini akan dihapus permanen!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Ya, hapus!',
      cancelButtonText: 'Batal'
    }).then((res) => {
      if (res.isConfirmed) {
        const row = document.getElementById('row-' + reportId);
        const originalText = btn.textContent;
        btn.textContent = 'Menghapus...';
        btn.disabled = true;

        fetch('delete_report_ajax.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded',
              'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: 'report_id=' + reportId
          })
          .then(r => r.json())
          .then(d => {
            if (d.success) {
              row.classList.add('bg-red-50', 'animate-pulse');
              setTimeout(() => {
                row.remove();
                Swal.fire('Dihapus!', 'Laporan berhasil dihapus.', 'success');
              }, 300);
            } else {
              Swal.fire('Gagal!', d.message, 'error');
              btn.textContent = originalText;
              btn.disabled = false;
            }
          })
          .catch(e => {
            console.error(e);
            Swal.fire('Error', 'Terjadi kesalahan koneksi.', 'error');
            btn.textContent = originalText;
            btn.disabled = false;
          });
      }
    });
  }
</script>

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
    fetch(`get_report_detail_user.php?id=${reportId}`, { credentials: 'same-origin' })
      .then(r => r.text())
      .then(d => {
        document.getElementById('modalContent').innerHTML = d;
        document.getElementById('reportModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
      })
      .catch(err => {
        console.error(err);
        document.getElementById('modalContent').innerHTML = '<p class="text-red-600">Terjadi kesalahan saat memuat data</p>';
      });
  }
  function closeModal() {
    document.getElementById('reportModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
  }
</script>

<?php include __DIR__ . '/footer.php'; ?>
