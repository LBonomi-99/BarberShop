<?php
/**
 * Risoluzione del negozio corrente (multi-tenant).
 *
 * STATO: scaffolding. NON ancora agganciato al path vivo del sito.
 * Finche' esiste un solo negozio, tutto risolve a tenant 1 (Matteo).
 * L'attivazione (includere questo file, filtrare le query, bindare la
 * sessione al login) e' descritta in ACTIVATION_MULTISHOP.md.
 *
 * Regola di sicurezza: in area ADMIN il tenant arriva SOLO dalla
 * sessione (mai da GET/POST). In area PUBBLICA arriva da ?shop=slug.
 */

/**
 * Tenant corrente.
 *  - Admin: $_SESSION['tenant_id'] (impostato al login).
 *  - Pubblico: ?shop=slug -> lookup; fallback al negozio storico (1).
 */
function current_tenant_id(mysqli $conn): int {
    // Contesto admin: vincolato in sessione, fonte fidata.
    if (!empty($_SESSION['tenant_id'])) {
        return (int)$_SESSION['tenant_id'];
    }
    // Contesto pubblico: slug dalla query string.
    if (!empty($_GET['shop'])) {
        $slug = preg_replace('/[^a-z0-9_-]/', '', strtolower((string)$_GET['shop']));
        if ($slug !== '') {
            $st = $conn->prepare("SELECT id FROM tenants WHERE slug = ? AND attivo = 1");
            $st->bind_param("s", $slug);
            $st->execute();
            $row = $st->get_result()->fetch_assoc();
            if ($row) return (int)$row['id'];
        }
    }
    // Default: negozio storico (Matteo). URL senza ?shop resta valido.
    return 1;
}

/** Dati anagrafici di un negozio (nome/telefono/email/booking_mode). NULL se assente. */
function load_tenant(mysqli $conn, int $id): ?array {
    $st = $conn->prepare("SELECT id, slug, nome, email, telefono, citta, attivo, booking_mode FROM tenants WHERE id = ?");
    $st->bind_param("i", $id);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    return $row ?: null;
}

/**
 * Crea un nuovo negozio (uso amministrativo manuale — Model A, niente
 * onboarding pubblico). Ritorna il nuovo id, o 0 se lo slug e' gia preso.
 */
function create_tenant(mysqli $conn, string $slug, string $nome, ?string $email = null, ?string $telefono = null, ?string $citta = null): int {
    $slug = preg_replace('/[^a-z0-9_-]/', '', strtolower($slug));
    if ($slug === '' || $nome === '') return 0;
    try {
        $st = $conn->prepare("INSERT INTO tenants (slug, nome, email, telefono, citta) VALUES (?, ?, ?, ?, ?)");
        $st->bind_param("sssss", $slug, $nome, $email, $telefono, $citta);
        $st->execute();
        return (int)$conn->insert_id;
    } catch (mysqli_sql_exception $e) {
        if ($conn->errno === 1062) return 0; // slug duplicato
        throw $e;
    }
}
