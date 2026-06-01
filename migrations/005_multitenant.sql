-- =====================================================================
-- 005 — Fondamenta multi-negozio (PREPARAZIONE, non attivazione)
-- =====================================================================
-- Obiettivo: rendere lo schema multi-tenant SENZA cambiare il
-- comportamento del negozio esistente (Matteo).
--
-- Principio chiave: ogni tabella per-negozio prende `tenant_id INT NOT
-- NULL DEFAULT 1`. Cosi:
--   * le righe esistenti vengono backfillate a 1 (Matteo);
--   * gli INSERT del codice attuale (che NON passano tenant_id)
--     continuano a funzionare prendendo il default 1;
--   * gli UNIQUE/PK diventano compositi con tenant_id (isolamento dati).
--
-- L'ATTIVAZIONE vera (filtrare ogni query per tenant_id, binding in
-- sessione al login, routing ?shop=slug, audit) e' descritta in
-- ACTIVATION_MULTISHOP.md e va fatta SOLO quando arriva un 2o negozio.
--
-- Reversibile: vedi 005_multitenant_rollback.sql
-- Target: MariaDB 10.4+ (XAMPP locale / Tophost).
-- =====================================================================

-- --- 1. Anagrafica negozi -------------------------------------------
CREATE TABLE IF NOT EXISTS tenants (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  slug         VARCHAR(50)  NOT NULL,
  nome         VARCHAR(120) NOT NULL,
  email        VARCHAR(120) DEFAULT NULL,
  telefono     VARCHAR(30)  DEFAULT NULL,
  citta        VARCHAR(80)  DEFAULT NULL,
  attivo       TINYINT(1)   NOT NULL DEFAULT 1,
  booking_mode ENUM('auto','approval') NOT NULL DEFAULT 'auto',
  creato_il    TIMESTAMP    NOT NULL DEFAULT current_timestamp(),
  UNIQUE KEY uniq_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Negozio storico = id 1 (Matteo). Idempotente.
INSERT INTO tenants (id, slug, nome, attivo, booking_mode)
VALUES (1, 'matteo', 'Matteo Cavallara', 1, 'auto')
ON DUPLICATE KEY UPDATE slug = slug;

-- --- 2. tenant_id su ogni tabella per-negozio (DEFAULT 1 = backfill) -
ALTER TABLE prenotazioni       ADD COLUMN IF NOT EXISTS tenant_id INT NOT NULL DEFAULT 1;
ALTER TABLE slot_occupati      ADD COLUMN IF NOT EXISTS tenant_id INT NOT NULL DEFAULT 1;
ALTER TABLE slot_full          ADD COLUMN IF NOT EXISTS tenant_id INT NOT NULL DEFAULT 1;
ALTER TABLE opening_hours      ADD COLUMN IF NOT EXISTS tenant_id INT NOT NULL DEFAULT 1;
ALTER TABLE service_categories ADD COLUMN IF NOT EXISTS tenant_id INT NOT NULL DEFAULT 1;
ALTER TABLE services_list      ADD COLUMN IF NOT EXISTS tenant_id INT NOT NULL DEFAULT 1;
ALTER TABLE site_content       ADD COLUMN IF NOT EXISTS tenant_id INT NOT NULL DEFAULT 1;
ALTER TABLE admin_config       ADD COLUMN IF NOT EXISTS tenant_id INT NOT NULL DEFAULT 1;

-- Backfill esplicito (sicurezza: se la colonna esisteva gia NULL/0).
UPDATE prenotazioni       SET tenant_id = 1 WHERE tenant_id IS NULL OR tenant_id = 0;
UPDATE slot_occupati      SET tenant_id = 1 WHERE tenant_id IS NULL OR tenant_id = 0;
UPDATE slot_full          SET tenant_id = 1 WHERE tenant_id IS NULL OR tenant_id = 0;
UPDATE opening_hours      SET tenant_id = 1 WHERE tenant_id IS NULL OR tenant_id = 0;
UPDATE service_categories SET tenant_id = 1 WHERE tenant_id IS NULL OR tenant_id = 0;
UPDATE services_list      SET tenant_id = 1 WHERE tenant_id IS NULL OR tenant_id = 0;
UPDATE site_content       SET tenant_id = 1 WHERE tenant_id IS NULL OR tenant_id = 0;
UPDATE admin_config       SET tenant_id = 1 WHERE tenant_id IS NULL OR tenant_id = 0;

-- --- 3. UNIQUE/PK compositi (isolamento per negozio) ----------------
-- slot_occupati: arbitro anti-doppia-prenotazione -> per negozio
ALTER TABLE slot_occupati  DROP INDEX IF EXISTS uniq_slot;
ALTER TABLE slot_occupati  ADD UNIQUE INDEX IF NOT EXISTS uniq_slot_t (tenant_id, data, ora);
ALTER TABLE slot_occupati  ADD INDEX IF NOT EXISTS idx_tenant (tenant_id, data);

-- slot_full: blocchi/ferie -> per negozio
ALTER TABLE slot_full      DROP INDEX IF EXISTS unique_slot;
ALTER TABLE slot_full      ADD UNIQUE INDEX IF NOT EXISTS unique_slot_t (tenant_id, data_blocco, ora_blocco);

-- service_categories: nome unico per negozio
ALTER TABLE service_categories DROP INDEX IF EXISTS name;
ALTER TABLE service_categories ADD UNIQUE INDEX IF NOT EXISTS name_t (tenant_id, name);

-- site_content: chiave sezione unica per negozio
ALTER TABLE site_content   DROP INDEX IF EXISTS section_key;
ALTER TABLE site_content   ADD UNIQUE INDEX IF NOT EXISTS section_key_t (tenant_id, section_key);

-- opening_hours: PK (giorno) -> (tenant_id, giorno)
ALTER TABLE opening_hours  DROP PRIMARY KEY, ADD PRIMARY KEY (tenant_id, giorno);

-- admin_config: PK (config_key) -> (tenant_id, config_key)
ALTER TABLE admin_config   DROP PRIMARY KEY, ADD PRIMARY KEY (tenant_id, config_key);

-- prenotazioni / services_list: indici di lookup per negozio
ALTER TABLE prenotazioni   ADD INDEX IF NOT EXISTS idx_tenant (tenant_id, data_appuntamento);
ALTER TABLE services_list  ADD INDEX IF NOT EXISTS idx_tenant (tenant_id);

-- =====================================================================
-- FINE 005. Stato: schema multi-tenant pronto, comportamento invariato.
-- Tutte le righe e i futuri INSERT del codice attuale = tenant_id 1.
-- =====================================================================
