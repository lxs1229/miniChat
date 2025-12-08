<?php
session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: admin_login.php");
    exit;
}

$action = $_POST['action'] ?? null;

$dsn = getenv("DATABASE_URL");
$pdo = new PDO($dsn);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if ($action === "clear_messages") {
    $pdo->exec("DELETE FROM messages");
    header("Location: admin.php");
    exit;
}

if ($action === "clear_history") {
    $pdo->exec("DELETE FROM Connect_Histoiry");
    header("Location: admin.php");
    exit;
}

if ($action === "backup") {
    header("Content-Type: text/plain");
    header("Content-Disposition: attachment; filename=backup_minichat.sql");

    echo "-- Backup MiniChat " . date("Y-m-d H:i:s") . "\n\n";

    foreach (["users", "rooms", "messages", "Connect_Histoiry"] as $table) {
        $rows = $pdo->query("SELECT * FROM $table")->fetchAll(PDO::FETCH_ASSOC);
        echo "\n-- TABLE: $table\n";
        foreach ($rows as $row) {
            $cols = implode(", ", array_map(fn($x) => "`$x`", array_keys($row)));
            $vals = implode(", ", array_map(fn($x) => $pdo->quote($x), array_values($row)));
            echo "INSERT INTO $table ($cols) VALUES ($vals);\n";
        }
    }
    exit;
}

// Unknown action
header("Location: admin.php");
exit;
