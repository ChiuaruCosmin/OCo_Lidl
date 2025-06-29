document.addEventListener("DOMContentLoaded", () => {
  const token = localStorage.getItem("jwt");

  if (!token) {
    window.location.href = "landing.html";
    return;
  }

  function readyToFilter() {
    return document.getElementById("search") &&
      document.getElementById("valoare_min") &&
      document.getElementById("valoare_max") &&
      document.getElementById("an") &&
      document.getElementById("tara") &&
      document.getElementById("perioada") &&
      document.getElementById("material") &&
      document.getElementById("eticheta");
  }

  function initialFilter() {
    if (readyToFilter()) {
      document.getElementById("search").value = "";
      document.getElementById("valoare_min").value = "";
      document.getElementById("valoare_max").value = "";
      document.getElementById("an").value = "";
      document.getElementById("tara").value = "";
      document.getElementById("perioada").value = "";
      document.getElementById("material").value = "";
      document.getElementById("eticheta").value = "";
      filtreaza();
    } else {
      setTimeout(initialFilter, 100);
    }
  }
  initialFilter();

  function fetchWithAuth(url, options = {}) {
    options.headers = options.headers || {};
    options.headers['Authorization'] = 'Bearer ' + token;
    return fetch(url, options);
  }

  document.getElementById("logoutBtn").addEventListener("click", () => {
    localStorage.removeItem("jwt");
    window.location.href = "landing.html";
  });

  filtreaza();

  const pdfBtn = document.getElementById('pdf-export-btn');
  if (pdfBtn) {
    pdfBtn.onclick = function() {
      if (!currentId) return;
      const token = localStorage.getItem("jwt");
      fetch(`/colectionari/backend/api/export_pdf_colectie.php?id=${currentId}`, {
        headers: { 'Authorization': 'Bearer ' + token }
      })
        .then(r => {
          if (!r.ok) throw new Error('Eroare la generarea PDF');
          return r.blob();
        })
        .then(blob => {
          const url = window.URL.createObjectURL(blob);
          const a = document.createElement('a');
          a.href = url;
          a.download = `colectie_${currentId}.pdf`;
          document.body.appendChild(a);
          a.click();
          a.remove();
          setTimeout(() => window.URL.revokeObjectURL(url), 1000);
        })
        .catch(e => alert('Eroare la export PDF: ' + e.message));
    };
  }
});

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
    
    let statusBadgeText = '';
    let statusBadgeClass = '';
    let tipButtonText = '';
    let tipButtonClass = '';
    let vindeButtonText = '';
    
    if (c.tip === 0) {
      statusBadgeText = 'Publică';
      statusBadgeClass = 'status-0';
      tipButtonText = 'Fă privată';
      tipButtonClass = 'btn-private';
      vindeButtonText = 'Vinde colecția';
    } else if (c.tip === 1) {
      statusBadgeText = 'Privată';
      statusBadgeClass = 'status-1';
      tipButtonText = 'Fă publică';
      tipButtonClass = 'btn-public';
      vindeButtonText = 'Vinde colecția';
    } else if (c.tip === 3) {
      statusBadgeText = 'De vânzare';
      statusBadgeClass = 'status-3';
      tipButtonText = 'Nu mai vinde';
      tipButtonClass = 'btn-stop-selling';
      vindeButtonText = 'În vânzare';
    } else {
      statusBadgeText = 'Publică';
      statusBadgeClass = 'status-0';
      tipButtonText = 'Fă privată';
      tipButtonClass = 'btn-private';
      vindeButtonText = 'Vinde colecția';
    }
    
    card.innerHTML = `
      <img src="${c.imagine}" alt="${c.titlu}">
      <h3>${c.titlu}</h3>
      <p>${c.nr_obiecte} obiecte</p>
      <div class="collection-status">
        <span class="status-badge ${statusBadgeClass}">${statusBadgeText}</span>
      </div>
      <div class="card-actions">
        <button class="btn-edit" onclick="event.stopPropagation(); openEditCollectionModal(${c.id}, '${c.titlu.replace(/'/g, "\\'")}', '${c.imagine.replace(/'/g, "\\'")}')">Editează</button>
        <button class="${tipButtonClass}" onclick="event.stopPropagation(); schimbaTipColectie(${c.id}, ${c.tip || 0})">${tipButtonText}</button>
        <button class="btn-vinde" onclick="event.stopPropagation(); vindeColectie(${c.id}, ${c.tip || 0})">${vindeButtonText}</button>
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

  params.append("_t", Date.now());

  const token = localStorage.getItem("jwt");
  
  fetch('/colectionari/backend/api/filtre_colectii.php?' + params.toString(), {
    headers: {
      'Authorization': 'Bearer ' + token,
      'Cache-Control': 'no-cache'
    }
  })
    .then(r => {
      if (r.status === 401) {
        localStorage.removeItem("jwt");
        window.location.href = "landing.html";
        throw new Error("Neautorizat");
      }
      return r.json();
    })
    .then(data => genereazaCarduri(data))
    .catch(e => {
      console.error(e);
      document.getElementById('colectiiContainer').innerHTML = '<p class="no-collections">Eroare la filtrare.</p>';
    });
}

function stergeFiltre() {
  document.getElementById("search").value = "";
  document.getElementById("valoare_min").value = "";
  document.getElementById("valoare_max").value = "";
  document.getElementById("an").value = "";
  document.getElementById("tara").value = "";
  document.getElementById("perioada").value = "";
  document.getElementById("material").value = "";
  document.getElementById("eticheta").value = "";
  filtreaza();
}

function deleteCollection(btn, id) {
  if (!confirm("Sigur dorești să ștergi această colecție?")) return;
  
  const token = localStorage.getItem("jwt");
  
  fetch("/colectionari/backend/api/sterge_colectie.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
      "Authorization": "Bearer " + token
    },
    body: "id=" + id
  })
  .then(res => {
    if (res.status === 401) {
      localStorage.removeItem("jwt");
      window.location.href = "landing.html";
      throw new Error("Neautorizat");
    }
    return res.text();
  })
  .then(resp => {
    if (resp.trim() === "Succes") {
      btn.closest('.collection-card').remove();
      if (!document.querySelector('.collection-card')) {
        document.getElementById('colectiiContainer').innerHTML = '<p class="no-collections">Nu există nicio colecție.</p>';
      }
    } else {
      alert("Eroare: " + resp);
    }
  })
  .catch(e => {
    console.error(e);
    alert("Eroare la ștergerea colecției.");
  });
}

function openModal(id, titlu) {
  currentId = id;
  document.getElementById('modal-title').innerText = 'Colecția: ' + titlu;
  document.getElementById('modal-overlay').style.display = 'flex';

  setTimeout(() => {
    const pdfBtn = document.getElementById('pdf-export-btn');
    if (pdfBtn) {
      pdfBtn.onclick = function() {
        if (!currentId) return;
        const token = localStorage.getItem("jwt");
        fetch(`/colectionari/backend/api/export_pdf_colectie.php?id=${currentId}`, {
          headers: { 'Authorization': 'Bearer ' + token }
        })
          .then(r => {
            if (!r.ok) throw new Error('Eroare la generarea PDF');
            return r.blob();
          })
          .then(blob => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `colectie_${currentId}.pdf`;
            document.body.appendChild(a);
            a.click();
            a.remove();
            setTimeout(() => window.URL.revokeObjectURL(url), 1000);
          })
          .catch(e => alert('Eroare la export PDF: ' + e.message));
      };
    }
    const csvBtn = document.getElementById('csv-export-btn');
    if (csvBtn) {
      csvBtn.onclick = function() {
        if (!currentId) return;
        const token = localStorage.getItem("jwt");
        fetch(`/colectionari/backend/api/export_csv_colectie.php?id=${currentId}`, {
          headers: { 'Authorization': 'Bearer ' + token }
        })
          .then(r => {
            if (!r.ok) throw new Error('Eroare la generarea CSV');
            return r.blob();
          })
          .then(blob => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `colectie_${currentId}.csv`;
            document.body.appendChild(a);
            a.click();
            a.remove();
            setTimeout(() => window.URL.revokeObjectURL(url), 1000);
          })
          .catch(e => alert('Eroare la export CSV: ' + e.message));
      };
    }
  }, 0);

  const params = new URLSearchParams();
  params.append("id", id);
  params.append("valoare_min", document.getElementById("valoare_min").value);
  params.append("valoare_max", document.getElementById("valoare_max").value);
  params.append("an", document.getElementById("an").value);
  params.append("tara", document.getElementById("tara").value);
  params.append("perioada", document.getElementById("perioada").value);
  params.append("material", document.getElementById("material").value);
  params.append("eticheta", document.getElementById("eticheta").value);

  const token = localStorage.getItem("jwt");

  fetch('/colectionari/backend/api/obiecte_colectie.php?' + params.toString(), {
    headers: {
      'Authorization': 'Bearer ' + token
    }
  })
    .then(r => {
      if (r.status === 401) {
        localStorage.removeItem("jwt");
        window.location.href = "landing.html";
        throw new Error("Neautorizat");
      }
      return r.json();
    })
    .then(data => {
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
    })
    .catch(e => {
      console.error(e);
      document.getElementById('modal-content').innerHTML = '<p>Eroare la încărcare.</p>';
    });
}

function openObiectModal(id) {
  currentObiectId = id;
  const listaModal = document.getElementById('modal-overlay');
  if (listaModal && listaModal.style.display === 'flex') {
    listaModal.setAttribute('data-was-open', '1');
    listaModal.style.display = 'none';
  }
  document.getElementById('modal-obiect-overlay').style.display = 'flex';
  
  const token = localStorage.getItem("jwt");
  
  fetch('/colectionari/backend/api/detalii_obiect.php?id=' + id, {
    headers: {
      'Authorization': 'Bearer ' + token
    }
  })
    .then(r => {
      if (r.status === 401) {
        localStorage.removeItem("jwt");
        window.location.href = "landing.html";
        throw new Error("Neautorizat");
      }
      return r.json();
    })
    .then(obj => {
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
        openEditObiectModal(obj.id);
      };
    })
    .catch(e => {
      console.error(e);
      document.getElementById('obiect-content').innerHTML = '<p>Eroare la încărcare.</p>';
    });
}

function stergeObiect() {
  if (!confirm("Sigur dorești să ștergi acest obiect?")) return;
  
  const token = localStorage.getItem("jwt");
  
  fetch("/colectionari/backend/api/sterge_obiect.php", {
    method: "POST",
    headers: { 
      "Content-Type": "application/x-www-form-urlencoded",
      "Authorization": "Bearer " + token
    },
    body: "id=" + currentObiectId
  })
  .then(res => {
    if (res.status === 401) {
      localStorage.removeItem("jwt");
      window.location.href = "landing.html";
      throw new Error("Neautorizat");
    }
    return res.text();
  })
  .then(resp => {
    if (resp.trim() === "Succes") {
      closeObiectModal();
      alert("Obiect șters cu succes.");
      filtreaza();
    } else {
      alert("Eroare la ștergere: " + resp);
    }
  })
  .catch(e => {
    console.error(e);
    alert("Eroare la ștergerea obiectului.");
  });
}

function closeModal() {
  document.getElementById('modal-overlay').style.display = 'none';
}

function closeObiectModal() {
  document.getElementById('modal-obiect-overlay').style.display = 'none';
  const listaModal = document.getElementById('modal-overlay');
  if (listaModal && listaModal.getAttribute('data-was-open') === '1') {
    listaModal.style.display = 'flex';
    listaModal.removeAttribute('data-was-open');
  }
}

function vindeObiect(idObiect, btn) {
  const pret = prompt("Introdu prețul de vânzare (lei):");
  if (!pret || isNaN(pret) || pret <= 0) {
    alert("Preț invalid.");
    return;
  }

  const token = localStorage.getItem("jwt");

  fetch("/colectionari/backend/api/vinde_obiect_api.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
      "Authorization": "Bearer " + token
    },
    body: `id=${idObiect}&pret=${encodeURIComponent(pret)}`
  })
  .then(r => {
    if (r.status === 401) {
      localStorage.removeItem("jwt");
      window.location.href = "landing.html";
      throw new Error("Neautorizat");
    }
    return r.text();
  })
  .then(resp => {
    if (resp.trim() === "Succes") {
      btn.closest(".modal-object").remove();
      alert("Obiectul a fost marcat ca vândut.");
    } else {
      alert("Eroare la vânzare: " + resp);
    }
  })
  .catch(e => {
    console.error(e);
    alert("Eroare la comunicarea cu serverul.");
  });
}

function openAddCollectionModal() {
  document.getElementById('add-collection-modal').style.display = 'flex';
}

function closeAddCollectionModal() {
  document.getElementById('add-collection-modal').style.display = 'none';
  document.getElementById('addCollectionForm').reset();
  document.getElementById('addCollectionMsg').textContent = '';
}

if (document.getElementById('addCollectionForm')) {
  document.getElementById('addCollectionForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const msg = document.getElementById('addCollectionMsg');
    msg.textContent = '';
    const formData = new FormData(this);
    const token = localStorage.getItem('jwt');
    try {
      const res = await fetch('/colectionari/backend/api/adauga_colectie.php', {
        method: 'POST',
        headers: { 'Authorization': 'Bearer ' + token },
        body: formData
      });
      const data = await res.json();
      if (data.success) {
        msg.style.color = 'green';
        msg.textContent = 'Colecția a fost adăugată!';
        setTimeout(() => {
          closeAddCollectionModal();
          filtreaza();
        }, 800);
      } else {
        msg.style.color = 'red';
        msg.textContent = data.error || 'Eroare la adăugare.';
      }
    } catch (err) {
      msg.style.color = 'red';
      msg.textContent = 'Eroare de rețea sau server.';
    }
  });
}

function openEditCollectionModal(id, titlu, imagine) {
  document.getElementById('edit_id').value = id;
  document.getElementById('edit_titlu').value = titlu;
  document.getElementById('edit_imagine').value = "";
  document.getElementById('edit-collection-modal').style.display = 'flex';
  document.getElementById('editCollectionMsg').textContent = '';
}

function closeEditCollectionModal() {
  document.getElementById('edit-collection-modal').style.display = 'none';
  document.getElementById('editCollectionForm').reset();
  document.getElementById('editCollectionMsg').textContent = '';
}

window.openEditCollectionModal = openEditCollectionModal;

if (document.getElementById('editCollectionForm')) {
  document.getElementById('editCollectionForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const msg = document.getElementById('editCollectionMsg');
    msg.textContent = '';
    const formData = new FormData(this);
    const token = localStorage.getItem('jwt');
    try {
      const res = await fetch('/colectionari/backend/api/editeaza_colectie.php', {
        method: 'POST',
        headers: { 'Authorization': 'Bearer ' + token },
        body: formData
      });
      const data = await res.json();
      if (data.success) {
        msg.style.color = 'green';
        msg.textContent = 'Modificările au fost salvate!';
        setTimeout(() => {
          closeEditCollectionModal();
          filtreaza();
        }, 800);
      } else {
        msg.style.color = 'red';
        msg.textContent = data.error || 'Eroare la editare.';
      }
    } catch (err) {
      msg.style.color = 'red';
      msg.textContent = 'Eroare de rețea sau server.';
    }
  });
}

function openAddObiectModal(colectieId) {
  document.getElementById('add_obiect_colectie_id').value = colectieId;
  document.getElementById('addObiectForm').reset();
  document.getElementById('addObiectMsg').textContent = '';
  document.getElementById('add-obiect-modal').style.display = 'flex';
}

function closeAddObiectModal() {
  document.getElementById('add-obiect-modal').style.display = 'none';
  document.getElementById('addObiectForm').reset();
  document.getElementById('addObiectMsg').textContent = '';
}

window.openAddObiectModal = openAddObiectModal;
if (document.getElementById('addObiectForm')) {
  document.getElementById('addObiectForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const msg = document.getElementById('addObiectMsg');
    msg.textContent = '';
    const titlu = document.getElementById('add_obiect_titlu').value.trim();
    const categorie = document.getElementById('add_obiect_categorie').value.trim();
    if (!titlu || !categorie) {
      msg.style.color = 'red';
      msg.textContent = 'Titlul și categoria sunt obligatorii!';
      return;
    }
    const formData = new FormData(this);
    const token = localStorage.getItem('jwt');
    try {
      const res = await fetch('/colectionari/backend/api/adauga_obiect.php', {
        method: 'POST',
        headers: { 'Authorization': 'Bearer ' + token },
        body: formData
      });
      const data = await res.json();
      if (data.success) {
        msg.style.color = 'green';
        msg.textContent = 'Obiectul a fost adăugat!';
        setTimeout(() => {
          closeAddObiectModal();
          openModal(document.getElementById('add_obiect_colectie_id').value, '');
        }, 800);
      } else {
        msg.style.color = 'red';
        msg.textContent = data.error || 'Eroare la adăugare.';
      }
    } catch (err) {
      msg.style.color = 'red';
      msg.textContent = 'Eroare de rețea sau server.';
    }
  });
}

function openEditObiectModal(obiectId) {
  const token = localStorage.getItem('jwt');
  
  fetch(`/colectionari/backend/api/detalii_obiect.php?id=${obiectId}`, {
    headers: {
      'Authorization': 'Bearer ' + token
    }
  })
  .then(r => {
    if (r.status === 401) {
      localStorage.removeItem("jwt");
      window.location.href = "landing.html";
      throw new Error("Neautorizat");
    }
    return r.json();
  })
  .then(obiect => {
    document.getElementById('edit_obiect_id').value = obiect.id;
    document.getElementById('edit_obiect_titlu').value = obiect.titlu || '';
    document.getElementById('edit_obiect_categorie').value = obiect.categorie || '';
    document.getElementById('edit_obiect_material').value = obiect.material || '';
    document.getElementById('edit_obiect_valoare').value = obiect.valoare || '';
    document.getElementById('edit_obiect_tara').value = obiect.tara || '';
    document.getElementById('edit_obiect_perioada').value = obiect.perioada || '';
    document.getElementById('edit_obiect_eticheta').value = obiect.eticheta !== null ? obiect.eticheta : '';
    document.getElementById('edit_obiect_descriere').value = obiect.descriere || '';
    document.getElementById('edit_obiect_an').value = obiect.an || '';
    
    document.getElementById('edit-obiect-modal').style.display = 'flex';
    document.getElementById('editObiectMsg').textContent = '';
  })
  .catch(e => {
    console.error(e);
    alert('Eroare la încărcarea datelor obiectului.');
  });
}

function closeEditObiectModal() {
  document.getElementById('edit-obiect-modal').style.display = 'none';
  document.getElementById('editObiectForm').reset();
  document.getElementById('editObiectMsg').textContent = '';
}

if (document.getElementById('editObiectForm')) {
  document.getElementById('editObiectForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const msg = document.getElementById('editObiectMsg');
    msg.textContent = '';
    const titlu = document.getElementById('edit_obiect_titlu').value.trim();
    const categorie = document.getElementById('edit_obiect_categorie').value.trim();
    if (!titlu || !categorie) {
      msg.style.color = 'red';
      msg.textContent = 'Titlul și categoria sunt obligatorii!';
      return;
    }
    const formData = new FormData(this);
    const token = localStorage.getItem('jwt');
    try {
      const res = await fetch('/colectionari/backend/api/editeaza_obiect.php', {
        method: 'POST',
        headers: { 'Authorization': 'Bearer ' + token },
        body: formData
      });
      const data = await res.json();
      if (data.success) {
        msg.style.color = 'green';
        msg.textContent = 'Modificările au fost salvate!';
        setTimeout(() => {
          closeEditObiectModal();
          closeObiectModal();
          if (currentId) {
            openModal(currentId, '');
          }
        }, 800);
      } else {
        msg.style.color = 'red';
        msg.textContent = data.error || 'Eroare la editare.';
      }
    } catch (err) {
      msg.style.color = 'red';
      msg.textContent = 'Eroare de rețea sau server.';
    }
  });
}

window.openEditObiectModal = openEditObiectModal;

function schimbaTipColectie(colectieId, tipCurent) {
  const token = localStorage.getItem("jwt");
  let nouTip = tipCurent === 0 ? 1 : 0;
  const formData = new FormData();
  formData.append('colectie_id', colectieId);
  formData.append('tip', nouTip);
  fetch('/colectionari/backend/api/schimba_tip_colectie.php', {
    method: 'POST',
    headers: {
      'Authorization': 'Bearer ' + token
    },
    body: formData
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      alert(data.message);
      filtreaza();
    } else {
      alert('Eroare: ' + data.message);
    }
  })
  .catch(e => {
    console.error(e);
    alert('Eroare la schimbarea tipului colecției.');
  });
}

function vindeColectie(colectieId, tipCurent) {
  if (tipCurent === 3) {
    schimbaTipColectie(colectieId, 3);
    return;
  }
  
  const pret = prompt('Introduceți prețul de vânzare pentru colecție (lei):');
  if (!pret || isNaN(pret) || pret <= 0) {
    alert('Preț invalid!');
    return;
  }
  
  const token = localStorage.getItem("jwt");
  const formData = new FormData();
  formData.append('colectie_id', colectieId);
  formData.append('tip', 3);
  formData.append('pret', pret);
  
  fetch('/colectionari/backend/api/schimba_tip_colectie.php', {
    method: 'POST',
    headers: {
      'Authorization': 'Bearer ' + token
    },
    body: formData
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      alert('Colecția a fost pusă la vânzare cu prețul de ' + pret + ' lei!');
      filtreaza();
    } else {
      alert('Eroare: ' + data.message);
    }
  })
  .catch(e => {
    console.error(e);
    alert('Eroare la punerea colecției la vânzare.');
  });
}
