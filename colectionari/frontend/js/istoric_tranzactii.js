document.addEventListener('DOMContentLoaded', () => {
    const grid = document.getElementById('tranzactii-grid');
    const token = localStorage.getItem('jwt');
    if (!token) {
        grid.innerHTML = '<p>Trebuie să fii logat pentru a vedea tranzacțiile.</p>';
        return;
    }

    fetch('/colectionari/backend/api/tranzactii_user.php', {
        headers: {
            'Authorization': 'Bearer ' + token
        }
    })
    .then(r => r.json())
    .then(data => {
        grid.innerHTML = '';
        if (!data.success || !data.tranzactii || data.tranzactii.length === 0) {
            grid.innerHTML = '<p>Nu există tranzacții înregistrate.</p>';
            return;
        }
        data.tranzactii.forEach(tr => {
            const card = document.createElement('div');
            card.className = 'vanzare-card';
            card.innerHTML = `
                <img src="${tr.imagine}" alt="Imagine ${tr.tip_tranzactie === 'colectie' ? 'colecție' : 'obiect'}">
                <div class="info">
                    <h3>${tr.titlu}</h3>
                    <p><strong>Tip: ${tr.tip_tranzactie === 'colectie' ? 'Colecție' : 'Obiect'}</strong></p>
                    <p>Preț: ${tr.pret} lei</p>
                    <p>Ofertant: ${tr.ofertant}</p>
                    <p>Proprietar: ${tr.proprietar}</p>
                    <p>Adresă livrare: ${tr.adresa}</p>
                    <p>Data tranzacției: ${tr.data}</p>
                    <p>Status: ${
                        tr.status === 'acceptata'
                        ? '<span style="color: green; font-weight: bold;">Acceptată</span>'
                        : '<span style="color: red; font-weight: bold;">Refuzată</span>'
                    }</p>
                </div>
            `;
            grid.appendChild(card);
        });
    })
    .catch(() => {
        grid.innerHTML = '<p>Eroare la conectare cu serverul.</p>';
    });
}); 