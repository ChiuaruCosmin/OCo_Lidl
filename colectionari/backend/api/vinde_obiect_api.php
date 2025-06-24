<?php
session_start();

if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo "Neautorizat.";
    exit;
}

if (!isset($_POST['id']) || !isset($_POST['pret'])) {
    http_response_code(400);
    echo "Date insuficiente.";
    exit;
}

$id = intval($_POST['id']);
$pret = floatval($_POST['pret']);
$username = $_SESSION['username'];

$dbPath = __DIR__ . '/../db/database.sqlite';

try {
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $db->prepare("
        SELECT o.id, o.colectie_id, c.user
        FROM obiecte o
        JOIN colectii c ON o.colectie_id = c.id
        WHERE o.id = :id
    ");
    $stmt->execute([':id' => $id]);
    $obiect = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$obiect || $obiect['user'] !== $username) {
        http_response_code(403);
        echo "Acces interzis.";
        exit;
    }

    $update = $db->prepare("UPDATE obiecte SET de_vanzare = 1, pret = :pret, proprietar = :proprietar WHERE id = :id");
    $update->execute([
        ':pret' => $pret,
        ':proprietar' => $username,
        ':id' => $id
    ]);

    $scade = $db->prepare("UPDATE colectii SET nr_obiecte = nr_obiecte - 1 WHERE id = :colectie_id");
    $scade->execute([':colectie_id' => $obiect['colectie_id']]);

    echo "Succes";
} catch (PDOException $e) {
    http_response_code(500);
    echo "Eroare DB: " . $e->getMessage();
}
