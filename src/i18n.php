<?php

if (!isset($_SESSION)) {
    session_start();
}

$MINICHAT_ALLOWED_LANGS = ["fr", "en", "zh"];

function detect_browser_lang(): string {
    $header = strtolower((string)($_SERVER["HTTP_ACCEPT_LANGUAGE"] ?? ""));
    if (str_starts_with($header, "zh") || str_contains($header, "zh-")) return "zh";
    if (str_starts_with($header, "fr") || str_contains($header, "fr-")) return "fr";
    if (str_starts_with($header, "en") || str_contains($header, "en-")) return "en";
    return "en";
}

$requested = null;
if (isset($_GET["lang"])) {
    $tmp = strtolower(trim((string)$_GET["lang"]));
    if (in_array($tmp, $MINICHAT_ALLOWED_LANGS, true)) {
        $requested = $tmp;
    }
}

$GLOBALS["MINICHAT_LANG"] = $requested ?? detect_browser_lang();

$GLOBALS["MINICHAT_I18N"] = [
    "fr" => [
        "language" => "Langue",
        "connected_as" => "Connecté : {{pseudo}}",

        "nav_ai" => "Assistant IA",
        "nav_chat" => "Aller au chat",
        "nav_rooms" => "Changer de salon",
        "nav_profile" => "Profil",
        "nav_leaderboard" => "Classements",
        "nav_logout" => "Déconnexion",
        "nav_admin" => "Admin",

        "rooms_badge" => "MiniChat • Salons",
        "rooms_title" => "Salons",
        "rooms_manage" => "Gérer les salons",
        "rooms_create" => "Créer un salon",
        "rooms_delete" => "Supprimer un salon",
        "rooms_existing" => "Salons existants",
        "rooms_none" => "Aucun salon disponible.",
        "rooms_none_deletable" => "Aucun salon à supprimer.",
        "rooms_creator" => "Créateur : {{creator}}",
        "rooms_name" => "Nom du salon",
        "rooms_password_optional" => "Mot de passe (optionnel)",
        "rooms_password" => "Mot de passe",
        "rooms_open" => "Ouvert",
        "rooms_join" => "Rejoindre",
        "rooms_create_btn" => "Créer",
        "rooms_delete_btn" => "Supprimer",
        "confirm_delete_room" => "Supprimer ce salon ?",
        "error_room_not_found" => "Salon introuvable.",
        "error_password_incorrect" => "Mot de passe incorrect.",
        "error_room_name_required" => "Nom du salon requis.",
        "error_room_name_exists" => "Ce nom existe déjà.",
        "error_delete_forbidden" => "Seul le créateur ou admin peut supprimer.",
        "error_schema_not_ready" => "⚠ Base non migrée. Lance init_db.php.",

        "chat_title" => "MiniChat - Salon {{room}}",
        "chat_room" => "Salon : {{room}}",
        "chat_messages" => "Messages",
        "chat_none" => "Aucun message pour l'instant.",
        "chat_refresh" => "Actualiser",
        "chat_write" => "Écrire un message",
        "chat_placeholder" => "Tape ton message...",
        "chat_send" => "Envoyer",

        "ai_title" => "Assistant IA • MiniChat",
        "ai_badge" => "Assistant IA • gratuit & rapide",
        "ai_h1" => "Discute avec l'IA",
        "ai_helper" => "L'IA te répond selon la langue que tu choisis.",
        "ai_conversation" => "Conversation IA",
        "ai_models_tag" => "Modèles Groq mis à jour automatiquement",
        "ai_assistant_name" => "Assistant",
        "ai_you" => "Toi",
        "ai_greeting" => "Salut {{pseudo}} ! Pose-moi ta question.",
        "ai_message_label" => "Message",
        "ai_reply_language" => "Langue de réponse : {{lang}}",
        "ai_prompt_placeholder" => "Ex : Explique-moi un concept...",
        "ai_model_label" => "Modèle (chargement dynamique)",
        "ai_send" => "Envoyer à l'IA",
        "ai_note_1" => "💡 Les modèles sont automatiquement récupérés depuis Groq.",
        "ai_note_2" => "Aucune donnée n'est stockée.",
        "ai_loading" => "Chargement...",
        "ai_load_error" => "Erreur chargement",
        "ai_api_error" => "Erreur API",
        "ai_network_error" => "Erreur réseau",

        "loadmsg_pick_room" => "Sélectionne d'abord un salon.",
        "loadmsg_table_missing" => "⚠ Table messages absente. Lance init_db.php.",
        "loadmsg_none" => "Aucun message pour l'instant.",

        "admin_login_title" => "Admin Login",
        "admin_login_h2" => "Connexion Administrateur",
        "admin_login_pwd" => "Mot de passe admin :",
        "admin_login_btn" => "Connexion",

        "game_2048_title" => "2048 • MiniChat",
        "game_2048_badge" => "MiniChat • 2048",
        "game_2048_h1" => "2048",
        "game_2048_rules" => "Flèches/WASD ou swipe • Entrée pour recommencer",
        "game_2048_new" => "Nouvelle partie",
        "game_2048_continue" => "Continuer",
        "game_2048_score" => "Score",
        "game_2048_best" => "Meilleur",
        "game_2048_saved" => "Sauvegardé",
        "game_2048_saving" => "Sauvegarde...",
        "game_2048_save_error" => "Erreur sauvegarde",
        "game_2048_leaderboard" => "Classement",
        "game_2048_play" => "Jouer à 2048",
        "game_2048_need_login" => "Connecte-toi pour jouer.",
        "game_2048_guest_note" => "Mode invité : pas de sauvegarde ni classement.",
        "game_2048_login_to_save" => "Connecte-toi pour sauvegarder.",
        "login_username" => "Pseudo",
        "login_password" => "Mot de passe",
        "login_required" => "Obligatoire",
        "login_btn" => "Se connecter",
        "signup_btn" => "Créer un compte",

        "game_ms_title" => "Minesweeper • MiniChat",
        "game_ms_badge" => "MiniChat • Minesweeper",
        "game_ms_h1" => "Minesweeper",
        "game_ms_rules" => "Clic/tap : révéler • Clic droit/pression longue : drapeau • Mode drapeau via bouton 🚩",
        "game_ms_new" => "Nouvelle partie",
        "game_ms_continue" => "Continuer",
        "game_ms_difficulty" => "Difficulté",
        "game_ms_beginner" => "Débutant",
        "game_ms_intermediate" => "Intermédiaire",
        "game_ms_expert" => "Expert",
        "game_ms_mines" => "Mines",
        "game_ms_flags" => "Drapeaux",
        "game_ms_time" => "Temps",
        "game_ms_saved" => "Sauvegardé",
        "game_ms_saving" => "Sauvegarde...",
        "game_ms_save_error" => "Erreur sauvegarde",
        "game_ms_leaderboard" => "Classement",
        "game_ms_guest_note" => "Mode invité : pas de sauvegarde ni classement.",
        "game_ms_login_to_save" => "Connecte-toi pour sauvegarder.",
        "game_ms_flag_mode_on" => "🚩 Drapeau : ON",
        "game_ms_flag_mode_off" => "🚩 Drapeau : OFF",

        "profile_title" => "Profil",
        "profile_badge" => "MiniChat • Profil",
        "profile_h1" => "Profil",
        "profile_helper" => "Tes stats et tes meilleurs scores.",
        "profile_overview" => "Aperçu",
        "profile_messages" => "Messages envoyés",
        "profile_rooms_created" => "Salons créés",
        "profile_last_login" => "Dernière connexion",
        "profile_last_ip" => "Dernière IP",
        "profile_best" => "Meilleurs scores",
        "profile_best_tag" => "Perso",
        "profile_ms_beginner" => "Démineur • Débutant",
        "profile_ms_intermediate" => "Démineur • Intermédiaire",
        "profile_ms_expert" => "Démineur • Expert",
        "profile_play_2048" => "Jouer à 2048",
        "profile_play_ms" => "Jouer au démineur",

        "leaderboard_title" => "Classements",
        "leaderboard_badge" => "MiniChat • Classements",
        "leaderboard_h1" => "Classements",
        "leaderboard_helper" => "Top scores et meilleurs temps.",
        "leaderboard_nav_2048" => "2048",
        "leaderboard_nav_ms" => "Démineur",
        "leaderboard_ms_title" => "Démineur",
        "leaderboard_ms_beginner" => "Débutant",
        "leaderboard_ms_intermediate" => "Intermédiaire",
        "leaderboard_ms_expert" => "Expert",
        "leaderboard_rank" => "Rang",
        "leaderboard_empty" => "Aucune donnée pour l'instant.",
    ],
    "en" => [
        "language" => "Language",
        "connected_as" => "Signed in: {{pseudo}}",

        "nav_ai" => "AI Assistant",
        "nav_chat" => "Go to chat",
        "nav_rooms" => "Change room",
        "nav_profile" => "Profile",
        "nav_leaderboard" => "Leaderboards",
        "nav_logout" => "Log out",
        "nav_admin" => "Admin",

        "rooms_badge" => "MiniChat • Rooms",
        "rooms_title" => "Rooms",
        "rooms_manage" => "Manage rooms",
        "rooms_create" => "Create a room",
        "rooms_delete" => "Delete a room",
        "rooms_existing" => "Existing rooms",
        "rooms_none" => "No rooms available.",
        "rooms_none_deletable" => "No rooms you can delete.",
        "rooms_creator" => "Owner: {{creator}}",
        "rooms_name" => "Room name",
        "rooms_password_optional" => "Password (optional)",
        "rooms_password" => "Password",
        "rooms_open" => "Open",
        "rooms_join" => "Join",
        "rooms_create_btn" => "Create",
        "rooms_delete_btn" => "Delete",
        "confirm_delete_room" => "Delete this room?",
        "error_room_not_found" => "Room not found.",
        "error_password_incorrect" => "Incorrect password.",
        "error_room_name_required" => "Room name is required.",
        "error_room_name_exists" => "This name already exists.",
        "error_delete_forbidden" => "Only the owner or admin can delete.",
        "error_schema_not_ready" => "⚠ Database not initialized. Run init_db.php.",

        "chat_title" => "MiniChat - Room {{room}}",
        "chat_room" => "Room: {{room}}",
        "chat_messages" => "Messages",
        "chat_none" => "No messages yet.",
        "chat_refresh" => "Refresh",
        "chat_write" => "Write a message",
        "chat_placeholder" => "Type your message...",
        "chat_send" => "Send",

        "ai_title" => "AI Assistant • MiniChat",
        "ai_badge" => "AI Assistant • free & fast",
        "ai_h1" => "Chat with the AI",
        "ai_helper" => "The AI replies in the language you choose.",
        "ai_conversation" => "AI conversation",
        "ai_models_tag" => "Groq models auto-updated",
        "ai_assistant_name" => "Assistant",
        "ai_you" => "You",
        "ai_greeting" => "Hi {{pseudo}}! Ask me anything.",
        "ai_message_label" => "Message",
        "ai_reply_language" => "Reply language: {{lang}}",
        "ai_prompt_placeholder" => "e.g. Explain a concept...",
        "ai_model_label" => "Model (dynamic loading)",
        "ai_send" => "Send to AI",
        "ai_note_1" => "💡 Models are fetched automatically from Groq.",
        "ai_note_2" => "No data is stored.",
        "ai_loading" => "Loading...",
        "ai_load_error" => "Load error",
        "ai_api_error" => "API error",
        "ai_network_error" => "Network error",

        "loadmsg_pick_room" => "Please pick a room first.",
        "loadmsg_table_missing" => "⚠ Messages table missing. Run init_db.php.",
        "loadmsg_none" => "No messages yet.",

        "admin_login_title" => "Admin Login",
        "admin_login_h2" => "Admin Sign-in",
        "admin_login_pwd" => "Admin password:",
        "admin_login_btn" => "Sign in",

        "game_2048_title" => "2048 • MiniChat",
        "game_2048_badge" => "MiniChat • 2048",
        "game_2048_h1" => "2048",
        "game_2048_rules" => "Arrow keys/WASD or swipe • Enter to restart",
        "game_2048_new" => "New game",
        "game_2048_continue" => "Continue",
        "game_2048_score" => "Score",
        "game_2048_best" => "Best",
        "game_2048_saved" => "Saved",
        "game_2048_saving" => "Saving...",
        "game_2048_save_error" => "Save error",
        "game_2048_leaderboard" => "Leaderboard",
        "game_2048_play" => "Play 2048",
        "game_2048_need_login" => "Please sign in to play.",
        "game_2048_guest_note" => "Guest mode: no saving or leaderboard.",
        "game_2048_login_to_save" => "Sign in to save.",
        "login_username" => "Username",
        "login_password" => "Password",
        "login_required" => "Required",
        "login_btn" => "Sign in",
        "signup_btn" => "Sign up",

        "game_ms_title" => "Minesweeper • MiniChat",
        "game_ms_badge" => "MiniChat • Minesweeper",
        "game_ms_h1" => "Minesweeper",
        "game_ms_rules" => "Click/tap: reveal • Right click/long press: flag • Toggle 🚩 Flag Mode",
        "game_ms_new" => "New game",
        "game_ms_continue" => "Continue",
        "game_ms_difficulty" => "Difficulty",
        "game_ms_beginner" => "Beginner",
        "game_ms_intermediate" => "Intermediate",
        "game_ms_expert" => "Expert",
        "game_ms_mines" => "Mines",
        "game_ms_flags" => "Flags",
        "game_ms_time" => "Time",
        "game_ms_saved" => "Saved",
        "game_ms_saving" => "Saving...",
        "game_ms_save_error" => "Save error",
        "game_ms_leaderboard" => "Leaderboard",
        "game_ms_guest_note" => "Guest mode: no saving or leaderboard.",
        "game_ms_login_to_save" => "Sign in to save.",
        "game_ms_flag_mode_on" => "🚩 Flag: ON",
        "game_ms_flag_mode_off" => "🚩 Flag: OFF",

        "profile_title" => "Profile",
        "profile_badge" => "MiniChat • Profile",
        "profile_h1" => "Profile",
        "profile_helper" => "Your stats and best scores.",
        "profile_overview" => "Overview",
        "profile_messages" => "Messages sent",
        "profile_rooms_created" => "Rooms created",
        "profile_last_login" => "Last login",
        "profile_last_ip" => "Last IP",
        "profile_best" => "Best scores",
        "profile_best_tag" => "You",
        "profile_ms_beginner" => "Minesweeper • Beginner",
        "profile_ms_intermediate" => "Minesweeper • Intermediate",
        "profile_ms_expert" => "Minesweeper • Expert",
        "profile_play_2048" => "Play 2048",
        "profile_play_ms" => "Play minesweeper",

        "leaderboard_title" => "Leaderboards",
        "leaderboard_badge" => "MiniChat • Leaderboards",
        "leaderboard_h1" => "Leaderboards",
        "leaderboard_helper" => "Top scores and best times.",
        "leaderboard_nav_2048" => "2048",
        "leaderboard_nav_ms" => "Minesweeper",
        "leaderboard_ms_title" => "Minesweeper",
        "leaderboard_ms_beginner" => "Beginner",
        "leaderboard_ms_intermediate" => "Intermediate",
        "leaderboard_ms_expert" => "Expert",
        "leaderboard_rank" => "Rank",
        "leaderboard_empty" => "No data yet.",
    ],
    "zh" => [
        "language" => "语言",
        "connected_as" => "已登录：{{pseudo}}",

        "nav_ai" => "AI 助手",
        "nav_chat" => "进入聊天",
        "nav_rooms" => "切换聊天室",
        "nav_profile" => "个人资料",
        "nav_leaderboard" => "排行榜",
        "nav_logout" => "退出登录",
        "nav_admin" => "管理员",

        "rooms_badge" => "MiniChat • 聊天室",
        "rooms_title" => "聊天室",
        "rooms_manage" => "管理聊天室",
        "rooms_create" => "创建聊天室",
        "rooms_delete" => "删除聊天室",
        "rooms_existing" => "已有聊天室",
        "rooms_none" => "暂无可用聊天室。",
        "rooms_none_deletable" => "没有可删除的聊天室。",
        "rooms_creator" => "创建者：{{creator}}",
        "rooms_name" => "聊天室名称",
        "rooms_password_optional" => "密码（可选）",
        "rooms_password" => "密码",
        "rooms_open" => "公开",
        "rooms_join" => "加入",
        "rooms_create_btn" => "创建",
        "rooms_delete_btn" => "删除",
        "confirm_delete_room" => "确认删除这个聊天室？",
        "error_room_not_found" => "未找到该聊天室。",
        "error_password_incorrect" => "密码错误。",
        "error_room_name_required" => "请填写聊天室名称。",
        "error_room_name_exists" => "该名称已存在。",
        "error_delete_forbidden" => "只有创建者或管理员可以删除。",
        "error_schema_not_ready" => "⚠ 数据库未初始化，请先运行 init_db.php。",

        "chat_title" => "MiniChat - 聊天室 {{room}}",
        "chat_room" => "聊天室：{{room}}",
        "chat_messages" => "消息",
        "chat_none" => "暂时还没有消息。",
        "chat_refresh" => "刷新",
        "chat_write" => "发送消息",
        "chat_placeholder" => "输入消息...",
        "chat_send" => "发送",

        "ai_title" => "AI 助手 • MiniChat",
        "ai_badge" => "AI 助手 • 免费 & 快速",
        "ai_h1" => "和 AI 聊天",
        "ai_helper" => "AI 会按你选择的语言回复。",
        "ai_conversation" => "AI 对话",
        "ai_models_tag" => "Groq 模型自动更新",
        "ai_assistant_name" => "助手",
        "ai_you" => "你",
        "ai_greeting" => "你好 {{pseudo}}！请告诉我你的问题。",
        "ai_message_label" => "内容",
        "ai_reply_language" => "回复语言：{{lang}}",
        "ai_prompt_placeholder" => "例如：解释一个概念……",
        "ai_model_label" => "模型（动态加载）",
        "ai_send" => "发送给 AI",
        "ai_note_1" => "💡 模型列表会从 Groq 自动获取。",
        "ai_note_2" => "不会存储任何数据。",
        "ai_loading" => "加载中...",
        "ai_load_error" => "加载失败",
        "ai_api_error" => "接口错误",
        "ai_network_error" => "网络错误",

        "loadmsg_pick_room" => "请先选择一个聊天室。",
        "loadmsg_table_missing" => "⚠ 缺少 messages 表，请先运行 init_db.php。",
        "loadmsg_none" => "暂时还没有消息。",

        "admin_login_title" => "管理员登录",
        "admin_login_h2" => "管理员登录",
        "admin_login_pwd" => "管理员密码：",
        "admin_login_btn" => "登录",

        "game_2048_title" => "2048 • MiniChat",
        "game_2048_badge" => "MiniChat • 2048",
        "game_2048_h1" => "2048",
        "game_2048_rules" => "方向键/WASD 或滑动操作 • Enter 重新开始",
        "game_2048_new" => "新游戏",
        "game_2048_continue" => "继续游戏",
        "game_2048_score" => "分数",
        "game_2048_best" => "最高分",
        "game_2048_saved" => "已保存",
        "game_2048_saving" => "保存中...",
        "game_2048_save_error" => "保存失败",
        "game_2048_leaderboard" => "排行榜",
        "game_2048_play" => "开始 2048",
        "game_2048_need_login" => "请先登录再开始游戏。",
        "game_2048_guest_note" => "游客模式：不保存进度、不计入排行榜。",
        "game_2048_login_to_save" => "登录后才能保存。",
        "login_username" => "用户名",
        "login_password" => "密码",
        "login_required" => "必填",
        "login_btn" => "登录",
        "signup_btn" => "注册",

        "game_ms_title" => "扫雷 • MiniChat",
        "game_ms_badge" => "MiniChat • 扫雷",
        "game_ms_h1" => "扫雷",
        "game_ms_rules" => "点击：打开 • 右键/长按：插旗 • 点击 🚩 切换插旗模式",
        "game_ms_new" => "新游戏",
        "game_ms_continue" => "继续游戏",
        "game_ms_difficulty" => "难度",
        "game_ms_beginner" => "初级",
        "game_ms_intermediate" => "中级",
        "game_ms_expert" => "高级",
        "game_ms_mines" => "地雷",
        "game_ms_flags" => "旗子",
        "game_ms_time" => "时间",
        "game_ms_saved" => "已保存",
        "game_ms_saving" => "保存中...",
        "game_ms_save_error" => "保存失败",
        "game_ms_leaderboard" => "排行榜",
        "game_ms_guest_note" => "游客模式：不保存进度、不计入排行榜。",
        "game_ms_login_to_save" => "登录后才能保存。",
        "game_ms_flag_mode_on" => "🚩 插旗：开",
        "game_ms_flag_mode_off" => "🚩 插旗：关",

        "profile_title" => "个人资料",
        "profile_badge" => "MiniChat • 个人资料",
        "profile_h1" => "个人资料",
        "profile_helper" => "查看你的统计和最佳成绩。",
        "profile_overview" => "概览",
        "profile_messages" => "发送消息数",
        "profile_rooms_created" => "创建聊天室数",
        "profile_last_login" => "最近登录",
        "profile_last_ip" => "最近 IP",
        "profile_best" => "最佳成绩",
        "profile_best_tag" => "我的",
        "profile_ms_beginner" => "扫雷 • 初级",
        "profile_ms_intermediate" => "扫雷 • 中级",
        "profile_ms_expert" => "扫雷 • 高级",
        "profile_play_2048" => "去玩 2048",
        "profile_play_ms" => "去玩扫雷",

        "leaderboard_title" => "排行榜",
        "leaderboard_badge" => "MiniChat • 排行榜",
        "leaderboard_h1" => "排行榜",
        "leaderboard_helper" => "2048 分数与扫雷最佳时间。",
        "leaderboard_nav_2048" => "2048",
        "leaderboard_nav_ms" => "扫雷",
        "leaderboard_ms_title" => "扫雷",
        "leaderboard_ms_beginner" => "初级",
        "leaderboard_ms_intermediate" => "中级",
        "leaderboard_ms_expert" => "高级",
        "leaderboard_rank" => "排名",
        "leaderboard_empty" => "暂无数据。",
    ],
];

