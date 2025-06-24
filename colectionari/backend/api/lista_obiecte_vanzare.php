<?php
session_start();
header('Content-Type: application/json');

try {
    $db = new PDO("sqlite:../db/database.sqlite");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $username = $_GET['username'] ?? '';

    $titlu       = $_GET['titlu'] ?? '';
    $valoare_min = $_GET['valoare_min'] ?? '';
    $valoare_max = $_GET['valoare_max'] ?? '';
    $an          = $_GET['an'] ?? '';
    $tara        = $_GET['tara'] ?? '';
    $material    = $_GET['material'] ?? '';
    $eticheta    = $_GET['eticheta'] ?? '';

    $query = "SELECT o.*, c.user 
              FROM obiecte o 
              JOIN colectii c ON o.colectie_id = c.id 
              WHERE o.de_vanzare = 1";

    $params = [];

    if (!empty($username)) {
        $query .= " AND c.user != :username";
        $params[':username'] = $username;
    }

    if ($titlu !== '') {
        $query .= " AND LOWER(o.titlu) LIKE :titlu";
        $params[':titlu'] = '%' . strtolower($titlu) . '%';
    }
    if ($valoare_min !== '') {
        $query .= " AND o.valoare >= :valoare_min";
        $params[':valoare_min'] = floatval($valoare_min);
    }
    if ($valoare_max !== '') {
        $query .= " AND o.valoare <= :valoare_max";
        $params[':valoare_max'] = floatval($valoare_max);
    }
    if ($an !== '') {
        $query .= " AND o.an = :an";
        $params[':an'] = intval($an);
    }
    if ($tara !== '') {
        $query .= " AND LOWER(o.tara) LIKE :tara";
        $params[':tara'] = '%' . strtolower($tara) . '%';
    }
    if ($material !== '') {
        $query .= " AND LOWER(o.material) LIKE :material";
        $params[':material'] = '%' . strtolower($material) . '%';
    }
    if ($eticheta !== '') {
        $query .= " AND o.eticheta = :eticheta";
        $params[':eticheta'] = $eticheta;
    }

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $rezultate = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($rezultate);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Eroare DB: " . $e->getMessage()]);
}
?>
