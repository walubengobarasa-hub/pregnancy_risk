<?php

class SimplePdf {
  public static function fromLines(array $lines): string {
    $safeLines = [];
    foreach ($lines as $line) {
      $line = str_replace(["\\", "(", ")", "\r"], ["\\\\", "\\(", "\\)", ''], (string)$line);
      $safeLines[] = substr($line, 0, 110);
    }

    $content = "BT\n/F1 10 Tf\n50 790 Td\n14 TL\n";
    foreach ($safeLines as $idx => $line) {
      if ($idx > 0) $content .= "T*\n";
      $content .= '(' . $line . ") Tj\n";
    }
    $content .= "ET";

    $objects = [];
    $objects[] = '1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj';
    $objects[] = '2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj';
    $objects[] = '3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >> endobj';
    $objects[] = '4 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj';
    $objects[] = '5 0 obj << /Length ' . strlen($content) . ' >> stream' . "\n" . $content . "\nendstream endobj";

    $pdf = "%PDF-1.4\n";
    $offsets = [0];
    foreach ($objects as $obj) {
      $offsets[] = strlen($pdf);
      $pdf .= $obj . "\n";
    }

    $xref = strlen($pdf);
    $pdf .= 'xref' . "\n";
    $pdf .= '0 ' . (count($objects) + 1) . "\n";
    $pdf .= sprintf("%010d %05d f \n", 0, 65535);
    for ($i = 1; $i <= count($objects); $i++) {
      $pdf .= sprintf("%010d %05d n \n", $offsets[$i], 0);
    }
    $pdf .= 'trailer << /Size ' . (count($objects) + 1) . ' /Root 1 0 R >>' . "\n";
    $pdf .= 'startxref' . "\n" . $xref . "\n%%EOF";
    return $pdf;
  }
}
