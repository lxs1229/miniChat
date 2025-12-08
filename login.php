<?php
session_start();

// ---- Récupération IP client ----
function getClientIpData(): array {
    $keys = [
        'HTTP_CF_CONNECTING_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_REAL_IP',
        'REMOTE_ADDR',
    ];

    $forwardedRaw = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';

    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ipList = explode(',', $_SERVER[$key]);
            $candidate = trim($ipList[0]);
            if (filter_var($candidate, FILTER_VALIDATE_IP)) {
                return ['ip' => $candidate, 'chain' => $forwardedRaw];
            }
        }
    }
    return ['ip' => '0.0.0.0', 'chain' => $forwardedRaw];
}

$ipData = getClientIpData();
$ip = substr($ipData['ip'], 0, 45);
$ipChain = $ipData['chain'];

// ---- Connexion PostgreSQL Render ----
$databaseUrl = getenv("DATABASE_URL");
if (!$databaseUrl) {
    die("DATABASE_URL manquant pour la connexion PDO.");
}

// Parse postgres:// URL en DSN pgsql:
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
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("Erreur connexion PostgreSQL : " . $e->getMessage());
}

// ---- Récupérer données POST ----
$pseudo = $_POST['pseudo'] ?? '';
$mdp = $_POST['mdp'] ?? '';

// ---- Vérifier si pseudo existe ----
$stmt = $pdo->prepare("SELECT mdp FROM users WHERE pseudo = ?");
$stmt->execute([$pseudo]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['login_error'] = "Pseudo inconnu";
    header("Location: index.html");
    exit;
}

// ---- Vérifier mot de passe ----
if ($mdp === $user['mdp']) {

    // Historique connexion (table correct = connect_history)
    try {
        $insert = $pdo->prepare("INSERT INTO connect_history (pseudo, ip_connection) VALUES (?, ?)");
        $insert->execute([$pseudo, $ip]);
    } catch (PDOException $e) {
        error_log("MiniChat connexion log failed: " . $e->getMessage());
    }

    if ($ipChain) {
        error_log("MiniChat connexion: pseudo={$pseudo}, ip={$ip}, xff={$ipChain}");
    }

    $_SESSION['pseudo'] = $pseudo;
    header("Location: rooms.php");
    exit;
}

// ---- Mot de passe incorrect ----
$_SESSION['login_error'] = "Mot de passe incorrect";
header("Location: index.html");
exit;
