-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Creato il: Nov 23, 2025 alle 21:24
-- Versione del server: 10.4.32-MariaDB
-- Versione PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `barber_shop`
--

-- --------------------------------------------------------

--
-- Struttura della tabella `prenotazioni`
--

CREATE TABLE `prenotazioni` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `telefono` varchar(20) NOT NULL,
  `data_appuntamento` date NOT NULL,
  `ora_appuntamento` varchar(20) NOT NULL,
  `servizio` text NOT NULL,
  `stato` enum('in_attesa','accettato','rifiutato') DEFAULT 'in_attesa',
  `data_richiesta` timestamp NOT NULL DEFAULT current_timestamp(),
  `log_azioni` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `prenotazioni`
--

INSERT INTO `prenotazioni` (`id`, `nome`, `telefono`, `data_appuntamento`, `ora_appuntamento`, `servizio`, `stato`, `data_richiesta`, `log_azioni`) VALUES
(1, 'Leonardo Bonomi', '3401393076', '2025-11-22', '08:30', 'Taglio', 'rifiutato', '2025-11-21 09:12:27', '[21/11 11:43] Aggiunto a Google Calendar\n[21/11 14:39] Inviato WA Annullamento\n[21/11 14:58] Sent WA Confirm\n[21/11 15:42] ❌ Rifiutato/Archiviato\n[21/11 15:42] 🔄 Ripristinato in Attesa\n[21/11 15:48] ❌ Rifiutato/Archiviato (DB)\n[21/11 15:48] Inviato WA Rifiuto\n[21/11 15:48] 🔄 Ripristinato in Attesa\n[21/11 15:48] Aggiunto a Google Calendar\n[21/11 15:48] ✅ Accettato (DB)\n[21/11 15:48] Inviato WA Conferma\n[21/11 15:49] ✅ Accettato (DB)\n[21/11 15:49] Inviato WA Conferma\n[21/11 15:53] ❌ Rifiutato/Archiviato (DB)\n[21/11 15:53] 🔄 Ripristinato in Attesa\n[21/11 15:55] Aggiunto a Google Calendar\n[21/11 15:55] ✅ Accettato (DB)\n[21/11 15:55] Inviato WA Conferma\n[21/11 15:58] ❌ Rifiutato/Archiviato (DB)\n[21/11 15:58] 🔄 Ripristinato in Attesa\n[21/11 16:22] Aggiunto a Google Calendar\n[21/11 16:34] Aggiunto a Google Calendar\n[21/11 16:34] ✅ Accettato (Spostato in Agenda)\n[21/11 16:34] Inviato WA Conferma\n[21/11 16:34] Aperto GCal per Eliminazione\n[21/11 16:35] ❌ Rifiutato (Archiviato)\n[21/11 16:35] 🔄 Ripristinato in Attesa\n[22/11 10:15] ❌ Rifiutato (Archiviato)\n[22/11 10:15] 🔄 Ripristinato in Attesa\n[22/11 10:15] ❌ Rifiutato (Archiviato)\n'),
(2, 'Leonardo Bonomi', '3401393076', '2025-11-22', '08:30', 'Taglio', 'rifiutato', '2025-11-21 09:23:08', '[21/11 11:26] Aggiunto a Google Calendar\n[21/11 11:36] Aggiunto a Google Calendar\n[21/11 16:12] Aperto GCal per Eliminazione\n[21/11 16:35] ❌ Rifiutato (Archiviato)\n[23/11 18:54] 🔄 Ripristinato in Attesa\n[23/11 18:54] ❌ Rifiutato (Archiviato)\n[23/11 21:06] 🔄 Ripristinato in Attesa\n[23/11 21:06] ❌ Rifiutato (Archiviato)\n'),
(3, 'Leonardo Bonomi', '3401393076', '2025-11-22', '09:00', 'Taglio', 'accettato', '2025-11-21 09:25:54', '[21/11 11:33] Aggiunto a Google Calendar\n[21/11 11:38] Aggiunto a Google Calendar\n'),
(4, 'Leonardo Bonomi', '3401393076', '2025-11-23', '09:30', 'Taglio', 'accettato', '2025-11-21 09:47:23', '[21/11 11:32] Aggiunto a Google Calendar\n'),
(5, 'Leonardo Bonomi', '3401393076', '2025-11-22', '09:30', 'taglio', 'accettato', '2025-11-21 09:54:46', '[21/11 11:14] Aperto WA Conferma\n[21/11 11:14] Aperto WA Conferma\n[21/11 11:15] Aperto Google Calendar\n[21/11 11:17] Aperto Google Calendar\n[21/11 11:23] Aperto Google Calendar\n'),
(6, 'Leonardo Bonomi', '3401393076', '2025-11-22', '12:00', 'taglio', 'accettato', '2025-11-21 11:29:27', '[21/11 12:31] Stato cambiato in: ACCETTATO\n[21/11 12:31] Inviato WA Conferma\n[21/11 12:32] Aggiunto a Google Calendar\n[21/11 12:33] Stato cambiato in: RIFIUTATO\n[21/11 12:34] Inviato WA Rifiuto\n[21/11 12:35] Stato cambiato in: ACCETTATO\n[21/11 12:35] Inviato WA Annullamento\n[21/11 14:30] Inviato WA Conferma\n'),
(7, 'Leonardo Bonomi', '3401393076', '2025-11-25', '16:30', 'Taglio', 'accettato', '2025-11-21 13:45:45', '[21/11 14:46] Inviato WA Rifiuto\n[21/11 15:18] ❌ Rifiutato (DB)\n[21/11 15:36] ❌ Rifiutato/Archiviato\n[21/11 15:42] 🔄 Ripristinato in Attesa\n[21/11 15:42] Aggiunto a Google Calendar\n[21/11 15:42] ✅ Accettato (Definitivo)\n'),
(8, 'Andrea', '3333', '2025-11-28', '09:30', 'Taglio', 'accettato', '2025-11-21 13:46:43', '[Inserito manualmente da Admin]\n[21/11 14:46] Aggiunto a Google Calendar\n[21/11 14:47] Stato cambiato in: RIFIUTATO\n[21/11 15:37] ❌ Rifiutato/Archiviato\n[21/11 15:37] ❌ Rifiutato/Archiviato\n[21/11 15:40] 🔄 Ripristinato in Attesa\n[21/11 15:40] Aggiunto a Google Calendar\n[21/11 15:41] ✅ Accettato (Definitivo)\n'),
(9, 'Leonardo Bonomi', '3401393076', '2025-11-28', '09:30', 'Troia', 'rifiutato', '2025-11-22 09:48:00', '[22/11 10:48] ❌ Rifiutato (Archiviato)\n'),
(10, 'Leonardo Bonomifdffffffffffffffffffffffffffffffffffffffffffffffff', '3401393076', '2025-11-25', '09:30', 'Ciao', 'rifiutato', '2025-11-22 09:50:54', '[22/11 10:53] ❌ Rifiutato (Archiviato)\n[23/11 18:26] 🔄 Ripristinato\n[23/11 18:26] ❌ Rifiutato\n'),
(11, 'Leonardo Bonomifdffffffffffffffffffffffffffffffffffffffffffffffff', '3401393076', '2025-11-25', '09:30', 'Ciao', 'rifiutato', '2025-11-22 09:50:56', '[22/11 10:53] ❌ Rifiutato (Archiviato)\n'),
(12, 'Leonardo Bonomifdffffffffffffffffffffffffffffffffffffffffffffffff', '3401393076', '2025-11-25', '09:30', 'Ciao', 'rifiutato', '2025-11-22 09:50:58', '[22/11 10:53] ❌ Rifiutato (Archiviato)\n'),
(13, 'Leonardo Bonomifdffffffffffffffffffffffffffffffffffffffffffffffff', '3401393076', '2025-11-25', '09:30', 'Ciao', 'rifiutato', '2025-11-22 09:51:00', '[22/11 10:53] ❌ Rifiutato (Archiviato)\n'),
(14, 'Leonardo Bonomifdffffffffffffffffffffffffffffffffffffffffffffffff', '3401393076', '2025-11-25', '09:30', 'Ciao', 'rifiutato', '2025-11-22 09:51:02', '[22/11 10:53] ❌ Rifiutato (Archiviato)\n'),
(15, 'Leonardo Bonomifdffffffffffffffffffffffffffffffffffffffffffffffff', '3401393076', '2025-11-25', '09:30', 'Ciao', 'rifiutato', '2025-11-22 09:51:04', '[22/11 10:53] ❌ Rifiutato (Archiviato)\n'),
(16, 'Leonardo Bonomi', '3401393077', '2025-11-26', '09:00', 'Taglio', 'rifiutato', '2025-11-22 12:54:22', '[23/11 18:26] ❌ Rifiutato\n'),
(17, 'Leonardo Bonomi', '3401393077', '2025-11-26', '12:30', 'Taglio', 'rifiutato', '2025-11-22 12:54:48', '[23/11 18:26] ❌ Rifiutato\n'),
(18, 'Giulia Summa', '3489917672', '2025-11-25', '10:00', 'Troia', 'rifiutato', '2025-11-23 17:43:34', '[23/11 18:47] ❌ Rifiutato\n'),
(19, 'Giulia Summa', '3489917672', '2025-11-26', '09:30', 'Taglio', 'accettato', '2025-11-23 17:48:07', '[23/11 18:48] GCal\n[23/11 18:51] Aggiunto a Google Calendar\n[23/11 18:51] ✅ Accettato (Spostato in Agenda)\n[23/11 18:51] Inviato WA Conferma\n'),
(20, 'Giulia Summa', '3489917672', '2025-11-26', '13:00', 'Troia', 'rifiutato', '2025-11-23 17:55:18', '[23/11 18:55] ❌ Rifiutato (Archiviato)\n[23/11 18:55] Inviato WA Rifiuto\n[23/11 18:56] ❌ Rifiutato (Archiviato)\n');

