<?php
session_start();
// Vérification de login
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

$error = null;
$info = null;
$currentRoomId = $_SESSION['room_id'] ?? null;
$currentRoomName = $_SESSION['room_name'] ?? null;

// Vérifier que le schéma DB a bien les colonnes/tables nécessaires
function schemaReady(PDO $pdo): bool {
    $hasRooms = $pdo->query("SHOW TABLES LIKE 'rooms'");
    $hasRoomColumn = $pdo->query("SHOW COLUMNS FROM message LIKE 'room_id'");
    return $hasRooms && $hasRooms->rowCount() > 0 && $hasRoomColumn && $hasRoomColumn->rowCount() > 0;
}

$schemaReady = schemaReady($pdo);
$rooms = [];
if ($schemaReady) {
    $rooms = $pdo->query("SELECT id, name, password, created_by FROM rooms ORDER BY created_at DESC")->fetchAll();
} else {
    $error = "Base non migrée : exécute le SQL d'ajout de rooms et de la colonne room_id (voir create_table_miniChat.sql).";
    $currentRoomId = null;
    $currentRoomName = null;
}

// Actions sur les salons (rejoindre / créer / supprimer)
if ($schemaReady && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['room_action'])) {
    if ($_POST['room_action'] === 'join') {
        $roomId = (int)($_POST['room_id'] ?? 0);
        $roomPassword = $_POST['room_password'] ?? '';

        if (!$roomId) {
            $error = "Choisis un salon.";
        } else {
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
                    $error = "Mot de passe du salon incorrect.";
                } else {
                    $_SESSION['room_id'] = $room['id'];
                    $_SESSION['room_name'] = $room['name'];
                    header("Location: chat.php");
                    exit;
                }
            }
        }
    } elseif ($_POST['room_action'] === 'create') {
        $roomName = trim($_POST['room_name'] ?? '');
        $roomPassword = $_POST['room_password'] ?? '';
        if ($roomName === '') {
            $error = "Nom du salon requis pour créer.";
        } else {
            $check = $pdo->prepare("SELECT id FROM rooms WHERE name = ?");
            $check->execute([$roomName]);
            if ($check->fetch()) {
                $error = "Ce nom de salon existe déjà.";
            } else {
                $hash = $roomPassword !== '' ? password_hash($roomPassword, PASSWORD_DEFAULT) : null;
                $insert = $pdo->prepare("INSERT INTO rooms (name, password, created_by) VALUES (?, ?, ?)");
                $insert->execute([$roomName, $hash, $pseudo]);
                $_SESSION['room_id'] = $pdo->lastInsertId();
                $_SESSION['room_name'] = $roomName;
                $info = "Salon \"$roomName\" créé.";
                header("Location: chat.php");
                exit;
            }
        }
    } elseif ($_POST['room_action'] === 'delete') {
        $roomId = (int)($_POST['room_id_delete'] ?? 0);
        if (!$roomId) {
            $error = "Choisis un salon à supprimer.";
        } else {
            $stmt = $pdo->prepare("SELECT id, name, created_by FROM rooms WHERE id = ?");
            $stmt->execute([$roomId]);
            $room = $stmt->fetch();
            $isOwner = $room && $room['created_by'] === $pseudo;
            $isAdmin = strtolower($pseudo) === 'admin';
            if (!$room) {
                $error = "Salon introuvable pour suppression.";
            } elseif (!$isOwner && !$isAdmin) {
                $error = "Seul le créateur ou un admin peut supprimer ce salon.";
            } else {
                $pdo->prepare("DELETE FROM message WHERE room_id = ?")->execute([$roomId]);
                $pdo->prepare("DELETE FROM rooms WHERE id = ?")->execute([$roomId]);
                if ($currentRoomId == $roomId) {
                    unset($_SESSION['room_id'], $_SESSION['room_name']);
                    $currentRoomId = null;
                    $currentRoomName = null;
                }
                $info = "Salon \"" . htmlentities($room['name']) . "\" supprimé.";
                header("Location: chat.php");
                exit;
            }
        }
    }
}

