<?php
$db = new PDO("sqlite:backend/db/database.sqlite");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$populare = $db->query("
    SELECT c.titlu, c.imagine, c.user,
           (SELECT COUNT(*) FROM obiecte o WHERE o.colectie_id = c.id AND o.de_vanzare = 0) AS nr_obiecte
    FROM colectii c
    ORDER BY nr_obiecte DESC
    LIMIT 4
")->fetchAll(PDO::FETCH_ASSOC);

$ultime = $db->query("SELECT titlu, imagine, user FROM colectii ORDER BY id DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);

$clasament = $db->query("
    SELECT c.user, COUNT(o.id) as total
    FROM colectii c
    JOIN obiecte o ON o.colectie_id = c.id
    WHERE o.de_vanzare = 0
    GROUP BY c.user
    ORDER BY total DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

$categorii = $db->query("
    SELECT categorie, COUNT(*) as total 
    FROM obiecte 
    WHERE de_vanzare = 0
    GROUP BY categorie 
    ORDER BY total DESC 
    LIMIT 3
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Colecțiile Tale</title>
    <link rel="stylesheet" href="css/landing.css">
</head>
<body>

  <header class="hero-section">
    <div class="hero-content">
      <h1>Organizează, împărtășește și explorează colecțiile tale</h1>
      <button onclick="window.location.href='register.html'">începe.acum</button>
    </div>
    <div class="hero-image">
      <img src="assets/landing-hero.png" alt="Colecții populare">
    </div>
  </header>

  <main>
    <section class="search-section">
      <h2>Căutare multi-criterială</h2>
      <form class="search-form">
        <div class="search-input-wrapper">
          <span class="search-icon-prefix">🔍</span>
          <input type="text" placeholder="Căutare">
        </div>
        <select aria-label="Tip">
          <option selected disabled>Tip</option>
          <option value="timbre">Timbre</option>
          <option value="vinil">Discuri de vinil</option>
          <option value="jucarii">Jucării</option>
        </select>
        <select aria-label="Valoare">
          <option selected disabled>Valoare</option>
          <option value="mica">Mică</option>
          <option value="medie">Medie</option>
          <option value="mare">Mare</option>
        </select>
        <select aria-label="Țară">
          <option selected disabled>Țară</option>
          <option value="ro">România</option>
          <option value="md">Moldova</option>
          <option value="de">Germania</option>
        </select>
        <button type="submit" class="search-submit-button" aria-label="Caută">
          <span class="search-icon-magnifying-glass">🔍</span>
        </button>
      </form>
    </section>

    <div class="main-content-columns">
      <div class="left-column">
        <section class="popular-collections">
          <h3>Colecții populare</h3>
          <div class="collections-grid">
            <?php foreach ($populare as $c): ?>
            <div class="collection-card">
              <img src="<?= htmlspecialchars($c['imagine']) ?>" alt="<?= htmlspecialchars($c['titlu']) ?>">
              <div class="card-content">
                <h4><?= htmlspecialchars($c['titlu']) ?></h4>
                <p><?= $c['nr_obiecte'] ?> obiecte</p>
                <p class="owner"><?= htmlspecialchars($c['user']) ?></p>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </section>

        <section class="last-shared-collections-left">
          <h3>Ultimele colecții partajate</h3>
          <ul class="shared-list">
            <?php foreach ($ultime as $u): ?>
            <li>
              <img src="<?= htmlspecialchars($u['imagine']) ?>" alt="<?= htmlspecialchars($u['titlu']) ?>">
              <div class="item-details">
                <h4><?= htmlspecialchars($u['titlu']) ?></h4>
                <p><?= htmlspecialchars($u['user']) ?></p>
              </div>
            </li>
            <?php endforeach; ?>
          </ul>
        </section>
      </div>

      <div class="right-column">
        <section class="collectors-leaderboard">
          <h3>Clasament colecționari</h3>
          <ol class="leaderboard-list">
            <?php foreach ($clasament as $i => $c): ?>
              <li><span class="rank"><?= $i + 1 ?></span> <span class="name"><?= htmlspecialchars($c['user']) ?></span> <span class="score"><?= $c['total'] ?></span></li>
            <?php endforeach; ?>
          </ol>
        </section>

        <section class="last-shared-collections-right">
          <h3>Top categorii populare</h3>
          <ul class="shared-list-right">
            <?php foreach ($categorii as $i => $cat): ?>
              <li>
                <span class="item-number"><?= $i + 1 ?></span>
                <div class="item-details">
                  <h4><?= htmlspecialchars($cat['categorie']) ?></h4>
                  <p><?= $cat['total'] ?> obiecte</p>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
        </section>
      </div>
    </div>
  </main>

<div class="modal-overlay" id="guestModal" style="display:none;">
  <div class="modal-box">
    <p>Pentru a vedea mai multe, conectează-te.</p>
    <div class="modal-buttons">
      <button onclick="location.href='login.html'" class="start-btn">Începe acum</button>
      <button onclick="document.getElementById('guestModal').style.display='none'" class="cancel-btn">Nu, mulțumesc</button>
    </div>
  </div>
</div>

<script>
  function showGuestModal(e) {
    e.preventDefault();
    document.getElementById('guestModal').style.display = 'flex';
  }

  document.querySelectorAll('.collection-card, .leaderboard-list li, .shared-list-right li, .search-form input, .search-form select, .search-submit-button')
    .forEach(el => el.addEventListener('click', showGuestModal));
</script>

</body>
</html>
