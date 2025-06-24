<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.html");
    exit;
}
$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Cumpără obiecte</title>
    <link rel="stylesheet" href="css/cumpara.css">
</head>
<body>
<div class="top-bar">
    <a href="backend/dashboard.php">Înapoi la pagina principală</a>
</div>

<main>
    <h1>Obiecte de vânzare</h1>

    <div class="filter-bar">
        <input type="text" id="titlu" placeholder="Titlu obiect">
        <input type="number" id="valoare_min" placeholder="Valoare minimă">
        <input type="number" id="valoare_max" placeholder="Valoare maximă">
        <input type="number" id="an" placeholder="An">
        <input type="text" id="tara" placeholder="Țara">
        <input type="text" id="material" placeholder="Material">
        <select id="eticheta">
            <option value="">Etichetă</option>
            <option value="1">Da</option>
            <option value="0">Nu</option>
        </select>
        <button onclick="filtreaza()">Aplică filtre</button>
        <button onclick="stergeFiltre()">Șterge filtre</button>
    </div>

    <div id="lista-obiecte" class="obiecte-grid"></div>
</main>

<div id="oferta-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <h2>Trimite ofertă</h2>
        <form id="oferta-form">
            <input type="hidden" id="obiect_id">
            <label>Preț oferit (lei):</label>
            <input type="number" id="pret_ofertat" required>
            <label>Date contract:</label>
            <textarea id="contract_info" required></textarea>
            <label>Adresă livrare:</label>
            <textarea id="adresa_livrare" required></textarea>
            <div class="modal-buttons">
                <button type="submit">Trimite</button>
                <button type="button" onclick="closeModal()">Renunță</button>
            </div>
        </form>
    </div>
</div>

<div id="detalii-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <h2 id="modal-titlu"></h2>
        <div class="modal-body">
            <img id="modal-imagine" src="" alt="Imagine" style="max-width: 150px;">
            <p><strong>Categorie:</strong> <span id="modal-categorie"></span></p>
            <p><strong>Material:</strong> <span id="modal-material"></span></p>
            <p><strong>Valoare:</strong> <span id="modal-valoare"></span> lei</p>
            <p><strong>Țara:</strong> <span id="modal-tara"></span></p>
            <p><strong>Perioadă:</strong> <span id="modal-perioada"></span></p>
            <p><strong>Istoric:</strong> <span id="modal-istoric"></span></p>
            <p><strong>Etichetă:</strong> <span id="modal-eticheta"></span></p>
            <p><strong>Descriere:</strong> <span id="modal-descriere"></span></p>
            <p><strong>An:</strong> <span id="modal-an"></span></p>
        </div>
        <div class="modal-buttons">
            <button onclick="closeDetaliiModal()">Închide</button>
        </div>
    </div>
</div>

<script>
function filtreaza() {
    const params = new URLSearchParams();
    params.append("titlu", document.getElementById("titlu").value);
    params.append("valoare_min", document.getElementById("valoare_min").value);
    params.append("valoare_max", document.getElementById("valoare_max").value);
    params.append("an", document.getElementById("an").value);
    params.append("tara", document.getElementById("tara").value);
    params.append("material", document.getElementById("material").value);
    params.append("eticheta", document.getElementById("eticheta").value);
    params.append("username", "<?= urlencode($username) ?>");

    fetch("backend/api/lista_obiecte_vanzare.php?" + params.toString())
        .then(r => r.json())
        .then(data => {
            const lista = document.getElementById("lista-obiecte");
            lista.innerHTML = "";
            if (data.length === 0) {
                lista.innerHTML = "<p>Nu s-au găsit obiecte de vânzare.</p>";
                return;
            }

            data.forEach(obj => {
                const div = document.createElement("div");
                div.className = "obiect-card";
                div.innerHTML = `
                    <img src="${obj.imagine}" alt="" onclick='deschideDetalii(${JSON.stringify(obj)})'>
                    <h3>${obj.titlu}</h3>
                    <p>Preț: ${obj.pret} lei</p>
                    <p>Țara: ${obj.tara || '-'}</p>
                    <p>An: ${obj.an || '-'}</p>
                    <button onclick="event.stopPropagation(); deschideOferta(${obj.id})">Fă o ofertă</button>
                `;
                lista.appendChild(div);
            });
        });
}

function stergeFiltre() {
    document.getElementById("titlu").value = '';
    document.getElementById("valoare_min").value = '';
    document.getElementById("valoare_max").value = '';
    document.getElementById("an").value = '';
    document.getElementById("tara").value = '';
    document.getElementById("material").value = '';
    document.getElementById("eticheta").value = '';
    filtreaza();
}

function deschideOferta(id) {
    document.getElementById("obiect_id").value = id;
    document.getElementById("oferta-modal").style.display = "block";
}

function closeModal() {
    document.getElementById("oferta-modal").style.display = "none";
}

function deschideDetalii(obj) {
    document.getElementById("modal-titlu").textContent = obj.titlu;
    document.getElementById("modal-imagine").src = obj.imagine;
    document.getElementById("modal-categorie").textContent = obj.categorie || '-';
    document.getElementById("modal-material").textContent = obj.material || '-';
    document.getElementById("modal-valoare").textContent = obj.valoare || '-';
    document.getElementById("modal-tara").textContent = obj.tara || '-';
    document.getElementById("modal-perioada").textContent = obj.perioada || '-';
    document.getElementById("modal-istoric").textContent = obj.istoric || '-';
    document.getElementById("modal-eticheta").textContent = obj.eticheta == 1 ? "Da" : "Nu";
    document.getElementById("modal-descriere").textContent = obj.descriere || '-';
    document.getElementById("modal-an").textContent = obj.an || '-';
    document.getElementById("detalii-modal").style.display = "flex";
}

function closeDetaliiModal() {
    document.getElementById("detalii-modal").style.display = "none";
}

document.getElementById("oferta-form").addEventListener("submit", function(e) {
    e.preventDefault();
    const formData = new URLSearchParams();
    formData.append("id_obiect", document.getElementById("obiect_id").value);
    formData.append("pret", document.getElementById("pret_ofertat").value);
    formData.append("contract", document.getElementById("contract_info").value);
    formData.append("adresa", document.getElementById("adresa_livrare").value);

    fetch("backend/api/trimite_oferta.php", {
        method: "POST",
        headers: {"Content-Type": "application/x-www-form-urlencoded"},
        body: formData.toString()
    }).then(r => r.text())
    .then(resp => {
        alert(resp);
        closeModal();
        filtreaza();
    });
});

document.addEventListener("DOMContentLoaded", filtreaza);
</script>
</body>
</html>
