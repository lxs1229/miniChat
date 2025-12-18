<?php
session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: admin_login.php");
    exit;
}

require __DIR__ . "/db.php";

/* =============================
   Statistiques globales
============================= */
$usersCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$roomsCount = $pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
$msgCount   = $pdo->query("SELECT COUNT(*) FROM messages")->fetchColumn();
$connCount  = $pdo->query("SELECT COUNT(*) FROM connect_history")->fetchColumn();

/* =============================
   Utilisateurs + dernière IP
============================= */
$users = $pdo->query("
    SELECT 
        u.pseudo,
        MAX(c.time) AS last_login,
        MAX(c.ip_connection) AS last_ip
    FROM users u
    LEFT JOIN connect_history c ON c.pseudo = u.pseudo
    GROUP BY u.pseudo
    ORDER BY u.pseudo ASC
")->fetchAll();

/* =============================
   Salons
============================= */
$rooms = $pdo->query("
    SELECT id, name, created_by, created_at
    FROM rooms
    ORDER BY created_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel • MiniChat</title>
    <link rel="stylesheet" href="styles.css">
</head>

<body>
<div class="page">
    <div class="card">

        <h1>🔐 Panneau Administrateur</h1>

        <!-- ===== STATISTIQUES ===== -->
        <div class="panel">
            <h3>📊 Statistiques</h3>
            <p>👤 Utilisateurs : <?= $usersCount ?></p>
            <p>💬 Messages : <?= $msgCount ?></p>
            <p>🏠 Salons : <?= $roomsCount ?></p>
            <p>📈 Connexions enregistrées : <?= $connCount ?></p>
        </div>

        <!-- ===== ACTIONS RAPIDES ===== -->
        <div class="panel">
            <h3>⚙️ Actions rapides</h3>

            <form action="admin_actions.php" method="post">
                <input type="hidden" name="action" value="clear_messages">
                <button class="btn btn-danger">🧹 Vider tous les messages</button>
            </form>

            <form action="admin_actions.php" method="post">
                <input type="hidden" name="action" value="clear_history">
                <button class="btn btn-danger">🧹 Vider l’historique de connexion</button>
            </form>

            <form action="admin_actions.php" method="post">
                <input type="hidden" name="action" value="backup">
                <button class="btn">💾 Télécharger sauvegarde SQL</button>
            </form>
        </div>

        <!-- ===== GESTION DES SALONS ===== -->
        <div class="panel">
            <h3>🏠 Gestion des salons</h3>

            <?php if (!$rooms): ?>
                <p class="muted">Aucun salon existant.</p>
            <?php else: ?>
                <?php foreach ($rooms as $room): ?>
                    <form method="post" action="admin_actions.php"
                          onsubmit="return confirm('Supprimer le salon « <?= htmlentities($room['name']) ?> » ?');">
                        <input type="hidden" name="action" value="delete_room">
                        <input type="hidden" name="room_id" value="<?= $room['id'] ?>">

                        <div class="room-row">
                            <span>
                                🏠 <b><?= htmlentities($room['name']) ?></b>
                                — créé par <?= htmlentities($room['created_by']) ?>
                            </span>
                            <button class="btn btn-danger-small">Supprimer</button>
                        </div>
                    </form>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- ===== GESTION DES UTILISATEURS ===== -->
        <div class="panel">
            <h3>👥 Gestion des utilisateurs</h3>

            <table class="admin-table">
                <tr>
                    <th>Pseudo</th>
                    <th>Dernière connexion</th>
                    <th>IP</th>
                    <th>Action</th>
                </tr>

                <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= htmlentities($u['pseudo']) ?></td>
                    <td><?= $u['last_login'] ?? '—' ?></td>
                    <td><?= $u['last_ip'] ?? '—' ?></td>
                    <td>
                        <?php if ($u['pseudo'] !== 'admin'): ?>
                        <form method="post" action="admin_actions.php"
                              onsubmit="return confirm('Supprimer l’utilisateur « <?= $u['pseudo'] ?> » ?');">
                            <input type="hidden" name="action" value="delete_user">
                            <input type="hidden" name="pseudo" value="<?= $u['pseudo'] ?>">
                            <button class="btn btn-danger-small">Supprimer</button>
                        </form>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>

            <p class="muted">
                ℹ️ Les adresses IP sont collectées uniquement à des fins de sécurité.
            </p>
        </div>

        <a class="btn btn-secondary" href="logout.php">Déconnexion Admin</a>
    </div>
</div>
</body>
</html>
