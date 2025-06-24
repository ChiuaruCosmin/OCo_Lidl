<?php
session_start();

if (!isset($_SESSION['username']) || !isset($_GET['id'])) {
    header("Location: login.html");
    exit;
}

$username = $_SESSION['username'];
$id = intval($_GET['id']);
$dbPath = __DIR__ . "/backend/db/database.sqlite";
$error = '';
$colectie = null;

try {
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $db->prepare("SELECT * FROM colectii WHERE id = :id AND user = :user");
    $stmt->execute([':id' => $id, ':user' => $username]);
    $colectie = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$colectie) {
        $error = "Colecția nu a fost găsită.";
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $titluNou = trim($_POST['titlu']);

        if ($titluNou === '') {
            $error = "Titlul nu poate fi gol.";
        } else {
            $imagineNoua = $colectie['imagine'];

            if (isset($_FILES['imagine']) && $_FILES['imagine']['error'] === UPLOAD_ERR_OK) {
                $ext = pathinfo($_FILES['imagine']['name'], PATHINFO_EXTENSION);
                $numeNou = uniqid() . '.' . $ext;
                $caleFinala = "assets/uploads/$numeNou";

                if (move_uploaded_file($_FILES['imagine']['tmp_name'], $caleFinala)) {
                    if (
                        str_starts_with($colectie['imagine'], 'assets/uploads/') &&
                        file_exists(__DIR__ . '/' . $colectie['imagine'])
                    ) {
                        unlink(__DIR__ . '/' . $colectie['imagine']);
                    }
                    $imagineNoua = $caleFinala;
                } else {
                    $error = "Eroare la încărcarea noii imagini.";
                }
            }

            if ($error === '') {
                $update = $db->prepare("UPDATE colectii SET titlu = :titlu, imagine = :img WHERE id = :id AND user = :user");
                $update->execute([
                    ':titlu' => $titluNou,
                    ':img' => $imagineNoua,
                    ':id' => $id,
                    ':user' => $username
                ]);

                header("Location: colectiile_mele.php");
                exit;
            }
        }
    }
} catch (PDOException $e) {
    $error = "Eroare DB: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Editează colecția</title>
    <link rel="stylesheet" href="css/editare_colectie.css">
</head>
<body>
<div class="form-container">
    <h2>Editează colecția</h2>
    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST" enctype="multipart/form-data" class="form-colectie">
        <label for="titlu">Nume colecție:</label>
        <input type="text" id="titlu" name="titlu" value="<?= htmlspecialchars($colectie['titlu']) ?>" required>

        <label for="imagine">Imagine nouă (opțional):</label>
        <input type="file" id="imagine" name="imagine" accept="image/*">

        <button type="submit" class="btn-add">Salvează modificările</button>
        <a href="colectiile_mele.php" class="btn-add" style="background-color: #555;">Anulează</a>
    </form>
</div>
</body>
</html>
