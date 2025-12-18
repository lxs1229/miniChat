<?php

$pdo = new PDO(
    "pgsql:host=localhost;port=5432;dbname=minichat_db",
    "minichat_user",
    "20021229",
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]
);
