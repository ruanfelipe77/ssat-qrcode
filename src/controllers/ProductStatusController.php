<?php

require '../../database.php';
require '../../src/models/ProductStatus.php';

$statusModel = new ProductStatus(Database::getInstance()->getConnection());

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch($action) {
        case 'create':
            $result = $statusModel->create($_POST);
            echo json_encode($result);
            break;

        case 'edit':
            $result = $statusModel->update($_POST);
            echo json_encode($result);
            break;

        case 'delete':
            $id = $_POST['id'] ?? '';
            $result = $statusModel->delete($id);
            echo json_encode($result);
            break;

        case 'search':
            $term = $_POST['term'] ?? '';
            $results = $statusModel->search($term);
            echo json_encode(['success' => true, 'results' => $results]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Ação inválida']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['id'])) {
        $status = $statusModel->getById($_GET['id']);
        echo json_encode($status);
    } elseif (isset($_GET['active'])) {
        $statuses = $statusModel->getActive();
        echo json_encode($statuses);
    } else {
        $statuses = $statusModel->getAll();
        echo json_encode($statuses);
    }
    exit;
}
