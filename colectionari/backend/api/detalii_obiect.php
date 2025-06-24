
<?php
session_start();
if (!isset($_GET['id']) || !isset($_SESSION['username'])) {
    http_response_code(400);
    exit("Cerere invalidă");
}

$dbPath = __DIR__ . '/../db/database.sqlite';
try {
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $db->prepare("SELECT * FROM obiecte WHERE id = :id");
    $stmt->execute([':id' => intval($_GET['id'])]);
    $obj = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$obj) {
        http_response_code(404);
        exit("Obiect inexistent");
    }

    echo json_encode($obj);
} catch (PDOException $e) {
    http_response_code(500);
    echo "Eroare DB";
}
