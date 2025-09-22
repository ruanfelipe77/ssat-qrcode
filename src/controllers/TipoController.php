<?php

require '../../database.php';
require '../../src/models/Tipo.php';
require '../../src/models/Audit.php';

$tipoModel = new Tipo(Database::getInstance()->getConnection());

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action_tipo'];
    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $before = $tipoModel->getById($id);
        $success = $tipoModel->delete($id);
        if ($success) {
            Audit::log(Database::getInstance()->getConnection(), 'delete', 'tipo', $id, [ 'before' => $before ]);
        }
        echo json_encode(['success' => $success]);
        exit;
    } elseif ($action === 'edit') {
        $before = $tipoModel->getById((int)($_POST['id'] ?? 0));
        $success = $tipoModel->update($_POST);
        if ($success) {
            $after = $tipoModel->getById((int)($_POST['id'] ?? 0));
            Audit::log(Database::getInstance()->getConnection(), 'update', 'tipo', (int)($_POST['id'] ?? 0), [ 'before' => $before, 'after' => $after ]);
        }
        echo json_encode(['success' => $success]);
        exit;
    } elseif ($action === 'add') {
        $success = $tipoModel->create($_POST);
        if ($success) {
            Audit::log(Database::getInstance()->getConnection(), 'create', 'tipo', 0, [ 'after' => $_POST ]);
        }
        echo json_encode(['success' => $success]);
        exit;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $tipo = $tipoModel->getById($id);
    echo json_encode($tipo);
    exit;
}
