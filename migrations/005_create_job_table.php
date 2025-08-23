<?php
use PDO;

return [
    'up' => function (PDO $pdo) {
        $sql = "CREATE TABLE";
        $pdo->exec($sql);
    },
    'down' => function (PDO $pdo) {
        
    }
];

?>