<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: login.html');
    exit;
}
$username = htmlspecialchars($_SESSION['username']);
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Colecțiile mele</title>
    <link rel="stylesheet" href="css/colectiile_mele.css">
</head>
<body>
<main>
    <div class="page-header">
        <h1>Colecțiile mele</h1>
        <div style="display: flex; gap: 15px;">
            <a class="btn-add" href="adauga_colectie.php">+ Adaugă colecție nouă</a>
            <a class="btn-add" href="backend/dashboard.php">Înapoi la pagina principală</a>
        </div>
    </div>

    <div class="dropdown-filter-toggle" onclick="toggleFilters()">🔍 Filtrează colecțiile</div>
    <div class="dropdown-filters" id="filterBox" style="display: flex;">
        <input type="text" id="search" placeholder="Titlu colecție">
        <input type="number" id="valoare_min" placeholder="Valoare minimă">
        <input type="number" id="valoare_max" placeholder="Valoare maximă">
        <input type="number" id="an" placeholder="An">
        <input type="text" id="tara" placeholder="Țara">
        <input type="text" id="perioada" placeholder="Perioada utilizare">
        <input type="text" id="material" placeholder="Material">
        <select id="eticheta">
            <option value="">Etichetă?</option>
            <option value="1">Da</option>
            <option value="0">Nu</option>
        </select>
        <button class="btn-apply" onclick="filtreaza()">Aplică filtre</button>
        <button class="btn-clear" onclick="stergeFiltre()">Șterge filtre</button>
    </div>

    <div class="collections-wrapper">
        <div class="collections-grid" id="colectiiContainer"></div>
    </div>
</main>

<div class="modal-overlay" id="modal-overlay" style="display:none;">
    <div class="modal">
        <h2 id="modal-title">Detalii colecție</h2>
        <div id="modal-content"><p>Se încarcă...</p></div>
        <div class="modal-buttons">
            <button onclick="closeModal()">Închide</button>
            <button onclick="window.location.href='adauga_obiect.php?id='+currentId">Adaugă obiecte</button>
        </div>
    </div>
</div>

<div class="modal-overlay" id="modal-obiect-overlay" style="display:none;">
    <div class="modal">
        <h2 id="obiect-title">Detalii obiect</h2>
        <div id="obiect-content"><p>Se încarcă...</p></div>
        <div class="modal-buttons">
            <button onclick="closeObiectModal()">Închide</button>
            <button onclick="stergeObiect()">Șterge</button>
            <button id="edit-obiect-btn">Editează</button>
        </div>
    </div>
</div>

<script>
let currentId = null;
let currentObiectId = null;

function toggleFilters() {
    const box = document.getElementById("filterBox");
    box.style.display = box.style.display === "flex" ? "none" : "flex";
}

function genereazaCarduri(colectii) {
    const container = document.getElementById('colectiiContainer');
    container.innerHTML = "";
    if (colectii.length === 0) {
        container.innerHTML = '<p class="no-collections">Nu există nicio colecție.</p>';
        return;
    }
    colectii.forEach(c => {
        const card = document.createElement('div');
        card.className = 'collection-card';
        card.setAttribute('data-title', c.titlu.toLowerCase());
        card.onclick = () => openModal(c.id, c.titlu);
        card.innerHTML = `
            <img src="${c.imagine}" alt="${c.titlu}">
            <h3>${c.titlu}</h3>
            <p>${c.nr_obiecte} obiecte</p>
            <div class="card-actions">
                <button class="btn-edit" onclick="event.stopPropagation(); window.location.href='editeaza_colectie.php?id=${c.id}'">Editează</button>
                <button class="btn-delete" onclick="event.stopPropagation(); deleteCollection(this, ${c.id})">Șterge</button>
            </div>
        `;
        container.appendChild(card);
    });
}

function filtreaza() {
    const params = new URLSearchParams();
    params.append("titlu", document.getElementById("search").value);
    params.append("valoare_min", document.getElementById("valoare_min").value);
    params.append("valoare_max", document.getElementById("valoare_max").value);
    params.append("an", document.getElementById("an").value);
    params.append("tara", document.getElementById("tara").value);
    params.append("perioada", document.getElementById("perioada").value);
    params.append("eticheta", document.getElementById("eticheta").value);
    params.append("material", document.getElementById("material").value);

    fetch('backend/api/filtre_colectii.php?' + params.toString())
        .then(r => r.json())
        .then(data => genereazaCarduri(data))
        .catch(e => {
            document.getElementById('colectiiContainer').innerHTML = '<p class="no-collections">Eroare la filtrare.</p>';
        });
}

function stergeFiltre() {
    document.getElementById("search").value = "";
    document.getElementById("valoare_min").value = "";
    document.getElementById("valoare_max").value = "";
    document.getElementById("tara").value = "";
    document.getElementById("perioada").value = "";
    document.getElementById("material").value = "";
    document.getElementById("eticheta").value = "";
    filtreaza();
}

