<?php
require_once __DIR__ . '/../lib/jwt.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

$username = $input['username'] ?? '';
$password = $input['password'] ?? '';
$email    = $input['email']    ?? '';

if (!$username || !$password || !$email) {
    echo json_encode(['success' => false, 'message' => 'Toate câmpurile sunt obligatorii']);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Parola trebuie să aibă cel puțin 6 caractere']);
    exit;
}

try {
    $db = new PDO("sqlite:" . __DIR__ . '/../db/database.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);

    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Utilizatorul există deja']);
        exit;
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $src = dirname(__DIR__, 2) . '/frontend/assets/avatars/avatar.png';
    $dstRel = 'uploads/avatars/' . $username . '.png';
    $dstAbs = dirname(__DIR__, 2) . '/frontend/' . $dstRel;
    error_log('DEBUG: Sursa avatar: ' . $src);
    error_log('DEBUG: Destinatie avatar: ' . $dstAbs);
    if (!file_exists($src)) {
        error_log('DEBUG: Fisierul sursa NU exista!');
    } else {
        error_log('DEBUG: Fisierul sursa exista!');
    }
    if (!is_dir(dirname($dstAbs))) {
        error_log('DEBUG: Folderul destinatie NU exista, incerc sa il creez...');
        mkdir(dirname($dstAbs), 0777, true);
    }
    if (!is_writable(dirname($dstAbs))) {
        error_log('DEBUG: Folderul destinatie NU este scriabil!');
    } else {
        error_log('DEBUG: Folderul destinatie este scriabil!');
    }
    $copyResult = copy($src, $dstAbs);
    error_log('DEBUG: Rezultat copy: ' . ($copyResult ? 'SUCCES' : 'ESUAT'));
    if ($copyResult) {
        error_log('Avatar copiat cu succes: ' . $dstAbs);
    } else {
        error_log('Eroare la copiere avatar: ' . $src . ' -> ' . $dstAbs);
    }

    $stmt = $db->prepare("INSERT INTO users (username, password, email, image_url) VALUES (?, ?, ?, ?)");
    $stmt->execute([$username, $hashedPassword, $email, $dstRel]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Eroare la salvare',
        'error' => $e->getMessage()
    ]);
}
