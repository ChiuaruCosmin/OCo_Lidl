<?php
session_start();
if (!isset($_SESSION['username'])) {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Neautentificat"]);
    exit;
}

$oldUsername = $_SESSION['username'];
$newUsername = trim($_POST['new_username'] ?? '');
$newPassword = trim($_POST['new_password'] ?? '');

$oldAvatarPath = __DIR__ . "/../../assets/avatars/$oldUsername.png";
$newAvatarPath = __DIR__ . "/../../assets/avatars/$newUsername.png";

header('Content-Type: application/json');

try {
    $db = new PDO("sqlite:" . __DIR__ . "/../db/database.sqlite");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $fields = [];
    $params = [];

    if ($newUsername !== '' && $newUsername !== $oldUsername) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$newUsername]);
        if ($stmt->fetchColumn() > 0) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Acest nume de utilizator există deja."]);
            exit;
        }

        $fields[] = "username = ?";
        $params[] = $newUsername;
    }

    if ($newPassword !== '') {
        if (strlen($newPassword) < 6) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Parola trebuie să aibă cel puțin 6 caractere."]);
            exit;
        }
        $fields[] = "password = ?";
        $params[] = password_hash($newPassword, PASSWORD_DEFAULT);
    }

    if (!empty($fields)) {
        $sql = "UPDATE users SET " . implode(", ", $fields) . " WHERE username = ?";
        $params[] = $oldUsername;
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
    }

    if (
        isset($_FILES['profile_pic']) &&
        $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK
    ) {
        if ($newUsername !== '' && $newUsername !== $oldUsername && file_exists($oldAvatarPath)) {
            rename($oldAvatarPath, $newAvatarPath);
        }

        $targetPath = $newUsername !== '' ? $newAvatarPath : $oldAvatarPath;
        move_uploaded_file($_FILES['profile_pic']['tmp_name'], $targetPath);
    } elseif ($newUsername !== '' && $newUsername !== $oldUsername && file_exists($oldAvatarPath)) {
        rename($oldAvatarPath, $newAvatarPath);
    }

    if ($newUsername !== '' && $newUsername !== $oldUsername) {
        $updateTables = [
            "colectii" => "\"user\"",
            "obiecte" => "\"proprietar\"",
            "oferte" => "\"user\"",
            "tranzactii" => "\"ofertant\"",
            "tranzactii" => "\"proprietar\""
        ];

        foreach ($updateTables as $table => $field) {
            $stmt = $db->prepare("UPDATE $table SET $field = ? WHERE $field = ?");
            $stmt->execute([$newUsername, $oldUsername]);
        }

        $_SESSION['username'] = $newUsername;
        session_write_close();

    }

    echo json_encode(["status" => "ok", "message" => "Profil actualizat cu succes!"]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Eroare la actualizare: " . $e->getMessage()]);
}