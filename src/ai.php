<?php
session_start();
require __DIR__ . "/i18n.php";
if (!isset($_SESSION['pseudo'])) {
    header("Location: index.html");
    exit;
}
$pseudo = $_SESSION['pseudo'];

$systemPrompt = match (minichat_lang()) {
    "zh" => "你是一个有用、清晰、简洁的助手。",
    "en" => "You are a helpful, clear and concise assistant.",
    default => "Tu es un assistant IA utile, clair et concis.",
};
$readyMessage = match (minichat_lang()) {
    "zh" => "我已准备好帮助你！",
    "en" => "I'm ready to help!",
    default => "Je suis prêt à t'aider !",
};
?>
<!DOCTYPE html>
<html lang="<?= htmlentities(minichat_html_lang()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlentities(t("ai_title")) ?></title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="grid-overlay" aria-hidden="true"></div>
<div class="page">
    <div class="card">
        <div class="topbar">
            <div class="stacked">
                <div class="badge"><?= htmlentities(t("ai_badge")) ?></div>
                <h1><?= htmlentities(t("ai_h1")) ?></h1>
                <p class="helper"><?= htmlentities(t("ai_helper")) ?></p>
            </div>
            <div class="right">
                <div class="pill"><?= htmlentities(t("connected_as", ["pseudo" => $pseudo])) ?></div>
                <?= render_lang_switcher() ?>
                <a class="btn btn-secondary" href="chat.php"><?= htmlentities(t("nav_chat")) ?></a>
                <a class="btn btn-secondary" href="logout.php"><?= htmlentities(t("nav_logout")) ?></a>
            </div>
        </div>

        <!-- Zone des messages -->
        <div class="panel">
            <div class="label-row">
                <span><?= htmlentities(t("ai_conversation")) ?></span>
                <span class="tag"><?= htmlentities(t("ai_models_tag")) ?></span>
            </div>
            <div id="aiMessages" class="ai-messages">
                <div class="ai-bubble ai-assistant">
                    <div class="pseudo"><?= htmlentities(t("ai_assistant_name")) ?></div>
                    <p class="message-body"><?= htmlentities(t("ai_greeting", ["pseudo" => $pseudo])) ?></p>
                </div>
            </div>
        </div>

        <!-- Formulaire d'envoi -->
        <div class="panel" style="margin-top:14px;">
            <form id="aiForm" class="stacked">
                <div class="field">
                    <div class="label-row">
                        <label for="aiPrompt"><?= htmlentities(t("ai_message_label")) ?></label>
                        <span class="muted"><?= htmlentities(t("ai_reply_language", ["lang" => minichat_lang_label(minichat_lang())])) ?></span>
                    </div>
                    <textarea id="aiPrompt" required placeholder="<?= htmlentities(t("ai_prompt_placeholder")) ?>"></textarea>
                </div>

                <div class="actions">
                    <div class="field" style="flex:1;">
                        <label for="aiModel"><?= htmlentities(t("ai_model_label")) ?></label>
                        <select id="aiModel">
                            <option value=""><?= htmlentities(t("ai_loading")) ?></option>
                        </select>
                    </div>
                    <button class="btn" type="submit"><?= htmlentities(t("ai_send")) ?></button>
                </div>

                <p class="muted">
                    <?= htmlentities(t("ai_note_1")) ?><br>
                    <?= htmlentities(t("ai_note_2")) ?>
                </p>
            </form>
        </div>
    </div>
</div>

<script>
const I18N = <?= json_encode([
    "loading" => t("ai_loading"),
    "loadError" => t("ai_load_error"),
    "apiError" => t("ai_api_error"),
    "assistantName" => t("ai_assistant_name"),
    "youName" => t("ai_you"),
    "networkError" => t("ai_network_error"),
    "systemPrompt" => $systemPrompt,
    "readyMessage" => $readyMessage,
], JSON_UNESCAPED_UNICODE) ?>;

/* ============================================================
   🔥 1. Auto-load Groq models from fetch_models.php
============================================================ */
async function loadModels() {
    const select = document.getElementById("aiModel");
    select.innerHTML = `<option>${I18N.loading}</option>`;

    try {
        const res = await fetch("fetch_models.php");
        const models = await res.json();

        if (!Array.isArray(models)) {
            select.innerHTML = `<option>${I18N.loadError}</option>`;
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
        select.innerHTML = `<option>${I18N.apiError}</option>`;
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

// Enter to send (Shift+Enter for newline)
let isComposingPrompt = false;
aiPrompt.addEventListener("compositionstart", () => (isComposingPrompt = true));
aiPrompt.addEventListener("compositionend", () => (isComposingPrompt = false));
aiPrompt.addEventListener("keydown", (e) => {
    if (e.key !== "Enter") return;
    if (e.shiftKey) return;
    if (isComposingPrompt) return;
    e.preventDefault();
    aiForm.requestSubmit();
});

// History sent to Groq
const history = [
    { role: "system", content: I18N.systemPrompt },
    { role: "assistant", content: I18N.readyMessage }
];

function addMessage(role, content) {
    history.push({ role, content });

    const div = document.createElement("div");
    div.className = `ai-bubble ${role === "assistant" ? "ai-assistant" : "ai-user"}`;
    div.innerHTML = `
        <div class="pseudo">${role === "assistant" ? I18N.assistantName : I18N.youName}</div>
        <p class="message-body">${content}</p>
    `;
    aiMessagesEl.appendChild(div);
    aiMessagesEl.scrollTop = aiMessagesEl.scrollHeight;
}

// Loading message
function showLoading() {
    const div = document.createElement("div");
    div.className = "ai-bubble ai-assistant ai-temp";
    div.innerHTML = `<div class="pseudo">${I18N.assistantName}</div><p class="message-body">...</p>`;
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
        addMessage("assistant", "⚠️ " + I18N.networkError + " : " + e.message);
    }

    aiSubmit.disabled = false;
});
</script>

</body>
</html>
