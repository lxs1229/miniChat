


<?php

$pdo = new PDO("mysql:host=localhost;dbname=miniChat_db;charset=utf8", "root", "20021229");
$pseudo = $_GET['pseudo'];
$mdp = $_GET['mdp'];
$check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE pseudo = ?");
$check->execute([$pseudo]);
$exists = $check->fetchColumn();
if ($exists > 0){
    echo "Le pseudo existe déjà ! Veuillez en choisir un autre.";
}else{
    $insert = $pdo->prepare("INSERT INTO users (pseudo, mdp) VALUES (?, ?)");
    $insert->execute([$pseudo, $mdp]);

    echo "✅ Inscription réussie ! Bienvenue, " . htmlentities($pseudo);
    header("Location: index.html");
}
?>
