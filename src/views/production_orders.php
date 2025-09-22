<?php
require 'database.php';
require 'src/models/ProductionOrder.php';
require 'src/models/Client.php';

$database = Database::getInstance();
$db = $database->getConnection();

$poModel = new ProductionOrder($db);
$orders = $poModel->getAll();
?>

<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Pedidos de Produção</h2>
        <button class="btn btn-primary" id="add-order">
            <i class="fas fa-plus me-2"></i>Novo Pedido
        </button>
    </div>

    <div class="card">
        <div class="card-body">
            <table id="orders-table" class="table table-striped table-hover" style="width:100%">
                <thead>
                    <tr>
                        <th>Pedido</th>
                        <th>Cliente</th>
                        <th>Data</th>
                        <th>Produtos</th>
                        <th>Status</th>
                        <th>Garantia</th>
                        <th style="width: 180px; text-align: center;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order) : ?>
                        <tr>
                            <td>
                                <span class="badge bg-primary">
                                    <?= $order['order_number'] ?>
                                </span>
                            </td>
                            <td>
                                <span class="text-dark fw-semibold">
                                    <?= $order['client_city'] ?>/<?= $order['client_state'] ?>
                                </span>
                            </td>
                            <td><?= (new DateTime($order['order_date']))->format('d/m/Y') ?></td>
                            <td class="text-center">
                                <button class="btn btn-link text-primary p-0 view-products" 
                                        data-id="<?= $order['id'] ?>"
                                        data-order="<?= htmlspecialchars($order['order_number']) ?>"
                                        title="Ver Produtos">
                                    <span class="position-relative">
                                        <i class="fas fa-box-open fs-6"></i>
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary products-badge">
                                            <?= $order['total_products'] ?>
                                        </span>
                                    </span>
                                </button>
                            </td>
                            <td>
                                <select class="form-select form-select-sm status-select" 
                                        data-id="<?= $order['id'] ?>"
                                        style="width: 120px;">
                                    <option value="pending" <?= $order['status'] == 'pending' ? 'selected' : '' ?>>Pendente</option>
                                    <option value="in_production" <?= $order['status'] == 'in_production' ? 'selected' : '' ?>>Em Produção</option>
                                    <option value="completed" <?= $order['status'] == 'completed' ? 'selected' : '' ?>>Concluído</option>
                                    <option value="delivered" <?= $order['status'] == 'delivered' ? 'selected' : '' ?>>Entregue</option>
                                </select>
                            </td>
                            <td><?= $order['warranty'] ?></td>
                            <td>
                                <div class="d-flex justify-content-center align-items-center gap-3">
                                    <button class="btn btn-link text-dark p-0 edit-order" 
                                            data-id="<?= $order['id'] ?>" 
                                            data-order="<?= htmlspecialchars($order['order_number']) ?>"
                                            title="Editar Pedido">
                                        <i class="fas fa-pen fs-5"></i>
                                    </button>
                                    <button class="btn btn-link text-dark p-0 print-all" 
                                            data-id="<?= $order['id'] ?>" 
                                            title="Imprimir QR Codes">
                                        <i class="fas fa-print fs-5"></i>
                                    </button>
                                    <button class="btn btn-link text-dark p-0 generate-pdf" 
                                            data-id="<?= $order['id'] ?>" 
                                            data-order="<?= htmlspecialchars($order['order_number']) ?>"
                                            title="Gerar PDF">
                                        <i class="fas fa-file-pdf fs-5"></i>
                                    </button>
                                    <button class="btn btn-link text-danger p-0 delete-order" 
                                            data-id="<?= $order['id'] ?>"
                                            data-order="<?= htmlspecialchars($order['order_number']) ?>"
                                            data-products="<?= $order['total_products'] ?>"
                                            title="Excluir Pedido">
                                        <i class="fas fa-trash-alt fs-5"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal de Produtos do Pedido -->
<div class="modal fade" id="productsModal" tabindex="-1" aria-labelledby="productsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="productsModalLabel">
                    <i class="fas fa-box-open me-2"></i>
                    <span class="modal-title-text">Produtos do Pedido</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="order-info mb-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="order-icon me-3">
                            <i class="fas fa-clipboard-list text-primary" style="font-size: 3rem;"></i>
                        </div>
                        <div>
                            <h4 class="order-number mb-1"></h4>
                            <p class="text-muted mb-0">
                                <span class="total-products"></span> produtos
                            </p>
                        </div>
                    </div>
                </div>
                <div class="products-list">
                    <!-- Produtos serão carregados aqui via JavaScript -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary print-selected d-none">
                    <i class="fas fa-print me-2"></i>Imprimir Selecionados
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Fechar
                </button>
            </div>
        </div>
    </div>
</div>

<?php include 'src/views/modal_order.php'; ?>

<style>
.badge {
    font-size: 0.875rem;
    padding: 0.5rem 0.75rem;
}

.status-select {
    border: none;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.875rem;
    cursor: pointer;
}

.status-select option[value="pending"] {
    background-color: #ffc107;
    color: #000;
}

.status-select option[value="in_production"] {
    background-color: #0dcaf0;
    color: #000;
}

.status-select option[value="completed"] {
    background-color: #198754;
    color: #fff;
}

.status-select option[value="delivered"] {
    background-color: #0d6efd;
    color: #fff;
}

.btn-link:hover {
    opacity: 0.7;
    transform: scale(1.1);
    transition: all 0.2s ease;
}

.btn-link:active {
    transform: scale(0.95);
}

/* Reduzir altura das linhas da tabela */
#orders-table tbody tr td {
    padding-top: 0.25rem;
    padding-bottom: 0.25rem;
    vertical-align: middle;
}

/* Afinar o select de status */
.status-select {
    border: 1px solid #dee2e6;
    /* padding direita maior para não cortar o caret do select */
    padding: 0.12rem 1.2rem 0.12rem 0.4rem;
    line-height: 1.2;
    height: auto;           /* deixar o navegador calcular */
    min-height: 28px;       /* altura mínima enxuta com texto visível */
    font-size: 0.85rem;     /* texto um pouco menor */
    border-radius: 0.35rem;
}

/* Badge menor sobre o ícone de produtos */
.products-badge {
    font-size: 0.5rem;
    padding: 0.1rem 0.25rem;
}
</style>
