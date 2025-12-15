<?php
ob_start();
session_start();

/* -------------------------
   Sécurité admin
-------------------------- */
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: admin_login.php");
    exit;
}

/* -------------------------
   Connexion PostgreSQL (Render)
-------------------------- */
$databaseUrl = getenv("DATABASE_URL");
if (!$databaseUrl) {
    header("Location: admin.php?err=db_url");
    exit;
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
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    header("Location: admin.php?err=db_connect");
    exit;
}

/* -------------------------
   🔑 ACTION (clé unique)
-------------------------- */
$action = $_POST['action'] ?? '';

/* ======================================================
   1️⃣ SUPPRIMER UN UTILISATEUR
====================================================== */
if ($action === "delete_user") {
    $pseudo = $_POST['pseudo'] ?? '';

    if ($pseudo === '' || $pseudo === 'admin') {
        header("Location: admin.php?err=delete_admin");
        exit;
    }

    $pdo->prepare("DELETE FROM messages WHERE pseudo = ?")->execute([$pseudo]);
    $pdo->prepare("DELETE FROM rooms WHERE created_by = ?")->execute([$pseudo]);
    $pdo->prepare("DELETE FROM connect_history WHERE pseudo = ?")->execute([$pseudo]);
    $pdo->prepare("DELETE FROM users WHERE pseudo = ?")->execute([$pseudo]);

    header("Location: admin.php?ok=user_deleted");
    exit;
}

/* ======================================================
   2️⃣ VIDER TOUS LES MESSAGES
====================================================== */
if ($action === "clear_messages") {
    $pdo->exec("DELETE FROM messages");
    header("Location: admin.php?ok=messages_cleared");
    exit;
}

/* ======================================================
   3️⃣ VIDER HISTORIQUE CONNEXIONS
====================================================== */
if ($action === "clear_history") {
    $pdo->exec("DELETE FROM connect_history");
    header("Location: admin.php?ok=history_cleared");
    exit;
}

/* ======================================================
   4️⃣ SUPPRIMER UN SALON
====================================================== */
if ($action === "delete_room") {
    $roomId = (int)($_POST['room_id'] ?? 0);

    if ($roomId > 0) {
        $pdo->prepare("DELETE FROM messages WHERE room_id = ?")->execute([$roomId]);
        $pdo->prepare("DELETE FROM rooms WHERE id = ?")->execute([$roomId]);
    }

    header("Location: admin.php?ok=room_deleted");
    exit;
}

/* ======================================================
   5️⃣ SAUVEGARDE SQL
====================================================== */
if ($action === "backup") {
    header("Content-Type: text/plain");
    header("Content-Disposition: attachment; filename=minichat_backup.sql");

    echo "-- MiniChat SQL Backup\n";
    echo "-- Date: " . date("Y-m-d H:i:s") . "\n\n";

    $tables = ["users", "rooms", "messages", "connect_history"];

    foreach ($tables as $table) {
        echo "-- Table: $table\n";
        $rows = $pdo->query("SELECT * FROM $table")->fetchAll();

        foreach ($rows as $row) {
            $cols = implode(", ", array_keys($row));
            $vals = implode(", ", array_map([$pdo, 'quote'], array_values($row)));
            echo "INSERT INTO $table ($cols) VALUES ($vals);\n";
        }
        echo "\n";
    }
    exit;
}

/* ======================================================
   ❌ Action inconnue → retour admin
====================================================== */
header("Location: admin.php");
exit;
