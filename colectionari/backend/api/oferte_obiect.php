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

$id_obiect = isset($_GET['id_obiect']) ? intval($_GET['id_obiect']) : 0;
if (!$id_obiect) {
    echo json_encode(['success' => false, 'message' => 'ID obiect lipsă.']);
    exit;
}

try {
    $db = new PDO("sqlite:" . __DIR__ . '/../db/database.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $db->prepare("SELECT proprietar FROM obiecte WHERE id = :id");
    $stmt->execute([':id' => $id_obiect]);
    $proprietar = $stmt->fetchColumn();
    if ($proprietar !== $username) {
        echo json_encode(['success' => false, 'message' => 'Nu ai dreptul să vezi aceste oferte.']);
        exit;
    }

    $stmt = $db->prepare("SELECT * FROM oferte WHERE id_obiect = :id ORDER BY data DESC");
    $stmt->execute([':id' => $id_obiect]);
    $oferte = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'oferte' => $oferte]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Eroare DB: ' . $e->getMessage()]);
} 