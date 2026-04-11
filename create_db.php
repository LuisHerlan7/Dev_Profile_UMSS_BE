<?php
try {
    $pdo = new PDO("pgsql:host=127.0.0.1;port=5432;dbname=postgres", "postgres", "1234");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('CREATE DATABASE "GeneradorCvDB"');
    echo "Database created successfully.\n";
} catch (PDOException $e) {
    echo "Error creating database: " . mb_convert_encoding($e->getMessage(), 'UTF-8', 'auto') . "\n";
}
