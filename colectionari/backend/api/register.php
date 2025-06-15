<?php
require_once '../utils.php';

header('Content-Type: application/json');
$input = json_decode(file_get_contents('php://input'), true);

$username = $input['username'] ?? '';
$password = $input['password'] ?? '';
$email    = $input['email']    ?? '';

if (!$username || !$password || !$email) {
    echo json_encode(['success' => false, 'message' => 'Toate câmpurile sunt obligatorii']);
    exit;
}

try {
    $db = connectDB();

    $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);

    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Utilizatorul există deja']);
        exit;
    }

    $stmt = $db->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)");
    $stmt->execute([$username, $password, $email]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Eroare la salvare',
        'error' => $e->getMessage() 
    ]);
}
