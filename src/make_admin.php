<?php
/* ======================================================
   Donner les droits admin à un utilisateur
   ⚠️ À utiliser UNE SEULE FOIS
====================================================== */
$ADMIN_PSEUDO = 'admin';

require __DIR__ . "/db.php";

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
