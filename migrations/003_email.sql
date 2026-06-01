-- BarberShop Migration 003 — Email cliente (per conferme/promemoria automatici)
-- Eseguire in phpMyAdmin: Database > barber_shop > Importa. Idempotente.

USE `barber_shop`;

ALTER TABLE `prenotazioni`
  ADD COLUMN IF NOT EXISTS `email` varchar(120) DEFAULT NULL AFTER `telefono`;
