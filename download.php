<?php
declare(strict_types=1);
try {
    require __DIR__ . '/bootstrap.php';
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method not allowed'); }
    [$url, $size, $fg, $bg, $transparent] = validateRequest();
    $format = strtolower((string)($_POST['format'] ?? 'png'));
    if (!in_array($format, ['png', 'jpeg', 'pdf', 'svg'], true)) throw new InvalidArgumentException('Invalid format.');
    $svg = $format === 'svg';
    $bytes = qrResult($url, $size, $fg, $bg, $transparent, $svg)->getString();
    if ($format === 'jpeg') $bytes = jpegFromPng($bytes);
    if ($format === 'pdf') {
        $base64 = base64_encode($bytes);
        $html = '<!doctype html><html><head><meta charset="utf-8"><style>@page { margin: 20mm; } body { text-align: center; } img { width: 150mm; height: 150mm; }</style></head><body><img alt="QR code" src="data:image/png;base64,' . $base64 . '"></body></html>';
        $pdf = new \Dompdf\Dompdf(['isRemoteEnabled' => false]);
        $pdf->loadHtml($html);
        $pdf->setPaper('a4');
        $pdf->render();
        $bytes = $pdf->output();
    }
    $mime = ['png' => 'image/png', 'jpeg' => 'image/jpeg', 'pdf' => 'application/pdf', 'svg' => 'image/svg+xml'][$format];
    header('Content-Type: ' . $mime);
    header('Content-Disposition: attachment; filename="qrcode-' . $size . '.' . ($format === 'jpeg' ? 'jpg' : $format) . '"');
    header('Content-Length: ' . strlen($bytes));
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    echo $bytes;
} catch (Throwable $e) {
    http_response_code($e instanceof InvalidArgumentException ? 400 : 500);
    header('Content-Type: text/plain; charset=utf-8');
    echo $e instanceof InvalidArgumentException ? $e->getMessage() : 'Unable to create download.';
}
