<?php
try {
    $pdo = new PDO("pgsql:host=127.0.0.1;port=5432;dbname=postgres", "postgres", "1234");
    $stmt = $pdo->query("SELECT datname FROM pg_database WHERE datistemplate = false");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['datname'] . "\n";
    }
} catch (PDOException $e) {
    echo "Error: " . mb_convert_encoding($e->getMessage(), 'UTF-8', 'auto') . "\n";
}
