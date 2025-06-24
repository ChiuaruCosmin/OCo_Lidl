<?php
header('Content-Type: application/json');

try {
    $db = new PDO("sqlite:../db/database.sqlite");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "SELECT * FROM colectii WHERE 1=1";
    $params = [];

    if (!empty($_POST['titlu'])) {
        $sql .= " AND titlu LIKE ?";
        $params[] = '%' . $_POST['titlu'] . '%';
    }

    if (!empty($_POST['valoare_min'])) {
        $sql .= " AND valoare >= ?";
        $params[] = $_POST['valoare_min'];
    }

    if (!empty($_POST['valoare_max'])) {
        $sql .= " AND valoare <= ?";
        $params[] = $_POST['valoare_max'];
    }

    if (!empty($_POST['an'])) {
        $sql .= " AND an = ?";
        $params[] = $_POST['an'];
    }

    if (!empty($_POST['tara'])) {
        $sql .= " AND tara LIKE ?";
        $params[] = '%' . $_POST['tara'] . '%';
    }

    if (!empty($_POST['perioada'])) {
        $sql .= " AND perioada_utilizare LIKE ?";
        $params[] = '%' . $_POST['perioada'] . '%';
    }

    if (!empty($_POST['material'])) {
        $sql .= " AND material LIKE ?";
        $params[] = '%' . $_POST['material'] . '%';
    }

    if (isset($_POST['eticheta']) && $_POST['eticheta'] !== '') {
        $sql .= " AND eticheta = ?";
        $params[] = $_POST['eticheta'];
    }

    $sql .= " ORDER BY nr_obiecte DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($results);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
