(() => {
  const cfg = window.__MINICHAT_2048__ || { lang: "fr", labels: {}, isLoggedIn: false };
  const API_BASE = "./api";

  const boardEl = document.querySelector(".board");
  const tileLayer = document.getElementById("tileLayer");
  const scoreEl = document.getElementById("score");
  const bestEl = document.getElementById("best");
  const saveStatusEl = document.getElementById("saveStatus");
  const leaderboardEl = document.getElementById("leaderboard");
  const newBtn = document.getElementById("newGame");
  const contBtn = document.getElementById("continueGame");
  const overlay = document.getElementById("overlay");
  const overlayTitle = document.getElementById("overlayTitle");
  const overlayNew = document.getElementById("overlayNew");

  const canPersist = Boolean(cfg.isLoggedIn);
  const MOVE_MS = 220;

  const COLORS = (v) => {
    if (v >= 2048) return "linear-gradient(135deg, rgba(34,211,238,0.65), rgba(124,58,237,0.65))";
    if (v >= 512) return "rgba(124, 58, 237, 0.60)";
    if (v >= 128) return "rgba(34, 211, 238, 0.60)";
    const map = {
      0: "rgba(255,255,255,0.06)",
      2: "rgba(34,211,238,0.10)",
      4: "rgba(34,211,238,0.16)",
      8: "rgba(124,58,237,0.20)",
      16: "rgba(124,58,237,0.28)",
      32: "rgba(245,158,11,0.22)",
      64: "rgba(245,158,11,0.32)",
    };
    return map[v] || "rgba(255,255,255,0.10)";
  };

  const state = {
    grid: new Array(16).fill(null), // tile objects or null
    score: 0,
    best: 0,
    hasSave: false,
    isOver: false,
    animating: false,
    saveTimer: null,
    tileSize: 0,
    gap: 10,
    nextId: 1,
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

  function computeTileMetrics() {
    if (!tileLayer) return;
    if (boardEl) {
      const styles = getComputedStyle(boardEl);
      const gap = parseFloat(styles.gap || styles.columnGap || "10");
      if (Number.isFinite(gap) && gap >= 0) state.gap = gap;
    }
    const w = tileLayer.clientWidth;
    state.tileSize = Math.max(0, (w - state.gap * 3) / 4);
  }

  function posForIdx(idx) {
    const col = idx % 4;
    const row = Math.floor(idx / 4);
    const x = col * (state.tileSize + state.gap);
    const y = row * (state.tileSize + state.gap);
    return { x, y };
  }

  function setTileTransform(el, idx) {
    const { x, y } = posForIdx(idx);
    const tr = `translate(${x}px, ${y}px)`;
    el.style.transform = tr;
    el.style.setProperty("--tile-transform", tr);
  }

  function ensureTileEl(tile) {
    let el = tileLayer.querySelector(`.tile[data-id="${tile.id}"]`);
    if (!el) {
      el = document.createElement("div");
      el.className = "tile";
      el.dataset.id = String(tile.id);
      tileLayer.appendChild(el);
    }
    el.textContent = String(tile.value);
    el.style.width = `${state.tileSize}px`;
    el.style.height = `${state.tileSize}px`;
    el.style.background = COLORS(tile.value);
    el.style.color = tile.value >= 128 ? "#0b1120" : "#e2e8f0";
    const base = Math.max(16, Math.round(state.tileSize * 0.38));
    const big = Math.max(14, Math.round(state.tileSize * 0.30));
    const huge = Math.max(12, Math.round(state.tileSize * 0.26));
    el.style.fontSize = tile.value >= 1024 ? `${huge}px` : tile.value >= 128 ? `${big}px` : `${base}px`;
    return el;
  }

  function removeTileElById(id) {
    const el = tileLayer.querySelector(`.tile[data-id="${id}"]`);
    if (el) el.remove();
  }

  function valuesArray() {
    return state.grid.map((t) => (t ? t.value : 0));
  }

  function emptyIdxs(grid) {
    const out = [];
    for (let i = 0; i < 16; i++) if (!grid[i]) out.push(i);
    return out;
  }

  function addRandomTile(grid) {
    const empties = emptyIdxs(grid);
    if (!empties.length) return null;
    const idx = empties[Math.floor(Math.random() * empties.length)];
    const value = Math.random() < 0.9 ? 2 : 4;
    const tile = { id: state.nextId++, value, idx };
    grid[idx] = tile;
    return tile;
  }

  function checkGameOver(values) {
    if (values.some((v) => v === 0)) return false;
    for (let r = 0; r < 4; r++) {
      for (let c = 0; c < 4; c++) {
        const idx = r * 4 + c;
        const v = values[idx];
        if (c < 3 && v === values[idx + 1]) return false;
        if (r < 3 && v === values[idx + 4]) return false;
      }
    }
    return true;
  }

  function showOverlay(show) {
    if (!overlay) return;
    overlay.hidden = !show;
    if (show && overlayTitle) overlayTitle.textContent = "Game Over";
  }

  function renderScores() {
    scoreEl.textContent = String(state.score);
    bestEl.textContent = String(state.best);
  }

  function renderAllTiles() {
    computeTileMetrics();
    // remove stray elements
    const alive = new Set(state.grid.filter(Boolean).map((t) => String(t.id)));
    Array.from(tileLayer.querySelectorAll(".tile")).forEach((el) => {
      if (!alive.has(el.dataset.id)) el.remove();
    });

    state.grid.forEach((tile, idx) => {
      if (!tile) return;
      tile.idx = idx;
      const el = ensureTileEl(tile);
      el.style.transition = "none";
      setTileTransform(el, idx);
      // force reflow then restore
      void el.offsetHeight;
      el.style.transition = `transform ${MOVE_MS}ms ease`;
    });
    renderScores();
    showOverlay(state.isOver);
  }

  function getLines(dir) {
    const lines = [];
    const rowColToIdx = (r, c) => r * 4 + c;
    if (dir === "left" || dir === "right") {
      for (let r = 0; r < 4; r++) {
        const line = [];
        for (let c = 0; c < 4; c++) line.push(rowColToIdx(r, c));
        if (dir === "right") line.reverse();
        lines.push(line);
      }
    } else {
      for (let c = 0; c < 4; c++) {
        const line = [];
        for (let r = 0; r < 4; r++) line.push(rowColToIdx(r, c));
        if (dir === "down") line.reverse();
        lines.push(line);
      }
    }
    return lines;
  }

  function computeMove(dir) {
    const oldGrid = state.grid;
    const newGrid = new Array(16).fill(null);
    const moves = []; // {id, fromIdx, toIdx}
    const merges = []; // {fromIds, toIdx, newTile}
    let gained = 0;

    for (let i = 0; i < 16; i++) if (oldGrid[i]) oldGrid[i].idx = i;

    const lines = getLines(dir);
    for (const line of lines) {
      const tiles = line.map((idx) => oldGrid[idx]).filter(Boolean);
      let write = 0;
      let read = 0;
      while (read < tiles.length) {
        const t1 = tiles[read];
        const t2 = tiles[read + 1];
        const toIdx = line[write];
        if (t2 && t1.value === t2.value) {
          moves.push({ id: t1.id, fromIdx: t1.idx, toIdx });
          moves.push({ id: t2.id, fromIdx: t2.idx, toIdx });
          const newTile = { id: state.nextId++, value: t1.value * 2, idx: toIdx };
          merges.push({ fromIds: [t1.id, t2.id], toIdx, newTile });
          newGrid[toIdx] = newTile;
          gained += newTile.value;
          read += 2;
          write += 1;
        } else {
          if (t1.idx !== toIdx) moves.push({ id: t1.id, fromIdx: t1.idx, toIdx });
          newGrid[toIdx] = { ...t1, idx: toIdx };
          read += 1;
          write += 1;
        }
      }
    }

    // detect if board changed
    let changed = false;
    for (let i = 0; i < 16; i++) {
      const ov = oldGrid[i] ? oldGrid[i].value : 0;
      const nv = newGrid[i] ? newGrid[i].value : 0;
      if (ov !== nv) {
        changed = true;
        break;
      }
    }
    if (!changed) return { changed: false };

    const spawned = addRandomTile(newGrid);
    const values = newGrid.map((t) => (t ? t.value : 0));
    const isOver = checkGameOver(values);
    return { changed: true, newGrid, moves, merges, gained, spawned, isOver };
  }

  function animateMove(result) {
    const { newGrid, moves, merges, spawned } = result;

    const fromIdxById = new Map();
    state.grid.forEach((t, idx) => {
      if (t) fromIdxById.set(t.id, idx);
    });

    // Ensure existing tile elements are at their from positions without transition.
    computeTileMetrics();
    state.grid.forEach((tile, idx) => {
      if (!tile) return;
      const el = ensureTileEl(tile);
      el.style.transition = "none";
      setTileTransform(el, idx);
      void el.offsetHeight;
      el.style.transition = `transform ${MOVE_MS}ms ease`;
    });

    // Create merged tiles hidden at destination (will reveal after move)
    for (const m of merges) {
      const el = ensureTileEl(m.newTile);
      el.style.transition = "none";
      el.style.opacity = "0";
      setTileTransform(el, m.toIdx);
      void el.offsetHeight;
      el.style.transition = `transform ${MOVE_MS}ms ease`;
    }

    // Apply movements next frame
    requestAnimationFrame(() => {
      for (const mv of moves) {
        const el = tileLayer.querySelector(`.tile[data-id="${mv.id}"]`);
        if (!el) continue;
        setTileTransform(el, mv.toIdx);
      }
    });

    // After move ends: cleanup merges, reveal merged tiles, spawn animation, unlock
    setTimeout(() => {
      for (const m of merges) {
        // Remove old tiles
        for (const id of m.fromIds) removeTileElById(id);
        // Reveal merged tile
        const el = tileLayer.querySelector(`.tile[data-id="${m.newTile.id}"]`);
        if (el) {
          el.style.opacity = "1";
          el.classList.remove("pop");
          // ensure base transform stored for keyframes
          setTileTransform(el, m.toIdx);
          void el.offsetHeight;
          el.classList.add("pop");
        }
      }

      if (spawned) {
        const el = ensureTileEl(spawned);
        el.style.transition = "none";
        el.style.opacity = "1";
        setTileTransform(el, spawned.idx);
        void el.offsetHeight;
        el.classList.remove("spawn");
        el.classList.add("spawn");
        el.style.transition = `transform ${MOVE_MS}ms ease`;
      }

      renderScores();
      state.animating = false;
      showOverlay(state.isOver);
      scheduleSave();
      loadLeaderboard();
    }, MOVE_MS + 30);
  }

  function move(dir) {
    if (state.animating) return;
    if (state.isOver) return;
    const result = computeMove(dir);
    if (!result.changed) return;

    state.animating = true;
    state.score += result.gained;
    if (state.score > state.best) state.best = state.score;
    state.grid = result.newGrid;
    state.isOver = result.isOver;
    animateMove(result);
  }

  function newGame() {
    state.grid = new Array(16).fill(null);
    state.score = 0;
    state.isOver = false;
    addRandomTile(state.grid);
    addRandomTile(state.grid);
    showOverlay(false);
    setStatus(canPersist ? "" : "login");
    renderAllTiles();
    scheduleSave();
  }

  function scheduleSave() {
    if (!canPersist) {
      setStatus("login");
      return;
    }
    setStatus("saving");
    clearTimeout(state.saveTimer);
    state.saveTimer = setTimeout(() => saveNow({ over: state.isOver }), 400);
  }

  async function saveNow({ over }) {
    if (!canPersist) return;
    try {
      const res = await fetch(`${API_BASE}/save.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          board: valuesArray(),
          score: state.score,
          best_score: state.best,
          over: Boolean(over),
        }),
      });
      if (!res.ok) throw new Error(`${res.status} ${res.statusText}`);
      setStatus("saved");
    } catch {
      setStatus("error");
    }
  }

  async function loadSave() {
    if (!canPersist) {
      state.hasSave = false;
      contBtn.disabled = true;
      setStatus("login");
      return;
    }
    try {
      const res = await fetch(`${API_BASE}/load.php`, { cache: "no-store" });
      if (!res.ok) throw new Error(`${res.status} ${res.statusText}`);
      const data = await res.json();
      if (data && Array.isArray(data.board) && data.board.length === 16) {
        state.grid = data.board.map((v, idx) => {
          const n = Number(v) || 0;
          return n > 0 ? { id: state.nextId++, value: n, idx } : null;
        });
        state.score = Number(data.score) || 0;
        state.best = Number(data.best_score) || Math.max(state.best, state.score);
        state.isOver = Boolean(data.is_over);
        state.hasSave = true;
        contBtn.disabled = false;
      } else {
        state.hasSave = false;
        contBtn.disabled = true;
      }
    } catch {
      state.hasSave = false;
      contBtn.disabled = true;
    }
  }

  async function loadLeaderboard() {
    if (!canPersist) return;
    try {
      const res = await fetch(`${API_BASE}/leaderboard.php`, { cache: "no-store" });
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
        score.textContent = String(r.best_score ?? 0);
        row.appendChild(rank);
        row.appendChild(name);
        row.appendChild(score);
        leaderboardEl.appendChild(row);
      });
    } catch {
      // ignore
    }
  }

  function onKeyDown(e) {
    if (e.defaultPrevented) return;
    if (e.isComposing) return;
    const tag = (e.target && e.target.tagName) || "";
    if (tag === "INPUT" || tag === "TEXTAREA" || tag === "SELECT") return;

    const key = e.key.toLowerCase();
    if (key === "enter") {
      e.preventDefault();
      newGame();
      return;
    }

    const map = {
      arrowleft: "left",
      a: "left",
      arrowright: "right",
      d: "right",
      arrowup: "up",
      w: "up",
      arrowdown: "down",
      s: "down",
    };
    const dir = map[key];
    if (!dir) return;
    e.preventDefault();
    move(dir);
  }

  function bindSwipeControls() {
    if (!boardEl) return;
    const MIN_SWIPE_PX = 26;
    let active = null; // {id, x, y}

    boardEl.addEventListener("pointerdown", (e) => {
      if (state.animating || state.isOver) return;
      if (e.pointerType === "mouse" && e.button !== 0) return;
      active = { id: e.pointerId, x: e.clientX, y: e.clientY };
      try {
        boardEl.setPointerCapture(e.pointerId);
      } catch {
        // ignore
      }
    });

    function endSwipe(e) {
      if (!active || active.id !== e.pointerId) return;
      const dx = e.clientX - active.x;
      const dy = e.clientY - active.y;
      active = null;

      const ax = Math.abs(dx);
      const ay = Math.abs(dy);
      if (Math.max(ax, ay) < MIN_SWIPE_PX) return;
      if (ax > ay) move(dx > 0 ? "right" : "left");
      else move(dy > 0 ? "down" : "up");
    }

    boardEl.addEventListener("pointerup", endSwipe);
    boardEl.addEventListener("pointercancel", () => (active = null));
  }

  newBtn.addEventListener("click", () => newGame());
  contBtn.addEventListener("click", async () => {
    await loadSave();
    if (state.hasSave) {
      renderAllTiles();
    } else {
      newGame();
    }
  });
  overlayNew.addEventListener("click", () => newGame());

  window.addEventListener("resize", () => renderAllTiles());
  document.addEventListener("keydown", onKeyDown, { passive: false });
  bindSwipeControls();

  (async () => {
    contBtn.disabled = true;
    setStatus(canPersist ? "" : "login");
    await loadSave();
    await loadLeaderboard();
    if (state.hasSave) {
      renderAllTiles();
    } else {
      newGame();
    }
  })();
})();
