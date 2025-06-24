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

    $stmt = $db->prepare("SELECT * FROM tranzactii WHERE ofertant = :user OR proprietar = :user ORDER BY data DESC");
    $stmt->execute([':user' => $username]);
    $tranzactii = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Eroare DB: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Istoric tranzacții</title>
    <link rel="stylesheet" href="css/vanzare.css">
</head>
<body>
    <div class="container">
        <h1>Istoricul tranzacțiilor</h1>
        <a class="btn-back" href="backend/dashboard.php">Înapoi la pagina principală</a>

        <?php if (empty($tranzactii)): ?>
            <p>Nu există tranzacții înregistrate.</p>
        <?php else: ?>
            <div class="vanzare-grid">
                <?php foreach ($tranzactii as $tr): ?>
                    <div class="vanzare-card">
                        <img src="<?= htmlspecialchars($tr['imagine']) ?>" alt="Imagine obiect">
                        <div class="info">
                            <h3><?= htmlspecialchars($tr['titlu']) ?></h3>
                            <p>Preț: <?= $tr['pret'] ?> lei</p>
                            <p>Ofertant: <?= htmlspecialchars($tr['ofertant']) ?></p>
                            <p>Proprietar: <?= htmlspecialchars($tr['proprietar']) ?></p>
                            <p>Adresă livrare: <?= htmlspecialchars($tr['adresa']) ?></p>
                            <p>Data tranzacției: <?= $tr['data'] ?></p>
                            <p>Status:
                                <?php if ($tr['status'] === 'acceptata'): ?>
                                    <span style="color: green; font-weight: bold;">Acceptată</span>
                                <?php else: ?>
                                    <span style="color: red; font-weight: bold;">Refuzată</span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
