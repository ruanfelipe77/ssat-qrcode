<?php

require '../../database.php';
require '../../src/models/ProductionBatch.php';
require '../../src/models/Audit.php';

$batchModel = new ProductionBatch(Database::getInstance()->getConnection());

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Debug log
    error_log("BatchController POST - Action: " . $action);
    error_log("BatchController POST - Data: " . print_r($_POST, true));
    
    switch($action) {
        case 'create_batch':
            $result = $batchModel->create($_POST);
            error_log("BatchController create_batch result: " . print_r($result, true));
            if (!empty($result['success'])) {
                $batchId = (int)($result['batch_id'] ?? 0);
                // Buscar produtos criados para este lote
                $prods = $batchModel->getProducts($batchId);
                $productList = array_map(function($p){
                    return [
                        'id' => (int)$p['id'],
                        'serial_number' => $p['serial_number'] ?? null,
                        'tipo_name' => $p['tipo_name'] ?? null,
                        'warranty' => $p['warranty'] ?? null,
                    ];
                }, $prods ?: []);
                Audit::log(Database::getInstance()->getConnection(), 'create', 'batch', $batchId, [
                    'batch_number' => $result['batch_number'] ?? null,
                    'payload' => $_POST,
                    'products' => $productList,
                ]);
            }
            echo json_encode($result);
            break;

        case 'delete_batch':
            $id = (int)($_POST['id'] ?? 0);
            // Coletar contexto do lote e produtos
            $batch = $batchModel->getById($id);
            $products = $batchModel->getProducts($id);
            $result = $batchModel->delete($id);
            if (!empty($result['success'])) {
                Audit::log(Database::getInstance()->getConnection(), 'delete', 'batch', $id, [
                    'batch_number' => $batch['batch_number'] ?? null,
                    'product_ids' => array_map(fn($p)=>$p['id'], $products ?? []),
                ]);
            }
            echo json_encode($result);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Ação inválida']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    if ($action === 'get_next_batch_number') {
        $nextNumber = $batchModel->getNextBatchNumber();
        echo json_encode(['success' => true, 'next_number' => $nextNumber]);
        exit;
    }
    if ($action === 'get_next_serial_start') {
        $tipoId = isset($_GET['tipo_id']) ? intval($_GET['tipo_id']) : 0;
        if ($tipoId <= 0) {
            echo json_encode(['success' => false, 'message' => 'tipo_id inválido']);
            exit;
        }
        if (method_exists($batchModel, 'getNextSerialStart')) {
            $next = $batchModel->getNextSerialStart($tipoId);
            echo json_encode(['success' => true, 'next_start' => $next]);
            exit;
        }
        echo json_encode(['success' => false, 'message' => 'Função indisponível']);
        exit;
    }
    
    if (isset($_GET['id'])) {
        if (isset($_GET['products'])) {
            $products = $batchModel->getProducts($_GET['id']);
            echo json_encode($products);
        } else {
            $batch = $batchModel->getById($_GET['id']);
            echo json_encode($batch);
        }
    } else {
        $batches = $batchModel->getAll();
        echo json_encode($batches);
    }
    exit;
}
