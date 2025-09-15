<?php

require '../../database.php';
require '../../src/models/Client.php';

$clientModel = new Client(Database::getInstance()->getConnection());

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch($action) {
        case 'add':
            $success = $clientModel->create($_POST);
            echo json_encode(['success' => $success !== false, 'id' => $success]);
            break;

        case 'edit':
            $success = $clientModel->update($_POST);
            echo json_encode(['success' => $success]);
            break;

        case 'delete':
            $id = $_POST['id'] ?? '';
            $success = $clientModel->delete($id);
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
