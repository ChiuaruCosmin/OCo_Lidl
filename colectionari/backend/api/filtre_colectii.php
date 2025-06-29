<?php
require_once __DIR__ . '/../lib/jwt.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$headers = getallheaders();
$auth_header = $headers['Authorization'] ?? '';

if (!preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
    http_response_code(401);
    echo json_encode(['error' => 'Token lipsă']);
    exit;
}

$token = $matches[1];

try {
    $payload = verifyJWT($token);
    $username = $payload['username'];
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['error' => 'Token invalid']);
    exit;
}

$titlu = $_GET['titlu'] ?? '';
$valoare_min = $_GET['valoare_min'] ?? '';
$valoare_max = $_GET['valoare_max'] ?? '';
$an = $_GET['an'] ?? '';
$tara = $_GET['tara'] ?? '';
$perioada = $_GET['perioada'] ?? '';
$eticheta = $_GET['eticheta'] ?? '';
$material = $_GET['material'] ?? '';

try {
    $db = new PDO("sqlite:" . __DIR__ . '/../db/database.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "SELECT c.*, COUNT(o.id) as nr_obiecte 
            FROM colectii c 
            LEFT JOIN obiecte o ON c.id = o.colectie_id AND o.de_vanzare = 0 
            WHERE c.user = :username AND (c.tip = 0 OR c.tip = 1 OR c.tip IS NULL)";
    
    $params = [':username' => $username];
    $conditions = [];

    if (!empty($titlu)) {
        $conditions[] = "c.titlu LIKE :titlu";
        $params[':titlu'] = '%' . $titlu . '%';
    }

    if (!empty($valoare_min) || !empty($valoare_max) || !empty($an) || 
        !empty($tara) || !empty($perioada) || !empty($material) || !empty($eticheta)) {
        
        $sql = "SELECT DISTINCT c.*, COUNT(o2.id) as nr_obiecte 
                FROM colectii c 
                LEFT JOIN obiecte o2 ON c.id = o2.colectie_id AND o2.de_vanzare = 0 
                INNER JOIN obiecte o ON c.id = o.colectie_id 
                WHERE c.user = :username AND (c.tip = 0 OR c.tip = 1 OR c.tip IS NULL)";
        
        if (!empty($valoare_min)) {
            $conditions[] = "o.valoare >= :valoare_min";
            $params[':valoare_min'] = $valoare_min;
        }
        
        if (!empty($valoare_max)) {
            $conditions[] = "o.valoare <= :valoare_max";
            $params[':valoare_max'] = $valoare_max;
        }
        
        if (!empty($an)) {
            $conditions[] = "o.an = :an";
            $params[':an'] = $an;
        }
        
        if (!empty($tara)) {
            $conditions[] = "o.tara LIKE :tara";
            $params[':tara'] = '%' . $tara . '%';
        }
        
        if (!empty($perioada)) {
            $conditions[] = "o.perioada LIKE :perioada";
            $params[':perioada'] = '%' . $perioada . '%';
        }
        
        if (!empty($material)) {
            $conditions[] = "o.material LIKE :material";
            $params[':material'] = '%' . $material . '%';
        }
        
        if ($eticheta !== '') {
            $conditions[] = "o.eticheta = :eticheta";
            $params[':eticheta'] = $eticheta;
        }
    }

    if (!empty($conditions)) {
        $sql .= " AND " . implode(" AND ", $conditions);
    }

    $sql .= " GROUP BY c.id ORDER BY c.titlu";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $colectii = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($colectii as &$colectie) {
        if (!isset($colectie['tip'])) {
            $colectie['tip'] = 0;
        }
    }

    echo json_encode($colectii);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Eroare server: ' . $e->getMessage()]);
}
?> 