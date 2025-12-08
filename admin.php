<?php
session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: admin_login.php");
    exit;
}

/* -------------------------
   Connexion PostgreSQL
-------------------------- */
$databaseUrl = getenv("DATABASE_URL");
if (!$databaseUrl) {
    die("DATABASE_URL manquant.");
}

$parts = parse_url($databaseUrl);

$host = $parts['host'];
$port = $parts['port'];
$user = $parts['user'];
$pass = $parts['pass'];
$dbname = ltrim($parts['path'], '/');

$dsn_pgsql = "pgsql:host={$host};port={$port};dbname={$dbname};";

try {
    $pdo = new PDO($dsn_pgsql, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Erreur connexion PostgreSQL : " . $e->getMessage());
}

/* -------------------------
   Statistiques
-------------------------- */
$usersCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$roomsCount = $pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
$msgCount = $pdo->query("SELECT COUNT(*) FROM message")->fetchColumn();
$connCount = $pdo->query("SELECT COUNT(*) FROM Connect_Histoire")->fetchColumn();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="page">
    <div class="card">
        <h1>🔐 Panneau Administrateur</h1>

        <div class="panel">
            <h3>Statistiques</h3>
            <p>👤 Utilisateurs : <?= $usersCount ?></p>
            <p>💬 Messages : <?= $msgCount ?></p>
            <p>🏠 Salons : <?= $roomsCount ?></p>
            <p>📊 Historique connexions : <?= $connCount ?></p>
        </div>

        <div class="panel">
            <h3>Actions rapides</h3>

            <form action="admin_actions.php" method="post">
                <button class="btn btn-danger" name="action" value="clear_messages">🧹 Vider tous les messages</button>
            </form>

            <form action="admin_actions.php" method="post">
                <button class="btn btn-danger" name="action" value="clear_history">🧹 Vider l'historique de connexion</button>
            </form>

            <form action="admin_actions.php" method="post">
                <button class="btn" name="action" value="backup">💾 Télécharger sauvegarde SQL</button>
            </form>
        </div>

        <a class="btn btn-secondary" href="logout.php">Déconnexion Admin</a>
    </div>
</div>
</body>
</html>
