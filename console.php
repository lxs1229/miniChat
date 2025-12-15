<?php
$databaseUrl = getenv("DATABASE_URL");
$parts = parse_url($databaseUrl);

$dsn = "pgsql:host={$parts['host']};port=" . ($parts['port'] ?? 5432) .
       ";dbname=" . ltrim($parts['path'], '/');

$pdo = new PDO($dsn, $parts['user'], $parts['pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

$pdo->exec("ALTER TABLE users ALTER COLUMN mdp TYPE VARCHAR(255)");
$pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS is_admin BOOLEAN DEFAULT FALSE");

echo "✅ Colonne mdp corrigée + is_admin ajouté";
