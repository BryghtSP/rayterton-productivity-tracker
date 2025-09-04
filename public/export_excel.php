<?php
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth.php';
require_admin();

$recapType = $_GET['recap_type'] ?? 'daily';
$date = $_GET['date'] ?? date('Y-m-d');
$month = $_GET['month'] ?? date('Y-m');
$notesFilter = $_GET['notes'] ?? '';

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=attendance_{$recapType}.xls");
header("Pragma: no-cache");
header("Expires: 0");

echo "<table border='1' cellpadding='5' cellspacing='0'>";

if ($recapType === 'daily') {
    // ambil data daily
    $sql = "SELECT a.*, u.name, u.email 
            FROM attendance a
            JOIN users u ON a.user_id = u.user_id
            WHERE a.date = ?";
    $params = [$date];
    if (!empty($notesFilter)) {
        $sql .= " AND a.notes = ?";
        $params[] = $notesFilter;
    }
    $sql .= " ORDER BY a.status, u.name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    echo "<tr>
            <th>Name</th>
            <th>Email</th>
            <th>Shift</th>
            <th>Check-in</th>
            <th>Check-out</th>
            <th>Status</th>
            <th>Location</th>
            <th>Notes</th>
          </tr>";
    foreach ($rows as $r) {
        echo "<tr>
                <td>".htmlspecialchars($r['name'])."</td>
                <td>".htmlspecialchars($r['email'])."</td>
                <td>".htmlspecialchars($r['shift'])."</td>
                <td>".($r['check_in'] ?? '-')."</td>
                <td>".($r['check_out'] ?? '-')."</td>
                <td>".htmlspecialchars($r['status'])."</td>
                <td>".htmlspecialchars($r['location'] ?? '-')."</td>
                <td>".(!empty($r['notes']) ? htmlspecialchars($r['notes']) : '-')."</td>
              </tr>";
    }

} else {
    // ambil data monthly
    $start = $month . "-01";
    $end = date('Y-m-t', strtotime($start));

    $stmt = $pdo->prepare("
        SELECT u.user_id, u.name, 
               SUM(CASE WHEN a.status = 'Hadir' AND TIME(a.check_in) <= '07:45:00' THEN 1 ELSE 0 END) as hadir_shift_pagi,
               SUM(CASE WHEN a.status = 'Hadir' AND TIME(a.check_in) > '07:45:00' AND TIME(a.check_in) <= '13:10:00' THEN 1 ELSE 0 END) as hadir_shift_siang,
               SUM(CASE WHEN a.status = 'Hadir' AND TIME(a.check_in) > '13:10:00' THEN 1 ELSE 0 END) as hadir_invalid,
               SUM(CASE WHEN a.status = 'Telat' THEN 1 ELSE 0 END) as telat,
               SUM(CASE WHEN a.status = 'Izin' THEN 1 ELSE 0 END) as izin,
               SUM(CASE WHEN a.status = 'Sakit' THEN 1 ELSE 0 END) as sakit
        FROM users u
        LEFT JOIN attendance a ON a.user_id = u.user_id AND a.date BETWEEN ? AND ?
        WHERE u.is_active = 1
        GROUP BY u.user_id
        ORDER BY u.name
    ");
    $stmt->execute([$start, $end]);
    $rows = $stmt->fetchAll();

    echo "<tr>
            <th>Name</th>
            <th>Shift Pagi</th>
            <th>Shift Siang</th>
            <th>Invalid</th>
            <th>Telat</th>
            <th>Izin</th>
            <th>Sakit</th>
          </tr>";
    foreach ($rows as $r) {
        echo "<tr>
                <td>".htmlspecialchars($r['name'])."</td>
                <td>".$r['hadir_shift_pagi']."</td>
                <td>".$r['hadir_shift_siang']."</td>
                <td>".$r['hadir_invalid']."</td>
                <td>".$r['telat']."</td>
                <td>".$r['izin']."</td>
                <td>".$r['sakit']."</td>
              </tr>";
    }
}

echo "</table>";
