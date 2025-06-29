<?php
require_once __DIR__ . '/../lib/jwt.php';
$headers = function_exists('getallheaders') ? getallheaders() : [];
if (!isset($headers['Authorization']) && !isset($headers['authorization'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Neautentificat']);
    exit;
}
$authHeader = $headers['Authorization'] ?? $headers['authorization'];
if (strpos($authHeader, 'Bearer ') !== 0) {
    http_response_code(401);
    echo json_encode(['error' => 'Format token invalid']);
    exit;
}
$token = substr($authHeader, 7);
try {
    $payload = verifyJWT($token);
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['error' => 'Token invalid']);
    exit;
}
try {
    $db = new PDO("sqlite:" . __DIR__ . '/../db/database.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $titlu        = $_POST['titlu'] ?? '';
    $valoare_min  = $_POST['valoare_min'] ?? '';
    $valoare_max  = $_POST['valoare_max'] ?? '';
    $an           = $_POST['an'] ?? '';
    $tara         = $_POST['tara'] ?? '';
    $perioada     = $_POST['perioada'] ?? '';
    $eticheta     = $_POST['eticheta'] ?? '';
    $material     = $_POST['material'] ?? '';
    $query = "SELECT c.id, c.titlu, c.imagine, c.user, COUNT(o.id) AS nr_obiecte FROM colectii c LEFT JOIN obiecte o ON o.colectie_id = c.id AND o.de_vanzare = 0 WHERE (c.tip = 0 OR c.tip IS NULL)";
    $conditions = [];
    $params = [];
    if ($titlu !== '') {
        $conditions[] = "LOWER(c.titlu) LIKE :titlu";
        $params[':titlu'] = '%' . strtolower($titlu) . '%';
    }
    if ($valoare_min !== '') {
        $conditions[] = "o.valoare >= :valoare_min";
        $params[':valoare_min'] = $valoare_min;
    }
    if ($valoare_max !== '') {
        $conditions[] = "o.valoare <= :valoare_max";
        $params[':valoare_max'] = $valoare_max;
    }
    if ($an !== '') {
        $conditions[] = "o.an = :an";
        $params[':an'] = intval($an);
    }
    if ($tara !== '') {
        $conditions[] = "LOWER(o.tara) LIKE :tara";
        $params[':tara'] = '%' . strtolower($tara) . '%';
    }
    if ($perioada !== '') {
        $conditions[] = "LOWER(o.perioada) LIKE :perioada";
        $params[':perioada'] = '%' . strtolower($perioada) . '%';
    }
    if ($eticheta !== '') {
        $conditions[] = "o.eticheta = :eticheta";
        $params[':eticheta'] = $eticheta;
    }
    if ($material !== '') {
        $conditions[] = "LOWER(o.material) LIKE :material";
        $params[':material'] = '%' . strtolower($material) . '%';
    }
    if (!empty($conditions)) {
        $query .= " AND " . implode(" AND ", $conditions);
    }
    $query .= " GROUP BY c.id ORDER BY nr_obiecte DESC";
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($results);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Eroare la filtrare: ' . $e->getMessage()]);
} 