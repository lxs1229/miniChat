<?php
session_start();
if (!isset($_SESSION['pseudo'])) {
    header("Location: index.html");
    exit;
}

$pseudo = $_SESSION['pseudo'];
$dsn = getenv("DATABASE_URL");
if (!$dsn) {
    die("DATABASE_URL manquant pour la connexion PDO.");
}
$pdo = new PDO($dsn);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$error = null;
$info = null;
$currentRoomId = $_SESSION['room_id'] ?? null;
$currentRoomName = $_SESSION['room_name'] ?? null;

function schemaReady(PDO $pdo): bool {
    $hasRooms = $pdo->query("SHOW TABLES LIKE 'rooms'");
    $hasRoomColumn = $pdo->query("SHOW COLUMNS FROM message LIKE 'room_id'");
    return $hasRooms && $hasRooms->rowCount() > 0 && $hasRoomColumn && $hasRoomColumn->rowCount() > 0;
}

$schemaReady = schemaReady($pdo);
$rooms = [];
if ($schemaReady) {
    $rooms = $pdo->query("SELECT id, name, password, created_by, created_at FROM rooms ORDER BY created_at DESC")->fetchAll();
} else {
    $error = "Base non migrée : exécute le SQL d'ajout de rooms et de room_id (voir create_table_miniChat.sql).";
}

