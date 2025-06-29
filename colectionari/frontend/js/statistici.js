function getToken() {
    return localStorage.getItem('jwt');
}

function incarcaSummary() {
    const token = getToken();
    fetch('/colectionari/backend/api/statistici_summary.php', {
        headers: { 'Authorization': 'Bearer ' + token }
    })
    .then(res => res.json())
    .then(data => {
        if (!data.personale || !data.globale) return;
        const p = data.personale;
        document.querySelector('.total-obiecte').textContent = "Total obiecte: " + p.total_obiecte;
        document.querySelector('.total-colectii').textContent = "Total colecții: " + p.total_colectii;
        document.querySelector('.valoare-totala').textContent = "Valoare totală estimată: " + p.valoare_totala + " lei";
        document.querySelector('.top-categorii-personale').textContent = "Top categorii personale: " + (p.top_categorii_personale && p.top_categorii_personale.length ? p.top_categorii_personale.map(c => c.categorie).join(", ") : '-');

        const g = data.globale;
        document.querySelector('.top-categorii').textContent = "Top 5 categorii: " + (g.top_categorii && g.top_categorii.length ? g.top_categorii.map(c => c.categorie).join(", ") : '-');
        document.querySelector('.top-colectie').textContent = g.top_colectie ? `Top colecție: „${g.top_colectie.titlu}” (${g.top_colectie.nr_obiecte} obiecte)` : 'Top colecție: -';
        document.querySelector('.top-user').textContent = g.top_user ? `Cel mai activ utilizator: ${g.top_user.user} (${g.top_user.total} obiecte)` : 'Cel mai activ utilizator: -';
    });
}

function incarcaGraficCategorii() {
    const token = getToken();
    fetch('/colectionari/backend/api/stats_categorii.php', {
        headers: { 'Authorization': 'Bearer ' + token }
    })
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
}

function incarcaGraficEvolutie() {
    const token = getToken();
    fetch('/colectionari/backend/api/stats_evolutie.php', {
        headers: { 'Authorization': 'Bearer ' + token }
    })
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
}

document.addEventListener('DOMContentLoaded', () => {
    incarcaSummary();
    incarcaGraficCategorii();
    incarcaGraficEvolutie();

    const btnPdf = document.getElementById('export-pdf');
    if (btnPdf) {
        btnPdf.addEventListener('click', () => {
            const token = getToken();
            fetch('/colectionari/backend/api/export_pdf.php', {
                headers: {
                    'Authorization': 'Bearer ' + token
                }
            })
            .then(response => {
                if (!response.ok) throw new Error('Eroare la export PDF');
                return response.blob();
            })
            .then(blob => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'statistici.pdf';
                document.body.appendChild(a);
                a.click();
                a.remove();
                setTimeout(() => window.URL.revokeObjectURL(url), 1000);
            })
            .catch(err => alert('Exportul PDF a eșuat: ' + err.message));
        });
    }

    const btnCsv = document.getElementById('export-csv');
    if (btnCsv) {
        btnCsv.addEventListener('click', () => {
            const token = getToken();
            fetch('/colectionari/backend/api/export_csv.php', {
                headers: {
                    'Authorization': 'Bearer ' + token
                }
            })
            .then(response => {
                if (!response.ok) throw new Error('Eroare la export CSV');
                return response.blob();
            })
            .then(blob => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'statistici.csv';
                document.body.appendChild(a);
                a.click();
                a.remove();
                setTimeout(() => window.URL.revokeObjectURL(url), 1000);
            })
            .catch(err => alert('Exportul CSV a eșuat: ' + err.message));
        });
    }
}); 