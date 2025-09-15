<?php

require '../../database.php';
require '../../src/models/ProductionBatch.php';

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
            echo json_encode($result);
            break;

        case 'delete_batch':
            $id = $_POST['id'] ?? '';
            $result = $batchModel->delete($id);
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
