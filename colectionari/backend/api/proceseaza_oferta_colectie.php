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
    echo json_encode(['success' => false, 'message' => 'Parametri invalizi.']);
    exit;
}

try {
    $db = new PDO("sqlite:" . __DIR__ . '/../db/database.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $db->prepare("SELECT o.*, c.titlu, c.imagine, c.user as proprietar 
                         FROM oferte_colectii o 
                         INNER JOIN colectii c ON o.id_colectie = c.id 
                         WHERE o.id = :id_oferta AND c.user = :username");
    $stmt->execute([':id_oferta' => $id_oferta, ':username' => $username]);
    $oferta = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$oferta) {
        echo json_encode(['success' => false, 'message' => 'Ofertă negăsită sau acces interzis.']);
        exit;
    }

    if ($actiune === 'accepta') {
        $db->beginTransaction();
        
        $db->prepare("UPDATE oferte_colectii SET status = 'acceptata' WHERE id = :id")
           ->execute([':id' => $id_oferta]);

        $db->prepare("UPDATE oferte_colectii SET status = 'refuzata' WHERE id_colectie = :colectie AND id != :id")
           ->execute([':colectie' => $oferta['id_colectie'], ':id' => $id_oferta]);

        $db->prepare("UPDATE colectii SET tip = 4 WHERE id = :id")
           ->execute([':id' => $oferta['id_colectie']]);

        $db->prepare("UPDATE obiecte SET de_vanzare = 4 WHERE colectie_id = :colectie_id")
           ->execute([':colectie_id' => $oferta['id_colectie']]);
        
        $db->prepare("INSERT INTO tranzactii_colectii (id_colectie, titlu, imagine, ofertant, proprietar, pret, contract, adresa, status)
                      VALUES (:id_colectie, :titlu, :imagine, :ofertant, :proprietar, :pret, :contract, :adresa, 'acceptata')")
           ->execute([
               ':id_colectie'   => $oferta['id_colectie'],
               ':titlu'         => $oferta['titlu'],
               ':imagine'       => $oferta['imagine'],
               ':ofertant'      => $oferta['user'],
               ':proprietar'    => $username,
               ':pret'          => $oferta['pret'],
               ':contract'      => $oferta['contract'],
               ':adresa'        => $oferta['adresa']
           ]);
        
        $db->commit();
        echo json_encode(['success' => true, 'message' => 'Oferta acceptată.', 'contract' => $oferta['contract'], 'adresa' => $oferta['adresa']]);
    } else {

        $db->prepare("UPDATE oferte_colectii SET status = 'refuzata' WHERE id = :id")
           ->execute([':id' => $id_oferta]);
        
        $db->prepare("INSERT INTO tranzactii_colectii (id_colectie, titlu, imagine, ofertant, proprietar, pret, contract, adresa, status)
                      VALUES (:id_colectie, :titlu, :imagine, :ofertant, :proprietar, :pret, :contract, :adresa, 'refuzata')")
           ->execute([
               ':id_colectie'   => $oferta['id_colectie'],
               ':titlu'         => $oferta['titlu'],
               ':imagine'       => $oferta['imagine'],
               ':ofertant'      => $oferta['user'],
               ':proprietar'    => $username,
               ':pret'          => $oferta['pret'],
               ':contract'      => $oferta['contract'],
               ':adresa'        => $oferta['adresa']
           ]);
        
        echo json_encode(['success' => true, 'message' => 'Oferta refuzată.']);
    }
} catch (PDOException $e) {
    if (isset($db)) $db->rollBack();
    echo json_encode(['success' => false, 'message' => 'Eroare DB: ' . $e->getMessage()]);
} 