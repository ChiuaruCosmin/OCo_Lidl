<?php
header('Content-Type: application/json');

try {
    $db = new PDO("sqlite:" . __DIR__ . '/../db/database.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $populare = $db->query("
        SELECT c.titlu, c.imagine, c.user,
               (SELECT COUNT(*) FROM obiecte o WHERE o.colectie_id = c.id AND o.de_vanzare = 0) AS nr_obiecte
        FROM colectii c
        WHERE (c.tip = 0 OR c.tip IS NULL)
        ORDER BY nr_obiecte DESC
        LIMIT 4
    ")->fetchAll(PDO::FETCH_ASSOC);

    $ultime = $db->query("
        SELECT titlu, imagine, user 
        FROM colectii 
        WHERE (tip = 0 OR tip IS NULL)
        ORDER BY id DESC 
        LIMIT 3
    ")->fetchAll(PDO::FETCH_ASSOC);

    $clasament = $db->query("
        SELECT c.user, COUNT(o.id) as total
        FROM colectii c
        JOIN obiecte o ON o.colectie_id = c.id
        WHERE o.de_vanzare = 0 AND (c.tip = 0 OR c.tip IS NULL)
        GROUP BY c.user
        ORDER BY total DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);

    $categorii = $db->query("
        SELECT o.categorie, COUNT(*) as total 
        FROM obiecte o
        JOIN colectii c ON o.colectie_id = c.id
        WHERE o.de_vanzare = 0 AND (c.tip = 0 OR c.tip IS NULL)
        GROUP BY o.categorie 
        ORDER BY total DESC 
        LIMIT 3
    ")->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'populare' => $populare,
        'ultime' => $ultime,
        'clasament' => $clasament,
        'categorii' => $categorii
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB error', 'details' => $e->getMessage()]);
}
