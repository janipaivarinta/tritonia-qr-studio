<?php
declare(strict_types=1);
try {
    require __DIR__ . '/bootstrap.php';
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method not allowed'); }
    [$url, $size, $fg, $bg, $transparent] = validateRequest();
    header('Content-Type: image/png');
    header('Cache-Control: no-store');
    echo qrResult($url, $size, $fg, $bg, $transparent)->getString();
} catch (Throwable $e) {
    http_response_code($e instanceof InvalidArgumentException ? 400 : 500);
    header('Content-Type: text/plain; charset=utf-8');
    echo $e instanceof InvalidArgumentException ? $e->getMessage() : 'Unable to generate QR code.';
}
