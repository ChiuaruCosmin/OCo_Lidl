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
  <meta charset="UTF-8">
  <title>Profil utilizator</title>
</head>
<body>
  <h2>Bine ai venit, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
  <p><a href="logout.php">Deconectare</a></p>
</body>
</html>
