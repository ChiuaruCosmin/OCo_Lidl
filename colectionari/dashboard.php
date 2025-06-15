<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../login.html');
    exit;
}
$username = htmlspecialchars($_SESSION['user']);
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
  <nav>
    <div>
      <a href="#">📚 Colecțiile mele</a>
      <a href="#">🏷️ Vinde obiecte</a>
      <a href="../adauga.html">➕ Adaugă obiect</a>
    </div>
    <div class="profile-menu">
      <button id="userBtn" type="button"><?= $username ?> ⌄</button>
      <div id="dropdown" class="dropdown-content hidden">
        <a href="profile.php">Profil</a>
        <a href="logout.php">Deconectare</a>
      </div>
    </div>
  </nav>

  <div class="dashboard-container">
    <div class="left-panel">
      <div class="greeting">Bun venit, <?= $username ?>!</div>
      <div class="subtitle">Iată colecțiile populare din această săptămână:</div>

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
          <img src="../assets/monede.png" alt="Monede">
          <p>127 de obiecte</p>
        </div>

        <div class="collection-card">
          <h4>Cărți poștale</h4>
          <img src="../assets/cartipostale.png" alt="Cărți poștale">
          <p>32 de obiecte</p>
          <div class="delete-icon">🗑️</div>
        </div>
      </div>

      <div class="search-bar">
        <span class="icon">🔍</span>
        <input type="text" placeholder="Caută obiecte...">
      </div>
    </div>

    <div class="right-panel">
      <img src="../assets/avatar.png" alt="Avatar utilizator">
      <h3><?= $username ?></h3>
      <p class="email"><?= $username ?>@example.com</p>
      <div class="profile-links">
        <a href="profile.php">Profilul meu</a>
        <a href="#">Clasamente</a>
        <a href="#">Reviews</a>
      </div>
      <button class="edit-profile-btn">Edit profile</button>
    </div>
  </div>

  <script>
    const userBtn = document.getElementById('userBtn');
    const dropdown = document.getElementById('dropdown');

    if (userBtn && dropdown) {
      userBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        dropdown.classList.toggle('hidden');
      });

      window.addEventListener('click', function (e) {
        if (!userBtn.contains(e.target) && !dropdown.contains(e.target)) {
          dropdown.classList.add('hidden');
        }
      });
    }
  </script>
</body>
</html>
