function getToken() {
    return localStorage.getItem('jwt');
}


let openedFromCollection = false;

function filtreazaObiecte() {
    const token = getToken();
    if (!token) {
        alert('Trebuie să fii logat!');
        return;
    }
    const params = new URLSearchParams();
    params.append('titlu', document.getElementById('titlu').value);
    params.append('valoare_min', document.getElementById('valoare_min').value);
    params.append('valoare_max', document.getElementById('valoare_max').value);
    params.append('an', document.getElementById('an').value);
    params.append('tara', document.getElementById('tara').value);
    params.append('material', document.getElementById('material').value);
    params.append('eticheta', document.getElementById('eticheta').value);

    fetch('/colectionari/backend/api/lista_obiecte_vanzare.php?' + params.toString(), {
        headers: {
            'Authorization': 'Bearer ' + token
        }
    })
    .then(r => r.json())
    .then(data => {
        const lista = document.getElementById('lista-obiecte');
        lista.innerHTML = '';
        if (!data.success || !data.obiecte || data.obiecte.length === 0) {
            lista.innerHTML = '<p>Nu s-au găsit obiecte de vânzare.</p>';
            return;
        }
        data.obiecte.forEach(obj => {
            const div = document.createElement('div');
            div.className = 'obiect-card';
            div.innerHTML = `
                <img src="${obj.imagine}" alt="" onclick='deschideDetalii(${JSON.stringify(obj)})'>
                <h3>${obj.titlu}</h3>
                <p>Preț: ${obj.pret} lei</p>
                <p>Țara: ${obj.tara || '-'} </p>
                <p>An: ${obj.an || '-'}</p>
                <button onclick="event.stopPropagation(); deschideOferta(${obj.id})">Fă o ofertă</button>
            `;
            div.onclick = () => deschideDetalii(obj);
            lista.appendChild(div);
        });
    })
    .catch(() => {
        document.getElementById('lista-obiecte').innerHTML = '<p>Eroare la conectare cu serverul.</p>';
    });
}

function stergeFiltreObiecte() {
    document.getElementById('titlu').value = '';
    document.getElementById('valoare_min').value = '';
    document.getElementById('valoare_max').value = '';
    document.getElementById('an').value = '';
    document.getElementById('tara').value = '';
    document.getElementById('material').value = '';
    document.getElementById('eticheta').value = '';
    filtreazaObiecte();
}

function filtreazaColectii() {
    const token = getToken();
    if (!token) {
        alert('Trebuie să fii logat!');
        return;
    }
    const params = new URLSearchParams();
    params.append('titlu', document.getElementById('titlu_colectie').value);
    params.append('pret_min', document.getElementById('pret_min_colectie').value);
    params.append('pret_max', document.getElementById('pret_max_colectie').value);

    fetch('/colectionari/backend/api/lista_colectii_vanzare.php?' + params.toString(), {
        headers: {
            'Authorization': 'Bearer ' + token
        }
    })
    .then(r => r.json())
    .then(data => {
        const lista = document.getElementById('lista-colectii');
        lista.innerHTML = '';
        if (!data.success || !data.colectii || data.colectii.length === 0) {
            lista.innerHTML = '<p>Nu s-au găsit colecții de vânzare.</p>';
            return;
        }
        data.colectii.forEach(col => {
            const div = document.createElement('div');
            div.className = 'colectie-card';
            div.innerHTML = `
                <img src="${col.imagine}" alt="" onclick='deschideDetaliiColectie(${JSON.stringify(col)})'>
                <h3>${col.titlu}</h3>
                <p>Proprietar: ${col.user}</p>
                <p>Obiecte: ${col.nr_obiecte}</p>
                <p>Valoare totală: ${col.valoare_totala || 0} lei</p>
                <p>Preț: ${col.pret || 'Necunoscut'} lei</p>
                <button onclick="event.stopPropagation(); deschideOfertaColectie(${col.id})">Fă o ofertă</button>
            `;
            div.onclick = () => deschideDetaliiColectie(col);
            lista.appendChild(div);
        });
    })
    .catch(() => {
        document.getElementById('lista-colectii').innerHTML = '<p>Eroare la conectare cu serverul.</p>';
    });
}

