<?php

require '../../database.php';
require '../../src/models/Product.php';
require '../../src/models/Audit.php';
require_once '../../vendor/autoload.php';

$productModel = new Product(Database::getInstance()->getConnection());

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    if ($action === 'delete') {
        $id = $_POST['id'];
        // Buscar contexto rico (tipo e lote)
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT p.*, t.nome AS tipo_name, pb.batch_number FROM products p LEFT JOIN tipos t ON p.tipo_id = t.id LEFT JOIN production_batches pb ON p.production_batch_id = pb.id WHERE p.id = :id");
            $stmt->execute([':id' => $id]);
            $before = $stmt->fetch(PDO::FETCH_ASSOC) ?: $productModel->getById($id);
        } catch (Throwable $e) { $before = $productModel->getById($id); }
        $success = $productModel->delete($id);
        if ($success) {
            $filePath = '../../public/qrcodes/' . $id . '.png';
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            Audit::log(Database::getInstance()->getConnection(), 'delete', 'product', (int)$id, [
                'before' => $before,
                'serial_number' => $before['serial_number'] ?? null,
                'tipo_name' => $before['tipo_name'] ?? null,
                'batch_number' => $before['batch_number'] ?? null,
            ]);
        }
        echo json_encode(['success' => $success]);
        exit;
    } elseif ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        // Buscar contexto rico (tipo e lote)
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT p.*, t.nome AS tipo_name, pb.batch_number FROM products p LEFT JOIN tipos t ON p.tipo_id = t.id LEFT JOIN production_batches pb ON p.production_batch_id = pb.id WHERE p.id = :id");
            $stmt->execute([':id' => $id]);
            $before = $stmt->fetch(PDO::FETCH_ASSOC) ?: $productModel->getById($id);
        } catch (Throwable $e) { $before = $productModel->getById($id); }
        $success = $productModel->update($_POST);
        if ($success) {
            try {
                $stmt = $db->prepare("SELECT p.*, t.nome AS tipo_name, pb.batch_number FROM products p LEFT JOIN tipos t ON p.tipo_id = t.id LEFT JOIN production_batches pb ON p.production_batch_id = pb.id WHERE p.id = :id");
                $stmt->execute([':id' => $id]);
                $after = $stmt->fetch(PDO::FETCH_ASSOC) ?: $productModel->getById($id);
            } catch (Throwable $e) { $after = $productModel->getById($id); }
            Audit::log(Database::getInstance()->getConnection(), 'update', 'product', $id, [ 'before' => $before, 'after' => $after ]);
        }
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
                Audit::log(Database::getInstance()->getConnection(), 'create', 'product', (int)$id, [ 'after' => $product ]);
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

