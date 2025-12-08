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
$msgCount   = $pdo->query("SELECT COUNT(*) FROM messages")->fetchColumn();
$connCount  = $pdo->query("SELECT COUNT(*) FROM connect_history")->fetchColumn();

/* -------------------------
   Utilisateurs (pour tableau)
-------------------------- */
$users = $pdo->query("SELECT pseudo FROM users ORDER BY pseudo ASC")->fetchAll();
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

        <?php if (isset($_GET['deleted'])): ?>
            <p class="pill" style="background:rgba(255,83,112,0.12); border:1px solid rgba(255,83,112,0.4); color:#fecdd3;">
                👤 L’utilisateur <strong><?= htmlentities($_GET['deleted']) ?></strong> a été supprimé.
            </p>
        <?php endif; ?>

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
                <button class="btn btn-danger" name="action" value="clear_messages">
                    🧹 Vider tous les messages
                </button>
            </form>

            <form action="admin_actions.php" method="post">
                <button class="btn btn-danger" name="action" value="clear_history">
                    🧹 Vider l'historique de connexion
                </button>
            </form>

            <form action="admin_actions.php" method="post">
                <button class="btn" name="action" value="backup">
                    💾 Télécharger sauvegarde SQL
                </button>
            </form>
        </div>

        <!-- -------------------------
             Gestion des utilisateurs
        -------------------------- -->
        <div class="panel">
            <h3>Gestion des utilisateurs</h3>

            <table border="1" cellpadding="6" style="width:100%; background:white; border-collapse:collapse;">
                <tr style="background:#f0f0f0;">
                    <th>Pseudo</th>
                    <th>Actions</th>
                </tr>

                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= htmlentities($u['pseudo']) ?></td>
                        <td>
                            <?php if ($u['pseudo'] !== "admin"): ?>
                                <form action="admin_actions.php" method="post" style="display:inline;">
                                    <input type="hidden" name="user_delete" value="<?= $u['pseudo'] ?>">
                                    <button class="btn btn-danger"
                                            onclick="return confirm('Supprimer l’utilisateur <?= $u['pseudo'] ?> ?')">
                                        ❌ Supprimer
                                    </button>
                                </form>
                            <?php else: ?>
                                <span class="muted">Compte protégé</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <a class="btn btn-secondary" href="logout.php">Déconnexion Admin</a>
    </div>
</div>
</body>
</html>
