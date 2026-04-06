<?php
require 'database.php';
require 'src/models/Client.php';
require 'src/models/Product.php';

$database = Database::getInstance();
$db = $database->getConnection();

$clientModel = new Client($db);
$clients = $clientModel->getAll();

$productModel = new Product($db);

// Pré-calcular dados para cards e filtro de estado
$totalClients   = count($clients);
$uniqueStates   = [];
$withProducts   = 0;
$withoutProducts = 0;
$clientProducts = [];

foreach ($clients as $client) {
    try {
        $prods = $productModel->getByClientId($client['id']);
        $count = count($prods);
    } catch (Exception $e) {
        $count = 0;
    }
    $clientProducts[$client['id']] = $count;
    if ($count > 0) $withProducts++;
    else            $withoutProducts++;
    if (!empty($client['state'])) $uniqueStates[$client['state']] = true;
}
ksort($uniqueStates);
?>

<div class="container-fluid px-4 pt-3 pb-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-1" style="color: #1a1a2e;">
                <i class="fas fa-users me-2" style="color: #0d6efd;"></i>Clientes
            </h4>
            <small class="text-muted"><?= number_format($totalClients, 0, ',', '.') ?> registros</small>
        </div>
        <button class="btn btn-primary btn-sm px-4" id="add-client">
            <i class="fas fa-plus me-2"></i>Novo Cliente
        </button>
    </div>

    <!-- Cards de resumo -->
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="summary-card" style="border-left: 4px solid #0d6efd;">
                <div class="summary-card-body">
                    <div class="summary-icon" style="background: rgba(13,110,253,0.1); color: #0d6efd;">
                        <i class="fas fa-users"></i>
                    </div>
                    <div>
                        <div class="summary-value"><?= number_format($totalClients, 0, ',', '.') ?></div>
                        <div class="summary-label">Total</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="summary-card" style="border-left: 4px solid #6f42c1;">
                <div class="summary-card-body">
                    <div class="summary-icon" style="background: rgba(111,66,193,0.1); color: #6f42c1;">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div>
                        <div class="summary-value"><?= count($uniqueStates) ?></div>
                        <div class="summary-label">Estados</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="summary-card" style="border-left: 4px solid #198754;">
                <div class="summary-card-body">
                    <div class="summary-icon" style="background: rgba(25,135,84,0.1); color: #198754;">
                        <i class="fas fa-box-open"></i>
                    </div>
                    <div>
                        <div class="summary-value"><?= $withProducts ?></div>
                        <div class="summary-label">Com Produtos</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="summary-card" style="border-left: 4px solid #6c757d;">
                <div class="summary-card-body">
                    <div class="summary-icon" style="background: rgba(108,117,125,0.1); color: #6c757d;">
                        <i class="fas fa-box"></i>
                    </div>
                    <div>
                        <div class="summary-value"><?= $withoutProducts ?></div>
                        <div class="summary-label">Sem Produtos</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Barra de filtros -->
    <div class="card filter-card mb-3">
        <div class="card-body py-2 px-3">
            <div class="row g-2 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="filter-label">Estado (UF)</label>
                    <select id="filterClientState" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <?php foreach ($uniqueStates as $uf => $_): ?>
                            <option value="<?= htmlspecialchars($uf) ?>"><?= htmlspecialchars($uf) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="filter-label">Produtos</label>
                    <select id="filterClientProducts" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <option value="with">Com produtos</option>
                        <option value="without">Sem produtos</option>
                    </select>
                </div>
                <div class="col-6 col-md-2 d-flex gap-2">
                    <button class="btn btn-sm btn-outline-danger flex-grow-1" id="btnClearClientFilters">
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
            <table id="clients-table" class="table table-hover mb-0" style="width:100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nome</th>
                        <th>Cidade</th>
                        <th>UF</th>
                        <th class="text-center" style="width: 100px;">Produtos</th>
                        <th class="text-center" style="width: 110px;">Ações</th>
                        <th class="d-none">has_products</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clients as $client) :
                        $totalProducts = $clientProducts[$client['id']] ?? 0;
                    ?>
                        <tr data-client-id="<?= $client['id'] ?>">
                            <td class="text-muted small"><?= $client['id'] ?></td>
                            <td class="fw-medium"><?= htmlspecialchars($client['name']) ?></td>
                            <td><?= htmlspecialchars($client['city']) ?></td>
                            <td>
                                <span class="badge rounded-pill" style="background: rgba(111,66,193,0.12); color: #6f42c1; font-weight:600; font-size:.75rem; padding:4px 10px;">
                                    <?= $client['state'] ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <?php if ($totalProducts > 0) : ?>
                                    <button class="btn btn-sm btn-light action-btn view-products"
                                            data-id="<?= $client['id'] ?>"
                                            data-name="<?= htmlspecialchars($client['name']) ?>"
                                            data-location="<?= htmlspecialchars($client['city'] . '/' . $client['state']) ?>"
                                            title="Ver Produtos">
                                        <span class="position-relative px-1">
                                            <i class="fas fa-box-open text-primary"></i>
                                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary" style="font-size:.5rem; padding:.1rem .25rem;">
                                                <?= $totalProducts ?>
                                            </span>
                                        </span>
                                    </button>
                                <?php else: ?>
                                    <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex justify-content-center align-items-center gap-2">
                                    <button class="btn btn-sm btn-light action-btn edit-client" data-id="<?= $client['id'] ?>" title="Editar">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                    <button class="btn btn-sm btn-light action-btn delete-client" data-id="<?= $client['id'] ?>" title="Excluir">
                                        <i class="fas fa-trash-alt text-danger"></i>
                                    </button>
                                </div>
                            </td>
                            <td class="d-none"><?= $totalProducts > 0 ? 'with' : 'without' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- DataTables Buttons -->
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.70/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.70/vfs_fonts.js"></script>

