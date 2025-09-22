<?php

require '../../database.php';
require '../../src/models/Client.php';
require '../../src/models/Audit.php';

$clientModel = new Client(Database::getInstance()->getConnection());

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch($action) {
        case 'add':
            $success = $clientModel->create($_POST);
            if ($success !== false) {
                $details = [
                    'id' => $success,
                    'after' => $_POST,
                ];
                Audit::log(Database::getInstance()->getConnection(), 'create', 'client', (int)$success, $details);
            }
            echo json_encode(['success' => $success !== false, 'id' => $success]);
            break;

        case 'edit':
            $before = $clientModel->getById((int)($_POST['id'] ?? 0));
            $success = $clientModel->update($_POST);
            if ($success) {
                $after = $clientModel->getById((int)($_POST['id'] ?? 0));
                Audit::log(Database::getInstance()->getConnection(), 'update', 'client', (int)($_POST['id'] ?? 0), [
                    'before' => $before,
                    'after' => $after,
                ]);
            }
            echo json_encode(['success' => $success]);
            break;

        case 'delete':
            $id = (int)($_POST['id'] ?? 0);
            $before = $clientModel->getById($id);
            $success = $clientModel->delete($id);
            if ($success) {
                Audit::log(Database::getInstance()->getConnection(), 'delete', 'client', $id, [ 'before' => $before ]);
            }
            echo json_encode(['success' => $success]);
            break;

        case 'search':
            $term = $_POST['term'] ?? '';
            $results = $clientModel->search($term);
            echo json_encode(['success' => true, 'results' => $results]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Ação inválida']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['id'])) {
        $client = $clientModel->getById($_GET['id']);
        echo json_encode($client);
    } else {
        $clients = $clientModel->getAll();
        echo json_encode($clients);
    }
    exit;
}
