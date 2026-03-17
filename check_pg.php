<?php
$dsn = "pgsql:host=127.0.0.1;port=5432;dbname=ecommerce_db";
$pdo = new PDO($dsn, 'ecommercemessanger', 'Mydream@2030');

// List all tables
$tables = $pdo->query("SELECT tablename FROM pg_tables WHERE schemaname='public' ORDER BY tablename")->fetchAll(PDO::FETCH_COLUMN);
echo "=== PostgreSQL Tables (" . count($tables) . ") ===\n";
foreach ($tables as $t) echo "  ✓ $t\n";

// Check new tables exist
$required = ['plan_upgrade_requests', 'feedbacks', 'categories', 'products', 'orders', 'users', 'clients'];
echo "\n=== Required Tables Check ===\n";
foreach ($required as $t) {
    echo (in_array($t, $tables) ? "  ✅" : "  ❌") . " $t\n";
}

// Check is_global column in categories
$col = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_name='categories' AND column_name='is_global'")->fetch();
echo "\n=== Column Checks ===\n";
echo ($col ? "  ✅" : "  ❌") . " categories.is_global\n";

// Check plan_upgrade_requests columns
$cols = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_name='plan_upgrade_requests'")->fetchAll(PDO::FETCH_COLUMN);
echo "  " . (count($cols) ? "✅" : "❌") . " plan_upgrade_requests columns: " . implode(', ', $cols) . "\n";

$fcols = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_name='feedbacks'")->fetchAll(PDO::FETCH_COLUMN);
echo "  " . (count($fcols) ? "✅" : "❌") . " feedbacks columns: " . implode(', ', $fcols) . "\n";

echo "\n✅ Migration check complete!\n";
