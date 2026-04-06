<?php
require 'database.php';
require 'src/models/Product.php';
require 'src/models/Tipo.php';
require_once 'src/models/Client.php';
require_once 'src/models/ProductStatus.php';

$database = Database::getInstance();
$db = $database->getConnection();

$productModel = new Product($db);
$products = $productModel->getAll();

$tipoModel = new Tipo($db);
$tipos = $tipoModel->getAll();

$clientModel = new Client($db);
$allClients = $clientModel->getAll();

$statusModel = new ProductStatus($db);
$allStatuses = $statusModel->getActive();

// Coletar valores únicos para filtros
$uniqueBatches = [];
$uniqueOrders = [];
$totalProducts = count($products);
$soldCount = 0;
$stockCount = 0;
$clientSet = [];

foreach ($products as $p) {
    if (!empty($p['batch_number']) && $p['batch_number'] !== 'Sem Lote') {
        $uniqueBatches[$p['batch_number']] = true;
    }
    if (!empty($p['pp_number'])) {
        $uniqueOrders[$p['pp_number']] = true;
    }
    $isStock = (isset($p['destination']) && $p['destination'] === 'estoque') 
               || (isset($p['client_name']) && $p['client_name'] === 'Em Estoque')
               || empty($p['pp_number']);
    if ($isStock) {
        $stockCount++;
    } else {
        $soldCount++;
    }
    if (!empty($p['client_name']) && $p['client_name'] !== 'Em Estoque') {
        $clientSet[$p['client_name']] = true;
    }
}
ksort($uniqueBatches);
ksort($uniqueOrders);
ksort($clientSet);
$uniqueClientCount = count($clientSet);
?>

