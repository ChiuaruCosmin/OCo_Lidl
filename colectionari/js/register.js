document.getElementById('registerForm')?.addEventListener('submit', async function (e) {
  e.preventDefault();

  const username = document.getElementById('username').value.trim();
  const password = document.getElementById('password').value.trim();
  const email = document.getElementById('email').value.trim();

  const response = await fetch('backend/api/register.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ username, password, email })
  });

  const data = await response.json();

  if (data.success) {
    alert("Cont creat cu succes!");
    window.location.href = "login.html";
  } else {
    alert("Eroare: " + data.message);
  }
});
