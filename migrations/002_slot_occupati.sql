-- BarberShop Migration 002 — Fonte unica disponibilita + reminder
-- Eseguire in phpMyAdmin: Database > barber_shop > Importa questo file
-- Idempotente (IF NOT EXISTS / INSERT IGNORE). MariaDB 10.4+.

USE `barber_shop`;

-- 1. Tabella occupazione slot (UNICA fonte di verita per "slot occupato")
--    Una riga = uno slot bloccato da una prenotazione attiva (accettato o in_attesa).
--    UNIQUE(data,ora) e l'arbitro anti-doppia-prenotazione (catch errno 1062 lato PHP).
--    NB: separata da `prenotazioni` perche quella tiene per sempre le righe `rifiutato`
--    (stesso slot ripetuto), quindi un UNIQUE diretto la sarebbe impossibile.
CREATE TABLE IF NOT EXISTS `slot_occupati` (
  `id`              int(11) NOT NULL AUTO_INCREMENT,
  `data`            date NOT NULL,
  `ora`             varchar(5) NOT NULL,
  `prenotazione_id` int(11) DEFAULT NULL,
  `creato_il`       timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_slot` (`data`, `ora`),
  KEY `idx_pren` (`prenotazione_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. Backfill: occupa gli slot delle prenotazioni attive future.
--    ORDER BY id => in caso di duplicati (data,ora) vince la piu vecchia, le altre
--    vengono ignorate (INSERT IGNORE). Solo da oggi in poi: i passati non si offrono mai.
INSERT IGNORE INTO `slot_occupati` (`data`, `ora`, `prenotazione_id`)
SELECT `data_appuntamento`, LEFT(`ora_appuntamento`, 5), `id`
FROM `prenotazioni`
WHERE `stato` IN ('accettato', 'in_attesa')
  AND `data_appuntamento` >= CURDATE()
ORDER BY `id`;

-- 3. Flag promemoria (per cron reminder, evita invii doppi)
ALTER TABLE `prenotazioni`
  ADD COLUMN IF NOT EXISTS `promemoria_inviato` tinyint(1) NOT NULL DEFAULT 0;

ALTER TABLE `prenotazioni`
  ADD INDEX IF NOT EXISTS `idx_reminder` (`data_appuntamento`, `stato`, `promemoria_inviato`);

-- 4. Modalita conferma prenotazioni online: 'auto' (default) | 'approval'
INSERT IGNORE INTO `admin_config` (`config_key`, `config_value`) VALUES
('booking_mode', 'auto');
