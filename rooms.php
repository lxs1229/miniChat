<?php
session_start();
if (!isset($_SESSION['pseudo'])) {
    header("Location: index.html");
    exit;
}

$pseudo = $_SESSION['pseudo'];
$currentRoomId = $_SESSION['room_id'] ?? null;
$currentRoomName = $_SESSION['room_name'] ?? null;

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
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("Erreur connexion PostgreSQL : " . $e->getMessage());
}

/* ------------------------------
   Vérifier que les tables existent
--------------------------------*/
function schemaReady(PDO $pdo): bool {
    $t1 = $pdo->query("SELECT to_regclass('public.rooms')")->fetchColumn();
    $t2 = $pdo->query("SELECT to_regclass('public.messages')")->fetchColumn();
    return $t1 !== null && $t2 !== null;
}

$schemaReady = schemaReady($pdo);
$error = null;
$info = null;

/* ------------------------------
   Charger les salons
--------------------------------*/
$rooms = [];
if ($schemaReady) {
    $rooms = $pdo->query("SELECT id, name, password, created_by, created_at FROM rooms ORDER BY created_at DESC")->fetchAll();
} else {
    $error = "⚠ Base non migrée. Lance init_db.php pour créer les tables.";
}

/* ------------------------------
   Gestion des actions (join/create/delete)
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
        } elseif (!empty($room['password']) && !password_verify($roomPassword, $room['password'])) {
            $error = "Mot de passe incorrect.";
        } else {
            $_SESSION['room_id'] = $room['id'];
            $_SESSION['room_name'] = $room['name'];
            header("Location: chat.php");
            exit;
        }
    }

    /* ----- Créer un salon ----- */
    elseif ($_POST['room_action'] === 'create') {
        $roomName = trim($_POST['room_name'] ?? '');
        $roomPassword = $_POST['room_password'] ?? '';

        if ($roomName === '') {
            $error = "Nom du salon requis.";
        } else {
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
            $pdo->prepare("DELETE FROM messages WHERE room_id = ?")->execute([$roomId]);
            $pdo->prepare("DELETE FROM rooms WHERE id = ?")->execute([$roomId]);

            if ($currentRoomId == $roomId) {
                unset($_SESSION['room_id'], $_SESSION['room_name']);
            }

            $info = "Salon \"" . htmlentities($room['name']) . "\" supprimé.";
            header("Location: rooms.php");
            exit;
        }
    }

    // Reload rooms list
    $rooms = $pdo->query("SELECT id, name, password, created_by, created_at FROM rooms ORDER BY created_at DESC")->fetchAll();
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-width">
    <title>Salons • MiniChat</title>
    <link rel="stylesheet" href="styles.css">
</head>

<body>
<div class="grid-overlay"></div>
<div class="page">
    <div class="card">

        <!-- Top bar -->
        <div class="topbar">
            <div class="stacked">
                <div class="badge">MiniChat • Salons</div>
                <h1>Gérer les salons</h1>
            </div>

            <div class="right">
                <div class="pill">Connecté : <?= htmlentities($pseudo) ?></div>

                <!-- ⭐ TON BOUTON IA EST JUSTE ICI -->
                <a class="btn btn-secondary" href="ai.php">Assistant IA</a>

                <a class="btn btn-secondary" href="chat.php">Aller au chat</a>
                <a class="btn btn-secondary" href="logout.php">Déconnexion</a>
            </div>
        </div>

        <!-- Messages et erreurs -->
        <?php if ($error): ?>
            <p class="pill" style="background:#fecdd3;border:1px solid #fda4af;">⚠ <?= htmlentities($error) ?></p>
        <?php elseif ($info): ?>
            <p class="pill" style="background:#bbf7d0;border:1px solid #86efac;"><?= htmlentities($info) ?></p>
        <?php endif; ?>

        <?php if (!$schemaReady): ?>
            <p class="muted">⚠ Base non migrée. Lance init_db.php.</p>
        <?php else: ?>

        <div class="layout-split">

            <!-- Création -->
            <div class="panel">
                <h2>Créer un salon</h2>
                <form method="post">
                    <input type="hidden" name="room_action" value="create">

                    <div class="field">
                        <label>Nom du salon</label>
                        <input name="room_name" required>
                    </div>

                    <div class="field">
                        <label>Mot de passe (optionnel)</label>
                        <input name="room_password" type="password">
                    </div>

                    <button class="btn">Créer</button>
                </form>

                <!-- Suppression -->
                <h2 style="margin-top:20px;">Supprimer un salon</h2>
                <?php
                $deletable = array_filter($rooms, fn($r) => $r['created_by'] === $pseudo || strtolower($pseudo) === 'admin');
                ?>
                <?php if (!$deletable): ?>
                    <p class="muted">Aucun salon à supprimer.</p>
                <?php else: ?>
                    <?php foreach ($deletable as $room): ?>
                        <form class="message-card room-card" method="post">
                            <input type="hidden" name="room_action" value="delete">
                            <input type="hidden" name="room_id_delete" value="<?= $room['id'] ?>">

                            <p><b><?= htmlentities($room['name']) ?></b></p>
                            <p>Créateur : <?= htmlentities($room['created_by']) ?></p>

                            <button class="btn btn-danger" onclick="return confirm('Supprimer ce salon ?');">
                                Supprimer
                            </button>
                        </form>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Liste des salons -->
            <div class="panel">
                <h2>Salons existants</h2>

                <?php if (!$rooms): ?>
                    <p class="muted">Aucun salon disponible.</p>
                <?php else: ?>
                    <?php foreach ($rooms as $room): ?>
                        <?php $isOpen = empty($room['password']); ?>
                        <form class="message-card room-card" method="post">
                            <input type="hidden" name="room_action" value="join">
                            <input type="hidden" name="room_id" value="<?= $room['id'] ?>">

                            <p><b><?= htmlentities($room['name']) ?></b></p>
                            <p>Créateur : <?= htmlentities($room['created_by']) ?></p>

                            <?php if (!$isOpen): ?>
                                <input name="room_password" type="password" placeholder="Mot de passe">
                            <?php else: ?>
                                <input type="text" placeholder="Ouvert" disabled>
                            <?php endif; ?>

                            <button class="btn">Rejoindre</button>
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
