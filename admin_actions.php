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
$user = $parts['user'] ?? null;
$pass = $parts['pass'] ?? null;
$dbname = ltrim($parts['path'] ?? '', '/');
$port = $parts['port'] ?? 5432;

$dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Erreur connexion PostgreSQL : " . $e->getMessage());
}


/* ======================================================
          🔥 1) SUPPRIMER UN UTILISATEUR (user_delete)
   ====================================================== */
if (isset($_POST['user_delete'])) {
    $pseudo = $_POST['user_delete'];

    if ($pseudo === "admin") {
        die("Impossible de supprimer l'utilisateur admin.");
    }

    // Supprimer ses messages
    $stmt = $pdo->prepare("DELETE FROM messages WHERE pseudo = ?");
    $stmt->execute([$pseudo]);

    // Supprimer ses salons
    $stmt = $pdo->prepare("DELETE FROM rooms WHERE created_by = ?");
    $stmt->execute([$pseudo]);

    // Supprimer historique de connexion
    $stmt = $pdo->prepare("DELETE FROM connect_history WHERE pseudo = ?");
    $stmt->execute([$pseudo]);

    // Supprimer l'utilisateur
    $stmt = $pdo->prepare("DELETE FROM users WHERE pseudo = ?");
    $stmt->execute([$pseudo]);

    header("Location: admin.php?deleted=" . urlencode($pseudo));
    exit;
}


/* ======================================================
          🔥 2) VIDER TOUS LES MESSAGES
   ====================================================== */
if (isset($_POST['action']) && $_POST['action'] === 'clear_messages') {
    $pdo->exec("DELETE FROM messages");
    header("Location: admin.php?msg_cleared=1");
    exit;
}


/* ======================================================
          🔥 3) VIDER L'HISTORIQUE DE CONNEXION
   ====================================================== */
if (isset($_POST['action']) && $_POST['action'] === 'clear_history') {
    $pdo->exec("DELETE FROM connect_history");
    header("Location: admin.php?history_cleared=1");
    exit;
}


if ($action === "delete_room") {
    $roomId = intval($_POST['room_id'] ?? 0);

    if ($roomId > 0) {
        // Supprimer messages du salon
        $pdo->prepare("DELETE FROM messages WHERE room_id = ?")->execute([$roomId]);

        // Supprimer salon
        $pdo->prepare("DELETE FROM rooms WHERE id = ?")->execute([$roomId]);

        header("Location: admin.php?success=room_deleted");
        exit;
    }
}


/* ======================================================
          🔥 4) GÉNÉRER UNE SAUVEGARDE SQL (Télécharger)
   ====================================================== */
if (isset($_POST['action']) && $_POST['action'] === 'backup') {
    header("Content-Type: text/plain");
    header("Content-Disposition: attachment; filename=minichat_backup.sql");

    echo "-- MiniChat SQL Backup\n";
    echo "-- Date: " . date("Y-m-d H:i:s") . "\n\n";

    $tables = ["users", "rooms", "messages", "connect_history"];

    foreach ($tables as $table) {
        echo "-- -----------------------------\n";
        echo "-- Table: $table\n";
        echo "-- -----------------------------\n\n";

        $rows = $pdo->query("SELECT * FROM $table")->fetchAll();

        foreach ($rows as $row) {
            $cols = implode(", ", array_map(fn($c) => "\"$c\"", array_keys($row)));
            $vals = implode(", ", array_map(fn($v) => $pdo->quote($v), array_values($row)));

            echo "INSERT INTO $table ($cols) VALUES ($vals);\n";
        }
        echo "\n\n";
    }

    exit;
}

/* ======================================================
          🔥 5) Sinon → Retour admin
   ====================================================== */
header("Location: admin.php");
exit;
?>
