<?php
require_once __DIR__ . '/../lib/jwt.php';
header('Content-Type: application/json');

$headers = function_exists('getallheaders') ? getallheaders() : [];
if (!isset($headers['Authorization']) && !isset($headers['authorization'])) {
    echo json_encode(['success' => false, 'message' => 'Token lipsă.']);
    exit;
}
$authHeader = $headers['Authorization'] ?? $headers['authorization'];
if (strpos($authHeader, 'Bearer ') !== 0) {
    echo json_encode(['success' => false, 'message' => 'Format token invalid.']);
    exit;
}
$token = substr($authHeader, 7);

try {
    $payload = verifyJWT($token);
    $username = $payload['username'];
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Token invalid: ' . $e->getMessage()]);
    exit;
}

$colectie_id = $_POST['colectie_id'] ?? '';
$nou_tip = $_POST['tip'] ?? '';
$pret = $_POST['pret'] ?? null;

if (!$colectie_id || !in_array($nou_tip, ['0', '1', '3'])) {
    echo json_encode(['success' => false, 'message' => 'Parametri invalizi.']);
    exit;
}

if ($nou_tip == '3' && (!$pret || !is_numeric($pret) || $pret <= 0)) {
    echo json_encode(['success' => false, 'message' => 'Preț invalid pentru vânzare.']);
    exit;
}

try {
    $db = new PDO("sqlite:" . __DIR__ . '/../db/database.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $db->prepare("SELECT id FROM colectii WHERE id = :id AND user = :user");
    $stmt->execute([':id' => $colectie_id, ':user' => $username]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Acces interzis.']);
        exit;
    }
    
    $db->beginTransaction();
    
    if ($nou_tip == '3') {
        $stmt = $db->prepare("UPDATE colectii SET tip = :tip, pret = :pret WHERE id = :id");
        $stmt->execute([':tip' => $nou_tip, ':pret' => $pret, ':id' => $colectie_id]);
    } else {
        $stmt = $db->prepare("UPDATE colectii SET tip = :tip WHERE id = :id");
        $stmt->execute([':tip' => $nou_tip, ':id' => $colectie_id]);
        $stmt = $db->prepare("UPDATE obiecte SET de_vanzare = 0 WHERE colectie_id = :colectie_id");
        $stmt->execute([':colectie_id' => $colectie_id]);
    }

    if ($nou_tip == '3') {
        $stmt = $db->prepare("UPDATE obiecte SET de_vanzare = 3 WHERE colectie_id = :colectie_id");
        $stmt->execute([':colectie_id' => $colectie_id]);
    }
    
    $db->commit();
    
    $tip_text = '';
    switch ($nou_tip) {
        case '0': $tip_text = 'publică'; break;
        case '1': $tip_text = 'privată'; break;
        case '3': $tip_text = 'de vânzare'; break;
    }
    
    echo json_encode(['success' => true, 'message' => 'Colecția a fost făcută ' . $tip_text . '.']);
    
} catch (PDOException $e) {
    if (isset($db)) $db->rollBack();
    echo json_encode(['success' => false, 'message' => 'Eroare DB: ' . $e->getMessage()]);
} 