function minichat_lang(): string {
    return (string)($GLOBALS["MINICHAT_LANG"] ?? "fr");
}

function minichat_html_lang(): string {
    $lang = minichat_lang();
    return $lang === "zh" ? "zh-CN" : $lang;
}

function t(string $key, array $vars = []): string {
    $lang = minichat_lang();
    $all = $GLOBALS["MINICHAT_I18N"] ?? [];
    $fallback = $all["fr"] ?? [];
    $dict = $all[$lang] ?? [];
    $text = $dict[$key] ?? ($fallback[$key] ?? $key);
    foreach ($vars as $k => $v) {
        $text = str_replace("{{{$k}}}", (string)$v, $text);
    }
    return $text;
}

function minichat_lang_label(string $lang): string {
    return match ($lang) {
        "zh" => "中文",
        "en" => "EN",
        default => "FR",
    };
}

function minichat_url_with_lang(string $lang): string {
    $uri = (string)($_SERVER["REQUEST_URI"] ?? "");
    $parts = parse_url($uri);
    $path = $parts["path"] ?? "";
    $query = [];
    parse_str($parts["query"] ?? "", $query);
    $query["lang"] = $lang;
    $qs = http_build_query($query);
    return $path . ($qs !== "" ? ("?" . $qs) : "");
}

function render_lang_switcher(): string {
    $current = minichat_lang();
    $langs = ["zh", "en", "fr"];
    $out = '<div class="lang-switch" aria-label="' . htmlentities(t("language")) . '">';
    foreach ($langs as $lang) {
        $isActive = $lang === $current;
        $classes = "btn btn-secondary" . ($isActive ? " is-active" : "");
        $out .= '<a class="' . $classes . '" href="' . htmlentities(minichat_url_with_lang($lang)) . '" ' .
            ($isActive ? 'aria-current="true"' : '') . '>' .
            htmlentities(minichat_lang_label($lang)) .
            "</a>";
    }
    $out .= "</div>";
    return $out;
}
