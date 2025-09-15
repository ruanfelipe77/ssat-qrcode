<?php

require '../../database.php';
require '../../src/models/ProductionBatch.php';

$batchModel = new ProductionBatch(Database::getInstance()->getConnection());

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch($action) {
        case 'create_batch':
            $result = $batchModel->create($_POST);
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
