<?php
require_once __DIR__ . '/../lib/jwt.php';

$headers = function_exists('getallheaders') ? getallheaders() : [];
if (!isset($headers['Authorization']) && !isset($headers['authorization'])) {
    die("Neautentificat");
}
$authHeader = $headers['Authorization'] ?? $headers['authorization'];
if (strpos($authHeader, 'Bearer ') !== 0) {
    die("Format token invalid");
}
$token = substr($authHeader, 7);

try {
    $payload = verifyJWT($token);
    $username = $payload['username'];
} catch (Exception $e) {
    die("Token invalid: " . $e->getMessage());
}

try {
    $db = new PDO("sqlite:" . __DIR__ . '/../db/database.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $filename = "export_colectie_{$username}.csv";
    header('Content-Type: text/csv; charset=utf-8');
    header("Content-Disposition: attachment; filename=\"$filename\"");
    echo "\xEF\xBB\xBF";
    $output = fopen("php://output", "w");

    $stmt = $db->prepare("
        SELECT o.titlu, o.categorie, o.material, o.valoare, o.tara, o.perioada, o.an
        FROM obiecte o
        JOIN colectii c ON o.colectie_id = c.id
        WHERE c.user = :username AND o.de_vanzare = 0
    ");
    $stmt->execute([':username' => $username]);
    $obiecte = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $totalObiecte = count($obiecte);

    $stmt = $db->prepare("SELECT COUNT(*) FROM colectii WHERE user = :username");
    $stmt->execute([':username' => $username]);
    $totalColectii = $stmt->fetchColumn();

    $stmt = $db->prepare("
        SELECT SUM(o.valoare) FROM obiecte o
        JOIN colectii c ON c.id = o.colectie_id
        WHERE c.user = :username AND o.de_vanzare = 0
    ");
    $stmt->execute([':username' => $username]);
    $valoareTotala = $stmt->fetchColumn() ?: 0;

    $stmt = $db->prepare("
        SELECT o.categorie, COUNT(*) AS total 
        FROM obiecte o
        JOIN colectii c ON c.id = o.colectie_id
        WHERE c.user = :username AND o.de_vanzare = 0
        GROUP BY o.categorie 
        ORDER BY total DESC LIMIT 3
    ");
    $stmt->execute([':username' => $username]);
    $topCategorii = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'categorie');

    $stmt = $db->prepare("
        SELECT strftime('%Y-%m', o.data_adaugare) AS luna, COUNT(*) AS nr_obiecte
        FROM obiecte o
        JOIN colectii c ON o.colectie_id = c.id
        WHERE c.user = :username AND o.de_vanzare = 0
        GROUP BY luna ORDER BY luna ASC
    ");
    $stmt->execute([':username' => $username]);
    $evolutie = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $globalCat = $db->query("
        SELECT o.categorie, COUNT(*) as total 
        FROM obiecte o JOIN colectii c ON o.colectie_id = c.id
        WHERE o.de_vanzare = 0 AND (c.tip = 0 OR c.tip IS NULL)
        GROUP BY o.categorie 
        ORDER BY total DESC LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);

    $topUtilizator = $db->query("
        SELECT c.user, COUNT(*) as total 
        FROM colectii c JOIN obiecte o ON c.id = o.colectie_id 
        WHERE o.de_vanzare = 0 AND (c.tip = 0 OR c.tip IS NULL)
        GROUP BY c.user 
        ORDER BY total DESC LIMIT 1
    ")->fetch(PDO::FETCH_ASSOC);

    fputcsv($output, ["STATISTICI PERSONALE"]);
    fputcsv($output, ["Total obiecte", $totalObiecte]);
    fputcsv($output, ["Total colecții", $totalColectii]);
    fputcsv($output, ["Valoare totală estimată (lei)", $valoareTotala]);
    fputcsv($output, ["Top categorii personale", implode(", ", $topCategorii)]);
    fputcsv($output, []);

    fputcsv($output, ["EVOLUȚIE TEMPORALĂ"]);
    fputcsv($output, ["Lună", "Număr obiecte"]);
    foreach ($evolutie as $row) {
        fputcsv($output, [$row['luna'], $row['nr_obiecte']]);
    }
    fputcsv($output, []);

    fputcsv($output, ["CLASAMENTE GLOBALE"]);
    fputcsv($output, ["Top 5 categorii", "Număr obiecte"]);
    foreach ($globalCat as $row) {
        fputcsv($output, [$row['categorie'], $row['total']]);
    }
    $celMaiActiv = ($topUtilizator && isset($topUtilizator['user']) && isset($topUtilizator['total']))
        ? $topUtilizator['user'] . " (" . $topUtilizator['total'] . " obiecte)"
        : '-';
    fputcsv($output, ["Cel mai activ utilizator", $celMaiActiv]);
    fputcsv($output, []);

    fputcsv($output, ["LISTA OBIECTE"]);
    fputcsv($output, ['Titlu', 'Categorie', 'Material', 'Valoare', 'Țara', 'Perioada', 'An']);
    foreach ($obiecte as $row) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit;
} catch (PDOException $e) {
    echo "Eroare la export: " . $e->getMessage();
} 