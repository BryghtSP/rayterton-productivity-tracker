<?php
// Pastikan path ke lib sesuai struktur folder Anda
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth.php';
require_admin();

$success = '';
$error = '';

// Konfigurasi pagination
$limit = 10;
$page = (int)($_GET['page'] ?? 1);
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Ambil parameter search
$search = trim($_GET['search'] ?? '');

// Handle: Tambah atau Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $employee_id = (int)$_POST['employee_id'];
    $workforce_id = (int)$_POST['workforce_id'];

    if (!$employee_id || !$workforce_id) {
        $error = "Pilih Employee dan Work Force.";
    } else {
        if ($action === 'add') {
            // Cek duplikat
            $check = $pdo->prepare("SELECT 1 FROM employees_workforce WHERE employee_id = ? AND workforce_id = ?");
            $check->execute([$employee_id, $workforce_id]);
            if ($check->fetch()) {
                $error = "Relasi ini sudah ada.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO employees_workforce (employee_id, workforce_id) VALUES (?, ?)");
                try {
                    $stmt->execute([$employee_id, $workforce_id]);
                    $success = "Relasi berhasil ditambahkan.";
                } catch (PDOException $e) {
                    $error = "Gagal menyimpan relasi.";
                }
            }
        } elseif ($action === 'edit') {
            $old_employee_id = (int)$_POST['old_employee_id'];
            $old_workforce_id = (int)$_POST['old_workforce_id'];

            // Cek apakah relasi baru sudah ada (kecuali relasi lama)
            $check = $pdo->prepare("SELECT 1 FROM employees_workforce WHERE employee_id = ? AND workforce_id = ? AND (employee_id != ? OR workforce_id != ?)");
            $check->execute([$employee_id, $workforce_id, $old_employee_id, $old_workforce_id]);
            if ($check->fetch()) {
                $error = "Relasi ini sudah ada.";
            } else {
                $stmt = $pdo->prepare("UPDATE employees_workforce SET employee_id = ?, workforce_id = ? WHERE employee_id = ? AND workforce_id = ?");
                try {
                    $stmt->execute([$employee_id, $workforce_id, $old_employee_id, $old_workforce_id]);
                    if ($stmt->rowCount()) {
                        $success = "Relasi berhasil diperbarui.";
                    } else {
                        $error = "Relasi tidak ditemukan.";
                    }
                } catch (PDOException $e) {
                    $error = "Gagal memperbarui relasi.";
                }
            }
        }
    }
}

// Handle: Hapus
if (isset($_GET['delete_emp']) && isset($_GET['delete_wf'])) {
    $employee_id = (int)$_GET['delete_emp'];
    $workforce_id = (int)$_GET['delete_wf'];

    $stmt = $pdo->prepare("DELETE FROM employees_workforce WHERE employee_id = ? AND workforce_id = ?");
    $stmt->execute([$employee_id, $workforce_id]);

    if ($stmt->rowCount()) {
        $success = "Relasi berhasil dihapus.";
    } else {
        $error = "Relasi tidak ditemukan.";
    }

    // Redirect agar parameter delete tidak tertinggal
    header("Location: admin_employees_workforce.php?search=" . urlencode($search) . "&page=$page");
    exit;
}

// Siapkan parameter pencarian
$searchParam = "%$search%";

// Hitung total data untuk pagination
$totalStmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM employees_workforce ew
    JOIN employees e ON e.employee_id = ew.employee_id
    JOIN work_force wf ON wf.workforce_id = ew.workforce_id
    WHERE e.name LIKE ? OR wf.workforce_name LIKE ?
");
$totalStmt->execute([$searchParam, $searchParam]);
$total = (int)$totalStmt->fetchColumn();
$totalPages = max(1, ceil($total / $limit));