function stergeFiltreColectii() {
    document.getElementById('titlu_colectie').value = '';
    document.getElementById('pret_min_colectie').value = '';
    document.getElementById('pret_max_colectie').value = '';
    filtreazaColectii();
}

function deschideOferta(id) {
    document.getElementById('obiect_id').value = id;
    document.getElementById('oferta-modal').style.display = 'block';
}

function closeModal() {
    document.getElementById('oferta-modal').style.display = 'none';
}

function deschideOfertaColectie(id) {
    document.getElementById('colectie_id').value = id;
    document.getElementById('oferta-colectie-modal').style.display = 'block';
}

function closeColectieModal() {
    document.getElementById('oferta-colectie-modal').style.display = 'none';
}

function deschideDetalii(obj) {
    openedFromCollection = false;
    const modal = document.getElementById('detalii-modal');
    const title = document.getElementById('modal-titlu');
    const content = document.getElementById('obiect-content');
    title.textContent = obj.titlu;
    content.innerHTML = `<div style='text-align:left'>
        <img src="${obj.imagine}" style="max-width:100px;display:block;margin:0 auto 12px auto;border-radius:10px;"><br>
        <strong>Categorie:</strong> ${obj.categorie || '-'}<br>
        <strong>Material:</strong> ${obj.material || '-'}<br>
        <strong>Valoare:</strong> ${obj.valoare || '-'} lei<br>
        <strong>Țara:</strong> ${obj.tara || '-'}<br>
        <strong>Perioadă:</strong> ${obj.perioada || '-'}<br>
        <strong>Istoric:</strong> ${obj.istoric || '-'}<br>
        <strong>Etichetă:</strong> ${obj.eticheta == 1 ? 'Da' : 'Nu'}<br>
        <strong>Descriere:</strong> ${obj.descriere || '-'}<br>
        <strong>An:</strong> ${obj.an || '-'}
      </div>`;
    modal.classList.add('show-modal');
    modal.style.display = 'flex';
}

function closeDetaliiModal() {
    const modal = document.getElementById('detalii-modal');
    modal.classList.remove('show-modal');
    modal.style.display = 'none';
    if (openedFromCollection) {
        document.getElementById('detalii-colectie-modal').style.display = 'flex';
    }
}

function deschideDetaliiColectie(col) {
    const modal = document.getElementById('detalii-colectie-modal');
    const title = document.getElementById('modal-titlu-colectie');
    const content = document.getElementById('modal-content');
    
    title.textContent = col.titlu;
    content.innerHTML = '';
    const headerDiv = document.createElement('div');
    headerDiv.style.textAlign = 'center';
    headerDiv.style.marginBottom = '18px';
    headerDiv.innerHTML = `
        <img src="${col.imagine}" alt="${col.titlu}" style="max-width:100px;border-radius:10px;margin-bottom:10px;">
        <p><strong>Proprietar:</strong> ${col.user}</p>
        <p><strong>Preț de vânzare:</strong> ${col.pret || 'Necunoscut'} lei</p>
    `;
    content.appendChild(headerDiv);
    const listaDiv = document.createElement('div');
    listaDiv.id = 'obiecte-colectie-lista';
    listaDiv.innerHTML = '<p>Se încarcă obiectele...</p>';
    content.appendChild(listaDiv);
    const token = getToken();
    fetch(`/colectionari/backend/api/obiecte_colectie.php?id=${col.id}&public=1`, {
        headers: {
            'Authorization': 'Bearer ' + token
        }
    })
    .then(r => r.json())
    .then(objs => {
        if (!Array.isArray(objs) || objs.length === 0) {
            listaDiv.innerHTML = '<p>Colecția nu conține obiecte.</p>';
            return;
        }
        listaDiv.innerHTML = '';
        objs.forEach(obj => {
            const div = document.createElement('div');
            div.className = 'modal-object';
            div.onclick = (e) => { e.stopPropagation(); deschideDetaliiObiect(obj); };
            div.innerHTML = `
                <img src="${obj.imagine}" alt="">
                <div class="modal-object-content">
                  <h4>${obj.titlu}</h4>
                  <em>${obj.categorie || ''}</em>
                  <small>An: ${obj.an || '-'}</small>
                </div>
            `;
            listaDiv.appendChild(div);
        });
    })
    .catch(() => {
        listaDiv.innerHTML = '<p style="color:red">Eroare la încărcarea obiectelor.</p>';
    });
    modal.style.display = 'flex';
}

