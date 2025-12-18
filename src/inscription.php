<?php

require __DIR__ . "/db.php";

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
$hash = password_hash($mdp, PASSWORD_DEFAULT);

$insert = $pdo->prepare(
    "INSERT INTO users (pseudo, mdp) VALUES (?, ?)"
);
$insert->execute([$pseudo, $hash]);

// ---- Redirection ----
header("Location: index.html?register=success");
exit;

?>
