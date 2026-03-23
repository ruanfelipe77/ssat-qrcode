<?php
session_start();

require '../../database.php';
require '../../src/models/ProductionOrder.php';
require '../../src/models/Product.php';
require '../../src/models/Assembly.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($orderId === 0) {
    die('ID do pedido não fornecido');
}

try {
    $db = Database::getInstance()->getConnection();
    $orderModel = new ProductionOrder($db);
    $productModel = new Product($db);
    $assemblyModel = new Assembly($db);
    
    // Buscar dados do pedido
    $order = $orderModel->getById($orderId);
    if (!$order) {
        die('Pedido não encontrado');
    }
    
    // Buscar produtos do pedido que são compostos
    $sql = "SELECT p.id, p.serial_number, t.nome as tipo_name
            FROM products p
            JOIN tipos t ON p.tipo_id = t.id
            WHERE p.production_order_id = :order_id
            AND EXISTS(
                SELECT 1 FROM assemblies a 
                WHERE a.composite_product_id = p.id 
                AND a.status = 'finalized'
            )
            ORDER BY p.serial_number ASC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute(['order_id' => $orderId]);
    $compositeProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($compositeProducts)) {
        die('Nenhum produto composto encontrado neste pedido');
    }
    
    // Buscar dados completos de cada produto composto
    $productsData = [];
    foreach ($compositeProducts as $product) {
        // Buscar assembly e componentes
        $sqlAssembly = "SELECT a.*, 
                               t.nome as composite_tipo_name, 
                               u.name as created_by_name, 
                               p.destination as composite_destination,
                               p.serial_number as composite_serial,
                               CASE 
                                   WHEN c.name IS NOT NULL THEN c.name
                                   WHEN p.destination REGEXP '^[0-9]+$' THEN CONCAT('Cliente ID: ', p.destination)
                                   WHEN p.destination = 'estoque' THEN 'Em Estoque'
                                   ELSE p.destination
                               END as client_name,
                               COALESCE(NULLIF(so.nfe, ''), so.order_number, 'N/A') as nfe_or_pp
                        FROM assemblies a
                        LEFT JOIN composite_templates ct ON a.template_id = ct.id
                        LEFT JOIN tipos t ON ct.tipo_id = t.id
                        LEFT JOIN users u ON a.created_by = u.id
                        LEFT JOIN products p ON a.composite_product_id = p.id
                        LEFT JOIN sales_orders so ON p.production_order_id = so.id
                        LEFT JOIN clients c ON (p.destination REGEXP '^[0-9]+$' AND p.destination = c.id)
                        WHERE a.composite_product_id = :product_id AND a.status = 'finalized'";
        
        $stmtAssembly = $db->prepare($sqlAssembly);
        $stmtAssembly->execute(['product_id' => $product['id']]);
        $assembly = $stmtAssembly->fetch(PDO::FETCH_ASSOC);
        
        if ($assembly) {
            $components = $assemblyModel->getAssemblyComponents($assembly['id']);
            $productsData[] = [
                'assembly' => $assembly,
                'components' => $components
            ];
        }
    }
    
} catch (Exception $e) {
    die('Erro ao buscar dados: ' . $e->getMessage());
}

// Gerar HTML para impressão
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Componentes - Pedido <?= htmlspecialchars($order['order_number']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @page {
            margin: 0;
        }
        
        @media print {
            .no-print {
                display: none !important;
            }
            .page-break {
                page-break-after: always;
            }
            body {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
                margin: 0;
            }
            
            /* Remover cabeçalhos e rodapés padrão do navegador */
            @page {
                margin: 0;
                size: auto;
            }
        }
        
        .product-page {
            min-height: 100vh;
            padding: 40px;
        }
        
        .component-item {
            border-left: 3px solid #0d6efd;
            padding-left: 15px;
            margin-bottom: 10px;
        }
        
        .info-label {
            font-weight: 600;
            color: #495057;
        }
    </style>
</head>
<body>
    <div class="no-print p-3 bg-light border-bottom">
        <div class="container">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print me-2"></i>Imprimir
            </button>
            <button onclick="window.close()" class="btn btn-secondary ms-2">
                <i class="fas fa-times me-2"></i>Fechar
            </button>
        </div>
    </div>

    <?php foreach ($productsData as $index => $data): ?>
        <div class="product-page <?= $index < count($productsData) - 1 ? 'page-break' : '' ?>">
            <div class="container">
                <div class="text-center mb-4">
                    <h2>Componentes do Produto #<?= htmlspecialchars($data['assembly']['composite_serial']) ?></h2>
                    <p class="text-muted">Pedido de Produção: <?= htmlspecialchars($order['order_number']) ?></p>
                </div>

                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Informações do Produto</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <span class="info-label">Produto:</span>
                                <span class="ms-2"><?= htmlspecialchars($data['assembly']['composite_tipo_name'] ?? 'N/A') ?></span>
                            </div>
                            <div class="col-md-6 mb-3">
                                <span class="info-label">Status:</span>
                                <span class="ms-2"><?= htmlspecialchars($data['assembly']['composite_destination'] ?? 'N/A') ?></span>
                            </div>
                            <div class="col-md-6 mb-3">
                                <span class="info-label">Cliente:</span>
                                <span class="ms-2"><?= htmlspecialchars($data['assembly']['client_name'] ?? 'N/A') ?></span>
                            </div>
                            <div class="col-md-6 mb-3">
                                <span class="info-label">Nota Fiscal/Empenho:</span>
                                <span class="ms-2"><?= htmlspecialchars($data['assembly']['nfe_or_pp'] ?? 'N/A') ?></span>
                            </div>
                            <div class="col-md-6 mb-3">
                                <span class="info-label">Montado em:</span>
                                <span class="ms-2"><?= $data['assembly']['created_at'] ? date('d/m/Y', strtotime($data['assembly']['created_at'])) : 'N/A' ?></span>
                            </div>
                            <div class="col-md-6 mb-3">
                                <span class="info-label">Montado por:</span>
                                <span class="ms-2"><?= htmlspecialchars($data['assembly']['created_by_name'] ?? 'N/A') ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0">Componentes</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($data['components'])): ?>
                            <?php foreach ($data['components'] as $component): ?>
                                <div class="component-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0"><?= htmlspecialchars($component['component_tipo_name']) ?></h6>
                                        <span class="badge bg-dark"><?= htmlspecialchars($component['component_serial']) ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted mb-0">Nenhum componente encontrado.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <script src="https://kit.fontawesome.com/your-code.js" crossorigin="anonymous"></script>
</body>
</html>