-- --------------------------------------------------------

--
-- Struttura della tabella `recensioni`
--

CREATE TABLE `recensioni` (
  `id` int(11) NOT NULL,
  `nome_cliente` varchar(100) NOT NULL,
  `testo` text NOT NULL,
  `voto` int(11) NOT NULL DEFAULT 5,
  `data_recensione` date DEFAULT curdate()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `recensioni`
--

INSERT INTO `recensioni` (`id`, `nome_cliente`, `testo`, `voto`, `data_recensione`) VALUES
(1, 'Nicola Galetti', 'Assistenti belle e brave...titolare bravissimo soprattutto a fare la barba', 5, '2025-04-01'),
(2, 'ettorre spaggiari', 'Taglio capelli ben fatto come volevo,\r\ntempi rapidi, ambiente molto pulito.\r\nPersonale gentile. Come cliente abituale lo consiglio a tutti.', 5, '2021-02-22'),
(3, 'Andrea Ghiselli', 'Direi che le stelle parlano da sole! Il negozio con pulizia impeccabile e tagli precisi e ben curati!', 5, '2021-06-04');

-- --------------------------------------------------------

--
-- Struttura della tabella `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `price` varchar(50) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `services`
--

INSERT INTO `services` (`id`, `name`, `price`, `description`) VALUES
(1, 'Taglio Classico', '€ 20,00', 'Forbice e macchinetta'),
(2, 'Barba', '€ 15,00', 'Panno caldo e rasoio'),
(4, 'Taglio Classico', '€ 20,00', 'Forbice e macchinetta'),
(5, 'Barba', '€ 15,00', 'Panno caldo e rasoio');

-- --------------------------------------------------------

--
-- Struttura della tabella `services_list`
--

CREATE TABLE `services_list` (
  `id` int(11) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `price` decimal(5,2) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `services_list`
--

INSERT INTO `services_list` (`id`, `category`, `name`, `description`, `price`, `sort_order`) VALUES
(1, 'Taglio & Styling', 'Taglio Semplice', '', 15.00, 1),
(2, 'Taglio & Styling', 'Taglio Sfumato (Fade)', '', 18.00, 2),
(3, 'Barba', 'Modellatura Barba', '', 10.00, 3),
(4, 'Barba', 'Rasatura Completa', '', 15.00, 4);

-- --------------------------------------------------------

--
-- Struttura della tabella `site_content`
--

CREATE TABLE `site_content` (
  `id` int(11) NOT NULL,
  `section_key` varchar(50) NOT NULL,
  `content_text` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `site_content`
--

INSERT INTO `site_content` (`id`, `section_key`, `content_text`) VALUES
(1, 'chi_siamo', 'La mia non è solo un\'attività, è una passione che si tramanda di padre in figlio. Qui da Barba & Capelli, cerchiamo ogni giorno di lavorare al meglio per accontentare tutti i clienti, dai più classici ai più moderni.\r\n\r\nEntrando nel mio salone, troverai un\'atmosfera familiare e calda. Credo che il barbiere non sia solo il luogo dove tagliare i capelli, ma un posto dove rilassarsi e sentirsi a casa.');

-- --------------------------------------------------------

--
-- Struttura della tabella `slot_full`
--

CREATE TABLE `slot_full` (
  `id` int(11) NOT NULL,
  `data_blocco` date NOT NULL,
  `ora_blocco` varchar(5) NOT NULL,
  `creato_il` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `slot_full`
--

INSERT INTO `slot_full` (`id`, `data_blocco`, `ora_blocco`, `creato_il`) VALUES
(5, '2025-11-22', '11:30', '2025-11-21 14:46:56'),
(6, '2025-11-26', '16:30', '2025-11-23 17:54:45');

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `prenotazioni`
--
ALTER TABLE `prenotazioni`
  ADD PRIMARY KEY (`id`);

--
-- Indici per le tabelle `recensioni`
--
ALTER TABLE `recensioni`
  ADD PRIMARY KEY (`id`);

--
-- Indici per le tabelle `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`);

--
-- Indici per le tabelle `services_list`
--
ALTER TABLE `services_list`
  ADD PRIMARY KEY (`id`);

--
-- Indici per le tabelle `site_content`
--
ALTER TABLE `site_content`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `section_key` (`section_key`);

--
-- Indici per le tabelle `slot_full`
--
ALTER TABLE `slot_full`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_slot` (`data_blocco`,`ora_blocco`);

--
-- AUTO_INCREMENT per le tabelle scaricate
--

--
-- AUTO_INCREMENT per la tabella `prenotazioni`
--
ALTER TABLE `prenotazioni`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT per la tabella `recensioni`
--
ALTER TABLE `recensioni`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT per la tabella `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT per la tabella `services_list`
--
ALTER TABLE `services_list`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT per la tabella `site_content`
--
ALTER TABLE `site_content`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT per la tabella `slot_full`
--
ALTER TABLE `slot_full`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
