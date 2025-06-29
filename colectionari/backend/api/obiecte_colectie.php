<?php
require_once __DIR__ . '/../lib/jwt.php';

header('Content-Type: application/json');

$public = isset($_GET['public']) && $_GET['public'] == '1';

if (!$public) {
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
} else {
  $username = null;
}

$colectie_id = $_GET['id'] ?? '';
$valoare_min = $_GET['valoare_min'] ?? '';
$valoare_max = $_GET['valoare_max'] ?? '';
$an = $_GET['an'] ?? '';
$tara = $_GET['tara'] ?? '';
$perioada = $_GET['perioada'] ?? '';
$material = $_GET['material'] ?? '';
$eticheta = $_GET['eticheta'] ?? '';

if (empty($colectie_id)) {
    http_response_code(400);
    echo json_encode(['error' => 'ID colecție lipsă']);
    exit;
}

try {
    $db = new PDO("sqlite:" . __DIR__ . '/../db/database.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($public) {
        $stmt = $db->prepare("SELECT id FROM colectii WHERE id = :id");
        $stmt->execute([':id' => $colectie_id]);
    } else {
        $stmt = $db->prepare("SELECT id FROM colectii WHERE id = :id AND user = :username");
        $stmt->execute([':id' => $colectie_id, ':username' => $username]);
    }
    
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['error' => 'Acces interzis']);
        exit;
    }

    if ($public) {
        $sql = "SELECT o.*, o.categorie 
                FROM obiecte o 
                WHERE o.colectie_id = :colectie_id";
    } else {
        $sql = "SELECT o.*, o.categorie 
                FROM obiecte o 
                WHERE o.colectie_id = :colectie_id AND o.de_vanzare = 0";
    }
    $params = [':colectie_id' => $colectie_id];
    $conditions = [];

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

    if (!empty($conditions)) {
        $sql .= " AND " . implode(" AND ", $conditions);
    }

    $sql .= " ORDER BY o.titlu";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $obiecte = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($obiecte);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Eroare server: ' . $e->getMessage()]);
}
?> 