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

function pdfText($text) {
    return iconv('UTF-8', 'windows-1250//TRANSLIT', $text);
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

if (!isset($_GET['id'])) {
    die("ID colecție lipsă");
}
$colectieId = (int)$_GET['id'];

try {
    $db = new PDO("sqlite:" . __DIR__ . '/../db/database.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $db->prepare("SELECT titlu, user FROM colectii WHERE id = :id");
    $stmt->execute([':id' => $colectieId]);
    $col = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$col) die("Colecție inexistentă");
    if ($col['user'] !== $username) die("Nu ai acces la această colecție");
    $titlu = $col['titlu'];

    $stmt = $db->prepare("SELECT titlu, categorie, material, valoare, tara, perioada, an FROM obiecte WHERE colectie_id = :id AND de_vanzare = 0");
    $stmt->execute([':id' => $colectieId]);
    $obiecte = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $db->prepare("SELECT SUM(valoare) FROM obiecte WHERE colectie_id = :id AND de_vanzare = 0");
    $stmt->execute([':id' => $colectieId]);
    $valoareTotala = $stmt->fetchColumn() ?: 0;

    $pdf = new PDF();
    $pdf->AddPage();
    $pdf->SetFont('helvetica','',14);
    $pdf->Cell(0, 10, pdfText("Colecție: " . $titlu), 0, 1, 'C');
    $pdf->Ln(5);

    $pdf->SetFont('helvetica','',11);
    $pdf->Cell(0, 7, pdfText("Număr obiecte: " . count($obiecte)), 0, 1);
    $pdf->Cell(0, 7, pdfText("Valoare totală: $valoareTotala lei"), 0, 1);
    $pdf->Ln(5);

    $pdf->SetFont('helvetica','',12);
    $pdf->Cell(0, 8, pdfText("Obiecte în colecție"), 0, 1);

    $header = ['Titlu', 'Categorie', 'Material', 'Valoare', 'Tara', 'Perioada', 'An'];
    $widths = [45, 30, 30, 15, 25, 30, 15];

    $pdf->SetFont('helvetica','',10);
    $x = $pdf->GetX();
    $y = $pdf->GetY();
    for ($i = 0; $i < count($header); $i++) {
        $pdf->Rect($x, $y, $widths[$i], 10);
        $pdf->MultiCell($widths[$i], 5, pdfText($header[$i]), 0, 'C');
        $x += $widths[$i];
        $pdf->SetXY($x, $y);
    }
    $pdf->SetY($y + 10);

    $pdf->SetFont('helvetica','',10);
    foreach ($obiecte as $row) {
        $line = [];
        foreach ($header as $key) {
            $val = $row[strtolower($key)] ?? '';
            $line[] = pdfText($val);
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

    $pdf->Output("D", "colectie_{$colectieId}.pdf");

} catch (PDOException $e) {
    echo "Eroare la export PDF: " . $e->getMessage();
} 