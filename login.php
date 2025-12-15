<?php
session_start();

/* ======================================================
   1) Récupération IP client (Cloudflare / Render OK)
====================================================== */
function getClientIp(): string {
    $keys = [
        'HTTP_CF_CONNECTING_IP', // Cloudflare
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_REAL_IP',
        'REMOTE_ADDR',
    ];

    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = explode(',', $_SERVER[$key])[0];
            $ip = trim($ip);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return substr($ip, 0, 45);
            }
        }
    }
    return '0.0.0.0';
}

$ip = getClientIp();

/* ======================================================
   2) Connexion PostgreSQL (Render)
====================================================== */
$databaseUrl = getenv("DATABASE_URL");
if (!$databaseUrl) {
    die("DATABASE_URL manquant.");
}

$parts = parse_url($databaseUrl);
$host = $parts['host'] ?? 'localhost';
$port = $parts['port'] ?? 5432;
$user = $parts['user'] ?? '';
$pass = $parts['pass'] ?? '';
$db   = ltrim($parts['path'] ?? '', '/');

$dsn = "pgsql:host={$host};port={$port};dbname={$db}";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Erreur connexion PostgreSQL : " . $e->getMessage());
}

/* ======================================================
   3) Récupération POST
====================================================== */
$pseudo = trim($_POST['pseudo'] ?? '');
$mdp    = $_POST['mdp'] ?? '';

if ($pseudo === '' || $mdp === '') {
    $_SESSION['login_error'] = "Champs manquants";
    header("Location: index.html");
    exit;
}

/* ======================================================
   4) Vérification utilisateur
====================================================== */
$stmt = $pdo->prepare("SELECT mdp FROM users WHERE pseudo = ?");
$stmt->execute([$pseudo]);
$userRow = $stmt->fetch();

if (!$userRow) {
    $_SESSION['login_error'] = "Pseudo inconnu";
    header("Location: index.html");
    exit;
}

$dbPassword = $userRow['mdp'];

/* ======================================================
   5) Cas A : mot de passe déjà hashé (normal)
====================================================== */
if (password_verify($mdp, $dbPassword)) {

    // Log connexion
    $pdo->prepare("
        INSERT INTO connect_history (pseudo, ip_connection, time)
        VALUES (?, ?, NOW())
    ")->execute([$pseudo, $ip]);

    $_SESSION['pseudo'] = $pseudo;
    header("Location: rooms.php");
    exit;
}

/* ======================================================
   6) Cas B : ancien utilisateur (mot de passe en clair)
====================================================== */
if ($mdp === $dbPassword) {

    // 🔐 Upgrade automatique vers hash sécurisé
    $newHash = password_hash($mdp, PASSWORD_DEFAULT);
    $pdo->prepare("UPDATE users SET mdp = ? WHERE pseudo = ?")
        ->execute([$newHash, $pseudo]);

    $pdo->prepare("
        INSERT INTO connect_history (pseudo, ip_connection, time)
        VALUES (?, ?, NOW())
    ")->execute([$pseudo, $ip]);

    $_SESSION['pseudo'] = $pseudo;
    header("Location: rooms.php");
    exit;
}

/* ======================================================
   7) Échec
====================================================== */
$_SESSION['login_error'] = "Mot de passe incorrect";
header("Location: index.html");
exit;
