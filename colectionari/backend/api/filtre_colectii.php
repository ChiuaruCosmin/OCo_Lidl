<?php
session_start();
if (!isset($_SESSION['username'])) {
    http_response_code(401);
    exit("Neautentificat");
}

header('Content-Type: application/json');

try {
    $db = new PDO("sqlite:../../backend/db/database.sqlite");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $username = $_SESSION['username'];

    $titlu        = $_GET['titlu'] ?? '';
    $valoare_min  = $_GET['valoare_min'] ?? '';
    $valoare_max  = $_GET['valoare_max'] ?? '';
    $an           = $_GET['an'] ?? '';
    $tara         = $_GET['tara'] ?? '';
    $perioada     = $_GET['perioada'] ?? '';
    $eticheta     = $_GET['eticheta'] ?? '';
    $material     = $_GET['material'] ?? '';

    $query = "
        SELECT c.*, COUNT(o.id) AS nr_obiecte
        FROM colectii c
        LEFT JOIN obiecte o ON o.colectie_id = c.id AND o.de_vanzare = 0
    ";

   $conditions = ['c."user" = :user'];

    $params = [':user' => $username];

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
        $query .= " WHERE " . implode(" AND ", $conditions);
    }

    $query .= " GROUP BY c.id";

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($results);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Eroare la baza de date']);
}