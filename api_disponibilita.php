<?php
header('Content-Type: application/json');
date_default_timezone_set('Europe/Rome');

require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/availability.php';

$conn = db_connect();
if (!$conn) { echo json_encode([]); exit; }

$data = $_GET['data'] ?? '';
echo json_encode(slot_disponibili($conn, $data));

$conn->close();
