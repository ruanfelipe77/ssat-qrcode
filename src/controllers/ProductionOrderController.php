<?php

require '../../database.php';
require '../../src/models/ProductionOrder.php';
require '../../src/models/Product.php';
require '../../src/models/Audit.php';

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
            // Audit
            if (!empty($result['success'])) {
                $db = Database::getInstance()->getConnection();
                // Info do cliente
                $clientInfo = null;
                try {
                    $cid = (int)($_POST['client_id'] ?? 0);
                    if ($cid > 0) {
                        $st = $db->prepare('SELECT id, name FROM clients WHERE id = :id');
                        $st->execute([':id' => $cid]);
                        $clientInfo = $st->fetch(PDO::FETCH_ASSOC) ?: null;
                    }
                } catch (Throwable $e) {}

                // Lista de produtos do pedido recem criado
                $productsList = [];
                try {
                    $ppId = (int)($result['pp_id'] ?? 0);
                    if ($ppId > 0) {
                        $prods = $poModel->getProducts($ppId);
                        foreach (($prods ?: []) as $p) {
                            $productsList[] = [
                                'id' => (int)$p['id'],
                                'serial_number' => $p['serial_number'] ?? null,
                                'tipo_name' => $p['tipo_name'] ?? null,
                                'batch_number' => $p['batch_number'] ?? null,
                                'warranty' => $p['warranty'] ?? null,
                            ];
                        }
                    }
                } catch (Throwable $e) {}

                $details = [
                    'order_number' => $_POST['order_number'] ?? $result['order_number'] ?? null,
                    'client_id'    => $_POST['client_id'] ?? null,
                    'client'       => $clientInfo,
                    'order_date'   => $_POST['order_date'] ?? null,
                    'warranty'     => $_POST['warranty'] ?? null,
                    'nfe'          => $_POST['nfe'] ?? null,
                    'product_ids'  => isset($_POST['products']) ? array_values((array)$_POST['products']) : [],
                    'products'     => $productsList,
                ];
                Audit::log(Database::getInstance()->getConnection(), 'create', 'order', $result['pp_id'] ?? null, $details);
            }
            echo json_encode($result);
            break;

        case 'delete_order':
            $id = intval($_POST['id'] ?? 0);
            // coletar contexto antes
            $before = $poModel->getById($id);
            $products = $poModel->getProducts($id);
            $result = $poModel->delete($id);
            // Audit
            if (!empty($result['success'])) {
                $details = [
                    'order_number' => $before['order_number'] ?? null,
                    'product_ids'  => array_map(fn($p)=>$p['id'], $products ?? []),
                ];
                Audit::log(Database::getInstance()->getConnection(), 'delete', 'order', $id, $details);
            }
            echo json_encode($result);
            break;

        case 'update_status':
            $id = intval($_POST['id'] ?? 0);
            $status = $_POST['status'] ?? '';
            $current = $poModel->getById($id);
            $success = $poModel->updateStatus($id, $status);
            if ($success) {
                $details = [
                    'order_number' => $current['order_number'] ?? null,
                    'from' => $current['status'] ?? null,
                    'to'   => $status,
                ];
                Audit::log(Database::getInstance()->getConnection(), 'status_change', 'order', $id, $details);
            }
            echo json_encode(['success' => $success]);
            break;

        case 'update_order':
            // contexto antes
            $before = $poModel->getById(intval($_POST['id'] ?? 0));
            $beforeProducts = $poModel->getProducts(intval($_POST['id'] ?? 0));
            $result = $poModel->update($_POST);
            // Audit
            if (!empty($result['success'])) {
                $after = $poModel->getById(intval($_POST['id'] ?? 0));
                $afterProducts = $poModel->getProducts(intval($_POST['id'] ?? 0));
                $beforeIds = array_map(fn($p)=>intval($p['id']), $beforeProducts);
                $afterIds  = array_map(fn($p)=>intval($p['id']), $afterProducts);
                $added = array_values(array_diff($afterIds, $beforeIds));
                $removed = array_values(array_diff($beforeIds, $afterIds));

                // Buscar detalhes dos produtos adicionados/removidos
                $db = Database::getInstance()->getConnection();
                $addedProducts = [];
                $removedProducts = [];
                try {
                    if (!empty($added)) {
                        $ph = implode(',', array_fill(0, count($added), '?'));
                        $st = $db->prepare("SELECT p.id, p.serial_number, p.warranty, t.nome AS tipo_name, pb.batch_number FROM products p LEFT JOIN tipos t ON p.tipo_id = t.id LEFT JOIN production_batches pb ON p.production_batch_id = pb.id WHERE p.id IN ($ph)");
                        $st->execute($added);
                        $addedProducts = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
                    }
                    if (!empty($removed)) {
                        $ph2 = implode(',', array_fill(0, count($removed), '?'));
                        $st2 = $db->prepare("SELECT p.id, p.serial_number, p.warranty, t.nome AS tipo_name, pb.batch_number FROM products p LEFT JOIN tipos t ON p.tipo_id = t.id LEFT JOIN production_batches pb ON p.production_batch_id = pb.id WHERE p.id IN ($ph2)");
                        $st2->execute($removed);
                        $removedProducts = $st2->fetchAll(PDO::FETCH_ASSOC) ?: [];
                    }
                } catch (Throwable $e) {}
                $details = [
                    'order_number' => $after['order_number'] ?? ($before['order_number'] ?? null),
                    'before' => [
                        'order_number' => $before['order_number'] ?? null,
                        'client_id' => $before['client_id'] ?? null,
                        'order_date' => $before['order_date'] ?? null,
                        'warranty' => $before['warranty'] ?? null,
                        'nfe' => $before['nfe'] ?? null,
                        'product_ids' => $beforeIds,
                    ],
                    'after' => [
                        'order_number' => $after['order_number'] ?? null,
                        'client_id' => $after['client_id'] ?? null,
                        'order_date' => $after['order_date'] ?? null,
                        'warranty' => $after['warranty'] ?? null,
                        'nfe' => $after['nfe'] ?? null,
                        'product_ids' => $afterIds,
                    ],
                    'added_product_ids' => $added,
                    'removed_product_ids' => $removed,
                    'added_products' => $addedProducts,
                    'removed_products' => $removedProducts,
                ];
                Audit::log(Database::getInstance()->getConnection(), 'update', 'order', intval($_POST['id'] ?? 0), $details);
                if (!empty($added)) {
                    Audit::log(Database::getInstance()->getConnection(), 'add_products', 'order', intval($_POST['id'] ?? 0), [
                        'order_number' => $after['order_number'] ?? null,
                        'added_product_ids' => $added,
                        'added_products' => $addedProducts,
                    ]);
                }
                if (!empty($removed)) {
                    Audit::log(Database::getInstance()->getConnection(), 'remove_products', 'order', intval($_POST['id'] ?? 0), [
                        'order_number' => $after['order_number'] ?? null,
                        'removed_product_ids' => $removed,
                        'removed_products' => $removedProducts,
                    ]);
                }
            }
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