<div class="container-fluid px-4 pt-3 pb-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-1" style="color: #1a1a2e;">
                <i class="fas fa-boxes me-2" style="color: #0d6efd;"></i>Produtos
            </h4>
            <small class="text-muted"><?= number_format($totalProducts, 0, ',', '.') ?> registros</small>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-sm btn-outline-secondary" id="btnExportExcel" title="Exportar Excel">
                <i class="fas fa-file-excel me-1"></i>Excel
            </button>
            <button class="btn btn-sm btn-outline-secondary" id="btnExportPdf" title="Exportar PDF">
                <i class="fas fa-file-pdf me-1"></i>PDF
            </button>
        </div>
    </div>

    <!-- Cards de resumo -->
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="summary-card" style="border-left: 4px solid #0d6efd;">
                <div class="summary-card-body">
                    <div class="summary-icon" style="background: rgba(13,110,253,0.1); color: #0d6efd;">
                        <i class="fas fa-box"></i>
                    </div>
                    <div>
                        <div class="summary-value"><?= number_format($totalProducts, 0, ',', '.') ?></div>
                        <div class="summary-label">Total</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="summary-card" style="border-left: 4px solid #198754;">
                <div class="summary-card-body">
                    <div class="summary-icon" style="background: rgba(25,135,84,0.1); color: #198754;">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div>
                        <div class="summary-value"><?= number_format($soldCount, 0, ',', '.') ?></div>
                        <div class="summary-label">Vendidos</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="summary-card" style="border-left: 4px solid #ffc107;">
                <div class="summary-card-body">
                    <div class="summary-icon" style="background: rgba(255,193,7,0.1); color: #ffc107;">
                        <i class="fas fa-warehouse"></i>
                    </div>
                    <div>
                        <div class="summary-value"><?= number_format($stockCount, 0, ',', '.') ?></div>
                        <div class="summary-label">Em Estoque</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="summary-card" style="border-left: 4px solid #6f42c1;">
                <div class="summary-card-body">
                    <div class="summary-icon" style="background: rgba(111,66,193,0.1); color: #6f42c1;">
                        <i class="fas fa-users"></i>
                    </div>
                    <div>
                        <div class="summary-value"><?= $uniqueClientCount ?></div>
                        <div class="summary-label">Clientes</div>
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
                    <label class="filter-label">Cliente</label>
                    <select id="filterClient" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <?php foreach ($allClients as $c): ?>
                            <option value="<?= htmlspecialchars($c['city'] . '/' . $c['state']) ?>">
                                <?= htmlspecialchars($c['name']) ?> - <?= $c['city'] ?>/<?= $c['state'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="filter-label">Lote</label>
                    <select id="filterBatch" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <?php foreach ($uniqueBatches as $bn => $_): ?>
                            <option value="<?= htmlspecialchars($bn) ?>"><?= htmlspecialchars($bn) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="filter-label">Pedido</label>
                    <select id="filterOrder" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <?php foreach ($uniqueOrders as $on => $_): ?>
                            <option value="<?= htmlspecialchars($on) ?>"><?= htmlspecialchars($on) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="filter-label">Produto</label>
                    <select id="filterProduct" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <?php foreach ($tipos as $t): ?>
                            <option value="<?= htmlspecialchars($t['nome']) ?>"><?= htmlspecialchars($t['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="filter-label">Status</label>
                    <select id="filterStatus" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <?php foreach ($allStatuses as $st): ?>
                            <option value="<?= htmlspecialchars($st['name']) ?>">
                                <?= ucfirst(str_replace('_', ' ', $st['name'])) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-2 d-flex gap-2">
                    <button class="btn btn-sm btn-outline-danger flex-grow-1" id="btnClearFilters" title="Limpar filtros">
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
            <table id="mcp-table" class="table table-hover mb-0" style="width:100%">
                <thead>
                    <tr>
                        <th>Lote</th>
                        <th>Pedido</th>
                        <th>Produto</th>
                        <th>Status</th>
                        <th>Série</th>
                        <th>Data de venda</th>
                        <th>Cliente</th>
                        <th>Garantia</th>
                        <th>Obs</th>
                        <th class="text-center" style="width: 140px;">Ações</th>
                        <th class="d-none">status_slug</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product) : 
                        $isEstoque = (isset($product['destination']) && $product['destination'] === 'estoque')
                                     || (isset($product['client_name']) && $product['client_name'] === 'Em Estoque');
                    ?>
                        <tr>
                            <td>
                                <?php if (!empty($product['batch_number']) && $product['batch_number'] !== 'Sem Lote'): ?>
                                    <span class="badge rounded-pill bg-info bg-opacity-75"><?= $product['batch_number'] ?></span>
                                <?php else: ?>
                                    <span class="badge rounded-pill bg-secondary bg-opacity-50">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($product['pp_number'])): ?>
                                    <span class="badge rounded-pill bg-primary bg-opacity-75"><?= $product['pp_number'] ?></span>
                                <?php else: ?>
                                    <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="fw-medium"><?= $product['tipo_name'] ?></td>
                            <td>
                                <?php if ($product['status_name'] === 'in_composite'): ?>
                                    <span class="status-badge" style="--status-color: #c65cff;">
                                        <i class="fas fa-cube me-1"></i>Composição
                                    </span>
                                <?php else: ?>
                                    <span class="status-badge" style="--status-color: <?= $product['status_color'] ?>;">
                                        <i class="<?= $product['status_icon'] ?> me-1"></i>
                                        <?= ucfirst(str_replace('_', ' ', $product['status_name'])) ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><span class="fw-semibold"><?= $product['serial_number'] ?></span></td>
                            <td>
                                <?php if (!$isEstoque && !empty($product['sale_date'])): ?>
                                    <?= (new DateTime($product['sale_date']))->format('d/m/Y') ?>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($product['client_name']) && $product['client_name'] !== 'Em Estoque'): ?>
                                    <?= $product['client_city'] ?>/<?= $product['client_state'] ?>
                                <?php else: ?>
                                    <span class="text-muted small">Em Estoque</span>
                                <?php endif; ?>
                            </td>
                            <td class="small"><?= $product['warranty'] ?></td>
                            <td>
                                <?php if (!empty($product['notes'])): ?>
                                    <button class="btn btn-sm btn-link text-warning p-0 view-product-notes" 
                                            data-notes="<?= htmlspecialchars($product['notes']) ?>"
                                            title="Ver Observações">
                                        <i class="fas fa-sticky-note"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex justify-content-center align-items-center gap-2">
                                    <button class="btn btn-sm btn-light action-btn edit-mcp" data-id="<?= $product['id'] ?>" title="Editar">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                    <button class="btn btn-sm btn-light action-btn delete-mcp" data-id="<?= $product['id'] ?>" title="Excluir">
                                        <i class="fas fa-trash-alt text-danger"></i>
                                    </button>
                                    <button class="btn btn-sm btn-light action-btn print-qrcode" data-id="<?= $product['id'] ?>" title="Imprimir QR">
                                        <i class="fas fa-qrcode"></i>
                                    </button>
                                    <?php if ($product['status_name'] === 'in_composite' && !empty($product['parent_composite_id'])): ?>
                                        <button class="btn btn-sm btn-light action-btn" onclick="viewCompositeRelation(<?= $product['id'] ?>, 'component')" title="Ver Produto Composto">
                                            <i class="fas fa-cube text-info"></i>
                                        </button>
                                    <?php elseif (isset($product['is_composite']) && $product['is_composite'] == 1): ?>
                                        <button class="btn btn-sm btn-light action-btn" onclick="viewCompositeRelation(<?= $product['id'] ?>, 'composite')" title="Ver Componentes">
                                            <i class="fas fa-cubes text-info"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="d-none"><?= $product['status_name'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
/* === Summary Cards === */
.summary-card {
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    transition: transform 0.2s, box-shadow 0.2s;
}
.summary-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0,0,0,0.1);
}
.summary-card-body {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px 18px;
}
.summary-icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    flex-shrink: 0;
}
.summary-value {
    font-size: 1.4rem;
    font-weight: 700;
    line-height: 1.1;
    color: #1a1a2e;
}
.summary-label {
    font-size: 0.78rem;
    color: #6c757d;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

/* === Filter Card === */
.filter-card {
    border: 1px solid #e9ecef;
    border-radius: 10px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.04);
    background: #fff;
}
.filter-label {
    display: block;
    font-size: 0.7rem;
    font-weight: 600;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 3px;
}
.filter-card .form-select-sm {
    font-size: 0.82rem;
    border-radius: 6px;
    border-color: #dee2e6;
    transition: border-color 0.2s, box-shadow 0.2s;
}
.filter-card .form-select-sm:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 3px rgba(13,110,253,0.15);
}

