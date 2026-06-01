-- BarberShop Migration 004 — Rate limiting (form pubblico + login admin)
-- Eseguire in phpMyAdmin. Idempotente.

USE `barber_shop`;

CREATE TABLE IF NOT EXISTS `rate_hits` (
  `id`         bigint(20) NOT NULL AUTO_INCREMENT,
  `scope`      varchar(20) NOT NULL,   -- es. 'form', 'login'
  `ident`      varchar(60) NOT NULL,   -- es. IP client
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_lookup` (`scope`, `ident`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
