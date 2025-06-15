    document.getElementById('addForm')?.addEventListener('submit', async function (e) {
    e.preventDefault();

    const titlu = document.getElementById('titlu').value.trim();
    const categorie = document.getElementById('categorie').value.trim();
    const descriere = document.getElementById('descriere').value.trim();
    const an = document.getElementById('an').value;

    const response = await fetch('backend/api/adauga.php', {
        method: 'POST',
        headers: {
        'Content-Type': 'application/json'
        },
        body: JSON.stringify({ titlu, categorie, descriere, an })
    });

    const data = await response.json();

    if (data.success) {
        alert('Obiect adăugat cu succes!');
        document.getElementById('addForm').reset();
    } else {
        alert('Eroare: ' + data.message);
    }
    });
