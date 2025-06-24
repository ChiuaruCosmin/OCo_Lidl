
document.getElementById('loginForm')?.addEventListener('submit', async function (e) {
  e.preventDefault();

  const username = document.getElementById('username').value.trim();
  const password = document.getElementById('password').value.trim();

  const response = await fetch('backend/api/login.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ username, password })
  });

  const data = await response.json();

  if (data.success) {
    alert('Autentificare reușită!');
    window.location.href = 'backend/dashboard.php';
  } else {
    alert('Eroare: ' + data.message);
  }
}); 