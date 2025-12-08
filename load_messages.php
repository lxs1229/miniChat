<?php
session_start();

// Vérifier session
if (!isset($_SESSION['pseudo']) || !isset($_SESSION['room_id'])) {
    echo "<p class='muted'>Sélectionne d'abord un salon.</p>";
    exit;
}

$roomId = $_SESSION['room_id'];

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
    echo "<p class='muted'>Erreur PostgreSQL : " . htmlentities($e->getMessage()) . "</p>";
    exit;
}

/* ------------------------------
   Vérifier existence table messages
--------------------------------*/
$tableExists = $pdo->query("SELECT to_regclass('public.messages')")->fetchColumn();
if ($tableExists === null) {
    echo "<p class='muted'>⚠ Table messages absente. Lance init_db.php.</p>";
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
    echo "<p class='muted'>Aucun message pour l'instant.</p>";
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
