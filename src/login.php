<?php
session_start();
require __DIR__ . "/i18n.php";

function safeNextRedirect(): ?string {
    $next = $_POST["next"] ?? ($_GET["next"] ?? null);
    if (!is_string($next) || $next === "") return null;
    if (!str_starts_with($next, "/")) return null;
    if (str_starts_with($next, "//")) return null;
    $parts = parse_url($next);
    if ($parts === false) return null;
    if (isset($parts["scheme"]) || isset($parts["host"])) return null;
    return $next;
}

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
require __DIR__ . "/db.php";

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
    $next = safeNextRedirect();
    header("Location: " . ($next ?? "rooms.php"));
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
    $next = safeNextRedirect();
    header("Location: " . ($next ?? "rooms.php"));
    exit;
}

/* ======================================================
   7) Échec
====================================================== */
$_SESSION['login_error'] = "Mot de passe incorrect";
header("Location: index.html");
exit;
