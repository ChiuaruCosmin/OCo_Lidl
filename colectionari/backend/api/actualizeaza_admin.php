<?php
require_once __DIR__ . '/../lib/jwt.php';
header('Content-Type: application/json');
$headers = function_exists('getallheaders') ? getallheaders() : [];
if (!isset($headers['Authorization']) && !isset($headers['authorization'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Neautentificat']);
    exit;
}
$authHeader = $headers['Authorization'] ?? $headers['authorization'];
if (strpos($authHeader, 'Bearer ') !== 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Format token invalid']);
    exit;
}
$token = substr($authHeader, 7);
try {
    $payload = verifyJWT($token);
    $username = $payload['username'];
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Token invalid']);
    exit;
}
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['username']) || !isset($data['actiune'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Date lipsă']);
    exit;
}
$target = $data['username'];
$actiune = $data['actiune'];
if (!in_array($actiune, ['fa_admin', 'scoate_admin'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Acțiune invalidă']);
    exit;
}
if ($target === 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Nu poți modifica adminul principal!']);
    exit;
}
try {
    $db = new PDO("sqlite:" . __DIR__ . '/../db/database.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $db->prepare("SELECT admin FROM users WHERE username = :u");
    $stmt->execute([':u' => $username]);
    $isAdmin = $stmt->fetchColumn();
    if (!$isAdmin) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Doar adminii pot accesa.']);
        exit;
    }
    $newVal = $actiune === 'fa_admin' ? 1 : 0;
    $stmt = $db->prepare("UPDATE users SET admin = :a WHERE username = :t");
    $stmt->execute([':a' => $newVal, ':t' => $target]);
    $actiune_text = $actiune === 'fa_admin'
        ? "L-a făcut pe $target admin"
        : "L-a scos pe $target de la admin";
    $stmt = $db->prepare("INSERT INTO admin_actiuni (admin_user, actiune) VALUES (:admin, :act)");
    $stmt->execute([':admin' => $username, ':act' => $actiune_text]);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Eroare la modificare: ' . $e->getMessage()]);
} 