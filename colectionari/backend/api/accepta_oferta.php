<?php
session_start();
header("Content-Type: application/json");

if (!isset($_SESSION['username']) || !isset($_POST['oferta_id'])) {
    http_response_code(400);
    echo json_encode(["error" => "Date lipsă"]);
    exit;
}

$oferta_id = intval($_POST['oferta_id']);
$username = $_SESSION['username'];

try {
    $db = new PDO("sqlite:../db/database.sqlite");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $db->prepare("
        SELECT o.id_obiect, ob.titlu, ob.imagine, ob.colectie_id, c.user AS proprietar,
               o.user AS ofertant, o.pret, o.contract, o.adresa
        FROM oferte o
        JOIN obiecte ob ON o.id_obiect = ob.id
        JOIN colectii c ON ob.colectie_id = c.id
        WHERE o.id = :id
    ");
    $stmt->execute([':id' => $oferta_id]);
    $oferta = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$oferta || $oferta['proprietar'] !== $username) {
        http_response_code(403);
        echo json_encode(["error" => "Nu ai dreptul să accepți această ofertă."]);
        exit;
    }

    $db->prepare("UPDATE obiecte SET de_vanzare = 2 WHERE id = :id")
        ->execute([':id' => $oferta['id_obiect']]);

    $db->prepare("UPDATE oferte SET status = 'respinsa' WHERE id_obiect = :id")
        ->execute([':id' => $oferta['id_obiect']]);

    $db->prepare("UPDATE oferte SET status = 'acceptata' WHERE id = :id")
        ->execute([':id' => $oferta_id]);

    $db->exec("CREATE TABLE IF NOT EXISTS tranzactii (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        id_obiect INTEGER,
        titlu TEXT,
        imagine TEXT,
        ofertant TEXT,
        proprietar TEXT,
        pret REAL,
        contract TEXT,
        adresa TEXT,
        status TEXT,
        data TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $db->prepare("INSERT INTO tranzactii (id_obiect, titlu, imagine, ofertant, proprietar, pret, contract, adresa, status)
                  VALUES (:id_obiect, :titlu, :imagine, :ofertant, :proprietar, :pret, :contract, :adresa, 'acceptata')")
       ->execute([
           ':id_obiect'   => $oferta['id_obiect'],
           ':titlu'       => $oferta['titlu'],
           ':imagine'     => $oferta['imagine'],
           ':ofertant'    => $oferta['ofertant'],
           ':proprietar'  => $username,
           ':pret'        => $oferta['pret'],
           ':contract'    => $oferta['contract'],
           ':adresa'      => $oferta['adresa']
       ]);

    echo json_encode([
        "success" => true,
        "contract" => $oferta['contract'],
        "adresa"   => $oferta['adresa']
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Eroare DB: " . $e->getMessage()]);
}
