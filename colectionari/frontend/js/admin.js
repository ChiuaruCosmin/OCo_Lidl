document.addEventListener('DOMContentLoaded', () => {
  const logoutBtn = document.getElementById('logoutBtn');
  if (logoutBtn) {
    logoutBtn.onclick = () => {
      localStorage.removeItem('jwt');
      window.location.href = 'landing.html';
    };
  }

  function fetchWithJWT(url) {
    const jwt = localStorage.getItem('jwt');
    return fetch(url, {
      headers: { 'Authorization': 'Bearer ' + jwt }
    }).then(r => r.json());
  }

  function cautaColectii() {
    const q = document.getElementById('searchCollectionInput').value.trim();
    let url = '../backend/api/filtre_colectii_admin.php';
    if (q) url += '?titlu=' + encodeURIComponent(q);
    fetchWithJWT(url).then(data => {
      const list = document.getElementById('adminCollectionsList');
      list.innerHTML = '';
      if (data.success && data.colectii.length) {
        data.colectii.forEach(col => {
          const div = document.createElement('div');
          div.className = 'admin-collection-card';
          div.innerHTML = `<strong>${col.titlu}</strong> <span>(${col.user})</span><br><img src="${col.imagine || 'assets/avatars/defuser.png'}" alt="img" width="60" height="60" style="object-fit:cover;">`;
          const btnDel = document.createElement('button');
          btnDel.textContent = 'Șterge';
          btnDel.className = 'admin-btn-delete';
          btnDel.onclick = () => {
            if (confirm('Sigur vrei să ștergi această colecție?')) stergeColectie(col.id);
          };
          div.appendChild(document.createElement('br'));
          div.appendChild(btnDel);
          list.appendChild(div);
        });
      } else {
        list.innerHTML = '<em>Nicio colecție găsită.</em>';
      }
    });
  }

  function cautaUtilizatori() {
    const q = document.getElementById('searchUserInput').value.trim();
    let url = '../backend/api/filtre_utilizatori_admin.php';
    if (q) url += '?username=' + encodeURIComponent(q);
    fetchWithJWT(url).then(data => {
      const list = document.getElementById('adminUsersList');
      list.innerHTML = '';
      if (data.success && data.users.length) {
        data.users.forEach(u => {
          const div = document.createElement('div');
          div.className = 'admin-user-card';
          div.innerHTML = `<strong>${u.username}</strong> <span>${u.email}</span> <span>${u.admin ? '👑' : ''}</span><br><img src="${u.image_url || 'assets/avatars/defuser.png'}" alt="img" width="60" height="60" style="object-fit:cover;">`;
          const btnDel = document.createElement('button');
          btnDel.textContent = 'Șterge';
          btnDel.className = 'admin-btn-delete';
          btnDel.onclick = () => {
            if (confirm('Sigur vrei să ștergi acest utilizator?')) stergeUser(u.username);
          };
          div.appendChild(document.createElement('br'));
          div.appendChild(btnDel);
          const btnAdmin = document.createElement('button');
          if (u.admin) {
            btnAdmin.textContent = 'Scoate de la admin';
            btnAdmin.className = 'admin-btn-admin-remove';
            btnAdmin.onclick = () => actualizeazaAdmin(u.username, 'scoate_admin');
          } else {
            btnAdmin.textContent = 'Fă admin';
            btnAdmin.className = 'admin-btn-admin-add';
            btnAdmin.onclick = () => actualizeazaAdmin(u.username, 'fa_admin');
          }
          div.appendChild(btnAdmin);
          list.appendChild(div);
        });
      } else {
        list.innerHTML = '<em>Niciun utilizator găsit.</em>';
      }
    });
  }

  function incarcaProbleme() {
    fetchWithJWT('../backend/api/lista_probleme.php').then(data => {
      const list = document.getElementById('adminProblemsList');
      const hist = document.getElementById('adminProblemsHistory');
      list.innerHTML = '';
      hist.innerHTML = '';
      if (data.success && data.probleme.length) {
        data.probleme.forEach(p => {
          const div = document.createElement('div');
          div.className = 'admin-problem-card';
          div.innerHTML = `<strong>${p.user}</strong>: ${p.mesaj}<br><small>${p.data}</small> <span>Status: ${p.status}</span>`;
          if (p.status === 'noua' || p.status === 'nouă') {
            const btnRez = document.createElement('button');
            btnRez.textContent = 'Rezolvat';
            btnRez.onclick = () => actualizeazaProblema(p.id, 'rezolvat');
            btnRez.className = 'admin-btn-rezolvat';
            const btnIgn = document.createElement('button');
            btnIgn.textContent = 'Ignorat';
            btnIgn.onclick = () => actualizeazaProblema(p.id, 'ignorat');
            btnIgn.className = 'admin-btn-ignorat';
            div.appendChild(document.createElement('br'));
            div.appendChild(btnRez);
            div.appendChild(btnIgn);
            list.appendChild(div);
          } else hist.appendChild(div);
        });
      } else {
        list.innerHTML = '<em>Nicio problemă nouă.</em>';
        hist.innerHTML = '<em>Niciun istoric.</em>';
      }
    });
  }

  function incarcaActiuniAdmin() {
    fetchWithJWT('../backend/api/istoric_actiuni_admin.php').then(data => {
      const list = document.getElementById('adminActionsHistory');
      list.innerHTML = '';
      if (data.success && data.actiuni.length) {
        data.actiuni.forEach(a => {
          const div = document.createElement('div');
          div.className = 'admin-action-card';
          div.innerHTML = `<strong>${a.admin_user}</strong>: ${a.actiune}<br><small>${a.data}</small>`;
          list.appendChild(div);
        });
      } else {
        list.innerHTML = '<em>Nicio acțiune înregistrată.</em>';
      }
    });
  }

  document.getElementById('searchCollectionInput').addEventListener('input', cautaColectii);
  document.getElementById('searchUserInput').addEventListener('input', cautaUtilizatori);

  cautaColectii();
  cautaUtilizatori();
  incarcaProbleme();
  incarcaActiuniAdmin();

  function actualizeazaProblema(id, status) {
    const jwt = localStorage.getItem('jwt');
    fetch('../backend/api/actualizeaza_problema.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer ' + jwt
      },
      body: JSON.stringify({ id, status })
    })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          incarcaProbleme();
          incarcaActiuniAdmin();
        } else alert('Eroare: ' + (data.message || '')); 
      });
  }

  function stergeColectie(id) {
    const jwt = localStorage.getItem('jwt');
    fetch('../backend/api/sterge_colectie_admin.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer ' + jwt
      },
      body: JSON.stringify({ id })
    })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          cautaColectii();
          incarcaActiuniAdmin();
        } else alert('Eroare: ' + (data.message || ''));
      });
  }

  function stergeUser(username) {
    const jwt = localStorage.getItem('jwt');
    fetch('../backend/api/sterge_user_admin.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer ' + jwt
      },
      body: JSON.stringify({ username })
    })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          cautaUtilizatori();
          incarcaActiuniAdmin();
        } else alert('Eroare: ' + (data.message || ''));
      });
  }

  function actualizeazaAdmin(username, actiune) {
    const jwt = localStorage.getItem('jwt');
    fetch('../backend/api/actualizeaza_admin.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer ' + jwt
      },
      body: JSON.stringify({ username, actiune })
    })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          cautaUtilizatori();
          incarcaActiuniAdmin();
        } else alert('Eroare: ' + (data.message || ''));
      });
  }
}); 