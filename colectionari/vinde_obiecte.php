<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.html");
    exit;
}
$username = $_SESSION['username'];

$dbPath = __DIR__ . '/backend/db/database.sqlite';

try {
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $db->prepare("SELECT * FROM obiecte WHERE de_vanzare = 1 AND colectie_id IN (SELECT id FROM colectii WHERE user = :username)");
    $stmt->execute([':username' => $username]);
    $obiecte = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $oferte = [];
    foreach ($obiecte as $obj) {
        $stmtOferte = $db->prepare("SELECT * FROM oferte WHERE id_obiect = :id AND status = 'in_asteptare'");
        $stmtOferte->execute([':id' => $obj['id']]);
        $oferte[$obj['id']] = $stmtOferte->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    die("Eroare DB: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Obiecte puse la vânzare</title>
    <link rel="stylesheet" href="css/vanzare.css">
    <script>
    function acceptaOferta(ofertaId, cardId) {
        if (!confirm("Ești sigur că vrei să accepți această ofertă?")) return;

        const formData = new FormData();
        formData.append('oferta_id', ofertaId);

        fetch('backend/api/accepta_oferta.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert("Ofertă acceptată cu succes!\n\nDate contract:\n" + data.contract + "\nAdresă livrare:\n" + data.adresa);
                const card = document.getElementById("card-" + cardId);
                if (card) card.remove();
            } else {
                alert("Eroare: " + (data.error || "necunoscută"));
            }
        });
    }

    function refuzaOferta(ofertaId) {
        if (!confirm("Ești sigur că vrei să refuzi această ofertă?")) return;

        const formData = new FormData();
        formData.append('oferta_id', ofertaId);

        fetch('backend/api/refuza_oferta.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert("Ofertă refuzată.");
                location.reload();
            } else {
                alert("Eroare: " + (data.error || "necunoscută"));
            }
        });
    }

    function scoateDeLaVanzare(id) {
        if (!confirm("Vrei să scoți acest obiect de la vânzare?")) return;

        const formData = new FormData();
        formData.append('id', id);

        fetch('backend/api/scoate_de_la_vanzare.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const card = document.getElementById("card-" + id);
                if (card) card.remove();
                alert("Obiectul a fost scos de la vânzare.");
            } else {
                alert("Eroare: " + (data.error || "necunoscută"));
            }
        });
    }
    </script>
</head>
<body>
    <div class="container">
        <h1>Obiectele mele de vânzare</h1>
        <a class="btn-back" href="backend/dashboard.php">Înapoi la pagina principală</a>
        <div class="vanzare-grid">
            <?php foreach ($obiecte as $obj): ?>
                <div class="vanzare-card" id="card-<?= $obj['id'] ?>">
                    <img src="<?= htmlspecialchars($obj['imagine']) ?>" alt="Imagine">
                    <div class="info">
                        <h3><?= htmlspecialchars($obj['titlu']) ?></h3>
                        <p><?= htmlspecialchars($obj['descriere']) ?></p>
                        <p><strong><?= htmlspecialchars($obj['pret']) ?> lei</strong></p>

                        <?php if (!empty($oferte[$obj['id']])): ?>
                            <div class="oferte-box">
                                <h4>Oferte primite:</h4>
                                <?php foreach ($oferte[$obj['id']] as $oferta): ?>
                                    <div class="oferta-item">
                                        <p><strong><?= htmlspecialchars($oferta['user']) ?></strong> oferă <?= $oferta['pret'] ?> lei</p>
                                        <button onclick="acceptaOferta(<?= $oferta['id'] ?>, <?= $obj['id'] ?>)">Acceptă</button>
                                        <button onclick="refuzaOferta(<?= $oferta['id'] ?>)">Refuză</button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <button onclick="scoateDeLaVanzare(<?= $obj['id'] ?>)">Scoate de la vânzare</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
