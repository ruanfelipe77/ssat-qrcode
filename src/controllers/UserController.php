<?php
require_once '../../database.php';
require_once '../../src/models/User.php';
require_once '../../src/models/Audit.php';

$db = Database::getInstance()->getConnection();
$userModel = new User($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    switch ($action) {
        case 'add':
            $res = $userModel->create($_POST);
            if (!empty($res['success'])) {
                $id = (int)($res['id'] ?? 0);
                $after = $userModel->getById($id);
                if (isset($after['password_hash'])) { $after['password_hash'] = '***'; }
                Audit::log($db, 'create', 'user', $id, [ 'after' => $after ]);
            }
            echo json_encode($res);
            break;
        case 'edit':
            $id = (int)($_POST['id'] ?? 0);
            $before = $userModel->getById($id);
            if (isset($before['password_hash'])) { $before['password_hash'] = '***'; }
            $res = $userModel->update($_POST);
            if (!empty($res['success']) || $res === true) {
                $after = $userModel->getById($id);
                if (isset($after['password_hash'])) { $after['password_hash'] = '***'; }
                Audit::log($db, 'update', 'user', $id, [ 'before' => $before, 'after' => $after ]);
            }
            echo json_encode($res);
            break;
        case 'delete':
            $id = (int)($_POST['id'] ?? 0);
            $before = $userModel->getById($id);
            if (isset($before['password_hash'])) { $before['password_hash'] = '***'; }
            $ok = $userModel->delete($id);
            if ($ok) {
                Audit::log($db, 'delete', 'user', $id, [ 'before' => $before ]);
            }
            echo json_encode(['success' => (bool)$ok]);
            break;
        case 'toggle_active':
            $id = (int)($_POST['id'] ?? 0);
            $active = (int)($_POST['active'] ?? 1);
            $before = $userModel->getById($id);
            $ok = $userModel->toggleActive($id, $active);
            if ($ok) {
                $after = $userModel->getById($id);
                Audit::log($db, 'update', 'user', $id, [ 'before' => $before, 'after' => $after, 'field' => 'active' ]);
            }
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
