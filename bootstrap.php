<?php
declare(strict_types=1);
require __DIR__ . '/vendor/autoload.php';

function validateRequest(): array {
    $url = trim((string)($_POST['url'] ?? ''));
    if ($url === '' || strlen($url) > 2048 || preg_match('/[\x00-\x1F\x7F]/', $url)) {
        throw new InvalidArgumentException('Enter a valid URL (maximum 2048 characters).');
    }
    $parts = parse_url($url);
    if ($parts === false || !in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true) || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])) {
        throw new InvalidArgumentException('Only absolute HTTP and HTTPS website URLs are supported.');
    }
    $size = (int)($_POST['size'] ?? 512);
    if (!in_array($size, [256, 512, 1024], true)) {
        throw new InvalidArgumentException('Invalid size.');
    }
    $fg = (string)($_POST['foreground'] ?? '#111827');
    $bg = (string)($_POST['background'] ?? '#ffffff');
    foreach ([$fg, $bg] as $color) {
        if (!preg_match('/^#[0-9a-fA-F]{6}$/D', $color)) {
            throw new InvalidArgumentException('Invalid color.');
        }
    }
    $transparent = ($_POST['transparent'] ?? '') === '1';
    return [$url, $size, $fg, $bg, $transparent];
}

function color(string $hex): \Endroid\QrCode\Color\Color {
    return new \Endroid\QrCode\Color\Color(hexdec(substr($hex, 1, 2)), hexdec(substr($hex, 3, 2)), hexdec(substr($hex, 5, 2)));
}

function qrResult(string $url, int $size, string $fg, string $bg, bool $transparent, bool $svg = false): \Endroid\QrCode\Writer\Result\ResultInterface {
    $qr = new \Endroid\QrCode\QrCode(
        data: $url,
        errorCorrectionLevel: \Endroid\QrCode\ErrorCorrectionLevel::High,
        size: $size,
        margin: max(12, (int)round($size * 0.045)),
        foregroundColor: color($fg),
        backgroundColor: color($bg),
        roundBlockSizeMode: \Endroid\QrCode\RoundBlockSizeMode::Margin,
    );
    $writer = $svg ? new \Endroid\QrCode\Writer\SvgWriter() : new \Endroid\QrCode\Writer\PngWriter();
//    return $writer->write($qr, $svg ? [] : [\Endroid\QrCode\Writer\PngWriter::WRITER_OPTION_NUMBER_OF_COLORS => 0]);
//    return $writer->write($qr,null,null,
//    $svg ? [] : [\Endroid\QrCode\Writer\PngWriter::WRITER_OPTION_NUMBER_OF_COLORS => 0]
    return $writer->write($qr);
}

function jpegFromPng(string $png): string {
    $source = imagecreatefromstring($png);
    if (!$source) throw new RuntimeException('Could not create JPEG.');
    $image = imagecreatetruecolor(imagesx($source), imagesy($source));
    imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
    imagecopy($image, $source, 0, 0, 0, 0, imagesx($source), imagesy($source));
    ob_start(); imagejpeg($image, null, 95); $jpeg = ob_get_clean();
    imagedestroy($image); imagedestroy($source);
    if (!is_string($jpeg)) throw new RuntimeException('JPEG encoding failed.');
    return $jpeg;
}
