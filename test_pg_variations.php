<?php
$host = '127.0.0.1';
$db   = 'ecommerce_db';
$user = 'ecommerceMessanger'; // Trying with capital M
$pass = 'Mydream@2030';
$port = '5432';

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$db";
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo "Connected successfully to PostgreSQL (with capital M)!\n";
} catch (PDOException $e) {
    echo "Connection failed (capital M): " . $e->getMessage() . "\n";
    
    // Trying lowercase M
    try {
        $user = 'ecommercemessanger';
        $dsn = "pgsql:host=$host;port=$port;dbname=$db";
        $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        echo "Connected successfully to PostgreSQL (with lowercase m)!\n";
    } catch (PDOException $e2) {
        echo "Connection failed (lowercase m): " . $e2->getMessage() . "\n";
    }
}
