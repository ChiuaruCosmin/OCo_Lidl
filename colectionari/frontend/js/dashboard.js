document.addEventListener("DOMContentLoaded", () => {
  const token = localStorage.getItem("jwt");

  if (!token) {
    window.location.href = "landing.html";
    return;
  }

  function fetchWithAuth(url, options = {}) {
    const freshToken = localStorage.getItem('jwt');
    options.headers = options.headers || {};
    options.headers['Authorization'] = 'Bearer ' + freshToken;
    return fetch(url, options);
  }

  fetchWithAuth("/colectionari/backend/api/user-info.php")
    .then(res => {
      if (res.status === 401) throw new Error("Neautorizat");
      return res.json();
    })
    .then(data => {
      document.getElementById("username").textContent = data.username;
      document.getElementById("email").textContent = data.email;
      document.getElementById("avatar").src = data.avatar_url || "assets/avatar.png";
      document.getElementById("greeting").textContent = `Bun venit, ${data.username}!`;
      if (data.admin == 1) {
        let adminBtn = document.getElementById('adminPanelBtn');
        if (!adminBtn) {
          adminBtn = document.createElement('a');
          adminBtn.href = 'admin.html';
          adminBtn.className = 'btn';
          adminBtn.id = 'adminPanelBtn';
          adminBtn.textContent = 'Administrare';
          document.querySelector('.profile-links').appendChild(adminBtn);
        }
      } else {
        const reportBtn = document.getElementById('reportProblemBtn');
        if (reportBtn) reportBtn.style.display = '';
      }
    })
    .catch(err => {
      alert("Eroare autentificare. Vei fi redirecționat.");
      localStorage.removeItem("jwt");
      window.location.href = "landing.html";
    });

  fetchWithAuth("/colectionari/backend/api/popular-collections.php")
    .then(res => res.json())
    .then(data => {
      const container = document.getElementById("popularCollections");
      container.innerHTML = "";
      data.forEach(c => {
        const card = document.createElement("div");
        card.className = "collection-card";
        card.setAttribute("data-user", c.user);
        card.setAttribute("data-id", c.id);
        card.innerHTML = `
          <h4>${c.titlu}</h4>
          <img src="${c.imagine}" alt="${c.titlu}" />
          <p>${c.nr_obiecte} de obiecte</p>
        `;
        container.appendChild(card);
      });
    });

  document.getElementById("logoutBtn").addEventListener("click", () => {
    localStorage.removeItem("jwt");
    window.location.href = "landing.html";
  });

  function openPopularModal(id, titlu, imagine, user) {
    console.log('openPopularModal:', {id, titlu, imagine, user});
    const modal = document.getElementById('popular-collection-modal');
    if (!modal) {
      console.error('Nu există modalul cu id popular-collection-modal în HTML!');
      return;
    }
    document.getElementById('popular-modal-title').innerText = `Colecția: ${titlu}`;
    const content = document.getElementById('popular-modal-content');
    content.innerHTML = `<img src="${imagine}" alt="${titlu}" style="max-width:120px;display:block;margin:0 auto 20px auto;border-radius:10px;"> <p style='text-align:center;'><strong>Proprietar:</strong> ${user}</p>`;
    modal.style.display = 'flex';
    console.log('Am setat display:flex pe modal');

    fetch(`/colectionari/backend/api/obiecte_colectie.php?id=${id}&public=1`)
      .then(r => r.json())
      .then(objs => {
        if (!objs.length) {
          content.innerHTML += '<p>Colecția nu conține obiecte.</p>';
          return;
        }
        content.innerHTML += '<div class="popular-objects-list">';
        objs.forEach(obj => {
          content.innerHTML += `<div class='popular-object-item'><img src='${obj.imagine}' alt=''><div class='popular-object-info'><strong>${obj.titlu}</strong><em>${obj.categorie}</em></div></div>`;
        });
        content.innerHTML += '</div>';
      })
      .catch(() => {
        content.innerHTML += '<p style="color:red">Eroare la încărcarea obiectelor.</p>';
      });
  }
  function closePopularModal() {
    document.getElementById('popular-collection-modal').style.display = 'none';
  }
  window.closePopularModal = closePopularModal;
  setTimeout(() => {
    const container = document.getElementById('popularCollections');
    if (container) {
      container.addEventListener('click', function(e) {
        let card = e.target;
        while (card && !card.classList.contains('collection-card')) card = card.parentElement;
        if (card) {
          const id = card.getAttribute('data-id');
          const titlu = card.querySelector('h4').innerText;
          const imagine = card.querySelector('img').getAttribute('src');
          const user = card.getAttribute('data-user') || '';
          console.log('CLICK pe card:', {id, titlu, imagine, user});
          openPopularModal(id, titlu, imagine, user);
        }
      });
    }
  }, 500);

  const editProfileBtn = document.getElementById('editProfileBtn');
  const editModal = document.getElementById('editModal');
  const closeEditModal = document.getElementById('closeEditModal');
  const cancelEditBtn = document.getElementById('cancelEditBtn');
  if(editProfileBtn && editModal) {
    editProfileBtn.onclick = () => {
      const username = document.getElementById('username').textContent;
      const email = document.getElementById('email').textContent;
      document.getElementById('new_username').value = username;
      document.getElementById('new_email').value = email;
      document.getElementById('new_password').value = '';
      const avatarSrc = document.getElementById('avatar').src;
      const profilePicPreview = document.getElementById('profilePicPreview');
      if(profilePicPreview) {
        profilePicPreview.src = avatarSrc;
        profilePicPreview.style.display = 'block';
      }
      document.getElementById('profile_pic').value = '';
      editModal.style.display = 'flex';
    };
  }
  if(closeEditModal && editModal) {
    closeEditModal.onclick = () => { editModal.style.display = 'none'; };
  }
  if(cancelEditBtn && editModal) {
    cancelEditBtn.onclick = () => { editModal.style.display = 'none'; };
  }
  const editProfileForm = document.getElementById('editProfileForm');
  if(editProfileForm) {
    editProfileForm.onsubmit = async function(e) {
      e.preventDefault();
      const formData = new FormData();
      const username = document.getElementById('new_username').value.trim();
      const email = document.getElementById('new_email').value.trim();
      const password = document.getElementById('new_password').value;
      const fileInput = document.getElementById('profile_pic');
      if(username) formData.append('new_username', username);
      if(email) formData.append('new_email', email);
      if(password) formData.append('new_password', password);
      if(fileInput && fileInput.files && fileInput.files[0]) {
        formData.append('profile_pic', fileInput.files[0]);
      }
      const token = localStorage.getItem('jwt');
      try {
        const res = await fetch('/colectionari/backend/api/editeaza_profil.php', {
          method: 'POST',
          headers: { 'Authorization': 'Bearer ' + token },
          body: formData
        });
        const data = await res.json();
        if(data.success) {
          if(data.new_token) {
            localStorage.setItem('jwt', data.new_token);
            window.location.reload();
            return;
          }
          fetchWithAuth("/colectionari/backend/api/user-info.php")
            .then(res => res.json())
            .then(user => {
              document.getElementById("username").textContent = user.username;
              document.getElementById("email").textContent = user.email;
              document.getElementById("avatar").src = user.avatar_url || "assets/avatar.png";
              editModal.style.display = 'none';
              alert('Profil actualizat cu succes!');
            });
        } else {
          alert(data.error || 'Eroare la actualizare profil.');
        }
      } catch (err) {
        alert('Eroare de rețea sau server.');
      }
    };
  }

  if (editModal) {
    editModal.addEventListener('click', function(e) {
      if (e.target === editModal) {
        editModal.style.display = 'none';
        if (editProfileForm) editProfileForm.reset();
      }
    });
  }

  const searchInput = document.getElementById('searchInput');
  const searchModal = document.getElementById('dashboard-search-modal');
  const closeSearchBtn = document.getElementById('closeDashboardSearchModal');
  const filterForm = document.getElementById('dashboardFilterForm');
  const resultsDiv = document.getElementById('dashboardCollectionsResults');

  if (searchInput && searchModal) {
    searchInput.addEventListener('click', () => {
      searchModal.style.display = 'flex';
      setTimeout(() => {
        const firstInput = filterForm.querySelector('input[name="titlu"]');
        if (firstInput) firstInput.focus();
      }, 100);
    });
    searchInput.addEventListener('keydown', e => e.preventDefault());
  }
  if (closeSearchBtn && searchModal) {
    closeSearchBtn.onclick = () => {
      searchModal.style.display = 'none';
      filterForm.reset();
      resultsDiv.innerHTML = '';
    };
  }
  if (searchModal) {
    searchModal.addEventListener('click', function(e) {
      if (e.target === searchModal) {
        searchModal.style.display = 'none';
        filterForm.reset();
        resultsDiv.innerHTML = '';
      }
    });
  }

  if (filterForm && resultsDiv) {
    filterForm.onsubmit = async function(e) {
      e.preventDefault();
      resultsDiv.innerHTML = '<p>Se caută...</p>';
      const formData = new FormData(filterForm);
      const params = new URLSearchParams();
      for (const [key, value] of formData.entries()) {
        if (key === 'eticheta') {
          let v = value.trim().toLowerCase();
          if (v === 'da') v = '1';
          else if (v === 'nu') v = '0';
          else if (v === '') continue;
          params.append(key, v);
        } else {
          if (value.trim() !== '') params.append(key, value);
        }
      }
      try {
        const res = await fetchWithAuth('/colectionari/backend/api/filtre_colectii_dashboard.php', {
          method: 'POST',
          body: params
        });
        const data = await res.json();
        if (!data.length) {
          resultsDiv.innerHTML = '<p>Nu s-au găsit colecții cu aceste filtre.</p>';
          return;
        }
        resultsDiv.innerHTML = '';
        data.forEach(col => {
          const card = document.createElement('div');
          card.className = 'collection-card filtered';
          card.innerHTML = `
            <h4>${col.titlu}</h4>
            <img src="${col.imagine}" alt="${col.titlu}" />
            <p><strong>Proprietar:</strong> ${col.user}</p>
            <p>${col.nr_obiecte} obiecte</p>
            <button class="btn btn-details" data-id="${col.id}" data-titlu="${col.titlu}" data-img="${col.imagine}" data-user="${col.user}">Detalii</button>
          `;
          resultsDiv.appendChild(card);
        });
      } catch (err) {
        resultsDiv.innerHTML = '<p style="color:red">Eroare la filtrare.</p>';
      }
    };
    resultsDiv.addEventListener('click', function(e) {
      const btn = e.target.closest('.btn-details');
      if (btn) {
        const id = btn.getAttribute('data-id');
        const titlu = btn.getAttribute('data-titlu');
        const imagine = btn.getAttribute('data-img');
        const user = btn.getAttribute('data-user');
        openDashboardCollectionModal(id, titlu, imagine, user);
      }
    });
  }

  function openDashboardCollectionModal(id, titlu, imagine, user) {
    const modal = document.getElementById('dashboard-collection-modal');
    document.getElementById('dashboard-modal-title').innerText = `Colecția: ${titlu}`;
    const content = document.getElementById('dashboard-modal-content');
    content.innerHTML = `<img src="${imagine}" alt="${titlu}" style="max-width:120px;display:block;margin:0 auto 20px auto;border-radius:10px;"> <p style='text-align:center;'><strong>Proprietar:</strong> ${user}</p><div id='dashboard-objects-list'><p>Se încarcă obiectele...</p></div>`;
    modal.style.display = 'flex';
    fetchWithAuth(`/colectionari/backend/api/obiecte_colectie_dashboard.php?id=${id}`)
      .then(r => r.json())
      .then(objs => {
        const listDiv = document.getElementById('dashboard-objects-list');
        if (!objs.length) {
          listDiv.innerHTML = '<p>Colecția nu conține obiecte.</p>';
          return;
        }
        listDiv.innerHTML = '';
        objs.forEach(obj => {
          const objDiv = document.createElement('div');
          objDiv.className = 'dashboard-object-item';
          objDiv.innerHTML = `
            <div class='dashboard-object-img'><img src='${obj.imagine}' alt='${obj.titlu}' /></div>
            <div class='dashboard-object-info'>
              <strong>${obj.titlu}</strong>
              <em>${obj.categorie || ''}</em>
              <button class='btn btn-object-details' data-id='${obj.id}'>Detalii</button>
            </div>
          `;
          listDiv.appendChild(objDiv);
        });
      })
      .catch(() => {
        document.getElementById('dashboard-objects-list').innerHTML = '<p style="color:red">Eroare la încărcarea obiectelor.</p>';
      });
  }
  function closeDashboardCollectionModal() {
    document.getElementById('dashboard-collection-modal').style.display = 'none';
  }
  window.closeDashboardCollectionModal = closeDashboardCollectionModal;

  document.getElementById('dashboard-modal-content').addEventListener('click', function(e) {
    const btn = e.target.closest('.btn-object-details');
    if (btn) {
      const id = btn.getAttribute('data-id');
      openDashboardObjectModal(id);
    }
  });

  function openDashboardObjectModal(id) {
    const modal = document.getElementById('dashboard-object-modal');
    const title = document.getElementById('dashboard-object-modal-title');
    const content = document.getElementById('dashboard-object-modal-content');
    title.innerText = 'Detalii obiect';
    content.innerHTML = '<p>Se încarcă...</p>';
    modal.style.display = 'flex';
    fetchWithAuth(`/colectionari/backend/api/detalii_obiect.php?id=${id}`)
      .then(r => r.json())
      .then(obj => {
        if (!obj || !obj.id) {
          content.innerHTML = '<p>Obiectul nu a fost găsit.</p>';
          return;
        }
        content.innerHTML = `
          <img src='${obj.imagine}' alt='${obj.titlu}' style='max-width:120px;display:block;margin:0 auto 20px auto;border-radius:10px;'>
          <h3 style='text-align:center;'>${obj.titlu}</h3>
          <ul class='object-details-list'>
            <li><strong>Categorie:</strong> ${obj.categorie || ''}</li>
            <li><strong>Valoare:</strong> ${obj.valoare || ''}</li>
            <li><strong>An:</strong> ${obj.an || ''}</li>
            <li><strong>Țara:</strong> ${obj.tara || ''}</li>
            <li><strong>Perioada:</strong> ${obj.perioada || ''}</li>
            <li><strong>Etichetă:</strong> ${obj.eticheta || ''}</li>
            <li><strong>Material:</strong> ${obj.material || ''}</li>
            <li><strong>Descriere:</strong> ${obj.descriere || ''}</li>
          </ul>
        `;
      })
      .catch(() => {
        content.innerHTML = '<p style="color:red">Eroare la încărcarea detaliilor.</p>';
      });
  }
  function closeDashboardObjectModal() {
    document.getElementById('dashboard-object-modal').style.display = 'none';
  }
  window.closeDashboardObjectModal = closeDashboardObjectModal;

  const reportBtn = document.getElementById('reportProblemBtn');
  const reportModal = document.getElementById('report-problem-modal');
  const closeReportBtn = document.getElementById('closeReportProblemModal');
  const reportForm = document.getElementById('reportProblemForm');
  if (reportBtn && reportModal) {
    reportBtn.onclick = () => {
      reportModal.style.display = 'flex';
      setTimeout(() => {
        document.getElementById('problemText').focus();
      }, 100);
    };
  }
  if (closeReportBtn && reportModal) {
    closeReportBtn.onclick = () => {
      reportModal.style.display = 'none';
      reportForm.reset();
    };
  }
  if (reportModal) {
    reportModal.addEventListener('click', function(e) {
      if (e.target === reportModal) {
        reportModal.style.display = 'none';
        reportForm.reset();
      }
    });
  }
  if (reportForm) {
    reportForm.onsubmit = async function(e) {
      e.preventDefault();
      const text = document.getElementById('problemText').value.trim();
      if (!text) {
        alert('Te rugăm să completezi problema!');
        return;
      }
      const formData = new FormData();
      formData.append('problemText', text);
      try {
        const token = localStorage.getItem('jwt');
        const res = await fetch('/colectionari/backend/api/raporteaza_problema.php', {
          method: 'POST',
          headers: { 'Authorization': 'Bearer ' + token },
          body: formData
        });
        const data = await res.json();
        if (data.success) {
          alert('Problema a fost raportată cu succes!');
          reportModal.style.display = 'none';
          reportForm.reset();
        } else {
          alert(data.message || 'Eroare la raportare.');
        }
      } catch (err) {
        alert('Eroare de rețea sau server.');
      }
    };
  }

});
