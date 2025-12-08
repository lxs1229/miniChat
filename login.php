<?php
session_start();

// Récupère l'IP client (best-effort) + chaîne brute pour diagnostic
function getClientIpData(): array {
    $keys = [
        'HTTP_CF_CONNECTING_IP', // Cloudflare
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_REAL_IP',
        'REMOTE_ADDR',
    ];
    $forwardedRaw = '';
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $forwardedRaw = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }

    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            // X-Forwarded-For peut contenir plusieurs IP, on prend la première
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
$ip = substr($ipData['ip'], 0, 45); // évite tout dépassement de longueur
$ipChain = $ipData['chain'];
$pdo = new PDO(
    "mysql:host=localhost;dbname=miniChat_db;charset=utf8",
    "root",
    "20021229",
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);
$pseudo = $_POST['pseudo'] ?? '';
$mdp = $_POST['mdp'] ?? '';

$sql = "SELECT mdp FROM users WHERE pseudo = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$pseudo]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['login_error'] = "Pseudo inconnu";
    header("Location: index.html");
    exit;
}

if ($mdp === $user['mdp']) {
    try {
        $insert = $pdo->prepare("INSERT INTO Connect_Histoire(pseudo, ip_connection) VALUES (?, ?)");
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

$_SESSION['login_error'] = "Mot de passe incorrect";
header("Location: index.html");
exit;
