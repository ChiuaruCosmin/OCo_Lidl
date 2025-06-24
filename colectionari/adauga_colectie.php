<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: login.html');
    exit;
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Adaugă colecție</title>
    <link rel="stylesheet" href="css/adauga_colectie.css">

</head>
<body>
    <main>
        <div class="page-header">
            <h1>Adaugă o colecție nouă</h1>
            <a class="btn-add" href="colectiile_mele.php">Înapoi la colecțiile mele</a>
        </div>

        <form method="post" action="salveaza_colectie.php" class="form-wrapper" enctype="multipart/form-data">
    <label for="titlu">Titlu colecție:</label>
    <input type="text" name="titlu" id="titlu" required>

    <label for="imagine">Imagine colecție:</label>
    <input type="file" name="imagine" id="imagine" accept="image/*" required>

    <button type="submit" class="btn-add">Salvează colecția</button>
</form>

    </main>
</body>
</html>
