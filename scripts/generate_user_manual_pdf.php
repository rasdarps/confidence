<?php

require __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$root = realpath(__DIR__ . '/..');
$manualPath = $root . DIRECTORY_SEPARATOR . 'USER_MANUAL.md';
$outputPath = $root . DIRECTORY_SEPARATOR . 'DarpPOS_Credit_User_Manual.pdf';

if (! file_exists($manualPath)) {
    fwrite(STDERR, "Manual not found: {$manualPath}" . PHP_EOL);
    exit(1);
}

$screenshots = [
    '2.1 Logging In' => [
        ['title' => 'Login screen', 'path' => $root . '/public/images/manual-desktop-login.png'],
    ],
    '2.2 Dashboard and Insight' => [
        ['title' => 'Dashboard overview', 'path' => $root . '/public/images/manual-desktop-dashboard.png'],
    ],
    '2.3 Insight Page' => [
        ['title' => 'Insight analytics screen', 'path' => $root . '/public/images/manual-desktop-insight.png'],
    ],
    '5.7 Products' => [
        ['title' => 'Create product screen', 'path' => $root . '/public/images/manual-desktop-create-product.png'],
    ],
    '6.1 Creating a Sale' => [
        ['title' => 'Create sale screen', 'path' => $root . '/public/images/manual-desktop-create-sale.png'],
    ],
    '7.1 Creating a Purchase' => [
        ['title' => 'Create purchase screen', 'path' => $root . '/public/images/manual-desktop-create-purchase.png'],
    ],
    '14.1 Transaction Reports' => [
        ['title' => 'Profit report screen', 'path' => $root . '/public/images/manual-desktop-profit-report.png'],
    ],
    '16. System Settings' => [
        ['title' => 'System settings screen', 'path' => $root . '/public/images/manual-desktop-settings.png'],
    ],
    '17.1 Users' => [
        ['title' => 'User management screen', 'path' => $root . '/public/images/manual-desktop-users.png'],
    ],
];

function inlineMarkdown(string $text): string
{
    $text = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $text = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $text);
    return $text;
}

function imageDataUri(string $path): ?string
{
    if (! file_exists($path)) {
        return null;
    }

    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $mime = match ($extension) {
        'jpg', 'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        default => 'application/octet-stream',
    };

    return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
}

function screenshotHtml(array $items): string
{
    $html = '';

    foreach ($items as $item) {
        $uri = imageDataUri($item['path']);
        if (! $uri) {
            continue;
        }

        $title = inlineMarkdown($item['title']);
        $html .= '<figure class="screenshot">';
        $html .= '<img src="' . $uri . '" alt="' . $title . '">';
        $html .= '<figcaption>' . $title . '</figcaption>';
        $html .= '</figure>';
    }

    return $html;
}

function closeLists(string &$html, ?string &$listType): void
{
    if ($listType !== null) {
        $html .= '</' . $listType . '>';
        $listType = null;
    }
}

$markdown = str_replace(["\r\n", "\r"], "\n", file_get_contents($manualPath));
$lines = explode("\n", $markdown);
$body = '';
$listType = null;

foreach ($lines as $line) {
    $trimmed = trim($line);

    if ($trimmed === '') {
        closeLists($body, $listType);
        continue;
    }

    if (preg_match('/^(#{1,6})\s+(.*)$/', $trimmed, $matches)) {
        closeLists($body, $listType);
        $level = strlen($matches[1]);
        $heading = $matches[2];
        $body .= '<h' . $level . '>' . inlineMarkdown($heading) . '</h' . $level . '>';
        if (isset($screenshots[$heading])) {
            $body .= screenshotHtml($screenshots[$heading]);
        }
        continue;
    }

    if (preg_match('/^-\s+(.*)$/', $trimmed, $matches)) {
        if ($listType !== 'ul') {
            closeLists($body, $listType);
            $body .= '<ul>';
            $listType = 'ul';
        }
        $body .= '<li>' . inlineMarkdown($matches[1]) . '</li>';
        continue;
    }

    if (preg_match('/^\d+\.\s+(.*)$/', $trimmed, $matches)) {
        if ($listType !== 'ol') {
            closeLists($body, $listType);
            $body .= '<ol>';
            $listType = 'ol';
        }
        $body .= '<li>' . inlineMarkdown($matches[1]) . '</li>';
        continue;
    }

    closeLists($body, $listType);
    $body .= '<p>' . inlineMarkdown($trimmed) . '</p>';
}

closeLists($body, $listType);

$html = '<!doctype html>
<html>
<head>
<meta charset="utf-8">
<style>
    @page { margin: 34px 38px 42px; }
    body { font-family: DejaVu Sans, Arial, sans-serif; color: #172033; font-size: 12px; line-height: 1.45; }
    h1 { color: #0f172a; font-size: 27px; margin: 0 0 12px; padding-bottom: 10px; border-bottom: 3px solid #0f766e; }
    h2 { color: #0f766e; font-size: 18px; margin: 22px 0 8px; page-break-after: avoid; }
    h3 { color: #1f2937; font-size: 14px; margin: 16px 0 6px; page-break-after: avoid; }
    p { margin: 0 0 8px; }
    ul, ol { margin: 4px 0 10px 22px; padding: 0; }
    li { margin: 3px 0; }
    strong { color: #111827; }
    .screenshot { margin: 10px 0 16px; padding: 8px; border: 1px solid #d8dee8; background: #f8fafc; page-break-inside: avoid; }
    .screenshot img { width: 100%; max-height: 430px; object-fit: contain; border: 1px solid #cbd5e1; }
    .screenshot figcaption { color: #475569; font-size: 10px; margin-top: 5px; text-align: center; }
</style>
</head>
<body>' . $body . '</body>
</html>';

$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);
$dompdf->setPaper('A4', 'portrait');
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->render();

file_put_contents($outputPath, $dompdf->output());

echo 'Generated: ' . $outputPath . PHP_EOL;
