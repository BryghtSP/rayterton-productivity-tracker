<?php

use PDO;

return [
    'up' => function (PDO $pdo) {
        $sql = "CREATE TABLE work_force(
            workforce_id INT AUTO_INCREMENT PRIMARY KEY,
            employee_id INT NOT NULL,
            workforce_name VARCHAR(100) NOT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

            -- Relasi ke master_employees
            CONSTRAINT fk_employee_workforce FOREIGN KEY (employee_id) 
                REFERENCES employees(employee_id)
                ON DELETE CASCADE
                ON UPDATE CASCADE
            )
        ";
        $pdo->exec($sql);
    },
    'down' => function (PDO $pdo) {}
];
?>