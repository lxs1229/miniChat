<?php
session_start();

// Redirection si pas connecté
if (!isset($_SESSION['pseudo'])) {
    header("Location: index.html");
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
$databaseUrl = getenv("DATABASE_URL");
if (!$databaseUrl) {
    die("DATABASE_URL manquant.");
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
    die("Connexion PostgreSQL échouée : " . $e->getMessage());
}

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
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MiniChat - Salon <?= htmlentities($currentRoomName) ?></title>
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
                <h1>Salon : <?= htmlentities($currentRoomName) ?></h1>
            </div>
            <div class="right">
                <div class="pill">Connecté : <?= htmlentities($pseudo) ?></div>
                <a class="btn btn-secondary" href="rooms.php">Changer de salon</a>
                <a class="btn btn-secondary" href="logout.php">Déconnexion</a>
            </div>
        </div>

        <!-- Messages -->
        <div class="panel">
            <h2>Messages</h2>
            <div id="messages" class="messages">
                <?php if (!$messages): ?>
                    <p class="muted">Aucun message pour l'instant.</p>
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
                <button class="btn btn-secondary" onclick="loadMessages()">Actualiser</button>
            </div>
        </div>

        <!-- Envoi message -->
        <div class="panel">
            <h2>Écrire un message</h2>
            <form id="msgForm" method="post" action="send_message.php">
                <textarea name="message" required placeholder="Tape ton message..."></textarea>
                <button class="btn" type="submit">Envoyer</button>
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

// Auto refresh toutes les 2s
setInterval(loadMessages, 2000);
</script>

</body>
</html>
