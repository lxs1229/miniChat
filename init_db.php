<?php

/* ======================================================
   Connexion PostgreSQL (Render / Local)
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
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Erreur connexion PostgreSQL : " . $e->getMessage());
}

/* ======================================================
   Création des tables (version sécurisée)
====================================================== */

$sql = <<<SQL

-- ================= USERS =================
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    pseudo VARCHAR(30) UNIQUE NOT NULL,
    mdp VARCHAR(255) NOT NULL,      -- password_hash compatible
    is_admin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ================= ROOMS =================
CREATE TABLE IF NOT EXISTS rooms (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255),
    created_by VARCHAR(30),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_room_creator
        FOREIGN KEY (created_by)
        REFERENCES users(pseudo)
        ON DELETE SET NULL
);

-- ================= MESSAGES =================
CREATE TABLE IF NOT EXISTS messages (
    id SERIAL PRIMARY KEY,
    pseudo VARCHAR(30),
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    room_id INT NOT NULL,

    CONSTRAINT fk_message_user
        FOREIGN KEY (pseudo)
        REFERENCES users(pseudo)
        ON DELETE CASCADE,

    CONSTRAINT fk_message_room
        FOREIGN KEY (room_id)
        REFERENCES rooms(id)
        ON DELETE CASCADE
);

-- ================= CONNEXION HISTORY =================
CREATE TABLE IF NOT EXISTS connect_history (
    id SERIAL PRIMARY KEY,
    pseudo VARCHAR(30),
    ip_connection VARCHAR(45),
    connected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_history_user
        FOREIGN KEY (pseudo)
        REFERENCES users(pseudo)
        ON DELETE CASCADE
);

-- ================= INDEX =================
CREATE INDEX IF NOT EXISTS idx_messages_room
    ON messages(room_id);

CREATE INDEX IF NOT EXISTS idx_history_pseudo
    ON connect_history(pseudo);

SQL;

/* ======================================================
   Exécution
====================================================== */
$pdo->exec($sql);

$pdo->exec("DELETE FROM users WHERE pseudo = 'admin'");

echo "✅ Base MiniChat initialisée avec succès.";
