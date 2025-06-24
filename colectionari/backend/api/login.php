<?php
session_start(); 

require_once '../utils.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$username = $data['username'] ?? '';
$password = $data['password'] ?? '';

$user = checkUserCredentials($username, $password);

if ($user) {
    
    $_SESSION['username'] = $user['username'];
    $_SESSION['user_id'] = $user['id']; 

    echo json_encode(['success' => true]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Utilizator sau parolă incorecte.'
    ]);
}