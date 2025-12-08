<?php
session_start();
if (!isset($_SESSION['pseudo']) || !isset($_SESSION['room_id'])) {
    echo "<p class='muted'>Sélectionne d'abord un salon.</p>";
    exit;
}

$roomId = $_SESSION['room_id'];

$pdo = new PDO("mysql:host=localhost;dbname=miniChat_db;charset=utf8", "root", "20021229");

// Vérifier colonne room_id
$hasRoomColumn = $pdo->query("SHOW COLUMNS FROM message LIKE 'room_id'");
if (!$hasRoomColumn || $hasRoomColumn->rowCount() === 0) {
    echo "<p class='muted'>Base non migrée : ajoute la colonne room_id sur message.</p>";
    exit;
}

$stmt = $pdo->prepare("
    SELECT pseudo, mesage, time
    FROM message
    WHERE room_id = ?
    ORDER BY num DESC
    LIMIT 10
");
$stmt->execute([$roomId]);

foreach ($stmt as $m) {
    echo '<div class="message-card">';
    echo '  <div class="message-header">';
    echo '      <span class="pseudo">' . htmlentities($m['pseudo']) . '</span>';
    echo '      <span class="timestamp">' . htmlentities($m['time']) . '</span>';
    echo '  </div>';
    echo '  <p class="message-body">' . htmlentities($m['mesage']) . '</p>';
    echo '</div>';
}
