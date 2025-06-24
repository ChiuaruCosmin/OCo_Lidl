<?php
session_start();
if (!isset($_SESSION['username']) || !isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(["error" => "Cerere invalidă"]);
    exit;
}

$username = $_SESSION['username'];
$id = intval($_GET['id']);

$titlu        = $_GET['titlu'] ?? '';
$valoare_min  = $_GET['valoare_min'] ?? '';
$valoare_max  = $_GET['valoare_max'] ?? '';
$tara         = $_GET['tara'] ?? '';
$perioada     = $_GET['perioada'] ?? '';
$material     = $_GET['material'] ?? '';
$eticheta     = $_GET['eticheta'] ?? '';
$an           = $_GET['an'] ?? '';

try {
    $dbPath = __DIR__ . "/../db/database.sqlite";
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $db->prepare("SELECT * FROM colectii WHERE id = :id AND user = :user");
    $stmt->execute([':id' => $id, ':user' => $username]);
    $colectie = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$colectie) {
        http_response_code(404);
        echo json_encode(["error" => "Colecția nu există"]);
        exit;
    }

    $query = "SELECT * FROM obiecte WHERE colectie_id = :id AND de_vanzare = 0";
    $params = [':id' => $id];

    if ($titlu !== '') {
        $query .= " AND LOWER(titlu) LIKE :titlu";
        $params[':titlu'] = '%' . strtolower($titlu) . '%';
    }
    if ($valoare_min !== '') {
        $query .= " AND valoare >= :valoare_min";
        $params[':valoare_min'] = $valoare_min;
    }
    if ($valoare_max !== '') {
        $query .= " AND valoare <= :valoare_max";
        $params[':valoare_max'] = $valoare_max;
    }
    if ($tara !== '') {
        $query .= " AND LOWER(tara) LIKE :tara";
        $params[':tara'] = '%' . strtolower($tara) . '%';
    }
    if ($perioada !== '') {
        $query .= " AND LOWER(perioada) LIKE :perioada";
        $params[':perioada'] = '%' . strtolower($perioada) . '%';
    }
    if ($material !== '') {
        $query .= " AND LOWER(material) LIKE :material";
        $params[':material'] = '%' . strtolower($material) . '%';
    }
    if ($eticheta !== '') {
        $query .= " AND eticheta = :eticheta";
        $params[':eticheta'] = $eticheta;
    }
    if ($an !== '') {
        $query .= " AND an = :an";
        $params[':an'] = intval($an);
    }

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $obiecte = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($obiecte);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Eroare DB: " . $e->getMessage()]);
}
?>
