<?php
session_start();
require __DIR__ . "/i18n.php";
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
require __DIR__ . "/db.php";

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
    $error = t("error_schema_not_ready");
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
            $error = t("error_room_not_found");
        } elseif (!empty($room['password']) && !password_verify($roomPassword, $room['password'])) {
            $error = t("error_password_incorrect");
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
            $error = t("error_room_name_required");
        } else {
            $check = $pdo->prepare("SELECT id FROM rooms WHERE name = ?");
            $check->execute([$roomName]);
            if ($check->fetch()) {
                $error = t("error_room_name_exists");
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
            $error = t("error_room_not_found");
        } elseif (!$isOwner && !$isAdmin) {
            $error = t("error_delete_forbidden");
        } else {
            $pdo->prepare("DELETE FROM messages WHERE room_id = ?")->execute([$roomId]);
            $pdo->prepare("DELETE FROM rooms WHERE id = ?")->execute([$roomId]);

            if ($currentRoomId == $roomId) {
                unset($_SESSION['room_id'], $_SESSION['room_name']);
            }

            $info = t("rooms_delete") . " : \"" . htmlentities($room['name']) . "\"";
            header("Location: rooms.php");
            exit;
        }
    }

    // Reload rooms list
    $rooms = $pdo->query("SELECT id, name, password, created_by, created_at FROM rooms ORDER BY created_at DESC")->fetchAll();
}

?>
<!DOCTYPE html>
<html lang="<?= htmlentities(minichat_html_lang()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-width">
    <title><?= htmlentities(t("rooms_title")) ?> • MiniChat</title>
    <link rel="stylesheet" href="styles.css">
</head>

<body>
<div class="grid-overlay"></div>
<div class="page">
    <div class="card">

        <!-- Top bar -->
        <div class="topbar">
            <div class="stacked">
                <div class="badge"><?= htmlentities(t("rooms_badge")) ?></div>
                <h1><?= htmlentities(t("rooms_manage")) ?></h1>
            </div>

            <div class="right">
                <div class="pill"><?= htmlentities(t("connected_as", ["pseudo" => $pseudo])) ?></div>
                <?= render_lang_switcher() ?>

                <!-- ⭐ TON BOUTON IA EST JUSTE ICI -->
                <a class="btn btn-secondary" href="ai.php"><?= htmlentities(t("nav_ai")) ?></a>

                <a class="btn btn-secondary" href="chat.php"><?= htmlentities(t("nav_chat")) ?></a>
                <a class="btn btn-secondary" href="logout.php"><?= htmlentities(t("nav_logout")) ?></a>
            </div>
        </div>

        <!-- Messages et erreurs -->
        <?php if ($error): ?>
            <p class="pill" style="background:#fecdd3;border:1px solid #fda4af;">⚠ <?= htmlentities($error) ?></p>
        <?php elseif ($info): ?>
            <p class="pill" style="background:#bbf7d0;border:1px solid #86efac;"><?= htmlentities($info) ?></p>
        <?php endif; ?>

        <?php if (!$schemaReady): ?>
            <p class="muted"><?= htmlentities(t("error_schema_not_ready")) ?></p>
        <?php else: ?>

        <div class="layout-split">

            <!-- Création -->
            <div class="panel">
                <h2><?= htmlentities(t("rooms_create")) ?></h2>
                <form method="post">
                    <input type="hidden" name="room_action" value="create">

                    <div class="field">
                        <label><?= htmlentities(t("rooms_name")) ?></label>
                        <input name="room_name" required>
                    </div>

                    <div class="field">
                        <label><?= htmlentities(t("rooms_password_optional")) ?></label>
                        <input name="room_password" type="password">
                    </div>

                    <button class="btn"><?= htmlentities(t("rooms_create_btn")) ?></button>
                </form>

                <!-- Suppression -->
                <h2 style="margin-top:20px;"><?= htmlentities(t("rooms_delete")) ?></h2>
                <?php
                $deletable = array_filter($rooms, fn($r) => $r['created_by'] === $pseudo || strtolower($pseudo) === 'admin');
                ?>
                <?php if (!$deletable): ?>
                    <p class="muted"><?= htmlentities(t("rooms_none_deletable")) ?></p>
                <?php else: ?>
                    <?php foreach ($deletable as $room): ?>
                        <form class="message-card room-card" method="post">
                            <input type="hidden" name="room_action" value="delete">
                            <input type="hidden" name="room_id_delete" value="<?= $room['id'] ?>">

                            <p><b><?= htmlentities($room['name']) ?></b></p>
                            <p><?= htmlentities(t("rooms_creator", ["creator" => $room['created_by']])) ?></p>

                            <button class="btn btn-danger" onclick="return confirm(<?= json_encode(t("confirm_delete_room")) ?>);">
                                <?= htmlentities(t("rooms_delete_btn")) ?>
                            </button>
                        </form>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Liste des salons -->
            <div class="panel">
                <h2><?= htmlentities(t("rooms_existing")) ?></h2>

                <?php if (!$rooms): ?>
                    <p class="muted"><?= htmlentities(t("rooms_none")) ?></p>
                <?php else: ?>
                    <?php foreach ($rooms as $room): ?>
                        <?php $isOpen = empty($room['password']); ?>
                        <form class="message-card room-card" method="post">
                            <input type="hidden" name="room_action" value="join">
                            <input type="hidden" name="room_id" value="<?= $room['id'] ?>">

                            <p><b><?= htmlentities($room['name']) ?></b></p>
                            <p><?= htmlentities(t("rooms_creator", ["creator" => $room['created_by']])) ?></p>

                            <?php if (!$isOpen): ?>
                                <input name="room_password" type="password" placeholder="<?= htmlentities(t("rooms_password")) ?>">
                            <?php else: ?>
                                <input type="text" placeholder="<?= htmlentities(t("rooms_open")) ?>" disabled>
                            <?php endif; ?>

                            <button class="btn"><?= htmlentities(t("rooms_join")) ?></button>
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
