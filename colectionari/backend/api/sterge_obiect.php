<?php
session_start();
if (!isset($_SESSION['username'])) {
    http_response_code(403);
    echo "Neautentificat";
    exit;
}

if (!isset($_POST['id'])) {
    http_response_code(400);
    echo "ID lipsă";
    exit;
}

$id = intval($_POST['id']);

try {
    $db = new PDO("sqlite:../db/database.sqlite");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $db->prepare("SELECT imagine FROM obiecte WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row && $row['imagine']) {
        $path = "../../" . $row['imagine'];
        if (file_exists($path)) {
            unlink($path);
        }
    }

    $stmt = $db->prepare("DELETE FROM obiecte WHERE id = :id");
    $stmt->execute([':id' => $id]);

    echo "Succes";
} catch (PDOException $e) {
    http_response_code(500);
    echo "Eroare: " . $e->getMessage();
}
