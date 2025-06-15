<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: ../login.html');
    exit;
}
$username = htmlspecialchars($_SESSION['username']);
?>
<!DOCTYPE html>
<html lang="ro">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard</title>
  <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body>
  <nav class="navbar">
    <div class="nav-links">
      <a href="#">📚 Colecțiile mele</a>
      <a href="#">🏷️ Vinde obiecte</a>
      <a href="../adauga.html">➕ Adaugă obiect</a>
    </div>
    <div class="logout-button">
      <a href="logout.php?redirect=landing" class="logout-link">Deconectare</a>
    </div>
  </nav>

  <div class="dashboard-container">
    <div class="left-panel">
      <h1 class="greeting">Bun venit, <?= $username ?>!</h1>
      <p class="subtitle">Iată colecțiile populare din această săptămână:</p>

      <div class="collection-grid">
        <div class="collection-card">
          <h4>Timbre</h4>
          <img src="../assets/timbre.png" alt="Timbre">
          <p>184 de obiecte</p>
        </div>

        <div class="collection-card">
          <h4>Viniluri</h4>
          <img src="../assets/vinil.png" alt="Viniluri">
          <p>58 de obiecte</p>
        </div>

        <div class="collection-card">
          <h4>Monede</h4>
          <img src="../assets/bani.png" alt="Monede">
          <p>127 de obiecte</p>
        </div>

        <div class="collection-card">
          <h4>Cărți poștale</h4>
          <img src="../assets/cartepostala.png" alt="Cărți poștale">
          <p>32 de obiecte</p>
          <span class="delete-icon">🗑️</span>
        </div>
      </div>

      <div class="search-bar">
        <span class="icon">🔍</span>
        <input type="text" placeholder="Caută obiecte...">
      </div>
    </div>

    <div class="right-panel">
      <div class="user-info-header">
          <img class="avatar" src="../assets/avatars/<?= $username ?>.png" alt="Avatar utilizator" onerror="this.src='../assets/avatar.png'">
          <div class="user-details">
              <h3><?= $username ?></h3>
              <p class="email"><?= $username ?>@example.com</p>
          </div>
      </div>
      <div class="profile-links">
        <a href="profile.php">Profilul meu</a>
        <a href="#">Clasamente</a>
        <a href="#">Reviews</a>
      </div>
      <button class="edit-profile-btn">Edit profile</button>
    </div>
  </div>

</body>
</html>