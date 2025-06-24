<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Statistici generale</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/statistici.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<a href="backend/dashboard.php" class="btn-back">Înapoi la pagină principală</a>
<br>
<br>
<h1>Statistici generale</h1>

<section class="stats-personale">
    <p>Aceste statistici reflectă datele tale personale din cont.</p>
    <h2>Statistici personale</h2>
    <ul>
        <li class="total-obiecte">Total obiecte:</li>
        <li class="total-colectii">Total colecții:</li>
        <li class="valoare-totala">Valoare totală estimată:</li>
        <li class="top-categorii-personale">Top categorii personale:</li>
    </ul>

    <h3>Evoluție temporală</h3>
    <canvas id="evolutieChart" width="600" height="300"></canvas>
</section>

<section class="stats-globale">
    <p>Clasamentele sunt calculate pe baza tuturor colecțiilor și obiectelor partajate.</p>
    <h2>Clasamente globale</h2>
    <ul>
        <li class="top-categorii">Top 5 categorii:</li>
        <li class="top-colectie">Top colecție:</li>
        <li class="top-user">Cel mai activ utilizator:</li>
    </ul>
</section>

<section class="stats-grafic">
    <h2>Distribuția pe categorii (Top 5)</h2>
    <canvas id="categorieChart" width="600" height="400"></canvas>
</section>

<section class="export">
    <p>Poți exporta datele tale în diferite formate pentru backup sau analiză.</p>
    <h2>Exportă statistici</h2>
    <a href="backend/api/export_csv.php" class="btn">CSV</a>
    <a href="backend/api/export_pdf.php" class="btn">PDF</a>
    
</section>

<script>
fetch('backend/api/stats_categorii.php')
  .then(response => response.json())
  .then(data => {
    const ctx = document.getElementById('categorieChart').getContext('2d');
    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: data.map(item => item.categorie),
        datasets: [{
          label: 'Număr de obiecte',
          data: data.map(item => item.nr_obiecte),
          backgroundColor: 'rgba(75, 192, 192, 0.6)',
          borderColor: 'rgba(75, 192, 192, 1)',
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { display: false },
          tooltip: { enabled: true }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: { precision: 0 }
          }
        }
      }
    });
  });

fetch('backend/api/stats_evolutie.php')
  .then(res => res.json())
  .then(data => {
    const ctx = document.getElementById('evolutieChart').getContext('2d');
    new Chart(ctx, {
      type: 'line',
      data: {
        labels: data.map(e => e.luna),
        datasets: [{
          label: 'Obiecte adăugate',
          data: data.map(e => e.nr_obiecte),
          fill: false,
          borderColor: '#ff9800',
          tension: 0.1
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { display: true }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: { precision: 0 }
          }
        }
      }
    });
  });

fetch('backend/api/statistici_summary.php')
  .then(res => res.json())
  .then(data => {
    const p = data.personale;
    document.querySelector('.total-obiecte').textContent = "Total obiecte: " + p.total_obiecte;
    document.querySelector('.total-colectii').textContent = "Total colecții: " + p.total_colectii;
    document.querySelector('.valoare-totala').textContent = "Valoare totală estimată: " + p.valoare_totala + " lei";
    document.querySelector('.top-categorii-personale').textContent = "Top categorii personale: " + p.top_categorii_personale.map(c => c.categorie).join(", ");

    const g = data.globale;
    document.querySelector('.top-categorii').textContent = "Top 5 categorii: " + g.top_categorii.map(c => c.categorie).join(", ");
    document.querySelector('.top-colectie').textContent = `Top colecție: „${g.top_colectie.titlu}” (${g.top_colectie.nr_obiecte} obiecte)`;
    document.querySelector('.top-user').textContent = `Cel mai activ utilizator: ${g.top_user.user} (${g.top_user.total} obiecte)`;
  });
</script>

</body>
</html>
