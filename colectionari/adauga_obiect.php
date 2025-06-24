<?php
session_start();
if (!isset($_SESSION['username']) || !isset($_GET['id'])) {
    header("Location: login.html");
    exit;
}

$username = $_SESSION['username'];
$colectie_id = intval($_GET['id']);
$dbPath = __DIR__ . "/backend/db/database.sqlite";
$mesaj = "";

try {
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $db->prepare("SELECT * FROM colectii WHERE id = :id AND user = :user");
    $stmt->execute([':id' => $colectie_id, ':user' => $username]);
    $colectie = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$colectie) {
        die("Colecția nu a fost găsită.");
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
        $imagine = 'assets/default_obj.png';

        if ($titlu === '' || $categorie === '') {
            $mesaj = "Titlul și categoria sunt obligatorii.";
        } else {
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
                $stmt = $db->prepare("INSERT INTO obiecte 
                    (titlu, categorie, descriere, an, imagine, colectie_id, material, valoare, tara, perioada, istoric, eticheta)
                    VALUES (:t, :c, :d, :a, :i, :cid, :mat, :val, :tara, :per, :ist, :eti)");
                $stmt->execute([
                    ':t' => $titlu,
                    ':c' => $categorie,
                    ':d' => $descriere,
                    ':a' => $an,
                    ':i' => $imagine,
                    ':cid' => $colectie_id,
                    ':mat' => $material,
                    ':val' => $valoare,
                    ':tara' => $tara,
                    ':per' => $perioada,
                    ':ist' => $istoric,
                    ':eti' => $eticheta
                ]);

                $db->prepare("UPDATE colectii SET nr_obiecte = nr_obiecte + 1 WHERE id = :id")
                   ->execute([':id' => $colectie_id]);

                header("Location: colectiile_mele.php");
                exit;
            }
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
    <title>Adaugă obiect</title>
    <link rel="stylesheet" href="css/adauga_obiect.css">
</head>
<body>
    <div class="top-bar">
  <a href="colectiile_mele.php">Înapoi la colecțiile mele</a>
</div>

    <h2>Adaugă obiect în colecția: <?= isset($colectie['titlu']) ? htmlspecialchars($colectie['titlu']) : 'necunoscută' ?></h2>
    <?php if ($mesaj): ?>
        <p style="color:red"><?= htmlspecialchars($mesaj) ?></p>
    <?php endif; ?>
    <form method="POST" enctype="multipart/form-data">
        <label>Titlu:</label><br>
        <input type="text" name="titlu" required><br><br>

        <label>Categorie:</label><br>
        <input type="text" name="categorie" required><br><br>

        <label>Descriere:</label><br>
        <textarea name="descriere"></textarea><br><br>

        <label>An:</label><br>
        <input type="number" name="an"><br><br>

        <label>Material:</label><br>
        <input type="text" name="material"><br><br>

        <label>Valoare estimată (lei):</label><br>
        <input type="number" name="valoare" step="0.01"><br><br>

        <label>Țara de origine:</label><br>
        <input type="text" name="tara"><br><br>

        <label>Perioadă de utilizare:</label><br>
        <input type="text" name="perioada"><br><br>

        <label>Istoric:</label><br>
        <textarea name="istoric"></textarea><br><br>

        <label>Are etichetă?</label>
        <input type="checkbox" name="eticheta" value="1"><br><br>

        <label>Imagine:</label><br>
        <input type="file" name="imagine" accept="image/*"><br><br>

        <button type="submit">Adaugă</button>
    </form>
    <br>
    
</body>
</html>
