# Attivazione Multi-Negozio (Fase 5)

Stato attuale: **fondamenta pronte, NON attive**. Lo schema e' multi-tenant
(migration `005_multitenant.sql` applicata) ma il codice gira ancora
single-tenant: tutto risolve a `tenant_id = 1` (Matteo) e il sito si
comporta esattamente come prima.

Questo documento e' la checklist da eseguire **solo quando arriva un 2o
barbiere reale** (Model A: creazione manuale, niente onboarding/billing).
Finche' c'e' un solo negozio, NON serve fare nulla di tutto questo.

---

## Cosa e' gia pronto (migration 005)

- Tabella `tenants` (id, slug, nome, email, telefono, citta, attivo, booking_mode). Negozio 1 = Matteo.
- Colonna `tenant_id INT NOT NULL DEFAULT 1` su: `prenotazioni`, `slot_occupati`, `slot_full`, `opening_hours`, `service_categories`, `services_list`, `site_content`, `admin_config`.
- UNIQUE/PK compositi con `tenant_id` (isolamento dati gia garantito a livello DB).
- `lib/tenant.php`: `current_tenant_id()`, `load_tenant()`, `create_tenant()` (scaffolding, non ancora incluso).
- `rate_hits` resta globale (limiter per IP, non per negozio).

---

## Passi di attivazione

### 1. Creare il 2o negozio
```sql
INSERT INTO tenants (slug, nome, email, telefono, citta)
VALUES ('nome-negozio', 'Nome Barbiere', 'mail@...', '3xx...', 'Citta');
```
Oppure via `create_tenant($conn, 'slug', 'Nome', ...)`.
Inserire poi i suoi `opening_hours` (7 righe con il nuovo `tenant_id`) e una
password admin in `admin_config (tenant_id, 'admin_password', <hash>)`.

### 2. Binding del tenant in sessione (admin) — `admin_components/auth.php`
Al login, dopo `session_regenerate_id(true)`:
```php
$_SESSION['logged_in'] = true;
$_SESSION['tenant_id'] = <id risolto dal login del negozio>;
```
Il login deve identificare il negozio (es. campo "negozio" o sottodominio/slug)
e verificare la password contro `admin_config WHERE tenant_id=? AND config_key='admin_password'`.
Aggiornare `checkAdminPassword()` per filtrare per `tenant_id`.

### 3. Routing pubblico — `index.php`, `api_disponibilita.php`, `invia_prenotazione.php`
```php
require_once __DIR__.'/lib/tenant.php';
$tenant_id = current_tenant_id($conn);   // da ?shop=slug, fallback 1
```
URL pubblico: `?shop=slug`. L'URL nudo (senza `?shop`) resta valido = Matteo.
Pretty-url `/slug` solo dietro `<IfModule mod_rewrite.c>` (Tophost puo' 500-are).

### 4. Filtrare OGNI query per tenant_id (il lavoro grosso)
Regola: **`tenant_id` solo da `$_SESSION` (admin) o dal resolver (pubblico),
mai da GET/POST diretti.** Ogni query per-negozio aggiunge `AND tenant_id = ?`.

File e funzioni da aggiornare:
- `lib/availability.php`: `slot_disponibili()` e `getBookingMode()` prendono `$tenant_id` come parametro; aggiungere `AND tenant_id=?` a `opening_hours`, `slot_full`, `slot_occupati`. `getBookingMode` legge da `tenants.booking_mode`.
- `invia_prenotazione.php`: INSERT `prenotazioni`/`slot_occupati` con `tenant_id`; rate-limit per `(tenant, ip)` se serve.
- `admin_components/init.php`: tutte le SELECT (stats, agenda json, opening_hours, categorie, social) filtrate; `occupaSlot()`/`liberaSlot()` includono `tenant_id`.
- `admin_components/actions.php`: **ogni mutazione** `WHERE id=? AND tenant_id=?` + check `affected_rows` (anti-IDOR cross-tenant); manual booking, move_booking, blocchi, CMS, opening_hours, booking_mode, change_password tutti per tenant.
- `admin_components/data.php`: tutte le SELECT filtrate.
- `cron/reminders.php`: iterare sui `tenants` attivi (loop), `promemoria_inviato` per riga.

### 5. Config per-negozio
Nome/telefono/email mittente: oggi da `config.local.php` (globali). Passare a
`tenants` (nome, telefono, email) e usarli in `lib/mailer.php` (display name,
Reply-To) e nelle viste pubbliche. `booking_mode` passa da `admin_config` a
`tenants.booking_mode`.

### 6. Audit gate (prima del rilascio)
Cercare query per-negozio prive di filtro tenant:
```
grep -rEn "FROM (prenotazioni|slot_occupati|slot_full|opening_hours|service_categories|services_list|site_content|admin_config)" . | grep -vi tenant_id
```
Zero risultati non giustificati = ok al rilascio. Ogni `UPDATE`/`DELETE` su
quelle tabelle deve avere `AND tenant_id=?`.

### 7. (Opzionale) Hardening integrita: FK
```sql
ALTER TABLE prenotazioni ADD CONSTRAINT fk_pren_tenant
  FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE;
-- idem per le altre tabelle per-negozio
```

---

## Test di attivazione (end-to-end)
1. Negozio A e B con orari diversi: il form pubblico `?shop=a` e `?shop=b` mostra slot indipendenti.
2. Stesso slot stesso orario prenotato su A e B: entrambi passano (UNIQUE e' `(tenant_id,data,ora)`).
3. Login admin A non vede prenotazioni/agenda/CMS di B.
4. Mutazione con `id` di B mentre loggato come A → 0 affected rows, nessun effetto (IDOR bloccato).
5. Reminder cron: itera A e B, marca `promemoria_inviato` per riga.

## Rollback delle fondamenta
Se si decide di NON proseguire e c'e' ancora un solo negozio:
esegui `migrations/005_multitenant_rollback.sql`. NON eseguirlo se esistono
gia dati di un 2o negozio.
