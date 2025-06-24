<?php
header('Content-Type: application/json');

$titlu = $_GET['titlu'] ?? $_GET['titlu_colectie'] ?? '';

if (empty($titlu)) {
    echo json_encode([]);
    exit;
}

$valoare_min  = $_GET['valoare_min'] ?? '';
$valoare_max  = $_GET['valoare_max'] ?? '';
$an           = $_GET['an'] ?? '';
$tara         = $_GET['tara'] ?? '';
$perioada     = $_GET['perioada'] ?? '';
$eticheta     = $_GET['eticheta'] ?? '';
$material     = $_GET['material'] ?? '';

try {
    $db = new PDO("sqlite:../db/database.sqlite");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $db->prepare("SELECT id FROM colectii WHERE titlu = ?");
    $stmt->execute([$titlu]);
    $colectie = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$colectie) {
        echo json_encode([]);
        exit;
    }

    $colectie_id = $colectie['id'];

    $query = "SELECT * FROM obiecte WHERE colectie_id = :colectie_id AND de_vanzare = 0";
    $params = [':colectie_id' => $colectie_id];

    if ($valoare_min !== '') {
        $query .= " AND valoare >= :valoare_min";
        $params[':valoare_min'] = $valoare_min;
    }
    if ($valoare_max !== '') {
        $query .= " AND valoare <= :valoare_max";
        $params[':valoare_max'] = $valoare_max;
    }
    if ($an !== '') {
        $query .= " AND an = :an";
        $params[':an'] = $an;
    }
    if ($tara !== '') {
        $query .= " AND LOWER(tara) LIKE :tara";
        $params[':tara'] = '%' . strtolower($tara) . '%';
    }
    if ($perioada !== '') {
        $query .= " AND LOWER(perioada) LIKE :perioada";
        $params[':perioada'] = '%' . strtolower($perioada) . '%';
    }
    if ($eticheta !== '') {
        $query .= " AND eticheta = :eticheta";
        $params[':eticheta'] = $eticheta;
    }
    if ($material !== '') {
        $query .= " AND LOWER(material) LIKE :material";
        $params[':material'] = '%' . strtolower($material) . '%';
    }

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $obiecte = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($obiecte);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
