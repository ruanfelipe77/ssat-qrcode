<?php

require '../../database.php';
require '../../src/models/ProductionOrder.php';
require '../../src/models/Product.php';

$poModel = new ProductionOrder(Database::getInstance()->getConnection());

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // DEBUG: Log da ação e dados recebidos
    error_log("DEBUG ProductionOrderController - Action: " . $action);
    error_log("DEBUG ProductionOrderController - POST data: " . json_encode($_POST));
    
    switch($action) {
        case 'create_pp':
        case 'create_order':
            $result = $poModel->create($_POST);
            echo json_encode($result);
            break;

        case 'delete_order':
            $id = $_POST['id'] ?? '';
            $result = $poModel->delete($id);
            echo json_encode($result);
            break;

        case 'update_status':
            $id = $_POST['id'] ?? '';
            $status = $_POST['status'] ?? '';
            $success = $poModel->updateStatus($id, $status);
            echo json_encode(['success' => $success]);
            break;

        case 'update_order':
            $result = $poModel->update($_POST);
            echo json_encode($result);
            break;

        case 'generate_pdf':
            $id = $_POST['id'] ?? '';
            $result = $poModel->generatePDF($id);
            echo json_encode($result);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Ação inválida']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['empty_count'])) {
        try {
            $db = Database::getInstance()->getConnection();
            $sql = "SELECT COUNT(*) AS cnt FROM (
                        SELECT po.id
                        FROM sales_orders po
                        LEFT JOIN products p ON p.production_order_id = po.id
                        GROUP BY po.id
                        HAVING COUNT(p.id) = 0
                    ) t";
            $stmt = $db->prepare($sql);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['count' => intval($row['cnt'] ?? 0)]);
        } catch (Throwable $e) {
            echo json_encode(['count' => 0]);
        }
    } elseif (isset($_GET['id'])) {
        if (isset($_GET['products'])) {
            $products = $poModel->getProducts($_GET['id']);
            echo json_encode($products);
        } else {
            $po = $poModel->getById($_GET['id']);
            echo json_encode($po);
        }
    } elseif (isset($_GET['available_products'])) {
        // Lista de produtos disponíveis para incluir em pedidos
        $productModel = new Product(Database::getInstance()->getConnection());
        $products = $productModel->getAvailableProducts();
        echo json_encode($products);
    } else {
        $pos = $poModel->getAll();
        echo json_encode($pos);
    }
    exit;
}