/* === Table Card === */
.table-card {
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    border: none;
}
.table-card .table thead th {
    background: #f1f3f5;
    border-bottom: 2px solid #dee2e6;
    font-size: 0.78rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    color: #495057;
    padding: 10px 14px;
    white-space: nowrap;
}
.table-card .table tbody td {
    padding: 8px 14px;
    vertical-align: middle;
    font-size: 0.85rem;
    border-bottom: 1px solid #f1f3f5;
}
.table-card .table tbody tr:hover {
    background-color: rgba(13,110,253,0.03);
}

/* === Status Badge === */
.status-badge {
    display: inline-flex;
    align-items: center;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 4px 10px;
    border-radius: 20px;
    background: color-mix(in srgb, var(--status-color) 15%, white);
    color: var(--status-color);
    white-space: nowrap;
}

/* === Action Buttons === */
.action-btn {
    width: 30px;
    height: 30px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    border: 1px solid #e9ecef;
    font-size: 0.8rem;
    transition: all 0.15s;
}
.action-btn:hover {
    background: #e9ecef;
    transform: translateY(-1px);
}

/* === Badges === */
.badge.rounded-pill {
    font-size: 0.78rem;
    font-weight: 600;
    padding: 4px 10px;
}

/* === DataTables overrides === */
#mcp-table_wrapper .dataTables_filter {
    display: none;
}
#mcp-table_wrapper .dataTables_info {
    font-size: 0.82rem;
    color: #6c757d;
    padding: 12px 16px;
}
#mcp-table_wrapper .dataTables_paginate {
    padding: 8px 16px 12px;
}
#mcp-table_wrapper .dataTables_paginate .paginate_button {
    border-radius: 6px !important;
    margin: 0 2px;
    font-size: 0.82rem;
}
#mcp-table_wrapper .dataTables_paginate .paginate_button.current {
    background: #0d6efd !important;
    border-color: #0d6efd !important;
    color: #fff !important;
}
#mcp-table_wrapper .dataTables_length {
    padding: 12px 16px;
    font-size: 0.82rem;
}

/* === Active filter indicator === */
.filter-card .form-select-sm.active-filter {
    border-color: #0d6efd;
    background-color: rgba(13,110,253,0.03);
}
</style>

