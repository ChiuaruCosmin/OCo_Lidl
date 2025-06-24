<?php
session_start();
if (!isset($_SESSION['username']) || !isset($_GET['id_obiect'])) {
    http_response_code(400);
    echo json_encode(["error" => "Cerere invalidă"]);
    exit;
}

$id_obiect = intval($_GET['id_obiect']);
$username = $_SESSION['username'];

try {
    $db = new PDO("sqlite:../db/database.sqlite");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $db->prepare("SELECT c.user FROM obiecte o JOIN colectii c ON o.colectie_id = c.id WHERE o.id = :id");
    $stmt->execute([':id' => $id_obiect]);
    $owner = $stmt->fetchColumn();

    if ($owner !== $username) {
        http_response_code(403);
        echo json_encode(["error" => "Nu ai dreptul să vezi aceste oferte."]);
        exit;
    }

    $stmt = $db->prepare("SELECT * FROM oferte WHERE id_obiect = :id ORDER BY data DESC");
    $stmt->execute([':id' => $id_obiect]);
    $oferte = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($oferte);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Eroare DB: " . $e->getMessage()]);
}
