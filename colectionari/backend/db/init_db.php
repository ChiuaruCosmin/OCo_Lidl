<?php
$dbFolder = __DIR__;
$dbPath = $dbFolder . '/database.sqlite';

if (!is_writable($dbFolder)) {
    die("Folderul NU este scriabil: " . $dbFolder);
}

try {
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $db->exec("DROP TABLE IF EXISTS tranzactii_colectii");
    $db->exec("DROP TABLE IF EXISTS oferte_colectii");
    $db->exec("DROP TABLE IF EXISTS tranzactii");
    $db->exec("DROP TABLE IF EXISTS oferte");
    $db->exec("DROP TABLE IF EXISTS obiecte");
    $db->exec("DROP TABLE IF EXISTS colectii");
    $db->exec("DROP TABLE IF EXISTS users");
    $db->exec("DROP TABLE IF EXISTS probleme");

    $db->exec("CREATE TABLE users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        email TEXT,
        image_url TEXT,
        admin INTEGER DEFAULT 0
    )");

    $hashedPassword = password_hash("admin", PASSWORD_DEFAULT);

    $stmt = $db->prepare("INSERT OR IGNORE INTO users (username, password, email, admin)
                          VALUES (:u, :p, :e, 1)");
    $stmt->execute([
        ':u' => 'admin',
        ':p' => $hashedPassword,
        ':e' => 'admin@example.com'
    ]);

    $db->exec("CREATE TABLE colectii (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user TEXT NOT NULL,
        titlu TEXT NOT NULL,
        nr_obiecte INTEGER DEFAULT 0,
        imagine TEXT DEFAULT 'assets/default.png',
        tip INTEGER DEFAULT 0,
        pret REAL,
        data_adaugare TEXT DEFAULT (datetime('now'))
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

    $db->exec("CREATE TABLE oferte_colectii (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        id_colectie INTEGER NOT NULL,
        user TEXT NOT NULL,
        pret REAL NOT NULL,
        contract TEXT,
        adresa TEXT,
        status TEXT DEFAULT 'in_asteptare',
        data TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_colectie) REFERENCES colectii(id)
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

    $db->exec("CREATE TABLE tranzactii_colectii (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        id_colectie INTEGER,
        titlu TEXT,
        imagine TEXT,
        ofertant TEXT,
        proprietar TEXT,
        pret REAL,
        contract TEXT,
        adresa TEXT,
        status TEXT DEFAULT 'necunoscut',
        data TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_colectie) REFERENCES colectii(id)
    )");

    $db->exec("CREATE TABLE probleme (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user TEXT NOT NULL,
        mesaj TEXT NOT NULL,
        data TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status TEXT DEFAULT 'nouă'
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS admin_actiuni (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        admin_user TEXT NOT NULL,
        actiune TEXT NOT NULL,
        data TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );");

    echo "Toate tabelele au fost recreate cu succes.";
} catch (PDOException $e) {
    echo "Eroare: " . $e->getMessage();
}