<script>
// Remova qualquer inicialização anterior do DataTable
var existingTable = $('#mcp-table').DataTable();
if (existingTable) {
    existingTable.destroy();
}
$('#mcp-table').off();

// Inicialize o DataTable
var table = $('#mcp-table').DataTable({
    pagingType: "full_numbers",
    lengthMenu: [
        [25, 50, 100, -1],
        [25, 50, 100, "Todos"]
    ],
    pageLength: 25,
    responsive: true,
    language: {
        "sEmptyTable": "Nenhum registro encontrado",
        "sInfo": "Mostrando _START_ a _END_ de _TOTAL_ registros",
        "sInfoEmpty": "Mostrando 0 de 0 registros",
        "sInfoFiltered": "(filtrados de _MAX_)",
        "sInfoThousands": ".",
        "sLengthMenu": "Exibir _MENU_",
        "sLoadingRecords": "Carregando...",
        "sProcessing": "Processando...",
        "sZeroRecords": "Nenhum registro encontrado",
        "sSearch": "",
        "sSearchPlaceholder": "Busca rápida...",
        "oPaginate": {
            "sNext": "<i class='fas fa-chevron-right'></i>",
            "sPrevious": "<i class='fas fa-chevron-left'></i>",
            "sFirst": "<i class='fas fa-angles-left'></i>",
            "sLast": "<i class='fas fa-angles-right'></i>"
        }
    },
    dom: '<"d-flex justify-content-between align-items-center px-3 pt-3"lf>rtip',
    order: [],
    destroy: true,
    initComplete: function() {
        $('#mcp-table').addClass('initialized');
        $('.table-loading-static').remove();
    },
    columnDefs: [
        { targets: 10, visible: false, searchable: true },
        { targets: [8, 9], orderable: false }
    ],
    buttons: [
        {
            extend: 'excelHtml5',
            text: '<i class="fas fa-file-excel me-1"></i>Excel',
            className: 'btn btn-sm btn-outline-success',
            exportOptions: { columns: [0,1,2,3,4,5,6,7] }
        },
        {
            extend: 'pdfHtml5',
            text: '<i class="fas fa-file-pdf me-1"></i>PDF',
            className: 'btn btn-sm btn-outline-danger',
            exportOptions: { columns: [0,1,2,3,4,5,6,7] },
            orientation: 'landscape',
            pageSize: 'A4'
        }
    ]
});

// Conectar botões de exportação do header
$('#btnExportExcel').on('click', function() {
    table.button('.buttons-excel').trigger();
});
$('#btnExportPdf').on('click', function() {
    table.button('.buttons-pdf').trigger();
});

// === Filtros por coluna ===
// Col 0 = Lote, Col 1 = Pedido, Col 2 = Produto, Col 6 = Cliente, Col 10 = status_slug
function applyFilters() {
    var batch = $('#filterBatch').val();
    var order = $('#filterOrder').val();
    var product = $('#filterProduct').val();
    var client = $('#filterClient').val();
    var status = $('#filterStatus').val();

    // Lote (col 0)
    table.column(0).search(batch ? $.fn.dataTable.util.escapeRegex(batch) : '', true, false);
    // Pedido (col 1)
    table.column(1).search(order ? '^\\s*' + $.fn.dataTable.util.escapeRegex(order) + '\\s*$' : '', true, false);
    // Produto (col 2)
    table.column(2).search(product ? '^' + $.fn.dataTable.util.escapeRegex(product) + '$' : '', true, false);
    // Cliente (col 6)
    table.column(6).search(client ? $.fn.dataTable.util.escapeRegex(client) : '', true, false);
    // Status (col 10 - hidden slug)
    table.column(10).search(status ? '^' + $.fn.dataTable.util.escapeRegex(status) + '$' : '', true, false);

    table.draw();

    // Visual indicator
    $('.filter-card .form-select-sm').each(function() {
        $(this).toggleClass('active-filter', $(this).val() !== '');
    });
}

$('#filterBatch, #filterOrder, #filterProduct, #filterClient, #filterStatus').on('change', applyFilters);

$('#btnClearFilters').on('click', function() {
    $('#filterBatch, #filterOrder, #filterProduct, #filterClient, #filterStatus').val('');
    applyFilters();
});

