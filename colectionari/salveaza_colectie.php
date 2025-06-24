<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: login.html');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titlu = trim($_POST['titlu']);
    $imaginePath = 'assets/default.png';

    if (isset($_FILES['imagine']) && $_FILES['imagine']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'assets/uploads/';
        $fileName = basename($_FILES['imagine']['name']);
        $targetPath = $uploadDir . time() . '_' . $fileName;

        if (move_uploaded_file($_FILES['imagine']['tmp_name'], $targetPath)) {
            $imaginePath = $targetPath;
        }
    }

    if ($titlu !== '') {
        try {
            $dbPath = __DIR__ . '/backend/db/database.sqlite';
            $db = new PDO("sqlite:$dbPath");
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $db->prepare("INSERT INTO colectii (user, titlu, nr_obiecte, imagine) 
                                  VALUES (:user, :titlu, 0, :img)");
            $stmt->execute([
                ':user' => $_SESSION['username'],
                ':titlu' => $titlu,
                ':img' => $imaginePath
            ]);

            header("Location: colectiile_mele.php");
            exit;
        } catch (PDOException $e) {
            echo "Eroare la salvare: " . htmlspecialchars($e->getMessage());
        }
    } else {
        echo "Titlul nu poate fi gol.";
    }
} else {
    header("Location: colectiile_mele.php");
    exit;
}
