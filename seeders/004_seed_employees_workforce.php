<?php
// seeders/EmployeeWorkforceSeeder.php

// class EmployeeWorkforceSeeder
return [
    'run' => function ($pdo)
    {
        echo "Menjalankan seeder: EmployeeWorkforceSeeder\n";

        $data = [
            [1, 1], // Putra + Jasaraharja
            [1, 4], // Putra + Inare
            [1, 5], // Putra + Antara
            [1, 7], // Putra + Cascamedika
            [2, 4], // Bryan + Inare
            [2, 5], // Bryan + Antara
            [2, 7], // Bryan + Cascamedika
            [3, 1], // Nabil + Jasaraharja
            [3, 2], // Nabil + Hildiktipari
            [25, 1], // Raffa + Jasaraharja
            [25, 5], // Raffa + Antara
            [25, 4], // Raffa + Inare
            [26, 4], // Fazle + Inare
            [26, 5], // Fazle + Antara
            [26, 7], // Fazle + Cascamedika
            [27, 7], // Farel + Cascamedika
            [27, 5], // Farel + Antara
            [27, 4], // Farel + Inare
            [28, 4], // Bintang + Inare
            [28, 5], // Bintang + Antara
            [28, 6], // Bintang + Trade Finance
            [29, 4], // Iqbal + Inare
            [29, 5], // Iqbal + Antara
            [29, 6], // Iqbal + Trade Finance
            [30, 1], // Zafira + Jasaraharja
            [31, 3], // Kiya + Pasifik
            [32, 4], // Asa + Inare
            [32, 5], // Asa + Antara
            [33, 3], // Ily + Pasifik
            [34, 4], // Hana + Inare
            [34, 5], // Hana + Antara
            [35, 1], // Fahmy + Jasaraharja
            [35, 2], // Fahmy + Hildiktipari
            [35, 7], // Fahmy + Cascamedika
            [36, 6], // Kevin + Trade Finance
            [37, 2], // Risvyan + Hildiktipari
            [38, 6], // Yafi + Trade Finance
            [39, 2], // Hildan + Hildiktipari
            [39, 7], // Hildan + Cascamedika
            [40, 3], // Joshua + Pasifik
            [40, 6], // Joshua + Trade Finance
            [41, 2], // Fadhal + Hildiktipari
            [41, 7], // Fadhal + Cascamedika
            [42, 1], // Rasya + Jasaraharja
            [42, 2], // Rasya + Hildiktipari
        ];


        $stmt = $pdo->prepare("
            INSERT INTO employees_workforce (employee_id, workforce_id) 
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE employee_id = employee_id
        ");

        foreach ($data as $row) {
            $stmt->execute($row);
        }

        echo "✅ Data employee_workforce berhasil diisi.\n";
    }
];