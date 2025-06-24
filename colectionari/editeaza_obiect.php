<?php
session_start();
if (!isset($_SESSION['username']) || !isset($_GET['id'])) {
    header("Location: login.html");
    exit;
}

$username = $_SESSION['username'];
$obiect_id = intval($_GET['id']);
$dbPath = __DIR__ . "/backend/db/database.sqlite";
$mesaj = "";

try {
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $db->prepare("
        SELECT o.*, c.user 
        FROM obiecte o
        JOIN colectii c ON o.colectie_id = c.id
        WHERE o.id = :id AND c.user = :user
    ");
    $stmt->execute([':id' => $obiect_id, ':user' => $username]);
    $obiect = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$obiect) {
        die("Obiectul nu a fost găsit sau nu ai acces.");
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $titlu = trim($_POST['titlu']);
        $categorie = trim($_POST['categorie']);
        $descriere = trim($_POST['descriere']);
        $an = intval($_POST['an']);
        $material = trim($_POST['material']);
        $valoare = floatval($_POST['valoare']);
        $tara = trim($_POST['tara']);
        $perioada = trim($_POST['perioada']);
        $istoric = trim($_POST['istoric']);
        $eticheta = isset($_POST['eticheta']) ? 1 : 0;
        $imagine = $obiect['imagine'];

        if (isset($_FILES['imagine']) && $_FILES['imagine']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['imagine']['name'], PATHINFO_EXTENSION);
            $numeNou = uniqid() . '.' . $ext;
            $caleFinala = "assets/uploads/" . $numeNou;

            if (!file_exists("assets/uploads/")) {
                mkdir("assets/uploads/", 0777, true);
            }

            if (move_uploaded_file($_FILES['imagine']['tmp_name'], $caleFinala)) {
                $imagine = $caleFinala;
            } else {
                $mesaj = "Eroare la salvarea imaginii.";
            }
        }

        if ($mesaj === "") {
            $stmt = $db->prepare("
                UPDATE obiecte SET
                    titlu = :t, categorie = :c, descriere = :d, an = :a, material = :mat,
                    valoare = :val, tara = :tara, perioada = :per, istoric = :ist,
                    eticheta = :eti, imagine = :i
                WHERE id = :id
            ");
            $stmt->execute([
                ':t' => $titlu,
                ':c' => $categorie,
                ':d' => $descriere,
                ':a' => $an,
                ':mat' => $material,
                ':val' => $valoare,
                ':tara' => $tara,
                ':per' => $perioada,
                ':ist' => $istoric,
                ':eti' => $eticheta,
                ':i' => $imagine,
                ':id' => $obiect_id
            ]);

            header("Location: colectiile_mele.php");
            exit;
        }
    }
} catch (PDOException $e) {
    $mesaj = "Eroare DB: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Editează obiect</title>
    <link rel="stylesheet" href="css/editeaza_obiect.css">
</head>
<body>
    <h2>Editează obiectul: <?= htmlspecialchars($obiect['titlu']) ?></h2>
    <?php if ($mesaj): ?>
        <p style="color:red"><?= htmlspecialchars($mesaj) ?></p>
    <?php endif; ?>
    <form method="POST" enctype="multipart/form-data">
        <label>Titlu:</label><br>
        <input type="text" name="titlu" value="<?= htmlspecialchars($obiect['titlu']) ?>" required><br><br>

        <label>Categorie:</label><br>
        <input type="text" name="categorie" value="<?= htmlspecialchars($obiect['categorie']) ?>" required><br><br>

        <label>Descriere:</label><br>
        <textarea name="descriere"><?= htmlspecialchars($obiect['descriere']) ?></textarea><br><br>

        <label>An:</label><br>
        <input type="number" name="an" value="<?= htmlspecialchars($obiect['an']) ?>"><br><br>

        <label>Material:</label><br>
        <input type="text" name="material" value="<?= htmlspecialchars($obiect['material']) ?>"><br><br>

        <label>Valoare estimată (lei):</label><br>
        <input type="number" name="valoare" step="0.01" value="<?= htmlspecialchars($obiect['valoare']) ?>"><br><br>

        <label>Țara de origine:</label><br>
        <input type="text" name="tara" value="<?= htmlspecialchars($obiect['tara']) ?>"><br><br>

        <label>Perioadă de utilizare:</label><br>
        <input type="text" name="perioada" value="<?= htmlspecialchars($obiect['perioada']) ?>"><br><br>

        <label>Istoric:</label><br>
        <textarea name="istoric"><?= htmlspecialchars($obiect['istoric']) ?></textarea><br><br>

        <label>Are etichetă?</label>
        <input type="checkbox" name="eticheta" value="1" <?= $obiect['eticheta'] ? 'checked' : '' ?>><br><br>

        <label>Imagine nouă:</label><br>
        <input type="file" name="imagine" accept="image/*"><br><br>

        <button type="submit">Salvează modificările</button>
    </form>
    <br>
    <a href="colectiile_mele.php">Înapoi</a>
</body>
</html>
