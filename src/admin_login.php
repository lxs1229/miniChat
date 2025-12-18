<?php
session_start();
require __DIR__ . "/i18n.php";
if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
    header("Location: admin.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="<?= htmlentities(minichat_html_lang()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlentities(t("admin_login_title")) ?></title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="page">
    <div class="card">
        <div class="topbar">
            <div class="stacked">
                <div class="badge"><?= htmlentities(t("nav_admin")) ?></div>
                <h2 style="margin:0;"><?= htmlentities(t("admin_login_h2")) ?></h2>
            </div>
            <div class="right">
                <?= render_lang_switcher() ?>
            </div>
        </div>
        <form action="admin_verify.php" method="post">
            <label><?= htmlentities(t("admin_login_pwd")) ?></label>
            <input type="password" name="password" required>
            <button class="btn" type="submit"><?= htmlentities(t("admin_login_btn")) ?></button>
        </form>
    </div>
</div>
</body>
</html>
