<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: ../login.html');
    exit;
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
  <meta charset="UTF-8" />
  <title>Adaugă obiect nou</title>
  <link rel="stylesheet" href="../css/style.css" />
</head>
<body>
  <div class="form-container">
    <h2>Adaugă obiect nou</h2>
    <form id="addForm">
      <label for="titlu">Titlu:</label>
      <input type="text" id="titlu" name="titlu" required />

      <label for="categorie">Categorie:</label>
      <input type="text" id="categorie" name="categorie" required />

      <label for="descriere">Descriere:</label>
      <textarea id="descriere" name="descriere" rows="4"></textarea>

      <label for="an">An:</label>
      <input type="number" id="an" name="an" min="0" />

      <button type="submit">Salvează</button>
    </form>

    <p><a href="dashboard.php">Înapoi la dashboard</a></p>
  </div>

  <script src="../js/adauga.js"></script>
</body>
</html>
