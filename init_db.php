$pdo->exec(file_get_contents("init_minichat.sql"));
DELETE FROM users WHERE pseudo = 'admin';
