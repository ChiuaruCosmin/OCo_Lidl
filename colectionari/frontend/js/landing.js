document.addEventListener("DOMContentLoaded", async () => {
  console.log("landing.js este încărcat și rulează");

  try {
    const res = await fetch("/colectionari/backend/api/landing-data.php");
    if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);

    const data = await res.json();

    populatePopularCollections(data.populare);
    populateLatestCollections(data.ultime);
    populateLeaderboard(data.clasament);
    populateCategories(data.categorii);
  } catch (err) {
    console.error("Eroare la încărcarea datelor:", err);
  }

  function populatePopularCollections(list) {
    const container = document.getElementById("popular-collections");
    container.innerHTML = "";
    list.forEach(c => {
      container.innerHTML += `
        <div class="collection-card" onclick="showGuestModal(event)">
          <img src="${c.imagine}" alt="${c.titlu}">
          <div class="card-content">
            <h4>${c.titlu}</h4>
            <p>${c.nr_obiecte} obiecte</p>
            <p class="owner">${c.user}</p>
          </div>
        </div>`;
    });
  }

  function populateLatestCollections(list) {
    const ul = document.getElementById("latest-collections");
    ul.innerHTML = "";
    list.forEach(u => {
      ul.innerHTML += `
        <li onclick="showGuestModal(event)">
          <img src="${u.imagine}" alt="${u.titlu}">
          <div class="item-details">
            <h4>${u.titlu}</h4>
            <p>${u.user}</p>
          </div>
        </li>`;
    });
  }

  function populateLeaderboard(list) {
    const ol = document.getElementById("leaderboard");
    ol.innerHTML = "";
    list.forEach((c, i) => {
      ol.innerHTML += `
        <li onclick="showGuestModal(event)">
          <span class="rank">${i + 1}</span> 
          <span class="name">${c.user}</span> 
          <span class="score">${c.total}</span>
        </li>`;
    });
  }

  function populateCategories(list) {
    const ul = document.getElementById("top-categories");
    ul.innerHTML = "";
    list.forEach((cat, i) => {
      ul.innerHTML += `
        <li onclick="showGuestModal(event)">
          <span class="item-number">${i + 1}</span>
          <div class="item-details">
            <h4>${cat.categorie}</h4>
            <p>${cat.total} obiecte</p>
          </div>
        </li>`;
    });
  }
});

function showGuestModal(e) {
  e.preventDefault();
  document.getElementById('guestModal').style.display = 'flex';
}
