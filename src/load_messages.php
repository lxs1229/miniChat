<?php
session_start();
require __DIR__ . "/i18n.php";

// Vérifier session
if (!isset($_SESSION['pseudo']) || !isset($_SESSION['room_id'])) {
    echo "<p class='muted'>" . htmlentities(t("loadmsg_pick_room")) . "</p>";
    exit;
}

$roomId = $_SESSION['room_id'];

require __DIR__ . "/db.php";

/* ------------------------------
   Vérifier existence table messages
--------------------------------*/
$tableExists = $pdo->query("SELECT to_regclass('public.messages')")->fetchColumn();
if ($tableExists === null) {
    echo "<p class='muted'>" . htmlentities(t("loadmsg_table_missing")) . "</p>";
    exit;
}

/* ------------------------------
   Charger les messages
--------------------------------*/
$stmt = $pdo->prepare("
    SELECT pseudo, message, time
    FROM messages
    WHERE room_id = ?
    ORDER BY num DESC
    LIMIT 20
");
$stmt->execute([$roomId]);

$rows = array_reverse($stmt->fetchAll()); // ordre chronologique

/* ------------------------------
   Affichage HTML
--------------------------------*/
if (!$rows) {
    echo "<p class='muted'>" . htmlentities(t("loadmsg_none")) . "</p>";
    exit;
}

foreach ($rows as $m) {
    echo '<div class="message-card">';
    echo '  <div class="message-header">';
    echo '      <span class="pseudo">' . htmlentities($m['pseudo']) . '</span>';
    echo '      <span class="timestamp">' . htmlentities($m['time']) . '</span>';
    echo '  </div>';
    echo '  <p class="message-body">' . htmlentities($m['message']) . '</p>';
    echo '</div>';
}
