<?php

$databaseUrl = getenv("DATABASE_URL");
if (!$databaseUrl) {
    die("DATABASE_URL n'est pas configuré.");
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
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Connexion PostgreSQL échouée : " . $e->getMessage());
}

$sql = "
CREATE TABLE IF NOT EXISTS users (
    num SERIAL PRIMARY KEY,
    mdp VARCHAR(20),
    pseudo VARCHAR(15) UNIQUE
);

CREATE TABLE IF NOT EXISTS rooms (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) UNIQUE,
    password VARCHAR(255),
    created_by VARCHAR(15),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS messages (
    num SERIAL PRIMARY KEY,
    pseudo VARCHAR(15),
    message TEXT,
    time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    room_id INT
);

CREATE TABLE IF NOT EXISTS connect_history (
    num SERIAL PRIMARY KEY,
    pseudo VARCHAR(15),
    time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_connection VARCHAR(45)
);
";

$pdo->exec($sql);

echo "✔ Les tables ont été créées avec succès !";
