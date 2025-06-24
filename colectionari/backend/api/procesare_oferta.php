<?php
session_start();
if (!isset($_SESSION['username'], $_POST['id_oferta'], $_POST['actiune'])) {
    http_response_code(400);
    exit("Date lipsă");
}

$id_oferta = intval($_POST['id_oferta']);
$actiune = $_POST['actiune'];
$username = $_SESSION['username'];

try {
    $db = new PDO("sqlite:../db/database.sqlite");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $db->prepare("
        SELECT o.id_obiect, c.user 
        FROM oferte o 
        JOIN obiecte ob ON ob.id = o.id_obiect 
        JOIN colectii c ON c.id = ob.colectie_id 
        WHERE o.id = :id
    ");
    $stmt->execute([':id' => $id_oferta]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || $row['user'] !== $username) {
        http_response_code(403);
        exit("Nu ai dreptul să modifici această ofertă.");
    }

    if ($actiune === 'accepta') {
        $db->beginTransaction();

        $db->prepare("UPDATE oferte SET status = 'acceptata' WHERE id = :id")
           ->execute([':id' => $id_oferta]);

        $db->prepare("UPDATE oferte SET status = 'refuzata' WHERE id_obiect = :obj AND id != :id")
           ->execute([':obj' => $row['id_obiect'], ':id' => $id_oferta]);

        $db->prepare("UPDATE obiecte SET de_vanzare = 0 WHERE id = :id")
           ->execute([':id' => $row['id_obiect']]);

        $db->commit();
        echo "Oferta acceptată.";
    } elseif ($actiune === 'refuza') {
        $db->prepare("UPDATE oferte SET status = 'refuzata' WHERE id = :id")
           ->execute([':id' => $id_oferta]);
        echo "Oferta refuzată.";
    } else {
        http_response_code(400);
        echo "Acțiune invalidă.";
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo "Eroare DB: " . $e->getMessage();
}
