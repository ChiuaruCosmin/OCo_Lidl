<?php
require_once __DIR__ . '/../lib/jwt.php';

header('Content-Type: application/json');

$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (!str_starts_with($authHeader, 'Bearer ')) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Token lipsă.']);
    exit;
}

$token = substr($authHeader, 7);
$payload = verifyJWT($token);

if (!$payload) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Token invalid sau expirat.']);
    exit;
}

echo json_encode(['success' => true, 'user' => $payload]);
