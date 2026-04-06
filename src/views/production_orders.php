<?php
require 'database.php';
require 'src/models/ProductionOrder.php';
require 'src/models/Client.php';

$database = Database::getInstance();
$db = $database->getConnection();

$poModel = new ProductionOrder($db);
$orders = $poModel->getAll();

$clientModel = new Client($db);
$allClients = $clientModel->getAll();

// Calcular cards de resumo
$totalOrders  = count($orders);
$pendingCount = 0;
$inProdCount  = 0;
$deliveredCount = 0;
$uniqueClients = [];

foreach ($orders as $o) {
    if ($o['status'] === 'pending')       $pendingCount++;
    if ($o['status'] === 'in_production') $inProdCount++;
    if ($o['status'] === 'delivered')     $deliveredCount++;
    if (!empty($o['client_name']))        $uniqueClients[$o['client_name']] = true;
}
?>

<div class="container-fluid px-4 pt-3 pb-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-1" style="color: #1a1a2e;">
                <i class="fas fa-clipboard-list me-2" style="color: #0d6efd;"></i>Pedidos de Produção
            </h4>
            <small class="text-muted"><?= number_format($totalOrders, 0, ',', '.') ?> registros</small>
        </div>
        <button class="btn btn-primary btn-sm px-4" id="add-order">
            <i class="fas fa-plus me-2"></i>Novo Pedido
        </button>
    </div>

    <!-- Cards de resumo -->
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="summary-card" style="border-left: 4px solid #0d6efd;">
                <div class="summary-card-body">
                    <div class="summary-icon" style="background: rgba(13,110,253,0.1); color: #0d6efd;">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <div>
                        <div class="summary-value"><?= number_format($totalOrders, 0, ',', '.') ?></div>
                        <div class="summary-label">Total</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="summary-card" style="border-left: 4px solid #ffc107;">
                <div class="summary-card-body">
                    <div class="summary-icon" style="background: rgba(255,193,7,0.1); color: #e6a800;">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div>
                        <div class="summary-value"><?= $pendingCount ?></div>
                        <div class="summary-label">Pendentes</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="summary-card" style="border-left: 4px solid #0dcaf0;">
                <div class="summary-card-body">
                    <div class="summary-icon" style="background: rgba(13,202,240,0.1); color: #0aa2c0;">
                        <i class="fas fa-cogs"></i>
                    </div>
                    <div>
                        <div class="summary-value"><?= $inProdCount ?></div>
                        <div class="summary-label">Em Produção</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="summary-card" style="border-left: 4px solid #198754;">
                <div class="summary-card-body">
                    <div class="summary-icon" style="background: rgba(25,135,84,0.1); color: #198754;">
                        <i class="fas fa-check-double"></i>
                    </div>
                    <div>
                        <div class="summary-value"><?= $deliveredCount ?></div>
                        <div class="summary-label">Entregues</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Barra de filtros -->
    <div class="card filter-card mb-3">
        <div class="card-body py-2 px-3">
            <div class="row g-2 align-items-end">
                <div class="col-6 col-md-3">
                    <label class="filter-label">Cliente</label>
                    <select id="filterOrderClient" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <?php foreach ($allClients as $c): ?>
                            <option value="<?= htmlspecialchars($c['city'] . '/' . $c['state']) ?>">
                                <?= htmlspecialchars($c['name']) ?> — <?= $c['city'] ?>/<?= $c['state'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="filter-label">Status</label>
                    <select id="filterOrderStatus" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <option value="pending">Pendente</option>
                        <option value="in_production">Em Produção</option>
                        <option value="completed">Concluído</option>
                        <option value="delivered">Entregue</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="filter-label">Produtos</label>
                    <select id="orders-only-empty-filter" class="form-select form-select-sm">
                        <option value="all">Todos</option>
                        <option value="empty">Sem produtos</option>
                    </select>
                </div>
                <div class="col-6 col-md-2 d-flex gap-2">
                    <button class="btn btn-sm btn-outline-danger flex-grow-1" id="btnClearOrderFilters">
                        <i class="fas fa-times me-1"></i>Limpar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela -->
    <div class="card table-card">
        <div class="card-body p-0">
            <div class="table-loading table-loading-static">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Carregando dados...</span>
                </div>
                <p class="mt-2 mb-0">Carregando dados...</p>
            </div>
            <table id="orders-table" class="table table-hover mb-0" style="width:100%">
                <thead>
                    <tr>
                        <th>Pedido</th>
                        <th>Cliente</th>
                        <th>Data</th>
                        <th class="text-center">Produtos</th>
                        <th>Status</th>
                        <th>Garantia</th>
                        <th class="text-center" style="width: 160px;">Ações</th>
                        <th class="d-none">status_slug</th>
                        <th class="d-none">total_products_hidden</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order) : ?>
                        <tr>
                            <td>
                                <span class="badge rounded-pill bg-primary bg-opacity-75 fw-semibold">
                                    <?= $order['order_number'] ?>
                                </span>
                            </td>
                            <td class="fw-medium"><?= $order['client_city'] ?>/<?= $order['client_state'] ?></td>
                            <td class="text-muted small"><?= (new DateTime($order['order_date']))->format('d/m/Y') ?></td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-light action-btn view-products"
                                        data-id="<?= $order['id'] ?>"
                                        data-order="<?= htmlspecialchars($order['order_number']) ?>"
                                        title="Ver Produtos">
                                    <span class="position-relative px-1">
                                        <i class="fas fa-box-open"></i>
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary products-badge">
                                            <?= $order['total_products'] ?>
                                        </span>
                                    </span>
                                </button>
                            </td>
                            <td>
                                <?php
                                $statusMap = [
                                    'pending'     => ['label' => 'Pendente',    'color' => '#e6a800', 'bg' => 'rgba(255,193,7,0.15)'],
                                    'in_production'=> ['label' => 'Em Produção', 'color' => '#0aa2c0', 'bg' => 'rgba(13,202,240,0.15)'],
                                    'completed'   => ['label' => 'Concluído',   'color' => '#198754', 'bg' => 'rgba(25,135,84,0.15)'],
                                    'delivered'   => ['label' => 'Entregue',    'color' => '#0d6efd', 'bg' => 'rgba(13,110,253,0.15)'],
                                ];
                                $s = $statusMap[$order['status']] ?? ['label' => $order['status'], 'color' => '#6c757d', 'bg' => 'rgba(108,117,125,0.15)'];
                                ?>
                                <select class="form-select form-select-sm status-select order-status-styled"
                                        data-id="<?= $order['id'] ?>"
                                        data-status="<?= $order['status'] ?>"
                                        style="width: 130px; color: <?= $s['color'] ?>; border-color: <?= $s['color'] ?>; background-color: <?= $s['bg'] ?>; font-weight: 600; font-size: 0.78rem;">
                                    <option value="pending"      <?= $order['status'] == 'pending'       ? 'selected' : '' ?>>Pendente</option>
                                    <option value="in_production"<?= $order['status'] == 'in_production' ? 'selected' : '' ?>>Em Produção</option>
                                    <option value="completed"    <?= $order['status'] == 'completed'     ? 'selected' : '' ?>>Concluído</option>
                                    <option value="delivered"    <?= $order['status'] == 'delivered'     ? 'selected' : '' ?>>Entregue</option>
                                </select>
                            </td>
                            <td class="small"><?= $order['warranty'] ?></td>
                            <td>
                                <div class="d-flex justify-content-center align-items-center gap-2">
                                    <button class="btn btn-sm btn-light action-btn edit-order"
                                            data-id="<?= $order['id'] ?>"
                                            data-order="<?= htmlspecialchars($order['order_number']) ?>"
                                            title="Editar Pedido">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                    <button class="btn btn-sm btn-light action-btn print-all"
                                            data-id="<?= $order['id'] ?>"
                                            title="Imprimir QR Codes">
                                        <i class="fas fa-qrcode"></i>
                                    </button>
                                    <button class="btn btn-sm btn-light action-btn generate-pdf"
                                            data-id="<?= $order['id'] ?>"
                                            data-order="<?= htmlspecialchars($order['order_number']) ?>"
                                            title="Gerar PDF">
                                        <i class="fas fa-file-pdf text-danger"></i>
                                    </button>
                                    <button class="btn btn-sm btn-light action-btn delete-order"
                                            data-id="<?= $order['id'] ?>"
                                            data-order="<?= htmlspecialchars($order['order_number']) ?>"
                                            data-products="<?= $order['total_products'] ?>"
                                            title="Excluir Pedido">
                                        <i class="fas fa-trash-alt text-danger"></i>
                                    </button>
                                </div>
                            </td>
                            <td class="d-none"><?= $order['status'] ?></td>
                            <td class="d-none"><?= (int)$order['total_products'] ?></td>
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
/* === reutiliza o mesmo padrão premium de main.php === */
.summary-card { background:#fff; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,.06); transition:transform .2s,box-shadow .2s; }
.summary-card:hover { transform:translateY(-2px); box-shadow:0 4px 16px rgba(0,0,0,.1); }
.summary-card-body { display:flex; align-items:center; gap:14px; padding:16px 18px; }
.summary-icon { width:44px; height:44px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.15rem; flex-shrink:0; }
.summary-value { font-size:1.4rem; font-weight:700; line-height:1.1; color:#1a1a2e; }
.summary-label { font-size:.78rem; color:#6c757d; font-weight:500; text-transform:uppercase; letter-spacing:.3px; }

.filter-card { border:1px solid #e9ecef; border-radius:10px; box-shadow:0 1px 4px rgba(0,0,0,.04); background:#fff; }
.filter-label { display:block; font-size:.7rem; font-weight:600; color:#6c757d; text-transform:uppercase; letter-spacing:.5px; margin-bottom:3px; }
.filter-card .form-select-sm { font-size:.82rem; border-radius:6px; border-color:#dee2e6; transition:border-color .2s,box-shadow .2s; }
.filter-card .form-select-sm:focus { border-color:#0d6efd; box-shadow:0 0 0 3px rgba(13,110,253,.15); }
.filter-card .form-select-sm.active-filter { border-color:#0d6efd; background-color:rgba(13,110,253,.03); }

.table-card { border-radius:10px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,.06); border:none; }
.table-card .table thead th { background:#f1f3f5; border-bottom:2px solid #dee2e6; font-size:.78rem; font-weight:700; text-transform:uppercase; letter-spacing:.4px; color:#495057; padding:10px 14px; white-space:nowrap; }
.table-card .table tbody td { padding:8px 14px; vertical-align:middle; font-size:.85rem; border-bottom:1px solid #f1f3f5; }
.table-card .table tbody tr:hover { background-color:rgba(13,110,253,.03); }

.action-btn { width:30px; height:30px; padding:0; display:inline-flex; align-items:center; justify-content:center; border-radius:6px; border:1px solid #e9ecef; font-size:.8rem; transition:all .15s; }
.action-btn:hover { background:#e9ecef; transform:translateY(-1px); }

/* Status select colorido */
.order-status-styled { padding:.15rem 1.5rem .15rem .5rem; border-radius:20px; min-height:28px; cursor:pointer; }
.order-status-styled:focus { box-shadow:none; }

/* Badge de produtos */
.products-badge { font-size:.5rem; padding:.1rem .25rem; }

/* DataTables override */
#orders-table_wrapper .dataTables_filter input { border-radius:6px; border-color:#dee2e6; font-size:.82rem; padding:.3rem .6rem; }
#orders-table_wrapper .dataTables_info { font-size:.82rem; color:#6c757d; padding:12px 16px; }
#orders-table_wrapper .dataTables_paginate { padding:8px 16px 12px; }
#orders-table_wrapper .dataTables_paginate .paginate_button { border-radius:6px !important; margin:0 2px; font-size:.82rem; }
#orders-table_wrapper .dataTables_paginate .paginate_button.current { background:#0d6efd !important; border-color:#0d6efd !important; color:#fff !important; }
#orders-table_wrapper .dataTables_length { padding:12px 16px; font-size:.82rem; }
</style>

<script>
// Filtros adicionais por coluna para a tabela de pedidos
$(document).ready(function() {
    if (!window._ordersTableFiltersInit && typeof ordersTable !== 'undefined') {
        window._ordersTableFiltersInit = true;
    }

    // Aguarda o DataTable ser inicializado pelo production_orders.js
    var waitForTable = setInterval(function() {
        if ($.fn.DataTable.isDataTable('#orders-table')) {
            clearInterval(waitForTable);
            initOrderFilters();
        }
    }, 100);

    function initOrderFilters() {
        function applyOrderFilters() {
            var dt = $('#orders-table').DataTable();
            var client = $('#filterOrderClient').val();
            var status = $('#filterOrderStatus').val();

            // Col 1 = Cliente, Col 7 = status_slug
            dt.column(1).search(client ? $.fn.dataTable.util.escapeRegex(client) : '', true, false);
            dt.column(7).search(status ? '^' + $.fn.dataTable.util.escapeRegex(status) + '$' : '', true, false);
            dt.draw();

            $('.filter-card .form-select-sm').each(function() {
                $(this).toggleClass('active-filter', $(this).val() !== '' && $(this).val() !== 'all');
            });
        }

        $('#filterOrderClient, #filterOrderStatus').on('change', applyOrderFilters);

        $('#btnClearOrderFilters').on('click', function() {
            $('#filterOrderClient').val('');
            $('#filterOrderStatus').val('');
            $('#orders-only-empty-filter').val('all').trigger('change');
            applyOrderFilters();
        });
    }
});
</script>
