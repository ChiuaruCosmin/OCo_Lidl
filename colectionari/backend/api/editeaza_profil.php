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
$payload = verifyJWT($token);
if (!$payload || !isset($payload['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Token invalid']);
    exit;
}
$user_id = $payload['user_id'];

$username_nou = trim($_POST['new_username'] ?? '');
$email_nou = trim($_POST['new_email'] ?? '');
$parola_noua = $_POST['new_password'] ?? '';

if ($username_nou === '' || $email_nou === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Username și email sunt obligatorii']);
    exit;
}

try {
    $db = new PDO("sqlite:" . __DIR__ . '/../db/database.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $db->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'User inexistent']);
        exit;
    }
    $username_vechi = $user['username'];
    if ($username_nou !== $username_vechi) {
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username_nou]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Username deja folosit']);
            exit;
        }
    }
    $imgPath = null;
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['tmp_name']) {
        $targetDir = '../../frontend/uploads/avatars/';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        $ext = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
        $imgName = uniqid('user_', true) . '.' . $ext;
        $targetFile = $targetDir . $imgName;
        if (!move_uploaded_file($_FILES['profile_pic']['tmp_name'], $targetFile)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Eroare la upload avatar']);
            exit;
        }
        $imgPath = 'uploads/avatars/' . $imgName;
    }
    $set = "username = :username, email = :email";
    $params = [':username' => $username_nou, ':email' => $email_nou, ':id' => $user_id];
    if ($parola_noua) {
        if (strlen($parola_noua) < 6) {
            echo json_encode(['success' => false, 'error' => 'Parola prea scurtă']);
            exit;
        }
        $set .= ", password = :password";
        $params[':password'] = password_hash($parola_noua, PASSWORD_DEFAULT);
    }
    if ($imgPath) {
        $set .= ", image_url = :img";
        $params[':img'] = $imgPath;
    }
    $stmt = $db->prepare("UPDATE users SET $set WHERE id = :id");
    $stmt->execute($params);
    if ($username_nou !== $username_vechi) {
        $tables_and_fields = [
            'colectii' => ['user'],
            'obiecte' => ['proprietar'],
            'tranzactii' => ['ofertant', 'proprietar'],
            'tranzactii_colectii' => ['ofertant', 'proprietar'],
            'oferte' => ['user'],
            'oferte_colectii' => ['user'],
            'probleme' => ['user'],
            'admin_actiuni' => ['admin_user'],
        ];
        foreach ($tables_and_fields as $table => $fields) {
            foreach ($fields as $field) {
                $stmt = $db->prepare("UPDATE $table SET $field = :new WHERE $field = :old");
                $stmt->execute([
                    ':new' => $username_nou,
                    ':old' => $username_vechi
                ]);
            }
        }
    }
    $newToken = null;
    if ($username_nou !== $username_vechi) {
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $userNou = $stmt->fetch(PDO::FETCH_ASSOC);
        $newToken = generateJWT(['user_id' => $userNou['id'], 'username' => $userNou['username']]);
    }
    echo json_encode(['success' => true, 'new_token' => $newToken]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Eroare DB: ' . $e->getMessage()]);
} 