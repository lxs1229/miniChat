<?php
session_start();
if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
    header("Location: admin.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Login</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="page">
    <div class="card">
        <h2>Connexion Administrateur</h2>
        <form action="admin_verify.php" method="post">
            <label>Mot de passe admin :</label>
            <input type="password" name="password" required>
            <button class="btn" type="submit">Connexion</button>
        </form>
    </div>
</div>
</body>
</html>
