document.addEventListener("DOMContentLoaded", () => {
  const token = localStorage.getItem("jwt");
  if (!token) {
    window.location.href = "landing.html";
    return;
  }

  document.getElementById("logoutBtn").addEventListener("click", () => {
    localStorage.removeItem("jwt");
    window.location.href = "landing.html";
  });

  const form = document.getElementById("addCollectionForm");
  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    const msg = document.getElementById("addCollectionMsg");
    msg.textContent = "";
    const formData = new FormData(form);
    try {
      const res = await fetch("/colectionari/backend/api/adauga_colectie.php", {
        method: "POST",
        headers: {
          'Authorization': 'Bearer ' + token
        },
        body: formData
      });
      const data = await res.json();
      if (data.success) {
        msg.style.color = "green";
        msg.textContent = "Colecția a fost adăugată!";
        setTimeout(() => window.location.href = "colectiile_mele.html", 1000);
      } else {
        msg.style.color = "red";
        msg.textContent = data.error || "Eroare la adăugare.";
      }
    } catch (err) {
      msg.style.color = "red";
      msg.textContent = "Eroare de rețea sau server.";
    }
  });
}); 