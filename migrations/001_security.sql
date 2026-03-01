-- =============================================================
-- Roomly — Migração de Segurança
-- Executar no phpMyAdmin ou CLI MySQL
-- =============================================================

-- 1. Adicionar coluna de token à tabela users
ALTER TABLE users ADD COLUMN token VARCHAR(64) DEFAULT NULL AFTER password;

-- 2. Índice no token para queries rápidas de autenticação
CREATE INDEX idx_users_token ON users (token);

-- 3. Índice único no email (se não existir)
-- Se der erro de "Duplicate entry", é porque já existe — ignora.
ALTER TABLE users ADD UNIQUE INDEX idx_users_email (email);

-- 4. Índices de performance nas reservations
CREATE INDEX idx_reservations_room_time ON reservations (rooms_id, start_time, end_time);
CREATE INDEX idx_reservations_user ON reservations (users_id);

-- 5. Índice para activity_logs
CREATE INDEX idx_logs_created ON activity_logs (created_at DESC);

-- 6. Tabela para rate limiting do login (opcional mas recomendado)
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_attempts_email_time (email, attempted_at)
);
