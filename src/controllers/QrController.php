<?php
require_once '../../vendor/autoload.php';
require_once '../../database.php';
require_once '../../src/models/Product.php';

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
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
    // If APP_PUBLIC_URL is set, use absolute URL. Otherwise, use relative.
    $baseUrl = getenv('APP_PUBLIC_URL');
    $pagePath = 'qrcode.php?id=' . urlencode($id);
    $qrData = $baseUrl ? rtrim($baseUrl, '/') . '/' . $pagePath : $pagePath;

    $qr = QrCode::create($qrData)
        ->setEncoding(new Encoding('UTF-8'))
        ->setErrorCorrectionLevel(new ErrorCorrectionLevelLow())
        ->setSize($size)
        ->setMargin(10);

    $writer = new PngWriter();
    $result = $writer->write($qr);

    header('Content-Type: ' . $result->getMimeType());
    echo $result->getString();
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Internal error generating QR', 'message' => $e->getMessage()]);
    exit;
}
