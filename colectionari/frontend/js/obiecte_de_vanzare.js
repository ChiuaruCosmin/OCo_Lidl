document.addEventListener('DOMContentLoaded', () => {
    const obiecteGrid = document.getElementById('obiecte-vanzare-grid');
    const colectiiGrid = document.getElementById('colectii-vanzare-grid');
    const token = localStorage.getItem('jwt');

    fetch('/colectionari/backend/api/obiecte_de_vanzare.php', {
        headers: {
            'Authorization': 'Bearer ' + token
        }
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) {
            obiecteGrid.innerHTML = '<p>Eroare: ' + (data.message || 'Nu s-au putut încărca obiectele.') + '</p>';
            colectiiGrid.innerHTML = '<p>Eroare: ' + (data.message || 'Nu s-au putut încărca colecțiile.') + '</p>';
            return;
        }

        if (!data.obiecte || data.obiecte.length === 0) {
            obiecteGrid.innerHTML = '<p>Nu ai niciun obiect scos la vânzare momentan.</p>';
        } else {
            data.obiecte.forEach(obj => {
                const card = document.createElement('div');
                card.className = 'vandut-card';
                card.innerHTML = `
                    <img src="${obj.imagine}" alt="Imagine obiect">
                    <div class="info">
                        <h3>${obj.titlu}</h3>
                        <p>${obj.descriere || ''}</p>
                        <p><strong>Preț: ${obj.pret ? obj.pret + ' lei' : 'N/A'}</strong></p>
                        <p>Data adăugare: <span class="data-vanzare">${obj.data_adaugare || ''}</span></p>
                        <div class="oferte-box" id="oferte-obiect-${obj.id}"><em>Se încarcă ofertele...</em></div>
                        <button class="btn-scoate-vanzare" onclick="scoateDeLaVanzare(${obj.id}, this, 'obiect')">Scoate de la vânzare</button>
                    </div>
                `;
                obiecteGrid.appendChild(card);
                incarcaOfertePentruObiect(obj.id, token);
            });
        }

        if (!data.colectii || data.colectii.length === 0) {
            colectiiGrid.innerHTML = '<p>Nu ai nicio colecție scoasă la vânzare momentan.</p>';
        } else {
            data.colectii.forEach(col => {
                const card = document.createElement('div');
                card.className = 'vandut-card';
                card.innerHTML = `
                    <img src="${col.imagine}" alt="Imagine colecție">
                    <div class="info">
                        <h3>${col.titlu}</h3>
                        <p><strong>Tip: Colecție</strong></p>
                        <p><strong>Preț: ${col.pret ? col.pret + ' lei' : 'N/A'}</strong></p>
                        <p>Data adăugare: <span class="data-vanzare">${col.data_adaugare || ''}</span></p>
                        <div class="oferte-box" id="oferte-colectie-${col.id}"><em>Se încarcă ofertele...</em></div>
                        <button class="btn-scoate-vanzare" onclick="scoateDeLaVanzare(${col.id}, this, 'colectie')">Scoate de la vânzare</button>
                    </div>
                `;
                colectiiGrid.appendChild(card);
                incarcaOfertePentruColectie(col.id, token);
            });
        }
    })
    .catch(err => {
        obiecteGrid.innerHTML = '<p>Eroare la conectare cu serverul.</p>';
        colectiiGrid.innerHTML = '<p>Eroare la conectare cu serverul.</p>';
    });
});

function incarcaOfertePentruObiect(id_obiect, token) {
    const box = document.getElementById('oferte-obiect-' + id_obiect);
    fetch(`/colectionari/backend/api/oferte_obiect.php?id_obiect=${id_obiect}`, {
        headers: {
            'Authorization': 'Bearer ' + token
        }
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success || !data.oferte || data.oferte.length === 0) {
            box.innerHTML = '<em>Nu există oferte pentru acest obiect.</em>';
            return;
        }
        box.innerHTML = '<h4>Oferte primite:</h4>' + data.oferte.map(oferta => `
            <div class="oferta-item">
                <p><strong>${oferta.user}</strong> oferă ${oferta.pret} lei</p>
                <p>Status: <span style="color:${oferta.status === 'acceptata' ? 'green' : oferta.status === 'refuzata' ? 'red' : 'orange'};font-weight:bold;">${oferta.status}</span></p>
                ${oferta.status === 'in_asteptare' ? `
                <button onclick="proceseazaOferta(${oferta.id}, 'accepta', ${id_obiect}, 'obiect')">Acceptă</button>
                <button onclick="proceseazaOferta(${oferta.id}, 'refuza', ${id_obiect}, 'obiect')">Refuză</button>
                ` : ''}
            </div>
        `).join('');
    })
    .catch(() => {
        box.innerHTML = '<em>Eroare la încărcarea ofertelor.</em>';
    });
}

