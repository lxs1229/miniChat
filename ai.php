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
                <p class="helper">L'IA te répond en français et reste concise.</p>
            </div>
            <div class="right">
                <div class="pill">Connecté : <?= htmlentities($pseudo) ?></div>
                <a class="btn btn-secondary" href="chat.php">Retour au chat</a>
                <a class="btn btn-secondary" href="logout.php">Déconnexion</a>
            </div>
        </div>

        <!-- Zone des messages -->
        <div class="panel">
            <div class="label-row">
                <span>Conversation IA</span>
                <span class="tag">Modèles Groq mis à jour automatiquement</span>
            </div>
            <div id="aiMessages" class="ai-messages">
                <div class="ai-bubble ai-assistant">
                    <div class="pseudo">Assistant</div>
                    <p class="message-body">Salut <?= htmlentities($pseudo) ?> ! Pose-moi ta question.</p>
                </div>
            </div>
        </div>

        <!-- Formulaire d'envoi -->
        <div class="panel" style="margin-top:14px;">
            <form id="aiForm" class="stacked">
                <div class="field">
                    <div class="label-row">
                        <label for="aiPrompt">Message</label>
                        <span class="muted">L'IA répondra en français</span>
                    </div>
                    <textarea id="aiPrompt" required placeholder="Ex : Explique-moi un concept..."></textarea>
                </div>

                <div class="actions">
                    <div class="field" style="flex:1;">
                        <label for="aiModel">Modèle (chargement dynamique)</label>
                        <select id="aiModel">
                            <option value="">Chargement...</option>
                        </select>
                    </div>
                    <button class="btn" type="submit">Envoyer à l'IA</button>
                </div>

                <p class="muted">
                    💡 Les modèles sont automatiquement récupérés depuis Groq.<br>
                    Aucune donnée n'est stockée.
                </p>
            </form>
        </div>
    </div>
</div>

<script>
/* ============================================================
   🔥 1. Auto-load Groq models from fetch_models.php
============================================================ */
async function loadModels() {
    const select = document.getElementById("aiModel");
    select.innerHTML = "<option>Chargement...</option>";

    try {
        const res = await fetch("fetch_models.php");
        const models = await res.json();

        if (!Array.isArray(models)) {
            select.innerHTML = "<option>Erreur chargement</option>";
            return;
        }

        select.innerHTML = "";

        const defaultModel = "llama-3.3-70b-versatile";

        models.forEach(m => {
            const opt = document.createElement("option");
            opt.value = m;
            opt.textContent = (m === defaultModel) ? m + " (recommandé)" : m;
            select.appendChild(opt);
        });

        // Set default if exists
        if (models.includes(defaultModel)) {
            select.value = defaultModel;
        }

    } catch (e) {
        select.innerHTML = "<option>Erreur API</option>";
    }
}
loadModels();


/* ============================================================
   💬 2. Chat UI Logic
============================================================ */
const aiMessagesEl = document.getElementById("aiMessages");
const aiForm = document.getElementById("aiForm");
const aiPrompt = document.getElementById("aiPrompt");
const aiModel = document.getElementById("aiModel");
const aiSubmit = aiForm.querySelector("button[type='submit']");

// History sent to Groq
const history = [
    { role: "system", content: "Tu es un assistant IA utile, clair et concis." },
    { role: "assistant", content: "Je suis prêt à t'aider !" }
];

function addMessage(role, content) {
    history.push({ role, content });

    const div = document.createElement("div");
    div.className = `ai-bubble ${role === "assistant" ? "ai-assistant" : "ai-user"}`;
    div.innerHTML = `
        <div class="pseudo">${role === "assistant" ? "Assistant" : "Toi"}</div>
        <p class="message-body">${content}</p>
    `;
    aiMessagesEl.appendChild(div);
    aiMessagesEl.scrollTop = aiMessagesEl.scrollHeight;
}

// Loading message
function showLoading() {
    const div = document.createElement("div");
    div.className = "ai-bubble ai-assistant ai-temp";
    div.innerHTML = `<div class="pseudo">Assistant</div><p class="message-body">...</p>`;
    aiMessagesEl.appendChild(div);
}

function hideLoading() {
    const temp = document.querySelector(".ai-temp");
    if (temp) temp.remove();
}

/* ============================================================
   🚀 3. Submit form : send to ai_proxy.php
============================================================ */
aiForm.addEventListener("submit", async (event) => {
    event.preventDefault();
    const prompt = aiPrompt.value.trim();
    if (!prompt) return;

    addMessage("user", prompt);
    aiPrompt.value = "";
    aiPrompt.focus();

    showLoading();
    aiSubmit.disabled = true;

    try {
        const response = await fetch("ai_proxy.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                messages: history,
                model: aiModel.value
            })
        });

        const data = await response.json();
        hideLoading();

        if (data.error) {
            addMessage("assistant", "⚠️ Erreur : " + data.error);
        } else {
            addMessage("assistant", data.reply);
        }

    } catch (e) {
        hideLoading();
        addMessage("assistant", "⚠️ Erreur réseau : " + e.message);
    }

    aiSubmit.disabled = false;
});
</script>

</body>
</html>
