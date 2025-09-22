<?php

require '../../database.php';
require '../../src/models/ProductStatus.php';
require '../../src/models/Audit.php';

$statusModel = new ProductStatus(Database::getInstance()->getConnection());

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch($action) {
        case 'create':
            $result = $statusModel->create($_POST);
            if (!empty($result['success'])) {
                Audit::log(Database::getInstance()->getConnection(), 'create', 'status', 0, [ 'after' => $_POST ]);
            }
            echo json_encode($result);
            break;

        case 'edit':
            $before = $statusModel->getById((int)($_POST['id'] ?? 0));
            $result = $statusModel->update($_POST);
            if (!empty($result['success']) || $result === true) {
                $after = $statusModel->getById((int)($_POST['id'] ?? 0));
                Audit::log(Database::getInstance()->getConnection(), 'update', 'status', (int)($_POST['id'] ?? 0), [ 'before' => $before, 'after' => $after ]);
            }
            echo json_encode($result);
            break;

        case 'delete':
            $id = (int)($_POST['id'] ?? 0);
            $before = $statusModel->getById($id);
            $result = $statusModel->delete($id);
            if (!empty($result['success']) || $result === true) {
                Audit::log(Database::getInstance()->getConnection(), 'delete', 'status', $id, [ 'before' => $before ]);
            }
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
