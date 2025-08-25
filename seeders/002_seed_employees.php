<?php
// seed_work_force.php

return [
    'run' => function (PDO $pdo) {
        echo "Menjalankan seeder untuk tabel employees...\n";

        $data = [
            [
                'name' => 'Shaquille Raffalea',
                'position' => 'Internship',
                'phone' => ''
            ],
            [
                'name' => 'Fazle Adrevi Bintang Al Farrel',
                'position' => 'Internship',
                'phone' => ''
            ],
            [
                'name' => 'Farel Fadlillah',
                'position' => 'Internship',
                'phone' => ''
            ],
            [
                'name' => 'Bintang Rayvan',
                'position' => 'Internship',
                'phone' => ''
            ],
            [
                'name' => 'Iqbal Hadi Mustafa',
                'position' => 'Internship',
                'phone' => ''
            ],
            [
                'name' => 'Zafira Marvella',
                'position' => 'Internship',
                'phone' => ''
            ],
            [
                'name' => 'Kirana Firjal Atakhira',
                'position' => 'Internship',
                'phone' => ''
            ],
            [
                'name' => 'Aisyah Ratna Aulia',
                'position' => 'Internship',
                'phone' => ''
            ],
            [
                'name' => 'Firyal Dema Elputri',
                'position' => 'Internship',
                'phone' => ''
            ],
            [
                'name' => 'Halena Maheswari Viehandini',
                'position' => 'Internship',
                'phone' => ''
            ],
            [
                'name' => 'Hannif Fahmy Fadilah',
                'position' => 'Internship',
                'phone' => ''
            ],
            [
                'name' => 'Kevin Revaldo',
                'position' => 'Internship',
                'phone' => ''
            ],
            [
                'name' => 'Achmad wafiq risvyan',
                'position' => 'Internship',
                'phone' => ''
            ],
            [
                'name' => 'Kurniawan yafi Djayakusuma',
                'position' => 'Internship',
                'phone' => ''
            ],
            [
                'name' => 'Hildan argiansyah',
                'position' => 'Internship',
                'phone' => ''
            ],
            [
                'name' => 'Joshua Matthew Hendra',
                'position' => 'Internship',
                'phone' => ''
            ],
            [
                'name' => 'Fadhal nurul azmi',
                'position' => 'Internship',
                'phone' => ''
            ],
            [
                'name' => 'Rasya al zikri',
                'position' => 'Internship',
                'phone' => ''
            ],
        ];

        $stmt = $pdo->prepare("INSERT INTO employees (name, position, phone) VALUES (:name, :position, :phone)");

        foreach ($data as $row) {
            try {
                $stmt->execute($row);
            } catch (Exception $e) {
                echo "Gagal insert employees {$row['name']}: " . $e->getMessage() . "\n";
            }
        }

        echo "Seeder work_force selesai.\n";
    }
];
