<?php
require_once __DIR__ . '/../lib/jwt.php';

$headers = function_exists('getallheaders') ? getallheaders() : [];
if (!isset($headers['Authorization']) && !isset($headers['authorization'])) {
    http_response_code(401);
    exit('Neautentificat');
}
$authHeader = $headers['Authorization'] ?? $headers['authorization'];
if (strpos($authHeader, 'Bearer ') !== 0) {
    http_response_code(401);
    exit('Format token invalid');
}
$token = substr($authHeader, 7);

try {
    $payload = verifyJWT($token);
    $username = $payload['username'];
} catch (Exception $e) {
    http_response_code(401);
    exit('Token invalid: ' . $e->getMessage());
}

if (!isset($_GET['id'])) {
    http_response_code(400);
    exit('ID colecție lipsă');
}
$colectieId = (int)$_GET['id'];

try {
    $db = new PDO("sqlite:" . __DIR__ . '/../db/database.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $db->prepare("SELECT titlu, user FROM colectii WHERE id = :id");
    $stmt->execute([':id' => $colectieId]);
    $col = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$col) exit('Colecție inexistentă');
    if ($col['user'] !== $username) exit('Nu ai acces la această colecție');
    $titlu = $col['titlu'];

    $stmt = $db->prepare("SELECT titlu, categorie, material, valoare, tara, perioada, an FROM obiecte WHERE colectie_id = :id AND de_vanzare = 0");
    $stmt->execute([':id' => $colectieId]);
    $obiecte = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="colectie_' . $colectieId . '.csv"');
    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Titlu', 'Categorie', 'Material', 'Valoare', 'Țara', 'Perioada', 'An']);
    foreach ($obiecte as $row) {
        fputcsv($out, [
            $row['titlu'], $row['categorie'], $row['material'], $row['valoare'],
            $row['tara'], $row['perioada'], $row['an']
        ]);
    }
    fclose($out);
    exit;

} catch (PDOException $e) {
    http_response_code(500);
    exit('Eroare la export CSV: ' . $e->getMessage());
} 