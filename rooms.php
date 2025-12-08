<?php
session_start();
if (!isset($_SESSION['pseudo'])) {
    header("Location: index.html");
    exit;
}

$pseudo = $_SESSION['pseudo'];

/* ------------------------------
   Connexion PostgreSQL Render
--------------------------------*/
$databaseUrl = getenv("DATABASE_URL");
if (!$databaseUrl) {
    die("DATABASE_URL manquant pour la connexion PDO.");
}

$parts = parse_url($databaseUrl);

$host = $parts['host'];
$port = $parts['port'] ?? 5432;
$user = $parts['user'];
$pass = $parts['pass'];
$db   = ltrim($parts['path'], '/');

$dsn = "pgsql:host={$host};port={$port};dbname={$db}";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Erreur connexion PostgreSQL : " . $e->getMessage());
}

/* ------------------------------
   Vérification du schéma PostgreSQL
--------------------------------*/
function schemaReady(PDO $pdo): bool {
    // Vérifier table rooms
    $rooms = $pdo->query("SELECT to_regclass('public.rooms')")->fetchColumn();

    // Vérifier colonne room_id dans messages
    $col = $pdo->query("
        SELECT column_name 
        FROM information_schema.columns 
        WHERE table_name='messages' AND column_name='room_id'
    ")->fetchColumn();

    return $rooms !== null && $col !== false;
}

$schemaReady = schemaReady($pdo);

$error = null;
$info = null;

$currentRoomId = $_SESSION['room_id'] ?? null;
$currentRoomName = $_SESSION['room_name'] ?? null;

/* ------------------------------
   Charger liste des salons
--------------------------------*/
$rooms = [];
if ($schemaReady) {
    $rooms = $pdo->query("SELECT id, name, password, created_by, created_at FROM rooms ORDER BY created_at DESC")->fetchAll();
} else {
    $error = "⚠ Base non migrée : exécute le fichier init_db.php pour créer les tables.";
}

/* ------------------------------
   Actions : rejoindre / créer / supprimer
--------------------------------*/

if ($schemaReady && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['room_action'])) {

    /* ----- Rejoindre un salon ----- */
    if ($_POST['room_action'] === 'join') {
        $roomId = (int)($_POST['room_id'] ?? 0);
        $roomPassword = $_POST['room_password'] ?? '';

        $stmt = $pdo->prepare("SELECT id, name, password FROM rooms WHERE id = ?");
        $stmt->execute([$roomId]);
        $room = $stmt->fetch();

        if (!$room) {
            $error = "Salon introuvable.";
        } else {
            $needsPassword = !empty($room['password']);

            if ($needsPassword && $roomPassword === '') {
                $error = "Mot de passe requis pour ce salon.";
            } elseif ($needsPassword && !password_verify($roomPassword, $room['password'])) {
                $error = "Mot de passe incorrect.";
            } else {
                $_SESSION['room_id'] = $room['id'];
                $_SESSION['room_name'] = $room['name'];
                header("Location: chat.php");
                exit;
            }
        }
    }

    /* ----- Créer un salon ----- */
    elseif ($_POST['room_action'] === 'create') {

        $roomName = trim($_POST['room_name'] ?? '');
        $roomPassword = $_POST['room_password'] ?? '';

        if ($roomName === '') {
            $error = "Nom de salon requis.";
        } else {
            // Vérifier unicité
            $check = $pdo->prepare("SELECT id FROM rooms WHERE name = ?");
            $check->execute([$roomName]);
            if ($check->fetch()) {
                $error = "Ce nom existe déjà.";
            } else {
                $hash = $roomPassword !== '' ? password_hash($roomPassword, PASSWORD_DEFAULT) : null;
                $insert = $pdo->prepare("INSERT INTO rooms (name, password, created_by) VALUES (?, ?, ?)");
                $insert->execute([$roomName, $hash, $pseudo]);

                $_SESSION['room_id'] = $pdo->lastInsertId();
                $_SESSION['room_name'] = $roomName;

                header("Location: chat.php");
                exit;
            }
        }
    }

    /* ----- Supprimer un salon ----- */
    elseif ($_POST['room_action'] === 'delete') {
        $roomId = (int)($_POST['room_id_delete'] ?? 0);

        $stmt = $pdo->prepare("SELECT id, name, created_by FROM rooms WHERE id = ?");
        $stmt->execute([$roomId]);
        $room = $stmt->fetch();

        $isOwner = $room && $room['created_by'] === $pseudo;
        $isAdmin = strtolower($pseudo) === 'admin';

        if (!$room) {
            $error = "Salon introuvable.";
        } elseif (!$isOwner && !$isAdmin) {
            $error = "Seul le créateur ou admin peut supprimer.";
        } else {
            // Supprimer messages
            $pdo->prepare("DELETE FROM messages WHERE room_id = ?")->execute([$roomId]);
            // Supprimer salon
            $pdo->prepare("DELETE FROM rooms WHERE id = ?")->execute([$roomId]);

            if ($currentRoomId == $roomId) {
                unset($_SESSION['room_id'], $_SESSION['room_name']);
                $currentRoomId = null;
                $currentRoomName = null;
            }

            $info = "Salon \"" . htmlentities($room['name']) . "\" supprimé.";
            header("Location: rooms.php");
            exit;
        }
    }

    // Recharger liste
    $rooms = $pdo->query("SELECT id, name, password, created_by, created_at FROM rooms ORDER BY created_at DESC")->fetchAll();
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-width">
    <title>Salons - MiniChat</title>
    <link rel="stylesheet" href="styles.css">
</head>

<body>
<div class="grid-overlay"></div>
<div class="page">
    <div class="card">

        <!-- Top Bar -->
        <div class="topbar">
            <h1>Salons</h1>
            <div class="right">
                <div class="pill">Connecté : <?= htmlentities($pseudo) ?></div>
                <a class="btn btn-secondary" href="chat.php">Aller au chat</a>
                <a class="btn btn-secondary" href="logout.php">Déconnexion</a>
            </div>
        </div>

        <!-- Messages -->
        <?php if ($error): ?>
            <p class="pill" style="background:#fecdd3; border:1px solid #fda4af;">⚠ <?= htmlentities($error) ?></p>
        <?php elseif ($info): ?>
            <p class="pill" style="background:#bbf7d0; border:1px solid #86efac;"><?= htmlentities($info) ?></p>
        <?php endif; ?>

        <?php if (!$schemaReady): ?>
            <p class="muted">⚠ Base non migrée. Lance <b>init_db.php</b> pour créer les tables.</p>
        <?php else: ?>

        <div class="layout-split">

            <!-- Création de salon -->
            <div class="panel">
                <h2>Créer un salon</h2>
                <form method="post" action="rooms.php">
                    <input type="hidden" name="room_action" value="create">
                    <div class="field">
                        <label>Nom</label>
                        <input name="room_name" required>
                    </div>
                    <div class="field">
                        <label>Mot de passe (optionnel)</label>
                        <input name="room_password" type="password">
                    </div>
                    <button class="btn" type="submit">Créer</button>
                </form>

                <!-- Suppression -->
                <h2 style="margin-top:20px;">Supprimer un salon</h2>
                <?php 
                $deletable = array_filter($rooms, fn($r) => $r['created_by'] === $pseudo || strtolower($pseudo) === 'admin');
                ?>
                <?php if ($deletable): ?>
                    <?php foreach ($deletable as $room): ?>
                        <form method="post" action="rooms.php" class="message-card room-card">
                            <input type="hidden" name="room_action" value="delete">
                            <input type="hidden" name="room_id_delete" value="<?= $room['id'] ?>">
                            <p><b><?= htmlentities($room['name']) ?></b></p>
                            <p>Créateur : <?= htmlentities($room['created_by']) ?></p>
                            <button class="btn btn-danger" onclick="return confirm('Supprimer ce salon ?');">Supprimer</button>
                        </form>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="muted">Aucun salon à supprimer.</p>
                <?php endif; ?>
            </div>

            <!-- Liste des salons -->
            <div class="panel">
                <h2>Salons existants</h2>

                <?php if (!$rooms): ?>
                    <p class="muted">Aucun salon pour le moment.</p>
                <?php else: ?>
                    <?php foreach ($rooms as $room): ?>
                        <?php $isOpen = empty($room['password']); ?>
                        <form class="message-card room-card" method="post" action="rooms.php">
                            <input type="hidden" name="room_action" value="join">
                            <input type="hidden" name="room_id" value="<?= $room['id'] ?>">

                            <p><b><?= htmlentities($room['name']) ?></b></p>
                            <p>Créateur : <?= htmlentities($room['created_by']) ?></p>

                            <?php if (!$isOpen): ?>
                                <input name="room_password" type="password" placeholder="Mot de passe">
                            <?php else: ?>
                                <input type="text" placeholder="Ouvert" disabled>
                            <?php endif; ?>

                            <button class="btn" type="submit">Rejoindre</button>
                        </form>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
