<?php
session_start();

// Vérifier session
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

// Récupérer message brut (ne pas htmlentities ici, on fera à l'affichage)
$message = trim($_POST['message'] ?? '');
if ($message === '') {
    header("Location: chat.php");
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
    die("Erreur PostgreSQL : " . $e->getMessage());
}

/* ------------------------------
   Vérifier existence table messages
--------------------------------*/
$tableExists = $pdo->query("SELECT to_regclass('public.messages')")->fetchColumn();
if ($tableExists === null) {
    die("⚠ Table messages absente. Lance init_db.php pour créer la base.");
}

/* ------------------------------
   Insérer message
--------------------------------*/
$stmt = $pdo->prepare("
    INSERT INTO messages (pseudo, message, room_id)
    VALUES (?, ?, ?)
");
$stmt->execute([$pseudo, $message, $roomId]);

/* ------------------------------
   Redirection retour au chat
--------------------------------*/
header("Location: chat.php");
exit;
?>
