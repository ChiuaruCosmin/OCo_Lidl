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

try {
    $db = new PDO("sqlite:" . __DIR__ . '/../db/database.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $db->prepare("SELECT id, titlu, descriere, imagine, pret, data_adaugare FROM obiecte WHERE de_vanzare = 1 AND proprietar = :username");
    $stmt->execute([':username' => $username]);
    $obiecte = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $db->prepare("SELECT id, titlu, imagine, pret, data_adaugare FROM colectii WHERE tip = 3 AND user = :username");
    $stmt->execute([':username' => $username]);
    $colectii = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'obiecte' => $obiecte, 'colectii' => $colectii]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Eroare DB: ' . $e->getMessage()]);
}
