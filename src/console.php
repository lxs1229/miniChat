<?php
require __DIR__ . "/db.php";

$pdo->exec("ALTER TABLE users ALTER COLUMN mdp TYPE VARCHAR(255)");
$pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS is_admin BOOLEAN DEFAULT FALSE");

echo "✅ Colonne mdp corrigée + is_admin ajouté";
