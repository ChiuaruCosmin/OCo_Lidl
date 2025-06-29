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

$titlu = $_GET['titlu'] ?? '';
$pret_min = $_GET['pret_min'] ?? '';
$pret_max = $_GET['pret_max'] ?? '';
$categorie = $_GET['categorie'] ?? '';

try {
    $db = new PDO("sqlite:" . __DIR__ . '/../db/database.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "SELECT c.*, COUNT(o.id) as nr_obiecte, SUM(o.valoare) as valoare_totala 
            FROM colectii c 
            LEFT JOIN obiecte o ON c.id = o.colectie_id 
            WHERE c.tip = 3 AND c.user != :username";
    $params = [':username' => $username];
    $conditions = [];

    if ($titlu !== '') {
        $conditions[] = "c.titlu LIKE :titlu";
        $params[':titlu'] = '%' . $titlu . '%';
    }
    if ($pret_min !== '') {
        $conditions[] = "c.pret >= :pret_min";
        $params[':pret_min'] = $pret_min;
    }
    if ($pret_max !== '') {
        $conditions[] = "c.pret <= :pret_max";
        $params[':pret_max'] = $pret_max;
    }
    if ($categorie !== '') {
        $conditions[] = "EXISTS (SELECT 1 FROM obiecte o2 WHERE o2.colectie_id = c.id AND o2.categorie LIKE :categorie)";
        $params[':categorie'] = '%' . $categorie . '%';
    }

    if (!empty($conditions)) {
        $sql .= ' AND ' . implode(' AND ', $conditions);
    }
    $sql .= ' GROUP BY c.id ORDER BY c.titlu';

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $colectii = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'colectii' => $colectii]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Eroare DB: ' . $e->getMessage()]);
} 