// Modal de Observações do Produto
const productNotesModalHtml = `
<div class="modal fade" id="productNotesModal" tabindex="-1" aria-labelledby="productNotesModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="productNotesModalLabel">
          <i class="fas fa-sticky-note me-2"></i>
          Observações do Produto
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="product-notes-content mb-0"></p>
      </div>
    </div>
  </div>
</div>`;

if (!document.getElementById('productNotesModal')) {
  const container = document.createElement('div');
  container.innerHTML = productNotesModalHtml;
  document.body.appendChild(container.firstElementChild);
}

$(document).on('click', '.view-product-notes', function() {
  const notes = $(this).data('notes') || '';
  $('#productNotesModal .product-notes-content').text(notes);
  const modal = new bootstrap.Modal(document.getElementById('productNotesModal'));
  modal.show();
});

// Função para visualizar relação de produtos compostos
function viewCompositeRelation(id, type) {
    if (type === 'component') {
        $.get(`${window.basePath}/src/controllers/CompositeController.php?action=get_parent_composite&component_id=${id}`, function(response) {
            const data = typeof response === 'string' ? JSON.parse(response) : response;
            
            if (!data.assembly) {
                Swal.fire('Erro!', 'Produto composto não encontrado.', 'error');
                return;
            }

            Swal.fire({
                title: `Produto Pai`,
                html: `
                    <div class="text-start">
                        <p><strong>Serial:</strong> ${data.assembly.composite_serial || 'N/A'}</p>
                        <p><strong>Produto:</strong> ${data.assembly.composite_tipo_name || 'N/A'}</p>
                        <p><strong>Status:</strong> ${data.assembly.composite_destination || 'N/A'}</p>
                        <p><strong>Montado em:</strong> ${data.assembly.created_at ? new Date(data.assembly.created_at).toLocaleDateString('pt-BR') : 'N/A'}</p>
                        <p><strong>Montado por:</strong> ${data.assembly.created_by_name || 'N/A'}</p>
                    </div>
                `,
                width: '500px'
            });
        }).fail(function() {
            Swal.fire('Erro!', 'Erro ao carregar informações do produto composto.', 'error');
        });
    } else {
        $.get(`${window.basePath}/src/controllers/CompositeController.php?action=get_composite_by_product&product_id=${id}`, function(response) {
            const data = typeof response === 'string' ? JSON.parse(response) : response;
            
            if (!data.assembly) {
                Swal.fire('Erro!', 'Assembly não encontrada para este produto.', 'error');
                return;
            }
            
            let componentsHtml = '';
            if (data.components && data.components.length > 0) {
                componentsHtml = '<div class="list-group mt-3">';
                data.components.forEach(comp => {
                    componentsHtml += `
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">${comp.component_tipo_name}</h6>
                                <small>${comp.component_serial}</small>
                            </div>
                        </div>`;
                });
                componentsHtml += '</div>';
            } else {
                componentsHtml = '<p class="text-muted">Nenhum componente encontrado.</p>';
            }

            Swal.fire({
                title: `Componentes do Produto #${data.assembly.composite_serial || 'N/A'}`,
                html: `
                    <div class="text-start">
                        <p><strong>Produto:</strong> ${data.assembly.composite_tipo_name || 'N/A'}</p>
                        <p><strong>Status:</strong> ${data.assembly.composite_destination || 'N/A'}</p>
                        <p><strong>Cliente:</strong> ${data.assembly.client_name || 'N/A'}</p>
                        <p><strong>Nota Fiscal/Empenho:</strong> ${data.assembly.nfe || data.assembly.pp_number || 'N/A'}</p>
                        <p><strong>Montado em:</strong> ${data.assembly.created_at ? new Date(data.assembly.created_at).toLocaleDateString('pt-BR') : 'N/A'}</p>
                        <p><strong>Montado por:</strong> ${data.assembly.created_by_name || 'N/A'}</p>
                        <h6 class="mt-3">Componentes:</h6>
                        ${componentsHtml}
                    </div>
                `,
                width: '600px'
            });
        }).fail(function() {
            Swal.fire('Erro!', 'Erro ao carregar componentes do produto.', 'error');
        });
    }
}
</script>