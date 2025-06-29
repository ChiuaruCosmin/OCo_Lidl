<?php
require_once __DIR__ . '/../lib/jwt.php';
header('Content-Type: application/json');
$headers = getallheaders();
$auth_header = $headers['Authorization'] ?? '';
if (!preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Token lipsă']);
    exit;
}
$token = $matches[1];
try {
    $payload = verifyJWT($token);
    $username = $payload['username'];
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Token invalid']);
    exit;
}
$colectie_id = $_POST['colectie_id'] ?? '';
$titlu = trim($_POST['titlu'] ?? '');
$categorie = trim($_POST['categorie'] ?? '');
$material = trim($_POST['material'] ?? '');
$valoare = $_POST['valoare'] ?? null;
$tara = trim($_POST['tara'] ?? '');
$perioada = trim($_POST['perioada'] ?? '');
$eticheta = $_POST['eticheta'] ?? null;
$descriere = trim($_POST['descriere'] ?? '');
$an = $_POST['an'] ?? null;

if ($colectie_id === '' || $titlu === '' || $categorie === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Câmpuri obligatorii lipsă']);
    exit;
}

$imgPath = 'assets/default_obj.png';
if (isset($_FILES['imagine']) && $_FILES['imagine']['tmp_name']) {
    $targetDir = '../../frontend/uploads/avatars/';
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    $ext = strtolower(pathinfo($_FILES['imagine']['name'], PATHINFO_EXTENSION));
    $imgName = uniqid('obj_', true) . '.' . $ext;
    $targetFile = $targetDir . $imgName;
    if (!move_uploaded_file($_FILES['imagine']['tmp_name'], $targetFile)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Eroare la upload imagine']);
        exit;
    }
    $imgPath = 'uploads/avatars/' . $imgName;
}

try {
    $db = new PDO("sqlite:" . __DIR__ . '/../db/database.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $db->prepare("SELECT id FROM colectii WHERE id = :id AND user = :user");
    $stmt->execute([':id' => $colectie_id, ':user' => $username]);
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Acces interzis']);
        exit;
    }
    $stmt = $db->prepare("INSERT INTO obiecte (titlu, categorie, material, valoare, tara, perioada, eticheta, descriere, an, imagine, colectie_id, proprietar) VALUES (:titlu, :categorie, :material, :valoare, :tara, :perioada, :eticheta, :descriere, :an, :imagine, :colectie_id, :proprietar)");
    $stmt->execute([
        ':titlu' => $titlu,
        ':categorie' => $categorie,
        ':material' => $material,
        ':valoare' => $valoare !== '' ? $valoare : null,
        ':tara' => $tara,
        ':perioada' => $perioada,
        ':eticheta' => $eticheta !== '' ? $eticheta : null,
        ':descriere' => $descriere,
        ':an' => $an !== '' ? $an : null,
        ':imagine' => $imgPath,
        ':colectie_id' => $colectie_id,
        ':proprietar' => $username
    ]);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Eroare DB: ' . $e->getMessage()]);
} 