<?php
session_start();
if (!isset($_SESSION['pseudo'])) {
    header("Location: index.html");
    exit;
}
$pseudo = $_SESSION['pseudo'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Assistant IA • MiniChat</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="grid-overlay" aria-hidden="true"></div>
<div class="page">
    <div class="card">
        <div class="topbar">
            <div class="stacked">
                <div class="badge">Assistant IA • gratuit & rapide</div>
                <h1>Discute avec l'IA</h1>
                <p class="helper">L'IA te répond en français et reste concise. Utilise-la pour brainstormer ou rédiger un message.</p>
            </div>
            <div class="right">
                <div class="pill">Connecté : <?= htmlentities($pseudo) ?></div>
                <a class="btn btn-secondary" href="chat.php">Retour au chat</a>
                <a class="btn btn-secondary" href="logout.php">Déconnexion</a>
            </div>
        </div>

        <div class="panel">
            <div class="label-row">
                <span>Conversation IA</span>
                <span class="tag">Basée sur Llama 3 via Groq (clé gratuite requise)</span>
            </div>
            <div id="aiMessages" class="ai-messages">
                <div class="ai-bubble ai-assistant">
                    <div class="pseudo">Assistant</div>
                    <p class="message-body">Salut <?= htmlentities($pseudo) ?> ! Je suis là pour t'aider à rédiger un message, résumer un échange, ou générer des idées. Pose-moi ta question.</p>
                </div>
            </div>
        </div>

        <div class="panel" style="margin-top:14px;">
            <form id="aiForm" class="stacked">
                <div class="field">
                    <div class="label-row">
                        <label for="aiPrompt">Message</label>
                        <span class="muted">L'IA répondra en français</span>
                    </div>
                    <textarea id="aiPrompt" name="prompt" placeholder="Ex : Rédige un message de bienvenue pour le salon Projet Nova" required></textarea>
                </div>
                <div class="actions">
                    <div class="field" style="flex:1;">
                        <div class="label-row">
                            <label for="aiModel">Modèle</label>
                            <span class="muted">Gratuit & performant</span>
                        </div>
                        <select id="aiModel" name="model">
                            <option value="llama3-70b-8192">Llama3 70B (Groq - rapide)</option>
                            <option value="llama3-8b-8192">Llama3 8B (Groq)</option>
                            <option value="mixtral-8x7b-32768">Mixtral 8x7B (Groq)</option>
                        </select>
                    </div>
                    <button class="btn" type="submit">Envoyer à l'IA</button>
                </div>
                <p class="muted">💡 Configure la variable d'environnement <code>GROQ_API_KEY</code> côté serveur. Aucune donnée n'est stockée, seules les requêtes nécessaires sont envoyées à Groq.</p>
            </form>
        </div>
    </div>
</div>

<script>
    const aiMessagesEl = document.getElementById("aiMessages");
    const aiForm = document.getElementById("aiForm");
    const aiPrompt = document.getElementById("aiPrompt");
    const aiModel = document.getElementById("aiModel");
    const aiSubmit = aiForm.querySelector("button[type=\"submit\"]");

    // Historique minimal conservé côté client pour le contexte
    const history = [
        { role: "system", content: "Tu es un assistant IA utile pour un mini-chat. Tu réponds en français, de façon concise et pratique." },
        { role: "assistant", content: "Salut ! Je suis là pour aider." }
    ];

    function renderMessages() {
        aiMessagesEl.innerHTML = "";
        history
            .filter(msg => msg.role !== "system")
            .forEach(msg => {
                const div = document.createElement("div");
                div.className = `ai-bubble ${msg.role === "assistant" ? "ai-assistant" : "ai-user"}`;
                const header = document.createElement("div");
                header.className = "pseudo";
                header.textContent = msg.role === "assistant" ? "Assistant" : "Toi";
                const body = document.createElement("p");
                body.className = "message-body";
                body.textContent = msg.content;
                div.appendChild(header);
                div.appendChild(body);
                aiMessagesEl.appendChild(div);
            });
        aiMessagesEl.scrollTop = aiMessagesEl.scrollHeight;
    }

    function addMessage(role, content) {
        history.push({ role, content });
        renderMessages();
    }

    aiForm.addEventListener("submit", async (event) => {
        event.preventDefault();
        const prompt = aiPrompt.value.trim();
        if (!prompt) return;

        addMessage("user", prompt);
        aiPrompt.value = "";
        aiPrompt.focus();

        addMessage("assistant", "...");
        renderMessages();

        if (aiSubmit) {
            aiSubmit.disabled = true;
        }

        try {
            const response = await fetch("ai_proxy.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    messages: history,
                    model: aiModel.value || "llama3-70b-8192"
                })
            });

            if (!response.ok) {
                throw new Error(`Erreur réseau ${response.status}`);
            }
            const data = await response.json();
            history.pop(); // remove placeholder
            if (data.error) {
                addMessage("assistant", "Erreur : " + data.error);
            } else {
                addMessage("assistant", data.reply || "Je n'ai pas pu générer de réponse.");
            }
        } catch (e) {
            history.pop(); // remove placeholder
            addMessage("assistant", "Erreur lors de l'appel IA : " + e.message);
        }
        setTimeout(() => {
            if (aiSubmit) aiSubmit.disabled = false;
        }, 1200);
    });

    renderMessages();
</script>
</body>
</html>