if ($schemaReady && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['room_action'])) {
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
    } elseif ($_POST['room_action'] === 'create') {
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
    } elseif ($_POST['room_action'] === 'delete') {
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
            $pdo->prepare("DELETE FROM message WHERE room_id = ?")->execute([$roomId]);
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

    // refresh list after action
    $rooms = $pdo->query("SELECT id, name, password, created_by, created_at FROM rooms ORDER BY created_at DESC")->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Salons • MiniChat</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="grid-overlay" aria-hidden="true"></div>
<div class="page">
    <div class="card">
        <div class="topbar">
            <div class="stacked">
                <div class="badge">MiniChat • Salons</div>
                <h1>Gérer tes salons</h1>
                <p class="helper">Choisis, crée ou supprime un salon. Rejoins le chat ensuite.</p>
            </div>
            <div class="right">
                <?php if ($currentRoomName): ?>
                    <div class="pill">Salon actuel : <?= htmlentities($currentRoomName) ?></div>
                <?php endif; ?>
                <div class="pill">Connecté : <?= htmlentities($pseudo) ?></div>
                <a class="btn btn-secondary" href="chat.php">Aller au chat</a>
                <a class="btn btn-secondary" href="ai.php">Assistant IA</a>
                <a class="btn btn-secondary" href="logout.php">Déconnexion</a>
            </div>
        </div>

        <?php if ($error): ?>
            <p class="pill" style="background: rgba(255,83,112,0.12); border:1px solid rgba(255,83,112,0.4); color:#fecdd3;">⚠ <?= htmlentities($error) ?></p>
        <?php elseif ($info): ?>
            <p class="pill" style="background: rgba(16,185,129,0.16); border:1px solid rgba(16,185,129,0.35); color:#bbf7d0;">✅ <?= htmlentities($info) ?></p>
        <?php endif; ?>

        <?php if (!$schemaReady): ?>
            <p class="muted">Base non migrée : ajoute la table rooms et la colonne room_id (voir create_table_miniChat.sql).</p>
        <?php else: ?>
            <div class="layout-split">
                <div class="panel">
                    <div class="label-row">
                        <span>Créer un salon</span>
                        <span class="tag">Nom + mot de passe (optionnel)</span>
                    </div>
                    <form class="stacked" method="post" action="rooms.php">
                        <input type="hidden" name="room_action" value="create">
                        <div class="field">
                            <div class="label-row">
                                <label for="room_name_new">Nom du salon</label>
                                <span class="muted">Unique</span>
                            </div>
                            <input id="room_name_new" name="room_name" placeholder="ex: Projet-Aurora" required>
                        </div>
                        <div class="field">
                            <div class="label-row">
                                <label for="room_password_new">Mot de passe</label>
                                <span class="muted">Laisse vide pour un salon ouvert</span>
                            </div>
                            <input id="room_password_new" name="room_password" type="password" placeholder="(optionnel)">
                        </div>
                        <div class="actions">
                            <button class="btn" type="submit">Créer et entrer</button>
                        </div>
                    </form>

                    <div class="label-row" style="margin-top:12px;">
                        <span>Supprimer un salon</span>
                        <span class="tag">Créateur ou admin</span>
                    </div>
                    <?php
                    $deletableRooms = array_filter($rooms, function($room) use ($pseudo) {
                        return $room['created_by'] === $pseudo || strtolower($pseudo) === 'admin';
                    });
                    ?>
                    <?php if (!$deletableRooms): ?>
                        <p class="muted">Aucun salon dont tu es propriétaire à supprimer.</p>
                    <?php else: ?>
                        <div class="room-grid">
                            <?php foreach ($deletableRooms as $room): ?>
                                <form class="message-card room-card danger" method="post" action="rooms.php" onsubmit="return confirm('Supprimer ce salon ? Ses messages seront perdus.');">
                                    <input type="hidden" name="room_action" value="delete">
                                    <input type="hidden" name="room_id_delete" value="<?= $room['id'] ?>">
                                    <div class="message-header">
                                        <span class="pseudo"><?= htmlentities($room['name']) ?></span>
                                        <span class="timestamp"><?= htmlentities($room['created_at']) ?></span>
                                    </div>
                                    <p class="message-body">Supprime le salon et tous ses messages.</p>
                                    <div class="actions">
                                        <button class="btn btn-danger" type="submit">Supprimer</button>
                                    </div>
                                </form>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="panel">
                    <div class="label-row">
                        <span>Salons existants</span>
                        <span class="tag">Cliquer pour rejoindre</span>
                    </div>
                    <div class="field">
                        <div class="label-row">
                            <label for="room_search">Filtrer</label>
                            <span class="muted">Tape pour chercher</span>
                        </div>
                        <input id="room_search" type="text" placeholder="ex: Equipe, Projet..." oninput="filterRoomCards()">
                    </div>
                    <div id="room_list" class="room-grid" style="margin-top:10px;">
                        <?php if (!$rooms): ?>
                            <p class="muted">Aucun salon pour le moment.</p>
                        <?php else: ?>
                            <?php foreach ($rooms as $room): ?>
                                <?php $isOpen = empty($room['password']); ?>
                                <form class="message-card room-card" method="post" action="rooms.php">
                                    <input type="hidden" name="room_action" value="join">
                                    <input type="hidden" name="room_id" value="<?= $room['id'] ?>">
                                    <div class="message-header">
                                        <span class="pseudo"><?= htmlentities($room['name']) ?></span>
                                        <span class="timestamp"><?= $isOpen ? "Ouvert" : "Privé" ?></span>
                                    </div>
                                    <p class="message-body">Créateur : <?= htmlentities($room['created_by'] ?? '—') ?></p>
                                    <div class="stacked" style="margin-top:8px;">
                                        <?php if ($isOpen): ?>
                                            <input name="room_password" type="password" placeholder="Pas de mot de passe" disabled>
                                        <?php else: ?>
                                            <input name="room_password" type="password" placeholder="Mot de passe requis">
                                        <?php endif; ?>
                                        <div class="actions">
                                            <button class="btn" type="submit">Rejoindre</button>
                                        </div>
                                    </div>
                                </form>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    const roomCards = document.querySelectorAll(".room-card");
    const roomSearch = document.getElementById("room_search");
    function filterRoomCards() {
        if (!roomSearch || !roomCards.length) return;
        const term = roomSearch.value.toLowerCase();
        roomCards.forEach(card => {
            const title = card.querySelector(".pseudo")?.textContent.toLowerCase() || "";
            card.style.display = title.includes(term) ? "" : "none";
        });
    }
    window.filterRoomCards = filterRoomCards;
</script>
</body>
</html>
