<?php
session_start();
header("Content-Type: application/json");

if (!isset($_SESSION['username']) || !isset($_POST['id'])) {
    http_response_code(400);
    echo json_encode(["error" => "Cerere invalidă"]);
    exit;
}

$id = intval($_POST['id']);
$username = $_SESSION['username'];

try {
    $db = new PDO("sqlite:../db/database.sqlite");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $db->prepare("
        UPDATE obiecte
        SET de_vanzare = 0
        WHERE id = :id AND colectie_id IN (SELECT id FROM colectii WHERE user = :user)
    ");
    $stmt->execute([
        ':id' => $id,
        ':user' => $username
    ]);

    echo json_encode(["success" => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Eroare DB: " . $e->getMessage()]);
}
