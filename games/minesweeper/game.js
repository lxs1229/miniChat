(() => {
  const cfg = window.__MINICHAT_MS__ || { isLoggedIn: false, labels: {} };
  const API_BASE = "./api";
  const canPersist = Boolean(cfg.isLoggedIn);

  const boardEl = document.getElementById("board");
  const difficultyEl = document.getElementById("difficulty");
  const minesLeftEl = document.getElementById("minesLeft");
  const flagsEl = document.getElementById("flags");
  const timeEl = document.getElementById("time");
  const saveStatusEl = document.getElementById("saveStatus");
  const leaderboardEl = document.getElementById("leaderboard");
  const newBtn = document.getElementById("newGame");
  const contBtn = document.getElementById("continueGame");
  const overlay = document.getElementById("overlay");
  const overlayTitle = document.getElementById("overlayTitle");
  const overlayNew = document.getElementById("overlayNew");

  const LEVELS = {
    beginner: { w: 9, h: 9, m: 10, cell: 36 },
    intermediate: { w: 16, h: 16, m: 40, cell: 30 },
    expert: { w: 30, h: 16, m: 99, cell: 26 },
  };

  const state = {
    difficulty: "beginner",
    w: 9,
    h: 9,
    mines: 10,
    firstClick: true,
    startedAt: 0,
    elapsedMs: 0,
    timer: null,
    over: false,
    won: false,
    mine: [],
    adj: [],
    revealed: [],
    flagged: [],
    saveTimer: null,
    bestTimeMs: null,
  };

  function setStatus(type) {
    if (!saveStatusEl) return;
    if (!type) {
      saveStatusEl.textContent = "";
      return;
    }
    const map = {
      saving: cfg.labels.saving || "Saving...",
      saved: cfg.labels.saved || "Saved",
      error: cfg.labels.saveError || "Save error",
      login: cfg.labels.loginToSave || "Sign in to save.",
    };
    saveStatusEl.textContent = map[type] || type;
  }

  function idx(x, y) {
    return y * state.w + x;
  }

  function inBounds(x, y) {
    return x >= 0 && y >= 0 && x < state.w && y < state.h;
  }

  function neighbors(x, y) {
    const out = [];
    for (let dy = -1; dy <= 1; dy++) {
      for (let dx = -1; dx <= 1; dx++) {
        if (dx === 0 && dy === 0) continue;
        const nx = x + dx;
        const ny = y + dy;
        if (inBounds(nx, ny)) out.push(idx(nx, ny));
      }
    }
    return out;
  }

  function resetArrays() {
    const n = state.w * state.h;
    state.mine = new Array(n).fill(false);
    state.adj = new Array(n).fill(0);
    state.revealed = new Array(n).fill(false);
    state.flagged = new Array(n).fill(false);
  }

  function placeMines(avoidIdx) {
    const n = state.w * state.h;
    const forbidden = new Set([avoidIdx, ...neighbors(avoidIdx % state.w, Math.floor(avoidIdx / state.w))]);
    let placed = 0;
    while (placed < state.mines) {
      const r = Math.floor(Math.random() * n);
      if (state.mine[r]) continue;
      if (forbidden.has(r)) continue;
      state.mine[r] = true;
      placed++;
    }
    // compute adjacency
    for (let y = 0; y < state.h; y++) {
      for (let x = 0; x < state.w; x++) {
        const i = idx(x, y);
        if (state.mine[i]) continue;
        const count = neighbors(x, y).reduce((acc, ni) => acc + (state.mine[ni] ? 1 : 0), 0);
        state.adj[i] = count;
      }
    }
  }

  function startTimer() {
    if (state.timer) return;
    state.startedAt = performance.now() - state.elapsedMs;
    state.timer = setInterval(() => {
      state.elapsedMs = Math.max(0, Math.round(performance.now() - state.startedAt));
      renderTime();
    }, 100);
  }

  function stopTimer() {
    if (state.timer) clearInterval(state.timer);
    state.timer = null;
  }

  function renderTime() {
    timeEl.textContent = `${(state.elapsedMs / 1000).toFixed(1)}s`;
  }

  function countFlags() {
    return state.flagged.reduce((a, v) => a + (v ? 1 : 0), 0);
  }

  function renderStats() {
    const flags = countFlags();
    flagsEl.textContent = String(flags);
    minesLeftEl.textContent = String(Math.max(0, state.mines - flags));
    renderTime();
  }

  function cellText(i) {
    if (!state.revealed[i]) return state.flagged[i] ? "🚩" : "";
    if (state.mine[i]) return "💣";
    const n = state.adj[i];
    return n === 0 ? "" : String(n);
  }

  function cellClass(i) {
    const classes = ["ms-cell"];
    if (state.revealed[i]) classes.push("revealed");
    if (state.flagged[i]) classes.push("flagged");
    if (state.mine[i]) classes.push("mine");
    const n = state.adj[i];
    if (state.revealed[i] && !state.mine[i] && n > 0) classes.push(`n${n}`);
    return classes.join(" ");
  }

  function renderBoard() {
    boardEl.style.setProperty("--cols", String(state.w));
    const level = LEVELS[state.difficulty];
    boardEl.style.setProperty("--cell", `${level.cell}px`);
    boardEl.innerHTML = "";
    const n = state.w * state.h;
    for (let i = 0; i < n; i++) {
      const b = document.createElement("button");
      b.type = "button";
      b.className = cellClass(i);
      b.dataset.i = String(i);
      b.textContent = cellText(i);
      b.addEventListener("click", (e) => {
        e.preventDefault();
        onReveal(i);
      });
      b.addEventListener("contextmenu", (e) => {
        e.preventDefault();
        onFlag(i);
      });
      b.addEventListener("dblclick", (e) => {
        e.preventDefault();
        onChord(i);
      });
      boardEl.appendChild(b);
    }
    renderStats();
  }

  function updateCell(i) {
    const el = boardEl.querySelector(`.ms-cell[data-i="${i}"]`);
    if (!el) return;
    el.className = cellClass(i);
    el.textContent = cellText(i);
  }

  function revealFlood(start) {
    const q = [start];
    const seen = new Set([start]);
    while (q.length) {
      const i = q.shift();
      if (state.revealed[i] || state.flagged[i]) continue;
      state.revealed[i] = true;
      updateCell(i);
      if (state.adj[i] !== 0) continue;
      const x = i % state.w;
      const y = Math.floor(i / state.w);
      for (const ni of neighbors(x, y)) {
        if (seen.has(ni)) continue;
        seen.add(ni);
        if (!state.revealed[ni] && !state.mine[ni]) q.push(ni);
      }
    }
  }

  function checkWin() {
    const n = state.w * state.h;
    let revealedSafe = 0;
    let totalSafe = 0;
    for (let i = 0; i < n; i++) {
      if (!state.mine[i]) {
        totalSafe++;
        if (state.revealed[i]) revealedSafe++;
      }
    }
    return revealedSafe === totalSafe;
  }

  function showOverlay(show, title) {
    overlay.hidden = !show;
    if (show) overlayTitle.textContent = title;
  }

  function gameOver(won) {
    state.over = true;
    state.won = won;
    stopTimer();
    // reveal all mines if lost
    if (!won) {
      for (let i = 0; i < state.w * state.h; i++) {
        if (state.mine[i]) {
          state.revealed[i] = true;
          updateCell(i);
        }
      }
      showOverlay(true, "Game Over");
    } else {
      showOverlay(true, "You Win!");
      if (state.bestTimeMs === null || state.elapsedMs < state.bestTimeMs) state.bestTimeMs = state.elapsedMs;
    }
    scheduleSave(true);
    loadLeaderboard();
  }

  function onReveal(i) {
    if (state.over) return;
    if (state.flagged[i]) return;
    if (state.firstClick) {
      state.firstClick = false;
      placeMines(i);
      startTimer();
    }
    if (state.mine[i]) {
      state.revealed[i] = true;
      updateCell(i);
      gameOver(false);
      return;
    }
    revealFlood(i);
    if (checkWin()) {
      gameOver(true);
      return;
    }
    scheduleSave(false);
    renderStats();
  }

  function onFlag(i) {
    if (state.over) return;
    if (state.revealed[i]) return;
    state.flagged[i] = !state.flagged[i];
    updateCell(i);
    renderStats();
    scheduleSave(false);
  }

  function onChord(i) {
    if (state.over) return;
    if (!state.revealed[i]) return;
    if (state.mine[i]) return;
    const n = state.adj[i];
    if (n === 0) return;
    const x = i % state.w;
    const y = Math.floor(i / state.w);
    const neigh = neighbors(x, y);
    const flags = neigh.reduce((a, ni) => a + (state.flagged[ni] ? 1 : 0), 0);
    if (flags !== n) return;
    for (const ni of neigh) {
      if (!state.flagged[ni] && !state.revealed[ni]) onReveal(ni);
    }
  }

  function newGame() {
    stopTimer();
    state.elapsedMs = 0;
    state.firstClick = true;
    state.over = false;
    state.won = false;
    resetArrays();
    setStatus(canPersist ? "" : "login");
    showOverlay(false, "");
    renderBoard();
    scheduleSave(false);
  }

  function currentStatePayload() {
    return {
      v: 1,
      difficulty: state.difficulty,
      w: state.w,
      h: state.h,
      mines: state.mines,
      firstClick: state.firstClick,
      elapsedMs: state.elapsedMs,
      over: state.over,
      won: state.won,
      mine: state.mine.map((b) => (b ? 1 : 0)),
      adj: state.adj,
      revealed: state.revealed.map((b) => (b ? 1 : 0)),
      flagged: state.flagged.map((b) => (b ? 1 : 0)),
    };
  }

  async function saveNow(won) {
    if (!canPersist) return;
    try {
      const res = await fetch(`${API_BASE}/save.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          difficulty: state.difficulty,
          time_ms: state.elapsedMs,
          won: Boolean(won),
          state: currentStatePayload(),
        }),
      });
      if (!res.ok) throw new Error(`${res.status} ${res.statusText}`);
      setStatus("saved");
    } catch {
      setStatus("error");
    }
  }

  function scheduleSave(won) {
    if (!canPersist) {
      setStatus("login");
      return;
    }
    setStatus("saving");
    clearTimeout(state.saveTimer);
    state.saveTimer = setTimeout(() => saveNow(won), 450);
  }

  async function loadSave() {
    if (!canPersist) {
      contBtn.disabled = true;
      return null;
    }
    try {
      const res = await fetch(`${API_BASE}/load.php?difficulty=${encodeURIComponent(state.difficulty)}`, { cache: "no-store" });
      if (!res.ok) throw new Error(`${res.status} ${res.statusText}`);
      const data = await res.json();
      if (!data || !data.state) {
        contBtn.disabled = true;
        return null;
      }
      contBtn.disabled = false;
      state.bestTimeMs = data.best_time_ms ?? state.bestTimeMs;
      return data.state;
    } catch {
      contBtn.disabled = true;
      return null;
    }
  }

  function applyLoadedState(s) {
    if (!s || s.v !== 1) return false;
    if (s.difficulty !== state.difficulty) return false;
    state.w = s.w;
    state.h = s.h;
    state.mines = s.mines;
    state.firstClick = Boolean(s.firstClick);
    state.elapsedMs = Number(s.elapsedMs) || 0;
    state.over = Boolean(s.over);
    state.won = Boolean(s.won);

    resetArrays();
    const n = state.w * state.h;
    if (!Array.isArray(s.mine) || s.mine.length !== n) return false;
    if (!Array.isArray(s.adj) || s.adj.length !== n) return false;
    if (!Array.isArray(s.revealed) || s.revealed.length !== n) return false;
    if (!Array.isArray(s.flagged) || s.flagged.length !== n) return false;

    state.mine = s.mine.map((v) => v === 1);
    state.adj = s.adj.map((v) => Number(v) || 0);
    state.revealed = s.revealed.map((v) => v === 1);
    state.flagged = s.flagged.map((v) => v === 1);

    if (!state.firstClick && !state.over) startTimer();
    renderBoard();
    showOverlay(state.over, state.won ? "You Win!" : "Game Over");
    return true;
  }

  async function loadLeaderboard() {
    if (!canPersist) return;
    try {
      const res = await fetch(`${API_BASE}/leaderboard.php?difficulty=${encodeURIComponent(state.difficulty)}`, { cache: "no-store" });
      if (!res.ok) return;
      const rows = await res.json();
      if (!Array.isArray(rows)) return;
      leaderboardEl.innerHTML = "";
      rows.forEach((r, idx) => {
        const row = document.createElement("div");
        row.className = "lb-row";
        const rank = document.createElement("div");
        rank.className = "lb-rank";
        rank.textContent = `#${idx + 1}`;
        const name = document.createElement("div");
        name.className = "lb-name";
        name.textContent = r.pseudo || "-";
        const score = document.createElement("div");
        score.className = "lb-score";
        const ms = Number(r.best_time_ms) || 0;
        score.textContent = `${(ms / 1000).toFixed(2)}s`;
        row.appendChild(rank);
        row.appendChild(name);
        row.appendChild(score);
        leaderboardEl.appendChild(row);
      });
    } catch {
      // ignore
    }
  }

  function applyDifficulty(diff) {
    state.difficulty = diff;
    const level = LEVELS[diff] || LEVELS.beginner;
    state.w = level.w;
    state.h = level.h;
    state.mines = level.m;
    newGame();
    loadLeaderboard();
    loadSave();
  }

  newBtn.addEventListener("click", () => newGame());
  overlayNew.addEventListener("click", () => newGame());
  difficultyEl.addEventListener("change", () => applyDifficulty(difficultyEl.value));
  contBtn.addEventListener("click", async () => {
    const loaded = await loadSave();
    if (loaded) applyLoadedState(loaded);
  });

  // init
  contBtn.disabled = true;
  setStatus(canPersist ? "" : "login");
  applyDifficulty("beginner");
})();

