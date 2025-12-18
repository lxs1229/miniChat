(() => {
  const SUPPORTED_LANGS = ["zh", "en", "fr"];

  const I18N = {
    zh: {
      pageTitle: "LXS 项目合集",
      badge: "LXS • Projects",
      h1: "LXS 项目合集",
      subtitle: "一个统一入口页面，方便集中管理与跳转到各个小项目。",
      quickTitle: "快速入口",
      quickHint: "",
      projectsAria: "项目列表",
      langLabel: "语言",
      username: "用户名",
      password: "密码",
      required: "必填",
      usernamePh: "输入用户名",
      passwordPh: "输入密码",
      enterMiniChat: "进入 MiniChat",
      signUp: "注册账号",
      admin: "管理员入口",
      templateTipsTitle: "建议结构：",
      templateTips: ["项目名 + 简短描述", "标签（技术栈/状态）", "一个或多个按钮（进入/文档/后台等）"],
      loadFailed: "项目配置加载失败：",
    },
    en: {
      pageTitle: "LXS Projects Hub",
      badge: "LXS • Projects",
      h1: "LXS Projects Hub",
      subtitle: "A single entry page to manage and jump into your mini projects.",
      quickTitle: "Quick Access",
      quickHint: "",
      projectsAria: "Project list",
      langLabel: "Language",
      username: "Username",
      password: "Password",
      required: "Required",
      usernamePh: "Enter username",
      passwordPh: "Enter password",
      enterMiniChat: "Enter MiniChat",
      signUp: "Sign up",
      admin: "Admin",
      templateTipsTitle: "Suggested structure:",
      templateTips: ["Project name + short description", "Tags (stack/status)", "Buttons (Open/Docs/Admin, etc.)"],
      loadFailed: "Failed to load projects config: ",
    },
    fr: {
      pageTitle: "LXS • Projets",
      badge: "LXS • Projects",
      h1: "LXS • Projets",
      subtitle: "Une page d’entrée unique pour gérer et accéder à tes mini-projets.",
      quickTitle: "Accès rapide",
      quickHint: "",
      projectsAria: "Liste des projets",
      langLabel: "Langue",
      username: "Pseudo",
      password: "Mot de passe",
      required: "Obligatoire",
      usernamePh: "Saisir le pseudo",
      passwordPh: "Saisir le mot de passe",
      enterMiniChat: "Entrer dans MiniChat",
      signUp: "Créer un compte",
      admin: "Admin",
      templateTipsTitle: "Structure conseillée :",
      templateTips: ["Nom + description courte", "Tags (stack/statut)", "Boutons (Entrer/Docs/Admin, etc.)"],
      loadFailed: "Échec de chargement de la config projets : ",
    },
  };

  function getLangFromUrl() {
    const params = new URLSearchParams(location.search);
    const lang = (params.get("lang") || "").toLowerCase();
    return SUPPORTED_LANGS.includes(lang) ? lang : null;
  }

  function getLang() {
    const fromUrl = getLangFromUrl();
    if (fromUrl) return fromUrl;
    const stored = (localStorage.getItem("lxs_lang") || "").toLowerCase();
    if (SUPPORTED_LANGS.includes(stored)) return stored;
    const nav = (navigator.language || "en").toLowerCase();
    if (nav.startsWith("zh")) return "zh";
    if (nav.startsWith("fr")) return "fr";
    return "en";
  }

  function setLang(lang) {
    localStorage.setItem("lxs_lang", lang);
    const url = new URL(location.href);
    url.searchParams.set("lang", lang);
    history.replaceState({}, "", url);
  }

  function t(lang, key) {
    return (I18N[lang] && I18N[lang][key]) || (I18N.en && I18N.en[key]) || key;
  }

  function textByLang(obj, lang) {
    if (!obj || typeof obj !== "object") return "";
    return obj[lang] ?? obj.en ?? obj.fr ?? obj.zh ?? "";
  }

  function el(tag, className, text) {
    const node = document.createElement(tag);
    if (className) node.className = className;
    if (text !== undefined) node.textContent = text;
    return node;
  }

  function renderLangSwitch(lang) {
    const wrap = el("div", "lang-switch");
    wrap.setAttribute("aria-label", t(lang, "langLabel"));

    const options = [
      { code: "zh", label: "中文" },
      { code: "en", label: "EN" },
      { code: "fr", label: "FR" },
    ];

    options.forEach(({ code, label }) => {
      const a = el("a", "btn btn-secondary" + (code === lang ? " is-active" : ""), label);
      a.href = "#";
      a.addEventListener("click", (e) => {
        e.preventDefault();
        setLang(code);
        bootstrap();
      });
      wrap.appendChild(a);
    });

    return wrap;
  }

  function renderMiniChatCard(project, lang) {
    const card = el("article", "project-card");
    card.setAttribute("aria-label", textByLang(project.title, lang) || "MiniChat");

    const top = el("div", "project-card__top");
    const left = el("div");
    left.appendChild(el("div", "project-card__title", textByLang(project.title, lang)));
    left.appendChild(el("div", "project-card__desc", textByLang(project.description, lang)));
    top.appendChild(left);

    const tags = el("div", "project-card__tags");
    (project.tags || []).forEach((tag) => tags.appendChild(el("span", "tag", tag)));
    top.appendChild(tags);

    const body = el("div", "project-card__body");
    const form = el("form", "form");
    form.method = "post";
    const urlParams = new URLSearchParams(location.search);
    const next = urlParams.get("next");
    if (next && next.startsWith("/") && !next.startsWith("//")) {
      form.action = `login.php?lang=${encodeURIComponent(lang)}&next=${encodeURIComponent(next)}`;
    } else {
      form.action = `login.php?lang=${encodeURIComponent(lang)}`;
    }
    form.setAttribute("aria-label", "MiniChat Login");

    const split = el("div", "layout-split");

    const f1 = el("div", "field");
    const lr1 = el("div", "label-row");
    const l1 = el("label", null, t(lang, "username"));
    l1.setAttribute("for", "pseudo");
    lr1.appendChild(l1);
    lr1.appendChild(el("span", "muted", t(lang, "required")));
    f1.appendChild(lr1);
    const i1 = document.createElement("input");
    i1.id = "pseudo";
    i1.type = "text";
    i1.name = "pseudo";
    i1.placeholder = t(lang, "usernamePh");
    i1.required = true;
    f1.appendChild(i1);

    const f2 = el("div", "field");
    const lr2 = el("div", "label-row");
    const l2 = el("label", null, t(lang, "password"));
    l2.setAttribute("for", "mdp");
    lr2.appendChild(l2);
    lr2.appendChild(el("span", "muted", t(lang, "required")));
    f2.appendChild(lr2);
    const i2 = document.createElement("input");
    i2.id = "mdp";
    i2.type = "password";
    i2.name = "mdp";
    i2.placeholder = t(lang, "passwordPh");
    i2.required = true;
    f2.appendChild(i2);

    split.appendChild(f1);
    split.appendChild(f2);
    form.appendChild(split);

    const actions = el("div", "actions");
    const btn = document.createElement("button");
    btn.className = "btn";
    btn.type = "submit";
    btn.textContent = t(lang, "enterMiniChat");
    actions.appendChild(btn);

    const signup = el("a", "btn btn-secondary", t(lang, "signUp"));
    signup.href = `inscription.html?lang=${encodeURIComponent(lang)}`;
    actions.appendChild(signup);

    const admin = el("a", "btn btn-secondary", t(lang, "admin"));
    admin.href = `admin_login.php?lang=${encodeURIComponent(lang)}`;
    actions.appendChild(admin);

    form.appendChild(actions);
    body.appendChild(form);

    card.appendChild(top);
    card.appendChild(body);
    return card;
  }

  function renderTemplateCard(project, lang) {
    const card = el("article", "project-card project-card--ghost");
    card.setAttribute("aria-label", textByLang(project.title, lang) || "Template");

    const top = el("div", "project-card__top");
    const left = el("div");
    left.appendChild(el("div", "project-card__title", textByLang(project.title, lang)));
    left.appendChild(el("div", "project-card__desc", textByLang(project.description, lang)));
    top.appendChild(left);

    const tags = el("div", "project-card__tags");
    (project.tags || []).forEach((tag) => tags.appendChild(el("span", "tag", tag)));
    top.appendChild(tags);

    const body = el("div", "project-card__body");
    const muted = el("div", "muted");
    muted.appendChild(document.createTextNode(t(lang, "templateTipsTitle")));
    const ul = el("ul", "hub-list");
    t(lang, "templateTips").forEach((line) => ul.appendChild(el("li", null, line)));
    muted.appendChild(ul);
    body.appendChild(muted);

    card.appendChild(top);
    card.appendChild(body);
    return card;
  }

  function renderGenericCard(project, lang) {
    const card = el("article", "project-card");
    card.setAttribute("aria-label", textByLang(project.title, lang) || project.id || "Project");

    const top = el("div", "project-card__top");
    const left = el("div");
    left.appendChild(el("div", "project-card__title", textByLang(project.title, lang)));
    left.appendChild(el("div", "project-card__desc", textByLang(project.description, lang)));
    top.appendChild(left);

    const tags = el("div", "project-card__tags");
    (project.tags || []).forEach((tag) => tags.appendChild(el("span", "tag", tag)));
    top.appendChild(tags);

    const body = el("div", "project-card__body");
    const actions = el("div", "actions");
    (project.links || []).forEach((link) => {
      const a = el("a", "btn" + (link.variant === "secondary" ? " btn-secondary" : ""), textByLang(link.label, lang) || link.label || "Open");
      let href = link.href || "#";
      if (href !== "#" && !/^https?:\/\//i.test(href) && !/^mailto:/i.test(href)) {
        try {
          const url = new URL(href, location.href);
          if (!url.searchParams.has("lang")) url.searchParams.set("lang", lang);
          href = url.pathname + (url.search ? url.search : "") + (url.hash ? url.hash : "");
        } catch {
          // keep as-is
        }
      }
      a.href = href;
      actions.appendChild(a);
    });
    body.appendChild(actions);

    card.appendChild(top);
    card.appendChild(body);
    return card;
  }

  async function loadConfig() {
    const res = await fetch("projects.json", { cache: "no-store" });
    if (!res.ok) throw new Error(`${res.status} ${res.statusText}`);
    return res.json();
  }

  async function bootstrap() {
    const lang = getLang();
    setLang(lang);

    document.documentElement.lang = lang === "zh" ? "zh-CN" : lang;
    document.title = t(lang, "pageTitle");

    const app = document.getElementById("projectsApp");
    app.innerHTML = "";

    const header = el("header", "hub__header");
    const left = el("div");
    left.appendChild(el("div", "badge", t(lang, "badge")));
    left.appendChild(el("h1", null, t(lang, "h1")));
    left.appendChild(el("p", null, t(lang, "subtitle")));
    header.appendChild(left);

    const meta = el("div", "hub__meta");
    meta.appendChild(el("div", "hub__meta-title", t(lang, "quickTitle")));
    const hint = t(lang, "quickHint");
    if (hint) meta.appendChild(el("div", "muted", hint));
    meta.appendChild(renderLangSwitch(lang));
    header.appendChild(meta);
    app.appendChild(header);

    const section = el("section", "project-grid");
    section.setAttribute("aria-label", t(lang, "projectsAria"));
    app.appendChild(section);

    try {
      const config = await loadConfig();
      const projects = (config && config.projects) || [];

      projects.forEach((p) => {
        if (p.type === "minichat") section.appendChild(renderMiniChatCard(p, lang));
        else if (p.type === "template") section.appendChild(renderTemplateCard(p, lang));
        else section.appendChild(renderGenericCard(p, lang));
      });
    } catch (err) {
      const card = el("article", "project-card project-card--ghost");
      card.appendChild(el("div", "project-card__title", t(lang, "loadFailed") + String(err && err.message ? err.message : err)));
      section.appendChild(card);
    }
  }

  window.addEventListener("popstate", bootstrap);
  bootstrap();
})();
