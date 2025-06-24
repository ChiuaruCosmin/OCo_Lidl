<?php
function connectDB() {
    $path = __DIR__ . '/db/database.sqlite';
    $db = new PDO('sqlite:' . $path);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $db;
}

function checkUserCredentials($username, $password) {
    $db = connectDB();
    $stmt = $db->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        return $user;
    }

    return false;
}
