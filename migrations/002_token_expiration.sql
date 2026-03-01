-- Migration 002: Token Expiration
-- Adiciona coluna token_expires_at para expiração de tokens (24h)

ALTER TABLE `users` ADD COLUMN `token_expires_at` DATETIME DEFAULT NULL AFTER `token`;
