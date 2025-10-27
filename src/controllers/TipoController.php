<?php

// Limpa qualquer output anterior
if (ob_get_level()) {
    ob_clean();
}

// Habilita exibição de erros para debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require '../../database.php';
    require '../../src/models/Tipo.php';
    require '../../src/models/Audit.php';

    $tipoModel = new Tipo(Database::getInstance()->getConnection());
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Erro ao inicializar: ' . $e->getMessage()]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action_tipo'];
    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $before = $tipoModel->getById($id);
        $success = $tipoModel->delete($id);
        if ($success) {
            Audit::log(Database::getInstance()->getConnection(), 'delete', 'tipo', $id, [ 'before' => $before ]);
        }
        header('Content-Type: application/json');
        echo json_encode(['success' => $success]);
        exit;
    } elseif ($action === 'edit') {
        try {
            // Debug: log dos dados recebidos
            error_log('Dados POST recebidos: ' . print_r($_POST, true));
            
            // Validação do ID
            if (isset($_POST['tipo_id']) && (!isset($_POST['id']) || $_POST['id'] === '')) {
                $_POST['id'] = $_POST['tipo_id'];
            }
            if (empty($_POST['id']) || !is_numeric($_POST['id'])) {
                throw new Exception('ID inválido ou não fornecido');
            }
            
            $id = (int)$_POST['id'];
            $before = $tipoModel->getById($id);
            
            if (!$before) {
                throw new Exception('Tipo não encontrado');
            }
            
            $success = $tipoModel->update($_POST);
            if ($success) {
                $after = $tipoModel->getById($id);
                Audit::log(Database::getInstance()->getConnection(), 'update', 'tipo', $id, [ 'before' => $before, 'after' => $after ]);
            }
            
            // Debug: log do que está sendo retornado
            error_log('Controller retornando: ' . json_encode(['success' => $success]));
            
            header('Content-Type: application/json');
            echo json_encode(['success' => $success]);
            exit;
        } catch (Exception $e) {
            error_log('Erro na edição: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    } elseif ($action === 'add') {
        $success = $tipoModel->create($_POST);
        if ($success) {
            Audit::log(Database::getInstance()->getConnection(), 'create', 'tipo', 0, [ 'after' => $_POST ]);
        }
        header('Content-Type: application/json');
        echo json_encode(['success' => $success]);
        exit;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $tipo = $tipoModel->getById($id);
    echo json_encode($tipo);
    exit;
}
