-- =====================================================================
-- 005 ROLLBACK — annulla le fondamenta multi-negozio
-- =====================================================================
-- Riporta lo schema allo stato pre-005 (single-tenant).
-- Sicuro finche' esiste UN SOLO negozio (tutte le righe tenant_id=1).
-- NON eseguire se esistono dati di un 2o negozio: la rimozione degli
-- UNIQUE compositi creerebbe collisioni di chiave.
-- Target: MariaDB 10.4+.
-- =====================================================================

-- --- 1. Ripristina UNIQUE/PK originali ------------------------------
ALTER TABLE slot_occupati      DROP INDEX IF EXISTS uniq_slot_t;
ALTER TABLE slot_occupati      DROP INDEX IF EXISTS idx_tenant;
ALTER TABLE slot_occupati      ADD UNIQUE INDEX IF NOT EXISTS uniq_slot (data, ora);

ALTER TABLE slot_full          DROP INDEX IF EXISTS unique_slot_t;
ALTER TABLE slot_full          ADD UNIQUE INDEX IF NOT EXISTS unique_slot (data_blocco, ora_blocco);

ALTER TABLE service_categories DROP INDEX IF EXISTS name_t;
ALTER TABLE service_categories ADD UNIQUE INDEX IF NOT EXISTS name (name);

ALTER TABLE site_content       DROP INDEX IF EXISTS section_key_t;
ALTER TABLE site_content       ADD UNIQUE INDEX IF NOT EXISTS section_key (section_key);

ALTER TABLE opening_hours      DROP PRIMARY KEY, ADD PRIMARY KEY (giorno);
ALTER TABLE admin_config       DROP PRIMARY KEY, ADD PRIMARY KEY (config_key);

ALTER TABLE prenotazioni       DROP INDEX IF EXISTS idx_tenant;
ALTER TABLE services_list      DROP INDEX IF EXISTS idx_tenant;

-- --- 2. Rimuovi le colonne tenant_id --------------------------------
ALTER TABLE prenotazioni       DROP COLUMN IF EXISTS tenant_id;
ALTER TABLE slot_occupati      DROP COLUMN IF EXISTS tenant_id;
ALTER TABLE slot_full          DROP COLUMN IF EXISTS tenant_id;
ALTER TABLE opening_hours      DROP COLUMN IF EXISTS tenant_id;
ALTER TABLE service_categories DROP COLUMN IF EXISTS tenant_id;
ALTER TABLE services_list      DROP COLUMN IF EXISTS tenant_id;
ALTER TABLE site_content       DROP COLUMN IF EXISTS tenant_id;
ALTER TABLE admin_config       DROP COLUMN IF EXISTS tenant_id;

-- --- 3. Elimina l'anagrafica negozi ---------------------------------
DROP TABLE IF EXISTS tenants;

-- =====================================================================
-- FINE rollback 005. Schema tornato single-tenant.
-- =====================================================================
