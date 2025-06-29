document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("loginForm");
  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    const username = document.getElementById("username").value;
    const password = document.getElementById("password").value;

    try {
      const res = await fetch("/colectionari/backend/api/login.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ username, password }),
      });

      const data = await res.json();

      if (data.success) {
        localStorage.setItem("jwt", data.token);
        alert("Autentificare reușită!");
        window.location.href = "dashboard.html";
      } else {
        alert(data.message || "Eroare la autentificare.");
      }
    } catch (err) {
      alert("Eroare de rețea sau server.");
      console.error(err);
    }
  });
});