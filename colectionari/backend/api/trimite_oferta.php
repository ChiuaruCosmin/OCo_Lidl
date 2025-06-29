<?php
require_once __DIR__ . '/../lib/jwt.php';
header('Content-Type: application/json');

$headers = function_exists('getallheaders') ? getallheaders() : [];
if (!isset($headers['Authorization']) && !isset($headers['authorization'])) {
    echo json_encode(['success' => false, 'message' => 'Token lipsă.']);
    exit;
}
$authHeader = $headers['Authorization'] ?? $headers['authorization'];
if (strpos($authHeader, 'Bearer ') !== 0) {
    echo json_encode(['success' => false, 'message' => 'Format token invalid.']);
    exit;
}
$token = substr($authHeader, 7);

try {
    $payload = verifyJWT($token);
    $username = $payload['username'];
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Token invalid: ' . $e->getMessage()]);
    exit;
}

$id_obiect = isset($_POST['id_obiect']) ? intval($_POST['id_obiect']) : 0;
$pret = isset($_POST['pret']) ? floatval($_POST['pret']) : 0;
$contract = trim($_POST['contract'] ?? '');
$adresa = trim($_POST['adresa'] ?? '');

if (!$id_obiect || !$pret || !$contract || !$adresa) {
    echo json_encode(['success' => false, 'message' => 'Date incomplete.']);
    exit;
}

try {
    $db = new PDO("sqlite:" . __DIR__ . '/../db/database.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $db->exec("CREATE TABLE IF NOT EXISTS oferte (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        id_obiect INTEGER NOT NULL,
        user TEXT NOT NULL,
        pret REAL NOT NULL,
        contract TEXT,
        adresa TEXT,
        status TEXT DEFAULT 'in_asteptare',
        data TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $stmt = $db->prepare("INSERT INTO oferte (id_obiect, user, pret, contract, adresa) VALUES (:id, :user, :pret, :contract, :adresa)");
    $stmt->execute([
        ':id' => $id_obiect,
        ':user' => $username,
        ':pret' => $pret,
        ':contract' => $contract,
        ':adresa' => $adresa
    ]);

    echo json_encode(['success' => true, 'message' => 'Oferta trimisă cu succes!']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Eroare DB: ' . $e->getMessage()]);
} 