<?php
/* ======================================================
   Donner les droits admin à un utilisateur
   ⚠️ À utiliser UNE SEULE FOIS
====================================================== */
$ADMIN_PSEUDO = 'admin';

/* ---------- Connexion PostgreSQL ---------- */
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

/* ---------- Vérifier utilisateur ---------- */
$stmt = $pdo->prepare("SELECT pseudo FROM users WHERE pseudo = ?");
$stmt->execute([$ADMIN_PSEUDO]);

if (!$stmt->fetch()) {
    die("❌ L'utilisateur '{$ADMIN_PSEUDO}' n'existe pas.");
}

/* ---------- Donner droits admin ---------- */
$update = $pdo->prepare("
    UPDATE users
    SET is_admin = TRUE
    WHERE pseudo = ?
");
$update->execute([$ADMIN_PSEUDO]);

echo "✅ L'utilisateur '{$ADMIN_PSEUDO}' est maintenant administrateur.";
