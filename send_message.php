<?php
session_start();

if (!isset($_SESSION['pseudo'])) {
    header("Location: index.html");
    exit;
}

$roomId = $_SESSION['room_id'] ?? null;
if (!$roomId) {
    header("Location: chat.php");
    exit;
}

$pseudo = $_SESSION['pseudo'];
$message = htmlentities($_POST['message'], ENT_QUOTES, 'UTF-8');

// Connexion DB
$pdo = new PDO("mysql:host=localhost;dbname=miniChat_db;charset=utf8", "root", "20021229");

// Vérifier colonne room_id
$hasRoomColumn = $pdo->query("SHOW COLUMNS FROM message LIKE 'room_id'");
if (!$hasRoomColumn || $hasRoomColumn->rowCount() === 0) {
    die("Base non migrée : ajoute la colonne room_id sur message.");
}

// Insérer le message
$stmt = $pdo->prepare("INSERT INTO message (pseudo, mesage, room_id) VALUES (?, ?, ?)");
$stmt->execute([$pseudo, $message, $roomId]);

// Retour au chat
header("Location: chat.php");
exit;
?>