<!-- Custom JS -->
<script src="public/js/clients.js"></script>

<style>
/* === Premium UX — mesmos tokens de main.php === */
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

/* Produto modal cards */
.products-list .product-card { transition:all .2s ease; border-left:4px solid transparent; }
.products-list .product-card:hover { background-color:#f8f9fa; border-left-color:#0d6efd; transform:translateX(4px); }
.product-card .product-icon { width:40px; height:40px; display:flex; align-items:center; justify-content:center; background-color:#e9ecef; border-radius:8px; }
.client-avatar { width:60px; height:60px; display:flex; align-items:center; justify-content:center; border-radius:50%; background-color:#e9ecef; }

/* DataTables override */
#clients-table_wrapper .dataTables_info { font-size:.82rem; color:#6c757d; padding:12px 16px; }
#clients-table_wrapper .dataTables_paginate { padding:8px 16px 12px; }
#clients-table_wrapper .dataTables_paginate .paginate_button { border-radius:6px !important; margin:0 2px; font-size:.82rem; }
#clients-table_wrapper .dataTables_paginate .paginate_button.current { background:#0d6efd !important; border-color:#0d6efd !important; color:#fff !important; }
#clients-table_wrapper .dataTables_length { padding:12px 16px; font-size:.82rem; }
</style>

<script>
// Filtros adicionais para a tabela de clientes
$(document).ready(function() {
    var waitForClientsTable = setInterval(function() {
        if ($.fn.DataTable.isDataTable('#clients-table')) {
            clearInterval(waitForClientsTable);
            initClientFilters();
        }
    }, 100);

    function initClientFilters() {
        function applyClientFilters() {
            var dt = $('#clients-table').DataTable();
            var state = $('#filterClientState').val();
            var products = $('#filterClientProducts').val();

            // Col 3 = UF, Col 6 = has_products (hidden)
            dt.column(3).search(state ? '^' + $.fn.dataTable.util.escapeRegex(state) + '$' : '', true, false);
            dt.column(6).search(products ? '^' + $.fn.dataTable.util.escapeRegex(products) + '$' : '', true, false);
            dt.draw();

            $('.filter-card .form-select-sm').each(function() {
                $(this).toggleClass('active-filter', $(this).val() !== '');
            });
        }

        $('#filterClientState, #filterClientProducts').on('change', applyClientFilters);

        $('#btnClearClientFilters').on('click', function() {
            $('#filterClientState, #filterClientProducts').val('');
            applyClientFilters();
        });
    }
});
</script>

<?php include 'src/views/modal_client.php'; ?>
