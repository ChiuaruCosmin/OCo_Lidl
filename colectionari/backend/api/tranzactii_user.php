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

    $stmt = $db->prepare("SELECT *, 'obiect' as tip_tranzactie FROM tranzactii WHERE ofertant = :username OR proprietar = :username");
    $stmt->execute([':username' => $username]);
    $tranzactii_obiecte = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $db->prepare("SELECT *, 'colectie' as tip_tranzactie FROM tranzactii_colectii WHERE ofertant = :username OR proprietar = :username");
    $stmt->execute([':username' => $username]);
    $tranzactii_colectii = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $tranzactii = array_merge($tranzactii_obiecte, $tranzactii_colectii);
    usort($tranzactii, function($a, $b) {
        return strtotime($b['data']) - strtotime($a['data']);
    });

    echo json_encode(['success' => true, 'tranzactii' => $tranzactii]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Eroare DB: ' . $e->getMessage()]);
} 