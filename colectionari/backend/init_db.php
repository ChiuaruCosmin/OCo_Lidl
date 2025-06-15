<?php
$dbPath = __DIR__ . '/db/database.sqlite';

try {
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Tabelul utilizatori
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        email TEXT
    )");

    // Utilizator admin default
    $stmt = $db->prepare("INSERT OR IGNORE INTO users (username, password, email)
                          VALUES (:u, :p, :e)");
    $stmt->execute([
        ':u' => 'admin',
        ':p' => 'admin',
        ':e' => 'admin@example.com'
    ]);

    // Tabelul obiecte
    $db->exec("CREATE TABLE IF NOT EXISTS obiecte (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        titlu TEXT NOT NULL,
        categorie TEXT NOT NULL,
        descriere TEXT,
        an INTEGER
    )");

    // 🔥 Tabelul colectii
    $db->exec("CREATE TABLE IF NOT EXISTS colectii (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user TEXT NOT NULL,
        titlu TEXT NOT NULL,
        nr_obiecte INTEGER DEFAULT 0,
        imagine TEXT DEFAULT 'assets/default.png'
    )");

    // 🔧 (Opțional) Date de test inițiale
    $db->exec("INSERT INTO colectii (user, titlu, nr_obiecte, imagine)
               VALUES
               ('admin', 'Timbre', 184, 'assets/timbre.png'),
               ('admin', 'Monede', 127, 'assets/monede.png'),
               ('admin', 'Cărți poștale', 32, 'assets/postale.png')");

    echo " Tabelele 'users', 'obiecte' și 'colectii' au fost create. Utilizatorul 'admin' a fost adăugat.";
} catch (PDOException $e) {
    echo " Eroare: " . $e->getMessage();
}
