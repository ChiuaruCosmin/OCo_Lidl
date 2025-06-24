<?php
session_start();
if (!isset($_SESSION['username'])) {
    http_response_code(401);
    exit("Neautentificat");
}

if (!isset($_POST['id_obiect'], $_POST['pret'], $_POST['contract'], $_POST['adresa'])) {
    http_response_code(400);
    exit("Date incomplete");
}

$id_obiect = intval($_POST['id_obiect']);
$pret = floatval($_POST['pret']);
$contract = trim($_POST['contract']);
$adresa = trim($_POST['adresa']);
$username = $_SESSION['username'];

try {
    $db = new PDO("sqlite:" . __DIR__ . "/../db/database.sqlite");

    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $db->exec("CREATE TABLE IF NOT EXISTS oferte (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        id_obiect INTEGER NOT NULL,
        user TEXT NOT NULL,
        pret REAL NOT NULL,
        contract TEXT,
        adresa TEXT,
        status TEXT DEFAULT 'in_asteptare',
        data TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $stmt = $db->prepare("INSERT INTO oferte (id_obiect, user, pret, contract, adresa) 
                          VALUES (:id, :user, :pret, :contract, :adresa)");
    $stmt->execute([
        ':id' => $id_obiect,
        ':user' => $username,
        ':pret' => $pret,
        ':contract' => $contract,
        ':adresa' => $adresa
    ]);

    echo "Oferta trimisă cu succes!";
} catch (PDOException $e) {
    http_response_code(500);
    echo "Eroare DB: " . $e->getMessage();
}
