<?php
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth.php';
require_login();

$month = $_GET['month'] ?? date('Y-m');
$start = $month . "-01";
$end = date('Y-m-t', strtotime($start));

// totals per user
$stmt = $pdo->prepare("
  SELECT u.user_id, u.name, u.role,
         COALESCE(SUM(pr.report_date BETWEEN ? AND ?), 0) as dummy,
         COUNT(pr.report_id) as total
  FROM users u
  LEFT JOIN production_reports pr ON pr.user_id = u.user_id AND pr.report_date BETWEEN ? AND ?
  WHERE u.is_active = 1
  GROUP BY u.user_id
  ORDER BY total DESC, u.name
");
$stmt->execute([$start, $end, $start, $end]);
$users = $stmt->fetchAll();

// type distribution
$types = $pdo->prepare("SELECT job_type, COUNT(*) c FROM production_reports WHERE report_date BETWEEN ? AND ? GROUP BY job_type ORDER BY c DESC");
$types->execute([$start, $end]);
$typeRows = $types->fetchAll();


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Production Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .progress-bar {
            height: 8px;
            border-radius: 4px;
        }

        .progress-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.4s ease;
        }
    </style>
</head>

<body class="bg-gray-50">

    <?php include __DIR__ . '/header.php';
    ?>
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <header class="mb-8">
            <h1 class="text-xl sm:text-2xl md:text-3xl sm:text-start text-center font-bold text-gray-800">Production Dashboard</h1>
            <div class="flex sm:flex-row flex-col items-center justify-between md:mt-4 mt-0 gap-2 md:gap-0">
                <span class="md:text-xl text-lg font-semibold text-indigo-600"><?php echo htmlspecialchars($month) ?></span>
                <form class="flex gap-2">
                    <input type="month" name="month" value="<?php echo htmlspecialchars($month) ?>"
                        class="md:w-full w-[178px] px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                        Filter
                    </button>
                </form>
            </div>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- User Performance Card -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <div class="p-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Employee Performance</h2>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="text-left border-b border-gray-200">
                                    <th class="pb-3 font-medium text-gray-600">Name</th>
                                    <th class="pb-3 font-medium text-gray-600">Total</th>
                                    <th class="pb-3 font-medium text-gray-600">Progress</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php foreach ($users as $u):
                                    $pct = $u['total'] >= 88 ? 100 : round(($u['total'] / 88) * 100);
                                    $colorClass = $u['total'] >= 50 ? 'bg-green-500' : ($u['total'] >= 30 ? 'bg-yellow-500' : 'bg-red-500');
                                ?>
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="py-4">
                                            <div class="flex items-center">
                                                <span class="font-medium text-gray-800"><?php echo htmlspecialchars($u['name']) ?></span>
                                                <?php if ($u['role'] === 'admin'): ?>
                                                    <span class="ml-2 px-2 py-1 text-xs bg-indigo-100 text-indigo-800 rounded-full">Admin</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="py-4 font-medium"><?php echo (int)$u['total'] ?></td>
                                        <td class="py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-full bg-gray-200 rounded-full h-2.5">
                                                    <div class="h-2.5 rounded-full <?php echo $colorClass ?>"
                                                        style="width: <?php echo $pct ?>%"></div>
                                                </div>
                                                <span class="text-sm font-medium text-gray-600"><?php echo $pct ?>%</span>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Job Distribution Card -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <div class="p-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Job Type Distribution</h2>
                    <div class="h-64">
                        <canvas id="pie"></canvas>
                    </div>
                    <p class="mt-4 text-sm text-gray-500">Rule: Minimum 2 items per day per staff.</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Pie Chart
        const pieLabels = <?php echo json_encode(array_column($typeRows, 'job_type')); ?>;
        const pieData = <?php echo json_encode(array_map('intval', array_column($typeRows, 'c'))); ?>;

        const pieColors = [
            'rgba(99, 102, 241, 0.7)',
            'rgba(59, 130, 246, 0.7)',
            'rgba(16, 185, 129, 0.7)',
            'rgba(245, 158, 11, 0.7)',
            'rgba(244, 63, 94, 0.7)'
        ];

        new Chart(document.getElementById('pie'), {
            type: 'pie',
            data: {
                labels: pieLabels,
                datasets: [{
                    data: pieData,
                    backgroundColor: pieColors,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                    }
                }
            }
        });
    </script>
</body>

</html>
<?php include __DIR__ . '/footer.php'; ?>