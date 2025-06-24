<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(["error" => "Utilizator neautentificat"]);
    exit;
}

try {
    $db = new PDO("sqlite:../db/database.sqlite");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $username = $_SESSION['username'];

    $query = "
        SELECT 
            strftime('%Y-%m', o.data_adaugare) AS luna,
            COUNT(*) AS nr_obiecte
        FROM obiecte o
        JOIN colectii c ON o.colectie_id = c.id
        WHERE c.user = :user AND o.de_vanzare = 0
        GROUP BY luna
        ORDER BY luna ASC
    ";

    $stmt = $db->prepare($query);
    $stmt->execute([':user' => $username]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($data);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Eroare DB: " . $e->getMessage()]);
}
?>
