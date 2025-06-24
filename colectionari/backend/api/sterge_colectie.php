<?php
session_start();

if (!isset($_SESSION['username']) || !isset($_POST['id'])) {
    http_response_code(400);
    echo "Cerere invalidă";
    exit;
}

$id = intval($_POST['id']);
$username = $_SESSION['username'];

try {
    $dbPath = __DIR__ . "/../db/database.sqlite";
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $db->prepare("SELECT imagine FROM colectii WHERE id = :id AND user = :username");
    $stmt->execute([':id' => $id, ':username' => $username]);
    $colectie = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$colectie) {
        echo "Colecție inexistentă";
        exit;
    }

    $imagine = $colectie['imagine'];

    if (str_starts_with($imagine, 'assets/uploads/')) {
        $absPath = realpath(__DIR__ . "/../../" . $imagine);
        if ($absPath && file_exists($absPath)) {
            unlink($absPath);
        }
    }

    $stmt = $db->prepare("DELETE FROM colectii WHERE id = :id AND user = :username");
    $stmt->execute([':id' => $id, ':username' => $username]);

    echo "Succes";
} catch (PDOException $e) {
    echo "Eroare: " . $e->getMessage();
}
