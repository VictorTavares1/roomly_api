-- Migration 001: QR Code check-in system
-- Executar no phpMyAdmin ou via mysql CLI

-- 1. Adicionar estado 'pendente' ao enum de reservas e renomear 'cancelled' para 'cancelada'
ALTER TABLE reservations
    MODIFY COLUMN status ENUM('pendente','confirmada','cancelada') NOT NULL DEFAULT 'pendente';

-- 2. Atualizar reservas existentes: 'cancelled' → 'cancelada', todas as outras → 'confirmada'
UPDATE reservations SET status = 'cancelada' WHERE status = 'cancelled';
UPDATE reservations SET status = 'confirmada' WHERE status NOT IN ('cancelada','pendente');

-- 3. Adicionar qr_token único por sala (usado para validar o scan)
ALTER TABLE rooms
    ADD COLUMN qr_token VARCHAR(64) NULL UNIQUE AFTER type;

-- 4. Gerar tokens para todas as salas existentes (valores únicos)
UPDATE rooms SET qr_token = SHA2(CONCAT(id, '-', name, '-roomly-secret'), 256) WHERE qr_token IS NULL;

-- 5. Adicionar coluna confirmed_at para saber quando foi feito o check-in
ALTER TABLE reservations
    ADD COLUMN confirmed_at DATETIME NULL AFTER status;
