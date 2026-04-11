<?php
try {
    $dsn = "pgsql:host=127.0.0.1;port=5432;dbname=GeneradorCvDB";
    $pdo = new PDO($dsn, "postgres", "1234");
    echo "SUCCESS\n";
} catch (PDOException $e) {
    echo "FAIL: " . mb_convert_encoding($e->getMessage(), 'UTF-8', 'ISO-8859-1,Windows-1252,UTF-8') . "\n";
}