// Ambil data relasi dengan pagination dan search
$stmt = $pdo->prepare("
    SELECT 
        ew.employee_id,
        ew.workforce_id,
        e.name AS employee_name,
        wf.workforce_name
    FROM employees_workforce ew
    JOIN employees e ON e.employee_id = ew.employee_id
    JOIN work_force wf ON wf.workforce_id = ew.workforce_id
    WHERE e.name LIKE ? OR wf.workforce_name LIKE ?
    ORDER BY e.name, wf.workforce_name
    LIMIT ? OFFSET ?
");

// Perbaikan: Gunakan bindValue dengan tipe data yang tepat
$stmt->bindValue(1, $searchParam, PDO::PARAM_STR);
$stmt->bindValue(2, $searchParam, PDO::PARAM_STR);
$stmt->bindValue(3, $limit, PDO::PARAM_INT);
$stmt->bindValue(4, $offset, PDO::PARAM_INT);

try {
    $stmt->execute();
    $relations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Terjadi kesalahan saat mengambil data: " . $e->getMessage();
    $relations = [];
}

// Ambil daftar employee dan work_force untuk dropdown
$employees = $pdo->query("SELECT employee_id, name FROM employees ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$workforces = $pdo->query("SELECT workforce_id, workforce_name FROM work_force ORDER BY workforce_name")->fetchAll(PDO::FETCH_ASSOC);

// Include header
include __DIR__ . '/header.php';
?>

<div class="max-w-6xl mx-auto px-4 py-8">
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="p-6 md:p-8">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">Manajemen Relation: Employee & Work Force</h1>

            <?php if ($success): ?>
                <div class="mb-6 p-3 bg-green-100 text-green-800 text-sm rounded">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="mb-6 p-3 bg-red-100 text-red-800 text-sm rounded">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <!-- Form Tambah/Edit -->
            <div class="bg-gray-50 p-6 rounded-lg mb-8">
               <div class="flex justify-between items-center">
                 <h2 class="text-lg font-semibold text-gray-800 mb-4" id="form-title">
                    Added New Relation
                </h2>
                <!-- Total Data -->
                <div class="mb-3 text-sm text-gray-600">
                    Total data: <?= $total ?>
                </div>
               </div>

                <form method="POST" id="relation-form">
                    <input type="hidden" name="action" value="add" id="action-input">
                    <input type="hidden" name="old_employee_id" value="" id="old-employee-id">
                    <input type="hidden" name="old_workforce_id" value="" id="old-workforce-id">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Employee</label>
                            <select name="employee_id" id="employee_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg" required>
                                <option value="">-- Select Employee --</option>
                                <?php foreach ($employees as $emp): ?>
                                    <option value="<?= $emp['employee_id'] ?>">
                                        <?= htmlspecialchars($emp['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Work Force</label>
                            <select name="workforce_id" id="workforce_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg" required>
                                <option value="">-- Select Work Force --</option>
                                <?php foreach ($workforces as $wf): ?>
                                    <option value="<?= $wf['workforce_id'] ?>">
                                        <?= htmlspecialchars($wf['workforce_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                            Save
                        </button>
                        <button type="button" id="cancel-edit" class="px-4 py-2 bg-gray-400 text-white rounded-lg hover:bg-gray-500 hidden">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>

            <!-- Search Form -->
            <div class="mb-6">
                <form method="GET" class="flex flex-col md:flex-row gap-3">
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                        placeholder="Cari employee atau work force..."
                        class="px-4 py-2 border border-gray-300 rounded-lg flex-1">
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                        Search
                    </button>
                    <a href="admin_employees_workforce.php" class="px-4 py-2 bg-gray-400 text-white rounded-lg hover:bg-gray-500">
                        Clear
                    </a>
                </form>
            </div>

            <!-- Tabel Data -->
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-left border-b border-gray-200">
                            <th class="pb-3 font-medium text-gray-600">Employee</th>
                            <th class="pb-3 font-medium text-gray-600">Work Force</th>
                            <th class="pb-3 font-medium text-gray-600">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if (empty($relations)): ?>
                            <tr>
                                <td colspan="3" class="py-4 text-center text-gray-500">No Data Found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($relations as $rel): ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="py-4 text-sm text-gray-800"><?= htmlspecialchars($rel['employee_name']) ?></td>
                                    <td class="py-4 text-sm text-gray-800"><?= htmlspecialchars($rel['workforce_name']) ?></td>
                                    <td class="py-4 text-sm">
                                        <div class="flex gap-2">
                                            <button type="button"
                                                onclick="editRelation(
                                <?= $rel['employee_id'] ?>, 
                                <?= $rel['workforce_id'] ?>, 
                                '<?= addslashes(htmlspecialchars($rel['employee_name'])) ?>', 
                                '<?= addslashes(htmlspecialchars($rel['workforce_name'])) ?>'
                              )"
                                                class="px-3 py-1 bg-yellow-500 text-white rounded hover:bg-yellow-600 text-xs">
                                                Edit
                                            </button>
                                            <button type="button"
                                                onclick="confirmDelete(
                                <?= $rel['employee_id'] ?>, 
                                <?= $rel['workforce_id'] ?>, 
                                '<?= addslashes(htmlspecialchars($rel['employee_name'])) ?>', 
                                '<?= addslashes(htmlspecialchars($rel['workforce_name'])) ?>'
                              )"
                                                class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600 text-xs">
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="flex justify-between items-center mt-6">
                    <div class="text-sm text-gray-600">
                        Page <?= $page ?> of <?= $totalPages ?>
                    </div>
                    <nav class="flex flex-wrap justify-center gap-1 mt-2">
                        <!-- Tombol Previous -->
                        <?php if ($page > 1): ?>
                            <a href="?search=<?= urlencode($search) ?>&page=<?= $page - 1 ?>"
                                class="px-3 py-2 bg-white text-indigo-600 border border-gray-300 rounded hover:bg-gray-50 text-sm font-medium transition">
                                &lt; prev
                            </a>
                        <?php else: ?>
                            <span class="px-3 py-2 bg-gray-100 text-gray-400 border border-gray-300 rounded text-sm font-medium cursor-not-allowed">
                                &lt; prev
                            </span>
                        <?php endif; ?>

                        <!-- Tombol Halaman -->
                        <?php
                        // Tentukan rentang halaman yang akan ditampilkan
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $startPage + 4);

                        // Sesuaikan jika di akhir
                        if ($endPage - $startPage < 4) {
                            $startPage = max(1, $endPage - 4);
                        }

                        for ($i = $startPage; $i <= $endPage; $i++):
                        ?>
                            <a href="?search=<?= urlencode($search) ?>&page=<?= $i ?>"
                                class="<?= $i == $page ? 'bg-indigo-600 text-white' : 'bg-white text-indigo-600 hover:bg-indigo-50' ?>
                  px-3 py-2 border border-gray-300 rounded text-sm font-medium transition">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>

                        <!-- Tombol Next -->
                        <?php if ($page < $totalPages): ?>
                            <a href="?search=<?= urlencode($search) ?>&page=<?= $page + 1 ?>"
                                class="px-3 py-2 bg-white text-indigo-600 border border-gray-300 rounded hover:bg-gray-50 text-sm font-medium transition">
                                next &gt;
                            </a>
                        <?php else: ?>
                            <span class="px-3 py-2 bg-gray-100 text-gray-400 border border-gray-300 rounded text-sm font-medium cursor-not-allowed">
                                next &gt;
                            </span>
                        <?php endif; ?>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- JavaScript untuk Edit dan SweetAlert -->
<script>
    // Fungsi untuk menampilkan notifikasi dari PHP
    <?php if ($success): ?>
        Swal.fire({
            icon: 'success',
            title: 'Sukses',
            text: '<?= addslashes($success) ?>',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        });
    <?php endif; ?>

    <?php if ($error): ?>
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: '<?= addslashes($error) ?>',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        });
    <?php endif; ?>

    function editRelation(empId, wfId, empName, wfName) {
        document.getElementById('form-title').textContent = 'Edit Relasi';
        document.getElementById('employee_id').value = empId;
        document.getElementById('workforce_id').value = wfId;
        document.getElementById('action-input').value = 'edit';
        document.getElementById('old-employee-id').value = empId;
        document.getElementById('old-workforce-id').value = wfId;
        document.getElementById('cancel-edit').classList.remove('hidden');

        // Scroll ke form
        document.getElementById('relation-form').scrollIntoView({
            behavior: 'smooth'
        });
    }

    function confirmDelete(empId, wfId, empName, wfName) {
        Swal.fire({
            title: 'Konfirmasi Hapus',
            html: `Yakin hapus relasi antara <b>${empName}</b> dan <b>${wfName}</b>?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `?delete_emp=${empId}&delete_wf=${wfId}&search=<?= urlencode($search) ?>&page=<?= $page ?>`;
            }
        });
    }

    document.getElementById('cancel-edit')?.addEventListener('click', function() {
        const form = document.getElementById('relation-form');
        form.reset();
        document.getElementById('form-title').textContent = 'Tambah Relasi Baru';
        document.getElementById('action-input').value = 'add';
        document.getElementById('old-employee-id').value = '';
        document.getElementById('old-workforce-id').value = '';
        this.classList.add('hidden');
    });

    // Validasi form sebelum submit
    document.getElementById('relation-form')?.addEventListener('submit', function(e) {
        const employeeId = document.getElementById('employee_id').value;
        const workforceId = document.getElementById('workforce_id').value;

        if (!employeeId || !workforceId) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Pilih Employee dan Work Force terlebih dahulu!',
            });
        }
    });
</script>

<?php include __DIR__ . '/footer.php'; ?>