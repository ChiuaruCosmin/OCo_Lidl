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

$id_colectie = isset($_POST['id_colectie']) ? intval($_POST['id_colectie']) : 0;
$pret = isset($_POST['pret']) ? floatval($_POST['pret']) : 0;
$contract = trim($_POST['contract'] ?? '');
$adresa = trim($_POST['adresa'] ?? '');

if (!$id_colectie || !$pret || !$contract || !$adresa) {
    echo json_encode(['success' => false, 'message' => 'Date incomplete.']);
    exit;
}

try {
    $db = new PDO("sqlite:" . __DIR__ . '/../db/database.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $db->prepare("SELECT id, user FROM colectii WHERE id = :id AND tip = 3 AND user != :username");
    $stmt->execute([':id' => $id_colectie, ':username' => $username]);
    $colectie = $stmt->fetch();
    
    if (!$colectie) {
        echo json_encode(['success' => false, 'message' => 'Colecția nu este disponibilă pentru oferte.']);
        exit;
    }

    $stmt = $db->prepare("SELECT id FROM oferte_colectii WHERE id_colectie = :id_colectie AND user = :username AND status = 'in_asteptare'");
    $stmt->execute([':id_colectie' => $id_colectie, ':username' => $username]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Ai deja o ofertă activă pentru această colecție.']);
        exit;
    }

    $stmt = $db->prepare("INSERT INTO oferte_colectii (id_colectie, user, pret, contract, adresa) VALUES (:id, :user, :pret, :contract, :adresa)");
    $stmt->execute([
        ':id' => $id_colectie,
        ':user' => $username,
        ':pret' => $pret,
        ':contract' => $contract,
        ':adresa' => $adresa
    ]);

    echo json_encode(['success' => true, 'message' => 'Oferta trimisă cu succes!']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Eroare DB: ' . $e->getMessage()]);
} 