<?php
class SimplePDF {
    private $pages = [];
    private $currentPage = '';
    private $font = 'Helvetica';
    private $fontSize = 12;
    private $x = 0;
    private $y = 0;
    private $pageWidth = 612;
    private $pageHeight = 792;
    private $margin = 36;

    public function AddPage() {
        if ($this->currentPage !== '') {
            $this->pages[] = $this->currentPage;
        }
        $this->currentPage = '';
        $this->x = $this->margin;
        $this->y = $this->margin + 50;
    }

    public function SetFont($font, $style = '', $size = 12) {
        $this->font = $font;
        $this->fontSize = $size;
    }

    public function Cell($w, $h, $txt, $border = 0, $ln = 0, $align = 'L') {
        $txt = str_replace(['(', ')', '\\', '<', '>', '[', ']', '{', '}', '/'], 
                          ['\\(', '\\)', '\\\\', '', '', '', '', '', '', ''], $txt);
        
        if (empty($txt)) $txt = ' ';

        $this->currentPage .= "BT\n";
        $this->currentPage .= "/F1 " . $this->fontSize . " Tf\n";

        $x = $this->x;
        if ($align === 'C') {
            $x = $this->x + $w / 2 - (strlen($txt) * $this->fontSize * 0.25);
        } elseif ($align === 'R') {
            $x = $this->x + $w - (strlen($txt) * $this->fontSize * 0.5);
        }

        $y = $this->pageHeight - $this->y;
        $this->currentPage .= sprintf("%.2f %.2f Td\n", $x, $y);
        $this->currentPage .= "(" . $txt . ") Tj\n";
        $this->currentPage .= "ET\n";

        if ($ln === 1) {
            $this->x = $this->margin;
            $this->y += $h;
        } else {
            $this->x += $w;
        }
    }

    public function MultiCell($w, $h, $txt) {
        $txt = str_replace(['(', ')', '\\', '<', '>', '[', ']', '{', '}', '/'], 
                          ['\\(', '\\)', '\\\\', '', '', '', '', '', '', ''], $txt);
        
        $words = explode(' ', $txt);
        $line = '';
        
        foreach ($words as $word) {
            $testLine = $line . ' ' . $word;
            if (strlen($testLine) * $this->fontSize * 0.5 > $w && strlen($line) > 0) {
                $this->Cell($w, $h, trim($line), 0, 1);
                $line = $word;
            } else {
                $line = $testLine;
            }
        }
        if (strlen(trim($line)) > 0) {
            $this->Cell($w, $h, trim($line), 0, 1);
        }
    }

    public function Ln($h = null) {
        $this->x = $this->margin;
        $this->y += $h ?: $this->fontSize + 2;
    }

    public function Line($x1, $y1, $x2, $y2) {
        $this->currentPage .= sprintf("%.2f %.2f m %.2f %.2f l S\n", 
            $x1, $this->pageHeight - $y1, $x2, $this->pageHeight - $y2);
    }

    public function Rect($x, $y, $w, $h, $style = 'S') {
        $this->currentPage .= sprintf("%.2f %.2f %.2f %.2f re %s\n",
            $x, $this->pageHeight - $y - $h, $w, $h, $style);
    }

    public function Output($filename) {
        if ($this->currentPage !== '') {
            $this->pages[] = $this->currentPage;
        }

        $pdf = "%PDF-1.4\n%\xe2\xe3\xcf\xd3\n";

        $objects = [];
        $objects[] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj";
        $objects[] = "2 0 obj\n<< /Type /Pages /Kids [" . implode(' ', array_map(function($i) { return ($i + 3) . " 0 R"; }, range(0, count($this->pages) - 1))) . "] /Count " . count($this->pages) . " >>\nendobj";

        // Font object
        $objects[] = "3 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>\nendobj";

        foreach ($this->pages as $i => $pageContent) {
            $pageObj = ($i + 4) . " 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 " . $this->pageWidth . " " . $this->pageHeight . "] /Contents " . ($i + 4 + count($this->pages)) . " 0 R /Resources << /Font << /F1 3 0 R >> >> >>\nendobj";
            $objects[] = $pageObj;
        }

        foreach ($this->pages as $i => $pageContent) {
            $contentObj = ($i + 4 + count($this->pages)) . " 0 obj\n<< /Length " . strlen($pageContent) . " >>\nstream\n" . $pageContent . "\nendstream\nendobj";
            $objects[] = $contentObj;
        }

        $offsets = [];
        $offset = strlen($pdf);
        foreach ($objects as $obj) {
            $offsets[] = $offset;
            $pdf .= $obj . "\n";
            $offset = strlen($pdf);
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n";
        $pdf .= "0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        foreach ($offsets as $off) {
            $pdf .= sprintf("%010d 00000 n \n", $off);
        }

        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . $xrefOffset . "\n%%EOF";

        file_put_contents($filename, $pdf);
    }
}
