<?php
require_once '../utils.php'; 

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

$titlu = $data['titlu'] ?? '';
$categorie = $data['categorie'] ?? '';
$descriere = $data['descriere'] ?? '';
$an = $data['an'] ?? null;

if (!$titlu || !$categorie) {
    echo json_encode(['success' => false, 'message' => 'Titlul și categoria sunt obligatorii.']);
    exit;
}

try {
    $db = connectDB();
    $stmt = $db->prepare("INSERT INTO obiecte (titlu, categorie, descriere, an) VALUES (?, ?, ?, ?)");
    $stmt->execute([$titlu, $categorie, $descriere, $an]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Eroare la inserare în baza de date.']);
}
