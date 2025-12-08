<?php
session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: admin_login.php");
    exit;
}

/* -------------------------
   Connexion PostgreSQL (Render Compatible)
-------------------------- */
$databaseUrl = getenv("DATABASE_URL");
if (!$databaseUrl) {
    die("DATABASE_URL manquant.");
}

$parts = parse_url($databaseUrl);

$host = $parts['host'] ?? 'localhost';
$port = $parts['port'] ?? 5432;
$user = $parts['user'] ?? '';
$pass = $parts['pass'] ?? '';
$db   = ltrim($parts['path'] ?? '', '/');

$dsn = "pgsql:host={$host};port={$port};dbname={$db}";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Erreur connexion PostgreSQL : " . $e->getMessage());
}

$action = $_POST['action'] ?? '';

/* -------------------------
   Actions Admin
-------------------------- */

if ($action === "clear_messages") {
    $pdo->exec("DELETE FROM messages");
    header("Location: admin.php");
    exit;
}

if ($action === "clear_history") {
    $pdo->exec("DELETE FROM connect_history");
    header("Location: admin.php");
    exit;
}

if ($action === "backup") {
    header("Content-Type: text/plain");
    header("Content-Disposition: attachment; filename=backup_minichat.sql");

    echo "-- Backup Minichat " . date("Y-m-d H:i:s") . "\n\n";

    $tables = ["users", "rooms", "messages", "connect_history"];

    foreach ($tables as $table) {
        echo "-- Table: $table\n";

        $rows = $pdo->query("SELECT * FROM $table")->fetchAll();

        foreach ($rows as $row) {
            $cols = implode(", ", array_map(fn($c) => "\"$c\"", array_keys($row)));
            $vals = implode(", ", array_map(fn($v) => $pdo->quote($v), array_values($row)));

            echo "INSERT INTO $table ($cols) VALUES ($vals);\n";
        }
        echo "\n";
    }
    exit;
}

header("Location: admin.php");
exit;
?>
