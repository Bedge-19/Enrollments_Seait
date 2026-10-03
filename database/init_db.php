<?php
// init_db.php - Create the enrollments database and tables from schema.sql
set_time_limit(0);
ini_set('memory_limit', '1024M');

$host = '127.0.0.1';
$port = '3306';
$user = 'root';
$pass = '';

echo "Connecting to MySQL server at {$host}:{$port}...\n";

try {
    $pdo = new PDO("mysql:host={$host};port={$port}", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    echo "Connected successfully!\n";

    $sqlPath = __DIR__ . '/schema.sql';
    if (!file_exists($sqlPath)) {
        throw new Exception("schema.sql not found at {$sqlPath}");
    }

    $sql = file_get_contents($sqlPath);
    echo "Executing schema.sql...\n";

    $pdo->exec($sql);
    echo "Database and schema created successfully!\n";

    // Verify tables
    $pdo->exec("USE `enrollments`");
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables in 'enrollments' (" . count($tables) . "):\n";
    foreach ($tables as $table) {
        echo " - {$table}\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
