<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(["error" => "Neautentificat"]);
    exit;
}

if (!isset($_POST['oferta_id'])) {
    http_response_code(400);
    echo json_encode(["error" => "ID ofertă lipsă"]);
    exit;
}

$id_oferta = intval($_POST['oferta_id']);
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
        WHERE o.id = :id_oferta
    ");
    $stmt->execute([':id_oferta' => $id_oferta]);
    $info = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$info || $info['proprietar'] !== $username) {
        http_response_code(403);
        echo json_encode(["error" => "Nu ai dreptul să refuzi această ofertă."]);
        exit;
    }

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

    $db->prepare("INSERT INTO tranzactii 
        (id_obiect, titlu, imagine, ofertant, proprietar, pret, contract, adresa, status)
        VALUES (:id_obiect, :titlu, :imagine, :ofertant, :proprietar, :pret, :contract, :adresa, 'refuzata')")
    ->execute([
        ':id_obiect'  => $info['id_obiect'],
        ':titlu'      => $info['titlu'],
        ':imagine'    => $info['imagine'],
        ':ofertant'   => $info['ofertant'],
        ':proprietar' => $info['proprietar'],
        ':pret'       => $info['pret'],
        ':contract'   => $info['contract'],
        ':adresa'     => $info['adresa']
    ]);

    $db->prepare("UPDATE oferte SET status = 'refuzata' WHERE id = :id")
        ->execute([':id' => $id_oferta]);

    echo json_encode(["success" => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Eroare DB: " . $e->getMessage()]);
}
