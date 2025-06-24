<?php
header('Content-Type: application/json');

try {
    $db = new PDO("sqlite:../db/database.sqlite");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $db->query("
        SELECT o.categorie, COUNT(*) AS nr_obiecte
        FROM obiecte o
        JOIN colectii c ON o.colectie_id = c.id
        WHERE o.de_vanzare = 0
        GROUP BY o.categorie
        ORDER BY nr_obiecte DESC
        LIMIT 5
    ");

    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
