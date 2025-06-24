<?php
$dbPath = __DIR__ . '/db/database.sqlite';

try {
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $db->exec("DROP TABLE IF EXISTS tranzactii");
    $db->exec("DROP TABLE IF EXISTS oferte");
    $db->exec("DROP TABLE IF EXISTS obiecte");
    $db->exec("DROP TABLE IF EXISTS colectii");
    $db->exec("DROP TABLE IF EXISTS users");

    $db->exec("CREATE TABLE users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        email TEXT
    )");

    $stmt = $db->prepare("INSERT OR IGNORE INTO users (username, password, email)
                          VALUES (:u, :p, :e)");
    $stmt->execute([
        ':u' => 'admin',
        ':p' => 'admin',
        ':e' => 'admin@example.com'
    ]);

    $db->exec("CREATE TABLE colectii (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user TEXT NOT NULL,
        titlu TEXT NOT NULL,
        nr_obiecte INTEGER DEFAULT 0,
        imagine TEXT DEFAULT 'assets/default.png'
    )");

    $db->exec("CREATE TABLE obiecte (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        titlu TEXT NOT NULL,
        categorie TEXT NOT NULL,
        material TEXT,
        valoare REAL,
        tara TEXT,
        perioada TEXT,
        istoric TEXT,
        eticheta INTEGER DEFAULT 0,
        descriere TEXT,
        an INTEGER,
        imagine TEXT DEFAULT 'assets/default_obj.png',
        colectie_id INTEGER NOT NULL,
        de_vanzare INTEGER DEFAULT 0,
        pret REAL,
        proprietar TEXT,
        data_adaugare TEXT DEFAULT (datetime('now')),
        FOREIGN KEY (colectie_id) REFERENCES colectii(id)
    )");

    $db->exec("CREATE TABLE oferte (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        id_obiect INTEGER NOT NULL,
        user TEXT NOT NULL,
        pret REAL NOT NULL,
        contract TEXT,
        adresa TEXT,
        status TEXT DEFAULT 'in_asteptare',
        data TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_obiect) REFERENCES obiecte(id)
    )");

    $db->exec("CREATE TABLE tranzactii (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        id_obiect INTEGER,
        titlu TEXT,
        imagine TEXT,
        ofertant TEXT,
        proprietar TEXT,
        pret REAL,
        contract TEXT,
        adresa TEXT,
        status TEXT DEFAULT 'necunoscut',
        data TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    echo "Toate tabelele au fost recreate cu succe";
} catch (PDOException $e) {
    echo "Eroare: " . $e->getMessage();
}
