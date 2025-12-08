<?php
session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: admin_login.php");
    exit;
}

/* -------------------------
   Connexion PostgreSQL (Render)
-------------------------- */
$databaseUrl = getenv("DATABASE_URL");
if (!$databaseUrl) {
    die("DATABASE_URL manquant.");
}

$parts  = parse_url($databaseUrl);
$host   = $parts['host'] ?? 'localhost';
$port   = $parts['port'] ?? 5432;
$user   = $parts['user'] ?? '';
$pass   = $parts['pass'] ?? '';
$dbname = ltrim($parts['path'] ?? '', '/');

$dsn = "pgsql:host={$host};port={$port};dbname={$dbname};";

try {
    $pdo = new PDO($dsn, $user, $pass, [
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
$msgCount   = $pdo->query("SELECT COUNT(*) FROM messages")->fetchColumn();
$connCount  = $pdo->query("SELECT COUNT(*) FROM connect_history")->fetchColumn();

/* -------------------------
   Charger utilisateurs et salons
-------------------------- */
$allUsers = $pdo->query("SELECT pseudo FROM users ORDER BY pseudo ASC")->fetchAll();
$allRooms = $pdo->query("
    SELECT id, name, created_by, created_at
    FROM rooms
    ORDER BY created_at DESC
")->fetchAll();
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

        <!-- ========= STATISTIQUES ========== -->
        <div class="panel">
            <h3>📊 Statistiques</h3>
            <p>👤 Utilisateurs : <?= $usersCount ?></p>
            <p>💬 Messages : <?= $msgCount ?></p>
            <p>🏠 Salons : <?= $roomsCount ?></p>
            <p>📈 Historique connexions : <?= $connCount ?></p>
        </div>

        <!-- ========= ACTIONS ========= -->
        <div class="panel">
            <h3>⚙️ Actions rapides</h3>

            <form action="admin_actions.php" method="post">
                <button class="btn btn-danger" name="action" value="clear_messages">🧹 Vider tous les messages</button>
            </form>

            <form action="admin_actions.php" method="post">
                <button class="btn btn-danger" name="action" value="clear_history">🧹 Vider l’historique de connexion</button>
            </form>

            <form action="admin_actions.php" method="post">
                <button class="btn" name="action" value="backup">💾 Télécharger sauvegarde SQL</button>
            </form>
        </div>

        <!-- ========= GESTION DES SALONS ========= -->
        <div class="panel">
            <h3>🏠 Gestion des salons (supprimer)</h3>

            <?php if (!$allRooms): ?>
                <p class="muted">Aucun salon existant.</p>
            <?php else: ?>
                <?php foreach ($allRooms as $room): ?>
                    <form method="post" action="admin_actions.php"
                          onsubmit="return confirm('Supprimer le salon « <?= $room['name'] ?> » ? Tous les messages seront effacés.');">

                        <input type="hidden" name="action" value="delete_room">
                        <input type="hidden" name="room_id" value="<?= $room['id'] ?>">

                        <div class="room-row">
                            <span>🏠 <b><?= htmlentities($room['name']) ?></b> — créé par <?= htmlentities($room['created_by']) ?></span>
                            <button class="btn btn-danger-small">Supprimer</button>
                        </div>
                    </form>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- ========= GESTION DES UTILISATEURS ========= -->
        <div class="panel">
            <h3>👥 Gestion des utilisateurs</h3>

            <?php if (!$allUsers): ?>
                <p class="muted">Aucun utilisateur.</p>
            <?php else: ?>
                <?php foreach ($allUsers as $u): ?>
                    <form method="post" action="admin_actions.php"
                          onsubmit="return confirm('Supprimer l’utilisateur « <?= $u['pseudo'] ?> » ?');">

                        <input type="hidden" name="action" value="delete_user">
                        <input type="hidden" name="pseudo" value="<?= $u['pseudo'] ?>">

                        <div class="room-row">
                            <span>👤 <?= htmlentities($u['pseudo']) ?></span>
                            <button class="btn btn-danger-small">Supprimer</button>
                        </div>
                    </form>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <a class="btn btn-secondary" href="logout.php">Déconnexion Admin</a>
    </div>
</div>
</body>
</html>
