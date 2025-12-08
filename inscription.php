<?php

// ---- Obtenir DATABASE_URL ----
$databaseUrl = getenv("DATABASE_URL");
if (!$databaseUrl) {
    die("DATABASE_URL manquant pour la connexion PDO.");
}

// ---- Convertir postgres:// URL en DSN pgsql ----
$parts = parse_url($databaseUrl);

$host = $parts['host'];
$port = $parts['port'] ?? 5432;
$user = $parts['user'];
$pass = $parts['pass'];
$db   = ltrim($parts['path'], '/');

$dsn = "pgsql:host={$host};port={$port};dbname={$db}";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Erreur connexion PostgreSQL : " . $e->getMessage());
}

// ---- Récupération pseudo / mdp ----
// Pour l'instant on garde GET parce que ton HTML l'utilise probablement.
// Idéalement tu devrais passer en POST.
$pseudo = $_GET['pseudo'] ?? '';
$mdp = $_GET['mdp'] ?? '';

if (!$pseudo || !$mdp) {
    die("Pseudo ou mot de passe manquant.");
}

// ---- Vérifier si pseudo déjà existant ----
$check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE pseudo = ?");
$check->execute([$pseudo]);
$exists = $check->fetchColumn();

if ($exists > 0) {
    echo "❌ Le pseudo existe déjà ! Veuillez en choisir un autre.";
    exit;
}

// ---- Insérer utilisateur ----
$insert = $pdo->prepare("INSERT INTO users (pseudo, mdp) VALUES (?, ?)");
$insert->execute([$pseudo, $mdp]);

// ---- Redirection ----
header("Location: index.html?register=success");
exit;

?>
