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
$valoare_min = $_GET['valoare_min'] ?? '';
$valoare_max = $_GET['valoare_max'] ?? '';
$an = $_GET['an'] ?? '';
$tara = $_GET['tara'] ?? '';
$material = $_GET['material'] ?? '';
$eticheta = $_GET['eticheta'] ?? '';

try {
    $db = new PDO("sqlite:" . __DIR__ . '/../db/database.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "SELECT * FROM obiecte WHERE de_vanzare = 1 AND proprietar != :username";
    $params = [':username' => $username];
    $conditions = [];

    if ($titlu !== '') {
        $conditions[] = "titlu LIKE :titlu";
        $params[':titlu'] = '%' . $titlu . '%';
    }
    if ($valoare_min !== '') {
        $conditions[] = "pret >= :valoare_min";
        $params[':valoare_min'] = $valoare_min;
    }
    if ($valoare_max !== '') {
        $conditions[] = "pret <= :valoare_max";
        $params[':valoare_max'] = $valoare_max;
    }
    if ($an !== '') {
        $conditions[] = "an = :an";
        $params[':an'] = $an;
    }
    if ($tara !== '') {
        $conditions[] = "tara LIKE :tara";
        $params[':tara'] = '%' . $tara . '%';
    }
    if ($material !== '') {
        $conditions[] = "material LIKE :material";
        $params[':material'] = '%' . $material . '%';
    }
    if ($eticheta !== '') {
        $conditions[] = "eticheta = :eticheta";
        $params[':eticheta'] = $eticheta;
    }

    if (!empty($conditions)) {
        $sql .= ' AND ' . implode(' AND ', $conditions);
    }
    $sql .= ' ORDER BY titlu';

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $obiecte = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'obiecte' => $obiecte]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Eroare DB: ' . $e->getMessage()]);
} 