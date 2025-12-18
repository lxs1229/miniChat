-- =====================================================
-- MiniChat - Base de données complète (PostgreSQL)
-- Compatible Render / Cloudflare / PHP PDO
-- =====================================================

-- =========================
-- 1️⃣ TABLE : users
-- =========================
CREATE TABLE IF NOT EXISTS users (
    num SERIAL PRIMARY KEY,
    pseudo VARCHAR(30) UNIQUE NOT NULL,
    mdp VARCHAR(255) NOT NULL,      -- mot de passe hashé
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS is_admin BOOLEAN DEFAULT FALSE;

-- =========================
-- 2️⃣ TABLE : rooms
-- =========================
CREATE TABLE IF NOT EXISTS rooms (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255),          -- hash si salon protégé
    created_by VARCHAR(30),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_room_creator
        FOREIGN KEY (created_by)
        REFERENCES users(pseudo)
        ON DELETE SET NULL
);

-- =========================
-- 3️⃣ TABLE : messages
-- =========================
CREATE TABLE IF NOT EXISTS messages (
    num SERIAL PRIMARY KEY,
    pseudo VARCHAR(30),
    message TEXT NOT NULL,
    time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    room_id INT NOT NULL,
    CONSTRAINT fk_message_user
        FOREIGN KEY (pseudo)
        REFERENCES users(pseudo)
        ON DELETE CASCADE,
    CONSTRAINT fk_message_room
        FOREIGN KEY (room_id)
        REFERENCES rooms(id)
        ON DELETE CASCADE
);

-- =========================
-- 4️⃣ TABLE : connect_history
-- =========================
CREATE TABLE IF NOT EXISTS connect_history (
    num SERIAL PRIMARY KEY,
    pseudo VARCHAR(30),
    ip_connection VARCHAR(45),      -- IPv4 / IPv6
    time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_history_user
        FOREIGN KEY (pseudo)
        REFERENCES users(pseudo)
        ON DELETE CASCADE
);

-- =========================
-- 5️⃣ INDEX (performance)
-- =========================
CREATE INDEX IF NOT EXISTS idx_messages_room
    ON messages(room_id);

CREATE INDEX IF NOT EXISTS idx_history_pseudo
    ON connect_history(pseudo);

-- =========================
-- 🎮 GAME : 2048 (SAVE + LEADERBOARD)
-- =========================
CREATE TABLE IF NOT EXISTS game_2048_saves (
    pseudo VARCHAR(30) PRIMARY KEY,
    board JSONB NOT NULL,
    score INT NOT NULL DEFAULT 0,
    best_score INT NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_2048_user
        FOREIGN KEY (pseudo)
        REFERENCES users(pseudo)
        ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_2048_best_score
    ON game_2048_saves(best_score DESC);

CREATE TABLE IF NOT EXISTS game_2048_scores (
    id SERIAL PRIMARY KEY,
    pseudo VARCHAR(30) NOT NULL,
    score INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_2048_score_user
        FOREIGN KEY (pseudo)
        REFERENCES users(pseudo)
        ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_2048_scores_score
    ON game_2048_scores(score DESC);

-- =========================
-- 🎮 GAME : MINESWEEPER (SAVE + LEADERBOARD)
-- =========================
CREATE TABLE IF NOT EXISTS game_minesweeper_saves (
    pseudo VARCHAR(30) NOT NULL,
    difficulty VARCHAR(16) NOT NULL,
    state JSONB NOT NULL,
    best_time_ms BIGINT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (pseudo, difficulty),
    CONSTRAINT fk_ms_user
        FOREIGN KEY (pseudo)
        REFERENCES users(pseudo)
        ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_ms_best_time
    ON game_minesweeper_saves(difficulty, best_time_ms ASC);

CREATE TABLE IF NOT EXISTS game_minesweeper_scores (
    id SERIAL PRIMARY KEY,
    pseudo VARCHAR(30) NOT NULL,
    difficulty VARCHAR(16) NOT NULL,
    time_ms BIGINT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ms_score_user
        FOREIGN KEY (pseudo)
        REFERENCES users(pseudo)
        ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_ms_scores
    ON game_minesweeper_scores(difficulty, time_ms ASC);

-- =========================
-- 6️⃣ ADMIN PAR DÉFAUT (optionnel)
-- =========================
-- Mot de passe : admin123 (à changer après)
-- Hash généré par password_hash()
INSERT INTO users (pseudo, mdp)
VALUES (
    'admin',
    '$2y$10$Zx9k1XKXk9d5Qw9kY2xU5u5w8u0rK7o1ZP9vX3X5gJ0mC0G1OaH0K'
)
ON CONFLICT (pseudo) DO NOTHING;
