<?php
require_once '../../database.php';
require_once '../../src/models/User.php';

$db = Database::getInstance()->getConnection();
$userModel = new User($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    switch ($action) {
        case 'add':
            echo json_encode($userModel->create($_POST));
            break;
        case 'edit':
            echo json_encode($userModel->update($_POST));
            break;
        case 'delete':
            $id = (int)($_POST['id'] ?? 0);
            $ok = $userModel->delete($id);
            echo json_encode(['success' => (bool)$ok]);
            break;
        case 'toggle_active':
            $id = (int)($_POST['id'] ?? 0);
            $active = (int)($_POST['active'] ?? 1);
            $ok = $userModel->toggleActive($id, $active);
            echo json_encode(['success' => (bool)$ok]);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Ação inválida']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['id'])) {
        echo json_encode($userModel->getById((int)$_GET['id']));
    } else {
        echo json_encode($userModel->getAll());
    }
    exit;
}
