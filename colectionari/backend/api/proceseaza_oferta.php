<?php
require_once __DIR__ . '/../lib/jwt.php';
header('Content-Type: application/json');

$headers = function_exists('getallheaders') ? getallheaders() : [];
if (!isset($headers['Authorization']) && !isset($headers['authorization'])) {
    echo json_encode(['success' => false, 'message' => 'Token lipsă.']);
    exit;
}
$authHeader = $headers['Authorization'] ?? $headers['authorization'];
if (strpos($authHeader, 'Bearer ') !== 0) {
    echo json_encode(['success' => false, 'message' => 'Format token invalid.']);
    exit;
}
$token = substr($authHeader, 7);

try {
    $payload = verifyJWT($token);
    $username = $payload['username'];
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Token invalid: ' . $e->getMessage()]);
    exit;
}

$id_oferta = isset($_POST['id_oferta']) ? intval($_POST['id_oferta']) : 0;
$actiune = $_POST['actiune'] ?? '';

if (!$id_oferta || !in_array($actiune, ['accepta', 'refuza'])) {
    echo json_encode(['success' => false, 'message' => 'Date lipsă sau acțiune invalidă.']);
    exit;
}

try {
    $db = new PDO("sqlite:" . __DIR__ . '/../db/database.sqlite');
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
    $oferta = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$oferta || $oferta['proprietar'] !== $username) {
        echo json_encode(['success' => false, 'message' => 'Nu ai dreptul să procesezi această ofertă.']);
        exit;
    }

    if ($actiune === 'accepta') {
        $db->beginTransaction();
        $db->prepare("UPDATE oferte SET status = 'acceptata' WHERE id = :id")
           ->execute([':id' => $id_oferta]);
        $db->prepare("UPDATE oferte SET status = 'refuzata' WHERE id_obiect = :obj AND id != :id")
           ->execute([':obj' => $oferta['id_obiect'], ':id' => $id_oferta]);
        $db->prepare("UPDATE obiecte SET de_vanzare = 2 WHERE id = :id")
           ->execute([':id' => $oferta['id_obiect']]);
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
        $db->commit();
        echo json_encode(['success' => true, 'message' => 'Oferta acceptată.', 'contract' => $oferta['contract'], 'adresa' => $oferta['adresa']]);
    } else if ($actiune === 'refuza') {
        $db->prepare("UPDATE oferte SET status = 'refuzata' WHERE id = :id")
           ->execute([':id' => $id_oferta]);
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
                      VALUES (:id_obiect, :titlu, :imagine, :ofertant, :proprietar, :pret, :contract, :adresa, 'refuzata')")
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
        echo json_encode(['success' => true, 'message' => 'Oferta refuzată.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Eroare DB: ' . $e->getMessage()]);
} 