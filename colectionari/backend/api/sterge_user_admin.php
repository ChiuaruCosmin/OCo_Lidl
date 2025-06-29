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
if (!isset($data['username'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Username lipsă']);
    exit;
}
$target = $data['username'];
if ($target === 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Nu poți șterge adminul principal!']);
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
    $stmt = $db->prepare("SELECT id FROM colectii WHERE user = :t AND (tip = 0 OR tip = 1 OR tip = 3) AND id NOT IN (SELECT id_colectie FROM tranzactii_colectii WHERE status = 'vandut')");
    $stmt->execute([':t' => $target]);
    $colectii = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($colectii as $cid) {
        $stmt2 = $db->prepare("DELETE FROM obiecte WHERE colectie_id = :cid");
        $stmt2->execute([':cid' => $cid]);
        $stmt2 = $db->prepare("DELETE FROM colectii WHERE id = :cid");
        $stmt2->execute([':cid' => $cid]);
    }
    $stmt = $db->prepare("DELETE FROM users WHERE username = :t");
    $stmt->execute([':t' => $target]);
    $stmt = $db->prepare("INSERT INTO admin_actiuni (admin_user, actiune) VALUES (:admin, :act)");
    $stmt->execute([':admin' => $username, ':act' => "A șters contul $target și colecțiile sale ne-vândute"]);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Eroare la ștergere: ' . $e->getMessage()]);
} 