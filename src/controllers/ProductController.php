<?php

require '../../database.php';
require '../../src/models/Product.php';
require_once '../../vendor/autoload.php';

$productModel = new Product(Database::getInstance()->getConnection());

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    if ($action === 'delete') {
        $id = $_POST['id'];
        $success = $productModel->delete($id);
        if ($success) {
            $filePath = '../../public/qrcodes/' . $id . '.png';
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
        echo json_encode(['success' => $success]);
        exit;
    } elseif ($action === 'edit') {
        $success = $productModel->update($_POST);
        echo json_encode(['success' => $success]);
        exit;
    } elseif ($action === 'add') {
        $success = $productModel->create($_POST);

        if ($success) {
            $id = $productModel->getLastInsertId();
            $product = $productModel->getById($id);

            if ($product) {
                // Padroniza payload do QR para URL com id
                $qrImageUrl = 'src/controllers/QrController.php?id=' . urlencode($id);
                $qrPageUrl = 'qrcode.php?id=' . urlencode($id);
                echo json_encode(['success' => true, 'qr_code_url' => $qrImageUrl, 'qrcode_page_url' => $qrPageUrl]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Falhou na geração do QR-code.']);
            }
        } else {
            echo json_encode(['success' => false]);
        }

        exit;
    } elseif ($action === 'get_qr_path') {
        $id = $_POST['id'];
        $product = $productModel->getById($id);
        if ($product) {
            // Retorna URLs para consumo pelo front
            $qrImageUrl = 'src/controllers/QrController.php?id=' . urlencode($id);
            $qrPageUrl = 'qrcode.php?id=' . urlencode($id);
            echo json_encode(['success' => true, 'qr_code_url' => $qrImageUrl, 'qrcode_page_url' => $qrPageUrl]);
        } else {
            echo json_encode(['success' => false]);
        }
        exit;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $mcp = $productModel->getById($id);
    echo json_encode($mcp);
    exit;
}

