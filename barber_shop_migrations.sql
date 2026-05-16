-- BarberShop Migrations
-- Eseguire in phpMyAdmin: Database > barber_shop > Importa questo file

USE `barber_shop`;

-- 1. Orari di apertura (sostituisce hardcoded in api_disponibilita.php)
CREATE TABLE IF NOT EXISTS `opening_hours` (
  `giorno`             tinyint(4) NOT NULL COMMENT '0=Dom 1=Lun 2=Mar 3=Mer 4=Gio 5=Ven 6=Sab',
  `mattina_inizio`     time DEFAULT NULL,
  `mattina_fine`       time DEFAULT NULL,
  `pomeriggio_inizio`  time DEFAULT NULL,
  `pomeriggio_fine`    time DEFAULT NULL,
  `chiuso`             tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`giorno`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `opening_hours`
  (`giorno`, `mattina_inizio`, `mattina_fine`, `pomeriggio_inizio`, `pomeriggio_fine`, `chiuso`) VALUES
(0, NULL,       NULL,       NULL,       NULL,       1),
(1, NULL,       NULL,       NULL,       NULL,       1),
(2, '08:00:00', '12:30:00', '15:00:00', '19:30:00', 0),
(3, '08:30:00', '18:30:00', NULL,       NULL,       0),
(4, '08:00:00', '12:30:00', '15:00:00', '19:30:00', 0),
(5, '08:00:00', '19:30:00', NULL,       NULL,       0),
(6, '08:00:00', '18:30:00', NULL,       NULL,       0);

-- 2. Categorie listino dinamiche
CREATE TABLE IF NOT EXISTS `service_categories` (
  `id`         int(11) NOT NULL AUTO_INCREMENT,
  `name`       varchar(100) NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `service_categories` (`name`, `sort_order`) VALUES
('Taglio & Styling', 1),
('Barba', 2);

-- 3. Configurazione admin (password hashata — auto-popolata da auth.php al primo login)
CREATE TABLE IF NOT EXISTS `admin_config` (
  `config_key`   varchar(50) NOT NULL,
  `config_value` text NOT NULL,
  PRIMARY KEY (`config_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 4. Social links in site_content
INSERT IGNORE INTO `site_content` (`section_key`, `content_text`) VALUES
('social_instagram', ''),
('social_facebook', '');
