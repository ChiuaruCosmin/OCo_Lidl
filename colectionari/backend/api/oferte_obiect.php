<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username']) || !isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(["error" => "Cerere invalidă"]);
    exit;
}

$username = $_SESSION['username'];
$id_obiect = intval($_GET['id']);

try {
    $db = new PDO("sqlite:../db/database.sqlite");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $db->prepare("SELECT c.user FROM obiecte o JOIN colectii c ON o.colectie_id = c.id WHERE o.id = :id");
    $stmt->execute([':id' => $id_obiect]);
    $owner = $stmt->fetchColumn();

    if ($owner !== $username) {
        http_response_code(403);
        echo json_encode(["error" => "Acces interzis"]);
        exit;
    }

    $stmt = $db->prepare("SELECT * FROM oferte WHERE id_obiect = :id ORDER BY data DESC");
    $stmt->execute([':id' => $id_obiect]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Eroare DB: " . $e->getMessage()]);
}