function closeDetaliiColectieModal() {
    document.getElementById('detalii-colectie-modal').style.display = 'none';
}

function deschideDetaliiObiect(obj) {
    openedFromCollection = true;
    document.getElementById('detalii-colectie-modal').style.display = 'none';
    const modal = document.getElementById('detalii-modal');
    const title = document.getElementById('modal-titlu');
    const content = document.getElementById('obiect-content');
    title.textContent = obj.titlu;
    content.innerHTML = `<div style='text-align:left'>
        <img src="${obj.imagine}" style="max-width:100px;display:block;margin:0 auto 12px auto;border-radius:10px;"><br>
        <strong>Categorie:</strong> ${obj.categorie || '-'}<br>
        <strong>Material:</strong> ${obj.material || '-'}<br>
        <strong>Valoare:</strong> ${obj.valoare || '-'} lei<br>
        <strong>Țara:</strong> ${obj.tara || '-'}<br>
        <strong>Perioadă:</strong> ${obj.perioada || '-'}<br>
        <strong>Istoric:</strong> ${obj.istoric || '-'}<br>
        <strong>Etichetă:</strong> ${obj.eticheta == 1 ? 'Da' : 'Nu'}<br>
        <strong>Descriere:</strong> ${obj.descriere || '-'}<br>
        <strong>An:</strong> ${obj.an || '-'}
      </div>`;
    modal.classList.add('show-modal');
    modal.style.display = 'flex';
}

document.getElementById('oferta-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const token = getToken();
    if (!token) {
        alert('Trebuie să fii logat!');
        return;
    }
    const formData = new FormData();
    formData.append('id_obiect', document.getElementById('obiect_id').value);
    formData.append('pret', document.getElementById('pret_ofertat').value);
    formData.append('contract', document.getElementById('contract_info').value);
    formData.append('adresa', document.getElementById('adresa_livrare').value);

    fetch('/colectionari/backend/api/trimite_oferta.php', {
        method: 'POST',
        headers: {
            'Authorization': 'Bearer ' + token
        },
        body: formData
    })
    .then(r => r.json())
    .then(resp => {
        alert(resp.message || (resp.success ? 'Oferta trimisă cu succes!' : 'Eroare la trimitere ofertă.'));
        closeModal();
        filtreazaObiecte();
    })
    .catch(() => {
        alert('Eroare la conectare cu serverul.');
    });
});

document.getElementById('oferta-colectie-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const token = getToken();
    if (!token) {
        alert('Trebuie să fii logat!');
        return;
    }
    const formData = new FormData();
    formData.append('id_colectie', document.getElementById('colectie_id').value);
    formData.append('pret', document.getElementById('pret_ofertat_colectie').value);
    formData.append('contract', document.getElementById('contract_info_colectie').value);
    formData.append('adresa', document.getElementById('adresa_livrare_colectie').value);

    fetch('/colectionari/backend/api/trimite_oferta_colectie.php', {
        method: 'POST',
        headers: {
            'Authorization': 'Bearer ' + token
        },
        body: formData
    })
    .then(r => r.json())
    .then(resp => {
        alert(resp.message || (resp.success ? 'Oferta trimisă cu succes!' : 'Eroare la trimitere ofertă.'));
        closeColectieModal();
        filtreazaColectii();
    })
    .catch(() => {
        alert('Eroare la conectare cu serverul.');
    });
});

document.addEventListener('DOMContentLoaded', function() {
    filtreazaObiecte();
    filtreazaColectii();
}); 