<?php
session_start();

// Vérifier session utilisateur
if (!isset($_SESSION['pseudo'])) {
    header("Location: index.html");
    exit;
}

$roomId = $_SESSION['room_id'] ?? null;
$pseudo = $_SESSION['pseudo'];

if (!$roomId) {
    header("Location: chat.php");
    exit;
}

// Récupérer texte envoyé
$message = trim($_POST['message'] ?? '');
if ($message === '') {
    header("Location: chat.php");
    exit;
}

/* ------------------------------
   Connexion PostgreSQL (Render)
--------------------------------*/
$databaseUrl = getenv("DATABASE_URL");
if (!$databaseUrl) {
    die("DATABASE_URL manquant.");
}

$parts  = parse_url($databaseUrl);
$host   = $parts['host'] ?? 'localhost';
$port   = $parts['port'] ?? 5432;
$user   = $parts['user'] ?? null;
$pass   = $parts['pass'] ?? null;
$dbname = ltrim($parts['path'] ?? '', '/');

$dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Erreur PostgreSQL : " . $e->getMessage());
}

/* ------------------------------
   Vérifier existence table messages
--------------------------------*/
$tableExists = $pdo->query("SELECT to_regclass('public.messages')")->fetchColumn();
if ($tableExists === null) {
    die("⚠ Table messages absente. Lance init_db.php pour créer la base.");
}

/* ------------------------------
   Insérer message utilisateur
--------------------------------*/
$stmt = $pdo->prepare("
    INSERT INTO messages (pseudo, message, room_id)
    VALUES (?, ?, ?)
");
$stmt->execute([$pseudo, $message, $roomId]);



/* ============================================================
                     🤖 AI BOT AUTO REPLY
============================================================ */

// Détecter si l'utilisateur appelle le bot
$trigger = false;

// Exemple : "@ai comment ça va ?"  → déclenche
if (stripos($message, "@ai") !== false || stripos($message, "@bot") !== false) {
    $trigger = true;
}

if ($trigger) {
    $apiKey = getenv("GROQ_API_KEY");

    if ($apiKey) {

        // Préparer le prompt pour Groq
        $payload = [
            "model" => "llama-3.1-8b-instant",
            "messages" => [
                [
                    "role" => "system",
                    "content" => "Tu es AI_BOT, un assistant intégré dans un mini-chat. Réponds en français, de manière courte, utile et amicale."
                ],
                [
                    "role" => "user",
                    "content" => $message
                ]
            ],
            "max_tokens" => 120
        ];

        // Appel API Groq
        $ch = curl_init("https://api.groq.com/openai/v1/chat/completions");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$apiKey}",
                "Content-Type: application/json"
            ],
            CURLOPT_POSTFIELDS => json_encode($payload)
        ]);

        $raw = curl_exec($ch);
        $data = json_decode($raw, true);
        curl_close($ch);

        // Récupérer réponse IA
        $botReply = $data["choices"][0]["message"]["content"] ?? "Désolé, je n'ai pas compris.";

        // Insérer réponse bot dans la DB
        $pdo->prepare("INSERT INTO messages (pseudo, message, room_id) VALUES ('AI_BOT', ?, ?)")
            ->execute([$botReply, $roomId]);
    }
}



/* ------------------------------
   Retour au chat
--------------------------------*/
header("Location: chat.php");
exit;
?>
