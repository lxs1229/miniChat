<?php
session_start();
require __DIR__ . "/i18n.php";

// Redirection si pas connecté
if (!isset($_SESSION['pseudo'])) {
    header("Location: /index.html?lang=" . urlencode(minichat_lang()) . "&next=" . urlencode("/chat.php"));
    exit;
}

$pseudo = $_SESSION['pseudo'];
$currentRoomId = $_SESSION['room_id'] ?? null;
$currentRoomName = $_SESSION['room_name'] ?? null;

// Rediriger si aucun salon sélectionné
if (!$currentRoomId) {
    header("Location: rooms.php");
    exit;
}

/* ------------------------------
   Connexion PostgreSQL Render
--------------------------------*/
require __DIR__ . "/db.php";

/* ------------------------------
   Charger les messages du salon
--------------------------------*/
$stmt = $pdo->prepare("
    SELECT pseudo, message, time 
    FROM messages 
    WHERE room_id = ?
    ORDER BY num DESC 
    LIMIT 20
");
$stmt->execute([$currentRoomId]);
$messages = array_reverse($stmt->fetchAll());  // plus ancien → plus récent

?>
<!DOCTYPE html>
<html lang="<?= htmlentities(minichat_html_lang()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlentities(t("chat_title", ["room" => $currentRoomName])) ?></title>
    <link rel="stylesheet" href="styles.css">
</head>

<body>
<div class="grid-overlay"></div>
<div class="page">
    <div class="card">

        <!-- Barre supérieure -->
        <div class="topbar">
            <div class="stacked">
                <div class="badge">MiniChat</div>
                <h1><?= htmlentities(t("chat_room", ["room" => $currentRoomName])) ?></h1>
            </div>
            <div class="right">
                <div class="pill"><?= htmlentities(t("connected_as", ["pseudo" => $pseudo])) ?></div>
                <?= render_lang_switcher() ?>
                <a class="btn btn-secondary" href="leaderboard.php"><?= htmlentities(t("nav_leaderboard")) ?></a>
                <a class="btn btn-secondary" href="profile.php"><?= htmlentities(t("nav_profile")) ?></a>
                <a class="btn btn-secondary" href="rooms.php"><?= htmlentities(t("nav_rooms")) ?></a>
                <a class="btn btn-secondary" href="logout.php"><?= htmlentities(t("nav_logout")) ?></a>
            </div>
        </div>

        <!-- Messages -->
        <div class="panel">
            <h2><?= htmlentities(t("chat_messages")) ?></h2>
            <div id="messages" class="messages">
                <?php if (!$messages): ?>
                    <p class="muted"><?= htmlentities(t("chat_none")) ?></p>
                <?php else: ?>
                    <?php foreach ($messages as $m): ?>
                        <div class="message-card">
                            <div class="message-header">
                                <span class="pseudo"><?= htmlentities($m['pseudo']) ?></span>
                                <span class="timestamp"><?= htmlentities($m['time']) ?></span>
                            </div>
                            <p class="message-body"><?= htmlentities($m['message']) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="actions">
                <button class="btn btn-secondary" onclick="loadMessages()"><?= htmlentities(t("chat_refresh")) ?></button>
            </div>
        </div>

        <!-- Envoi message -->
        <div class="panel">
            <h2><?= htmlentities(t("chat_write")) ?></h2>
            <form id="msgForm" method="post" action="send_message.php">
                <textarea name="message" required placeholder="<?= htmlentities(t("chat_placeholder")) ?>"></textarea>
                <button class="btn" type="submit"><?= htmlentities(t("chat_send")) ?></button>
            </form>
        </div>

    </div>
</div>

<script>
// Auto refresh
function loadMessages() {
    fetch("load_messages.php")
        .then(r => r.text())
        .then(html => {
            document.getElementById("messages").innerHTML = html;
        });
}

// Enter to send (Shift+Enter for newline)
const msgForm = document.getElementById("msgForm");
const msgTextarea = msgForm?.querySelector("textarea[name='message']");
let isComposing = false;
msgTextarea?.addEventListener("compositionstart", () => (isComposing = true));
msgTextarea?.addEventListener("compositionend", () => (isComposing = false));
msgTextarea?.addEventListener("keydown", (e) => {
    if (e.key !== "Enter") return;
    if (e.shiftKey) return;
    if (isComposing) return;
    e.preventDefault();
    msgForm.requestSubmit();
});

// Auto refresh toutes les 2s
setInterval(loadMessages, 2000);
</script>

</body>
</html>