function incarcaOfertePentruColectie(id_colectie, token) {
    const box = document.getElementById('oferte-colectie-' + id_colectie);
    fetch(`/colectionari/backend/api/oferte_colectie.php?id_colectie=${id_colectie}`, {
        headers: {
            'Authorization': 'Bearer ' + token
        }
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success || !data.oferte || data.oferte.length === 0) {
            box.innerHTML = '<em>Nu există oferte pentru această colecție.</em>';
            return;
        }
        box.innerHTML = '<h4>Oferte primite:</h4>' + data.oferte.map(oferta => `
            <div class="oferta-item">
                <p><strong>${oferta.user}</strong> oferă ${oferta.pret} lei</p>
                <p>Status: <span style="color:${oferta.status === 'acceptata' ? 'green' : oferta.status === 'refuzata' ? 'red' : 'orange'};font-weight:bold;">${oferta.status}</span></p>
                ${oferta.status === 'in_asteptare' ? `
                <button onclick="proceseazaOferta(${oferta.id}, 'accepta', ${id_colectie}, 'colectie')">Acceptă</button>
                <button onclick="proceseazaOferta(${oferta.id}, 'refuza', ${id_colectie}, 'colectie')">Refuză</button>
                ` : ''}
            </div>
        `).join('');
    })
    .catch(() => {
        box.innerHTML = '<em>Eroare la încărcarea ofertelor.</em>';
    });
}

window.proceseazaOferta = function(id_oferta, actiune, id_item, tip) {
    const token = localStorage.getItem('jwt');
    if (!token) {
        alert('Trebuie să fii logat!');
        return;
    }
    if (!confirm(`Sigur vrei să ${actiune === 'accepta' ? 'accepți' : 'refuzi'} această ofertă?`)) return;
    
    const formData = new FormData();
    formData.append('id_oferta', id_oferta);
    formData.append('actiune', actiune);
    
    const apiUrl = tip === 'colectie' ? '/colectionari/backend/api/proceseaza_oferta_colectie.php' : '/colectionari/backend/api/proceseaza_oferta.php';
    
    fetch(apiUrl, {
        method: 'POST',
        headers: {
            'Authorization': 'Bearer ' + token
        },
        body: formData
    })
    .then(r => r.json())
    .then(resp => {
        alert(resp.message || (resp.success ? 'Ofertă procesată!' : 'Eroare la procesare ofertă.'));
        if (tip === 'colectie') {
            incarcaOfertePentruColectie(id_item, token);
        } else {
            incarcaOfertePentruObiect(id_item, token);
        }
    })
    .catch(() => {
        alert('Eroare la conectare cu serverul.');
    });
}

window.scoateDeLaVanzare = function(id_item, btn, tip) {
    const token = localStorage.getItem('jwt');
    if (!token) {
        alert('Trebuie să fii logat!');
        return;
    }
    if (!confirm('Sigur vrei să scoți acest ' + (tip === 'colectie' ? 'colecție' : 'obiect') + ' de la vânzare?')) return;
    
    if (tip === 'colectie') {
        const formData = new FormData();
        formData.append('colectie_id', id_item);
        formData.append('tip', '0');
        
        fetch('/colectionari/backend/api/schimba_tip_colectie.php', {
            method: 'POST',
            headers: {
                'Authorization': 'Bearer ' + token
            },
            body: formData
        })
        .then(r => r.json())
        .then(resp => {
            if (resp.success) {
                btn.closest('.vandut-card').remove();
            } else {
                alert(resp.message || 'Eroare la scoatere de la vânzare.');
            }
        })
        .catch(() => {
            alert('Eroare la conectare cu serverul.');
        });
    } else {
        const formData = new FormData();
        formData.append('id_obiect', id_item);
        fetch('/colectionari/backend/api/scoate_de_la_vanzare.php', {
            method: 'POST',
            headers: {
                'Authorization': 'Bearer ' + token
            },
            body: formData
        })
        .then(r => r.json())
        .then(resp => {
            if (resp.success) {
                btn.closest('.vandut-card').remove();
            } else {
                alert(resp.message || 'Eroare la scoatere de la vânzare.');
            }
        })
        .catch(() => {
            alert('Eroare la conectare cu serverul.');
        });
    }
}
