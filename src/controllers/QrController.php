<?php
require_once '../../vendor/autoload.php';
require_once '../../database.php';
require_once '../../src/models/Product.php';

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelLow;

// Parameters: id (required), s (size, optional)
$id = isset($_GET['id']) ? trim($_GET['id']) : null;
$size = isset($_GET['s']) ? (int)$_GET['s'] : 300;
if ($size < 100) { $size = 100; }
if ($size > 1000) { $size = 1000; }

if (!$id) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Missing parameter: id']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    $productModel = new Product($db);
    $product = $productModel->getById($id);

    if (!$product) {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Product not found']);
        exit;
    }

    // Build public page URL: qrcode.php?id=...
    // Prefer APP_PUBLIC_URL; otherwise auto-detect scheme/host from request.
    $pagePath = 'qrcode.php?id=' . urlencode($id);
    $baseUrl = getenv('APP_PUBLIC_URL');
    if (!$baseUrl) {
        // Try to infer from current request
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                   || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
        if (!empty($_SERVER['HTTP_HOST'])) {
            $scheme = $isHttps ? 'https' : 'http';
            $baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'];
        }
    }
    // Final fallback: usar domínio informado caso não detecte nada
    if (!$baseUrl) {
        $baseUrl = 'https://suporte.centralssat.com.br';
    }
    $qrData = $baseUrl ? rtrim($baseUrl, '/') . '/' . $pagePath : $pagePath;

    // Debug helper: when ?debug=1, return JSON with computed values
    if (isset($_GET['debug'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'baseUrl' => $baseUrl,
            'pagePath' => $pagePath,
            'qrData' => $qrData,
            'server' => [
                'HTTP_HOST' => $_SERVER['HTTP_HOST'] ?? null,
                'HTTPS' => $_SERVER['HTTPS'] ?? null,
                'HTTP_X_FORWARDED_PROTO' => $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? null,
            ],
        ]);
        exit;
    }

    $qr = QrCode::create($qrData)
        ->setEncoding(new Encoding('UTF-8'))
        ->setErrorCorrectionLevel(new ErrorCorrectionLevelLow())
        ->setSize($size)
        ->setMargin(10);

    // Tentar gerar PNG (requer GD). Se falhar, gerar SVG como fallback.
    try {
        $pngWriter = new PngWriter();
        $result = $pngWriter->write($qr);
        header('Content-Type: ' . $result->getMimeType());
        // Cache leve (1 hora) para evitar reprocessar em impressão múltipla
        header('Cache-Control: public, max-age=3600');
        echo $result->getString();
        exit;
    } catch (Throwable $e) {
        // Fallback SVG (não requer GD)
        $svgWriter = new SvgWriter();
        $result = $svgWriter->write($qr);
        header('Content-Type: ' . $result->getMimeType());
        header('Cache-Control: public, max-age=3600');
        echo $result->getString();
        exit;
    }
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Internal error generating QR', 'message' => $e->getMessage()]);
    exit;
}
