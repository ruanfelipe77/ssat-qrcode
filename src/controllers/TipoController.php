<?php

require '../../database.php';
require '../../src/models/Tipo.php';

$tipoModel = new Tipo(Database::getInstance()->getConnection());

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action_tipo'];
    if ($action === 'delete') {
        $id = $_POST['id'];
        $success = $tipoModel->delete($id);
        echo json_encode(['success' => $success]);
        exit;
    } elseif ($action === 'edit') {
        $success = $tipoModel->update($_POST);
        echo json_encode(['success' => $success]);
        exit;
    } elseif ($action === 'add') {
        $success = $tipoModel->create($_POST);
        echo json_encode(['success' => $success]);
        exit;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $tipo = $tipoModel->getById($id);
    echo json_encode($tipo);
    exit;
}
