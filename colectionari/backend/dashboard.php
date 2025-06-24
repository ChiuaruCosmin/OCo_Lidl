<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: ../login.html');
    exit;
}
$username = htmlspecialchars($_SESSION['username']);

try {
    $db = new PDO("sqlite:" . __DIR__ . "/db/database.sqlite");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $db->prepare("SELECT email FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $email = htmlspecialchars($user['email'] ?? 'N/A');

   $stmt = $db->query("
    SELECT c.titlu, c.imagine,
        (SELECT COUNT(*) FROM obiecte o WHERE o.colectie_id = c.id AND o.de_vanzare = 0) AS nr_obiecte
    FROM colectii c
    ORDER BY nr_obiecte DESC
    LIMIT 4
");

    $colectii_populare = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $email = 'Eroare la citire';
    $colectii_populare = [];
}
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
      <a href="../colectiile_mele.php">📚 Colecțiile mele</a>
      <a href="../cumpara_obiecte.php">🏷️ Cumpără obiecte</a>
      <a href="../vinde_obiecte.php">💵 Vinde obiecte</a>
    </div>
    <div class="logout-button">
      <a href="../raport.html" class="logout-link">Raport</a>
      <a href="logout.php?redirect=landing" class="logout-link">Deconectare</a>
      
    </div>
  </nav>

  <div class="dashboard-container">
    <div class="left-panel">
      <h1 class="greeting">Bun venit, <?= $username ?>!</h1>
      <p class="subtitle">Iată cele mai populare colecții din această săptămână:</p>
      <div class="collection-grid">
        <?php foreach ($colectii_populare as $c): ?>
          <div class="collection-card" data-titlu="<?= htmlspecialchars($c['titlu']) ?>">
            <h4><?= htmlspecialchars($c['titlu']) ?></h4>
            <img src="../<?= htmlspecialchars($c['imagine']) ?>" alt="imagine">
            <p><?= (int)$c['nr_obiecte'] ?> de obiecte</p>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="search-bar"><span class="icon">🔍</span><input type="text" placeholder="Caută obiecte..."></div>
    </div>

    <div class="right-panel">
      <div class="user-info-header">
          <img class="avatar" src="../assets/avatars/<?= $username ?>.png" alt="Avatar" onerror="this.src='../assets/avatar.png'">
          <div class="user-details">
              <h3><?= $username ?></h3>
              <p class="email"><?= $email ?></p>
          </div>
      </div>
      <div class="profile-links">
        <a href="../istoric_tranzactii.php" class="btn">Istoric tranzacții</a>
        <a href="../statistici.php">Statistici</a>
        
        
      </div>
      <button class="edit-profile-btn">Edit profile</button>
    </div>
  </div>
  <div id="editModal" class="modal">
    <div class="modal-content">
      <span class="close-btn">&times;</span>
      <h2>Editare profil</h2>
      <form id="editProfileForm" enctype="multipart/form-data">
        <label for="new_username">Username nou:</label>
        <input type="text" id="new_username" name="new_username">

        <label for="new_password">Parolă nouă:</label>
        <input type="password" id="new_password" name="new_password">

        <label for="profile_pic">Poză profil:</label>
        <input type="file" id="profile_pic" name="profile_pic" accept="image/*">

        <div class="modal-actions">
          <button type="submit" class="save-btn">Salvează</button>
          <button type="button" class="cancel-btn">Anulează</button>
        </div>
      </form>
    </div>
  </div>

  <div id="searchOverlay" class="search-overlay">
    <div class="search-modal">
      <h2>Caută colecții</h2>
      <form id="searchForm" class="search-form-grid">
        <input type="text" name="titlu" placeholder="Titlu colecție">
        <input type="number" name="valoare_min" placeholder="Valoare minimă">
        <input type="number" name="valoare_max" placeholder="Valoare maximă">
        <input type="text" name="an" placeholder="An">
        <input type="text" name="tara" placeholder="Țara">
        <input type="text" name="perioada" placeholder="Perioadă utilizare">
        <input type="text" name="material" placeholder="Material">
        <select name="eticheta">
          <option value="">Etichetă?</option>
          <option value="1">Da</option>
          <option value="0">Nu</option>
        </select>
        <button type="submit" class="apply-btn">Aplică filtre</button>
        <button type="button" id="closeOverlay" class="cancel-btn">Închide</button>
      </form>
      <div class="search-results-container" id="searchResults"></div>
    </div>
  </div>

  <div id="colectieModal" class="modal">
    <div class="modal-content">
      <span class="close-colectie">&times;</span>
      <h2 id="colectieTitlu"></h2>
      <div id="obiecteContainer" class="obiecte-container"></div>
    </div>
  </div>

  <script>
    const modal = document.getElementById('editModal');
    const editBtn = document.querySelector('.edit-profile-btn');
    const closeBtn = document.querySelector('.close-btn');
    const cancelBtn = document.querySelector('.cancel-btn');

    editBtn.onclick = () => {
      const name = document.querySelector('.user-details h3').textContent.trim();
      document.getElementById('new_username').value = name;
      modal.classList.add('show-modal');
    };
    closeBtn.onclick = () => modal.classList.remove('show-modal');
    cancelBtn.onclick = () => modal.classList.remove('show-modal');
    window.onclick = (e) => {
      if (e.target === modal) modal.classList.remove('show-modal');
    };
    document.getElementById('editProfileForm').addEventListener('submit', function(e) {
      e.preventDefault();
      const formData = new FormData(this);
      fetch('api/update_profile.php', {
  method: 'POST',
  body: formData
})
.then(res => res.json())
.then(data => {
  alert(data.message);
  modal.classList.remove('show-modal');
  location.reload();
})
.catch(err => {
  alert("Eroare la actualizare profil.");
});

    });

    const overlay = document.getElementById('searchOverlay');
    const searchBarInput = document.querySelector('.search-bar input');
    const closeOverlayBtn = document.getElementById('closeOverlay');
    const searchForm = document.getElementById('searchForm');
    const resultsContainer = document.getElementById('searchResults');
    const colectieModal = document.getElementById('colectieModal');
    const obiecteContainer = document.getElementById('obiecteContainer');
    const closeColectie = document.querySelector('.close-colectie');
    const colectieTitlu = document.getElementById('colectieTitlu');

    searchBarInput.addEventListener('focus', () => {
      overlay.style.display = 'flex';
    });
    closeOverlayBtn.addEventListener('click', () => {
      overlay.style.display = 'none';
      resultsContainer.innerHTML = '';
      searchForm.reset();
    });

    searchForm.addEventListener('submit', function(e) {
      e.preventDefault();
      const formData = new FormData(this);
      fetch('api/filtre_dashboard.php', {

        method: 'POST',
        body: formData
      }).then(r => r.json()).then(data => {
        resultsContainer.innerHTML = '';
        if (data.length === 0) {
          resultsContainer.innerHTML = '<p>Nu s-au găsit colecții.</p>';
          return;
        }
        data.forEach(c => {
          const card = document.createElement('div');
          card.className = 'search-result-card';
          card.innerHTML = `
            <img src="../${c.imagine}" alt="${c.titlu}">
            <h4>${c.titlu}</h4>
            <p>${c.nr_obiecte} obiecte</p>
            <p><strong>${c.user}</strong></p>
          `;
          card.addEventListener('click', () => afiseazaObiecte(c.titlu));
          resultsContainer.appendChild(card);
        });
      });
    });

    document.querySelectorAll('.collection-card').forEach(card => {
      card.addEventListener('click', () => {
        const titlu = card.dataset.titlu;
        afiseazaObiecte(titlu);
      });
    });

function afiseazaObiecte(titlu) {
  colectieTitlu.textContent = titlu;
  obiecteContainer.innerHTML = '<p>Se încarcă...</p>';
  colectieModal.classList.add('show-modal');

  const formData = new FormData(document.getElementById('searchForm'));
  formData.append('titlu', titlu);

  const params = new URLSearchParams(formData).toString();

  fetch('../backend/api/obiecte_din_colectie.php?' + params)
    .then(res => {
      if (!res.ok) throw new Error('Eroare la fetch: ' + res.status);
      return res.json();
    })
    .then(data => {
      obiecteContainer.innerHTML = '';

      if (!Array.isArray(data) || data.length === 0) {
        obiecteContainer.innerHTML = '<p>Nu există obiecte în această colecție care corespund filtrelor.</p>';
        return;
      }

      data.forEach(obj => {
        const card = document.createElement('div');
        card.className = 'modal-object';
        card.innerHTML = `
          <img src="../${obj.imagine}" alt="${obj.titlu}">
          <div class="modal-object-content">
            <h4>${obj.titlu}</h4>
            <em>${obj.descriere || ''}</em>
            <small><strong>Valoare:</strong> ${obj.valoare || '-'} RON</small>
            <small><strong>Țara:</strong> ${obj.tara || '-'}</small>
            <small><strong>Perioadă:</strong> ${obj.perioada || '-'}</small>
            <small><strong>Material:</strong> ${obj.material || '-'}</small>
            <small><strong>Etichetă:</strong> ${obj.eticheta == 1 ? 'Da' : 'Nu'}</small>
          </div>
        `;
        obiecteContainer.appendChild(card);
      });
    })
    .catch(error => {
      console.error("Eroare la afișare obiecte:", error);
      obiecteContainer.innerHTML = '<p>Eroare la încărcarea obiectelor.</p>';
    });
}

    closeColectie.addEventListener('click', () => colectieModal.classList.remove('show-modal'));
    window.addEventListener('click', (e) => {
      if (e.target === colectieModal) colectieModal.classList.remove('show-modal');
    });
  </script>

</body>
</html>
