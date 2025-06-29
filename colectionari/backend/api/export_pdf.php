<?php
require_once __DIR__ . '/../lib/jwt.php';
require_once __DIR__ . '/../lib/fpdf/fpdf.php';

$headers = function_exists('getallheaders') ? getallheaders() : [];
if (!isset($headers['Authorization']) && !isset($headers['authorization'])) {
    die("Neautentificat");
}
$authHeader = $headers['Authorization'] ?? $headers['authorization'];
if (strpos($authHeader, 'Bearer ') !== 0) {
    die("Format token invalid");
}
$token = substr($authHeader, 7);

function transliterate($text) {
    $map = [
        'ă' => 'a', 'â' => 'a', 'î' => 'i', 'ș' => 's', 'ţ' => 't',
        'ț' => 't', 'Ă' => 'A', 'Â' => 'A', 'Î' => 'I', 'Ș' => 'S', 'Ț' => 'T',
        'â€" ' => '-', '–' => '-', '"' => '"', '"' => '"', "'" => "'"
    ];
    return strtr($text, $map);
}

class PDF extends FPDF {
    function NbLines($w, $txt) {
        $cw = &$this->CurrentFont['cw'];
        if ($w == 0) $w = $this->w - $this->rMargin - $this->x;
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);
        if ($nb > 0 && $s[$nb - 1] == "\n") $nb--;
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $nl = 1;
        while ($i < $nb) {
            $c = $s[$i];
            if ($c == "\n") {
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
                continue;
            }
            if ($c == ' ') $sep = $i;
            $l += $cw[$c] ?? 0;
            if ($l > $wmax) {
                if ($sep == -1) {
                    if ($i == $j) $i++;
                } else {
                    $i = $sep + 1;
                }
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
            } else {
                $i++;
            }
        }
        return $nl;
    }
}

try {
    $payload = verifyJWT($token);
    $username = $payload['username'];
} catch (Exception $e) {
    die("Token invalid: " . $e->getMessage());
}