// Charger messages du salon courant
$messages = [];
if ($schemaReady && $currentRoomId) {
    $stmt = $pdo->prepare("
        SELECT pseudo, mesage, time 
        FROM message 
        WHERE room_id = ?
        ORDER BY num DESC 
        LIMIT 10
    ");
    $stmt->execute([$currentRoomId]);
    $messages = $stmt->fetchAll();
}

// Si pas de salon sélectionné, redirige vers la page de gestion des salons
if ($schemaReady && !$currentRoomId) {
    header("Location: rooms.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mini Chat</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body data-has-room="<?= $currentRoomId ? '1' : '0' ?>">

<div class="grid-overlay" aria-hidden="true"></div>
<div class="page">
    <div class="card">
        <div class="topbar">
            <div class="stacked">
                <div class="badge">MiniChat • Espaces privés</div>
                <h1><?= $currentRoomName ? "Salon : " . htmlentities($currentRoomName) : "Choisis un salon" ?></h1>
                <p class="helper">Sélectionne un salon existant, entre le mot de passe, et rejoins la conversation.</p>
            </div>
            <div class="right">
                <div class="pill">Connecté : <?= htmlentities($pseudo) ?></div>
                <a class="btn btn-secondary" href="ai.php">Assistant IA</a>
                <a class="btn btn-secondary" href="logout.php">Déconnexion</a>
            </div>
        </div>

        <?php if ($error): ?>
            <p class="pill" style="background: rgba(255,83,112,0.12); border:1px solid rgba(255,83,112,0.4); color:#fecdd3;">⚠ <?= htmlentities($error) ?></p>
        <?php elseif ($info): ?>
            <p class="pill" style="background: rgba(16,185,129,0.16); border:1px solid rgba(16,185,129,0.35); color:#bbf7d0;">✅ <?= htmlentities($info) ?></p>
        <?php endif; ?>

        <div class="panel">
            <div class="label-row">
                <span>Salon actuel</span>
                <span class="tag">Gérer les salons sur la page dédiée</span>
            </div>
            <p class="message-body">Tu es dans le salon <strong><?= htmlentities($currentRoomName) ?></strong>. Pour changer de salon, créer ou supprimer, passe par la page des salons.</p>
            <div class="actions">
                <a class="btn btn-secondary" href="rooms.php">Gérer les salons</a>
            </div>
        </div>

        <div class="panel" style="margin-top:14px;">
            <div class="label-row">
                <span>Flux en direct</span>
                <span class="tag"><?= $currentRoomName ? "Salon " . htmlentities($currentRoomName) : "Sélectionne un salon" ?></span>
            </div>
            <div id="messages" class="messages">
                <?php if (!$messages): ?>
                    <p class="muted">Aucun message pour l'instant. Lance la conversation !</p>
                <?php else: ?>
                    <?php foreach ($messages as $m): ?>
                        <div class="message-card">
                            <div class="message-header">
                                <span class="pseudo"><?= htmlentities($m['pseudo']) ?></span>
                                <span class="timestamp"><?= htmlentities($m['time']) ?></span>
                            </div>
                            <p class="message-body"><?= htmlentities($m['mesage']) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="panel" style="margin-top:14px;">
            <div class="label-row">
                <span>Écrire un message</span>
                <span class="tag"><?= $currentRoomName ? "Ctrl + Entrée pour envoyer" : "Sélectionne un salon" ?></span>
            </div>
            <form id="messageForm" class="stacked" method="post" action="send_message.php">
                <textarea id="message" name="message" placeholder="Partage une idée, une mise à jour, un emoji..." <?= $currentRoomId ? 'required' : 'disabled' ?>></textarea>
                <div class="actions">
                    <button class="btn" type="submit" <?= $currentRoomId ? '' : 'disabled' ?>>Envoyer</button>
                    <button class="btn btn-secondary" type="button" onclick="loadMessages()">Actualiser</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const messagesEl = document.getElementById("messages");
    const form = document.getElementById("messageForm");
    const textarea = document.getElementById("message");
    const hasRoom = document.body.dataset.hasRoom === "1";

    function scrollToBottom() {
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    function loadMessages() {
        if (!hasRoom) return;
        fetch("load_messages.php")
            .then(response => response.text())
            .then(html => {
                messagesEl.innerHTML = html;
                scrollToBottom();
            })
            .catch(() => {
                messagesEl.insertAdjacentHTML("afterbegin", "<p class='muted'>Impossible de charger les messages.</p>");
            });
    }

    // Auto refresh toutes les 2s
    if (hasRoom) {
        setInterval(loadMessages, 2000);
        loadMessages();
        scrollToBottom();
    }

    // Ctrl + Entrée pour envoyer
    if (hasRoom) {
        form.addEventListener("keydown", (event) => {
            if (event.key === "Enter" && event.ctrlKey) {
                form.submit();
            }
        });
    }

    // Focus textarea au chargement
    if (textarea) {
        textarea.focus();
    }
</script>

</body>
</html>