function deleteCollection(btn, id) {
    if (!confirm("Sigur dorești să ștergi această colecție?")) return;
    fetch("backend/api/sterge_colectie.php", {
        method: "POST",
        headers: {"Content-Type": "application/x-www-form-urlencoded"},
        body: "id=" + id
    })
    .then(res => res.text())
    .then(resp => {
        if (resp.trim() === "Succes") {
            btn.closest('.collection-card').remove();
            if (!document.querySelector('.collection-card')) {
                document.getElementById('colectiiContainer').innerHTML = '<p class="no-collections">Nu există nicio colecție.</p>';
            }
        } else alert("Eroare: " + resp);
    });
}

function openModal(id, titlu) {
    currentId = id;
    document.getElementById('modal-title').innerText = 'Colecția: ' + titlu;
    document.getElementById('modal-overlay').style.display = 'flex';

    const params = new URLSearchParams();
    params.append("id", id);
    params.append("valoare_min", document.getElementById("valoare_min").value);
    params.append("valoare_max", document.getElementById("valoare_max").value);
    params.append("an", document.getElementById("an").value);
    params.append("tara", document.getElementById("tara").value);
    params.append("perioada", document.getElementById("perioada").value);
    params.append("material", document.getElementById("material").value);
    params.append("eticheta", document.getElementById("eticheta").value);

    fetch('backend/api/obiecte_colectie.php?' + params.toString())
        .then(r => r.json()).then(data => {
            const c = document.getElementById('modal-content');
            c.innerHTML = '';
            if (data.length === 0) {
                c.innerHTML = '<p>Colecția nu conține obiecte care să corespundă filtrelor.</p>';
            } else {
                data.forEach(obj => {
                    const div = document.createElement('div');
                    div.className = 'modal-object';
                    div.onclick = () => openObiectModal(obj.id);
                    div.innerHTML = `
                        <img src="${obj.imagine}" alt="">
                        <div class="modal-object-content">
                            <h4>${obj.titlu}</h4>
                            <em>${obj.categorie}</em>
                            <small>An: ${obj.an}</small>
                        </div>
                        <button class="btn-sell" onclick="event.stopPropagation(); vindeObiect(${obj.id}, this)">Vinde</button>
                    `;
                    c.appendChild(div);
                });
            }
        }).catch(_ => {
            document.getElementById('modal-content').innerHTML = '<p>Eroare la încărcare.</p>';
        });
}

function openObiectModal(id) {
    currentObiectId = id;
    document.getElementById('modal-obiect-overlay').style.display = 'flex';
    fetch('backend/api/detalii_obiect.php?id=' + id)
        .then(r => r.json()).then(obj => {
            document.getElementById('obiect-title').innerText = obj.titlu;
            const c = document.getElementById('obiect-content');
            c.innerHTML = `<div style="text-align:left">
                <img src="${obj.imagine}" style="max-width:100px"><br><br>
                <strong>Categorie:</strong> ${obj.categorie}<br>
                <strong>Material:</strong> ${obj.material || '-'}<br>
                <strong>Valoare:</strong> ${obj.valoare || '-'} lei<br>
                <strong>Țara:</strong> ${obj.tara || '-'}<br>
                <strong>Perioadă:</strong> ${obj.perioada || '-'}<br>
                <strong>Istoric:</strong> ${obj.istoric || '-'}<br>
                <strong>Etichetă:</strong> ${obj.eticheta ? 'Da' : 'Nu'}<br>
                <strong>Descriere:</strong> ${obj.descriere || '-'}<br>
                <strong>An:</strong> ${obj.an || '-'}
            </div>`;
            document.getElementById('edit-obiect-btn').onclick = () => {
                window.location.href = 'editeaza_obiect.php?id=' + obj.id;
            };
        }).catch(_=> document.getElementById('obiect-content').innerHTML = '<p>Eroare la încărcare.</p>');
}

function stergeObiect() {
    if (!confirm("Sigur dorești să ștergi acest obiect?")) return;
    fetch("backend/api/sterge_obiect.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "id=" + currentObiectId
    })
    .then(res => res.text())
    .then(resp => {
        if (resp.trim() === "Succes") {
            closeObiectModal();
            alert("Obiect șters cu succes.");
            filtreaza();
        } else {
            alert("Eroare la ștergere: " + resp);
        }
    });
}

function closeModal() {
    document.getElementById('modal-overlay').style.display = 'none';
}
function closeObiectModal() {
    document.getElementById('modal-obiect-overlay').style.display = 'none';
}

function vindeObiect(idObiect, btn) {
    const pret = prompt("Introdu prețul de vânzare (lei):");
    if (!pret || isNaN(pret) || pret <= 0) {
        alert("Preț invalid.");
        return;
    }

    fetch("backend/api/vinde_obiect_api.php", {
        method: "POST",
        headers: {"Content-Type": "application/x-www-form-urlencoded"},
        body: `id=${idObiect}&pret=${encodeURIComponent(pret)}`
    })
    .then(r => r.text())
    .then(resp => {
        if (resp.trim() === "Succes") {
            btn.closest(".modal-object").remove();
            alert("Obiectul a fost marcat ca vândut.");
        } else {
            alert("Eroare la vânzare: " + resp);
        }
    })
    .catch(() => alert("Eroare la comunicarea cu serverul."));
}

document.addEventListener("DOMContentLoaded", filtreaza);
</script>
</body>
</html>