try {
    $db = new PDO("sqlite:" . __DIR__ . '/../db/database.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $db->prepare("SELECT o.titlu, o.categorie, o.material, o.valoare, o.tara, o.perioada, o.an FROM obiecte o JOIN colectii c ON o.colectie_id = c.id WHERE c.user = :username AND o.de_vanzare = 0");
    $stmt->execute([':username' => $username]);
    $obiecte = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $totalObiecte = count($obiecte);

    $stmt = $db->prepare("SELECT COUNT(*) FROM colectii WHERE user = :username");
    $stmt->execute([':username' => $username]);
    $totalColectii = $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT SUM(o.valoare) FROM obiecte o JOIN colectii c ON c.id = o.colectie_id WHERE c.user = :username AND o.de_vanzare = 0");
    $stmt->execute([':username' => $username]);
    $valoareTotala = $stmt->fetchColumn() ?: 0;

    $stmt = $db->prepare("SELECT o.categorie, COUNT(*) AS total FROM obiecte o JOIN colectii c ON c.id = o.colectie_id WHERE c.user = :username AND o.de_vanzare = 0 GROUP BY o.categorie ORDER BY total DESC LIMIT 3");
    $stmt->execute([':username' => $username]);
    $topCategorii = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'categorie');

    $stmt = $db->prepare("SELECT strftime('%Y-%m', o.data_adaugare) AS luna, COUNT(*) AS nr_obiecte FROM obiecte o JOIN colectii c ON o.colectie_id = c.id WHERE c.user = :username AND o.de_vanzare = 0 GROUP BY luna ORDER BY luna ASC");
    $stmt->execute([':username' => $username]);
    $evolutie = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $globalCat = $db->query("SELECT o.categorie, COUNT(*) as total FROM obiecte o JOIN colectii c ON o.colectie_id = c.id WHERE o.de_vanzare = 0 AND (c.tip = 0 OR c.tip IS NULL) GROUP BY o.categorie ORDER BY total DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

    $topUtilizator = $db->query("SELECT c.user, COUNT(*) as total FROM colectii c JOIN obiecte o ON c.id = o.colectie_id WHERE o.de_vanzare = 0 AND (c.tip = 0 OR c.tip IS NULL) GROUP BY c.user ORDER BY total DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);

    $pdf = new PDF();
    $pdf->AddPage();
    $pdf->SetFont('helvetica','',14);
    $pdf->Cell(0, 10, "Statistici Export - $username", 0, 1, 'C');
    $pdf->Ln(5);

    $pdf->SetFont('helvetica','',12);
    $pdf->Cell(0, 8, "Statistici personale", 0, 1);

    $pdf->SetFont('helvetica','',11);
    $pdf->Cell(0, 7, "Total obiecte: $totalObiecte", 0, 1);
    $pdf->Cell(0, 7, "Total colectii: $totalColectii", 0, 1);
    $pdf->Cell(0, 7, "Valoare estimata: $valoareTotala lei", 0, 1);
    $pdf->Cell(0, 7, "Top categorii: " . transliterate(implode(', ', $topCategorii)), 0, 1);
    $pdf->Ln(5);

    $pdf->SetFont('helvetica','',12);
    $pdf->Cell(0, 8, "Evolutie temporala (obiecte/luna)", 0, 1);

    $pdf->SetFont('helvetica','',11);
    foreach ($evolutie as $row) {
        $pdf->Cell(0, 6, $row['luna'] . " - " . $row['nr_obiecte'] . " obiecte", 0, 1);
    }
    $pdf->Ln(5);

    $pdf->SetFont('helvetica','',12);
    $pdf->Cell(0, 8, "Clasamente globale", 0, 1);

    $pdf->SetFont('helvetica','',11);
    $pdf->Cell(0, 7, "Top categorii globale:", 0, 1);
    foreach ($globalCat as $row) {
        $pdf->Cell(0, 6, "- " . transliterate($row['categorie']) . " (" . $row['total'] . " obiecte)", 0, 1);
    }

    $pdf->Ln(2);
    $pdf->Cell(0, 7, "Cel mai activ utilizator: " . $topUtilizator['user'] . " (" . $topUtilizator['total'] . " obiecte)", 0, 1);
    $pdf->Ln(5);

    $pdf->SetFont('helvetica','',12);
    $pdf->Cell(0, 8, "Lista obiecte", 0, 1);

    $header = ['Titlu', 'Categorie', 'Material', 'Valoare', 'Tara', 'Perioada', 'An'];
    $widths = [45, 30, 30, 15, 25, 30, 15];

    $pdf->SetFont('helvetica','',10);
    $x = $pdf->GetX();
    $y = $pdf->GetY();
    for ($i = 0; $i < count($header); $i++) {
        $pdf->Rect($x, $y, $widths[$i], 10);
        $pdf->MultiCell($widths[$i], 5, transliterate($header[$i]), 0, 'C');
        $x += $widths[$i];
        $pdf->SetXY($x, $y);
    }
    $pdf->SetY($y + 10);

    $pdf->SetFont('helvetica','',10);
    foreach ($obiecte as $row) {
        $line = [];
        foreach ($header as $key) {
            $val = $row[strtolower($key)] ?? '';
            $line[] = transliterate($val);
        }

        $xStart = $pdf->GetX();
        $yStart = $pdf->GetY();
        $heights = [];

        for ($i = 0; $i < count($line); $i++) {
            $nb = $pdf->NbLines($widths[$i], $line[$i]);
            $heights[$i] = $nb * 5;
        }

        $rowHeight = max($heights);
        $x = $pdf->GetX();
        $y = $pdf->GetY();

        for ($i = 0; $i < count($line); $i++) {
            $pdf->Rect($x, $y, $widths[$i], $rowHeight);
            $pdf->MultiCell($widths[$i], 5, $line[$i], 0, 'L');
            $x += $widths[$i];
            $pdf->SetXY($x, $y);
        }

        $pdf->SetY($y + $rowHeight);
    }

    $pdf->Output("D", "statistici_complete_{$username}.pdf");

} catch (PDOException $e) {
    echo "Eroare la export PDF: " . $e->getMessage();
} 