    OCo – Object Collector Web App

OCo este o aplicație Web destinată colecționarilor de obiecte de interes (dopuri de plută, mărci poștale, viniluri etc.). Utilizatorii autentificați își pot crea, organiza și partaja colecțiile, pot pune obiecte la vânzare, face oferte, accesa statistici și exporta datele în format CSV sau PDF.

    Tehnologii utilizate

Frontend: HTML, CSS, JavaScript (Vanilla)
Backend: PHP
Bază de date: SQLite
Stocare fișiere: local (imagini salvate în `assets/uploads`)
Server local: XAMPP (Apache + PHP)

    Cum rulez local

1) Instalează XAMPP
2) Clonează sau descarcă proiectul în folderul `htdocs`
3) Pornește Apache din XAMPP Control Panel
4) Accesează în browser: http://localhost/colectionari/landing.php

    Autentificare

Accesul la funcționalitățile aplicației este disponibil doar utilizatorilor autentificați. Utilizatorii pot:
- Crea colecții și obiecte
- Vizualiza colecțiile altor utilizatori
- Pune obiecte la vânzare și face oferte
- Vizualiza istoricul tranzacțiilor
- Exporta statistici în CSV / PDF

    Structura proiectului

/backend/
api/ -> endpoint-uri PHP
dashboard.php -> pagină principală după login
/assets/
uploads/ -> fișiere imagine (obiecte, profil)
/js/, /css/, /html/ -> interfață frontend
/LICENSE
/README.md

    Arhitectură

Aplicația este structurată monolitic și a fost documentată cu ajutorul Modelului C4 (niveluri C1–C4).  
Vezi diagramele în folderul `/diagrams`.

    Licență

Acest proiect este publicat sub licență [MIT](./LICENSE)  
Toate resursele utilizate respectă termenii Creative Commons sau licențe libere.

Echipa Lidl (Albert Andreea, Chiuaru Cosmin)
Proiect educațional realizat în cadrul laboratorului de Tehnologii Web, coordonat de Lect. Dr. Captarencu Oana.
