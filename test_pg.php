<?php
$host = '127.0.0.1';
$db   = 'ecommerce_db';
$user = 'ecommerceMessanger';
$pass = 'Mydream@2030';
$port = '5432';

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$db";
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo "Connected successfully to PostgreSQL!\n";
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
    echo "Check if database exists and user has permissions.\n";
}
