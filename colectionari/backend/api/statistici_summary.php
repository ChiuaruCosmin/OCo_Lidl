<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(["error" => "Utilizator neautentificat"]);
    exit;
}

$username = $_SESSION['username'];

try {
    $db = new PDO("sqlite:../db/database.sqlite");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $db->prepare("
        SELECT COUNT(*) AS total_obiecte,
               IFNULL(SUM(o.valoare), 0) AS valoare_totala
        FROM obiecte o
        JOIN colectii c ON o.colectie_id = c.id
        WHERE c.user = :username AND o.de_vanzare = 0
    ");
    $stmt->execute([':username' => $username]);
    $statPers = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $db->prepare("SELECT COUNT(*) AS total_colectii FROM colectii WHERE user = :username");
    $stmt->execute([':username' => $username]);
    $statPers['total_colectii'] = $stmt->fetchColumn();

    $stmt = $db->prepare("
        SELECT categorie, COUNT(*) as nr
        FROM obiecte o
        JOIN colectii c ON o.colectie_id = c.id
        WHERE c.user = :username AND o.de_vanzare = 0
        GROUP BY categorie
        ORDER BY nr DESC
        LIMIT 3
    ");
    $stmt->execute([':username' => $username]);
    $statPers['top_categorii_personale'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $global = [];

    $stmt = $db->query("
        SELECT categorie, COUNT(*) AS nr
        FROM obiecte
        WHERE de_vanzare = 0
        GROUP BY categorie
        ORDER BY nr DESC
        LIMIT 5
    ");
    $global['top_categorii'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $db->query("
        SELECT titlu, nr_obiecte
        FROM colectii
        ORDER BY nr_obiecte DESC
        LIMIT 1
    ");
    $global['top_colectie'] = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $db->query("
        SELECT c.user, COUNT(*) AS total
        FROM colectii c
        JOIN obiecte o ON c.id = o.colectie_id
        WHERE o.de_vanzare = 0
        GROUP BY c.user
        ORDER BY total DESC
        LIMIT 1
    ");
    $global['top_user'] = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'personale' => $statPers,
        'globale' => $global
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
