<?php
require 'database.php';
require 'src/models/Product.php';
require 'src/models/Tipo.php';

$database = Database::getInstance();
$db = $database->getConnection();

$productModel = new Product($db);
$products = $productModel->getAll();

$tipoModel = new Tipo($db);
$tipos = $tipoModel->getAll();
?>

<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Lista de Produtos</h2>
        <div class="d-flex gap-2">
            <div class="btn-group">
                <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-filter me-2"></i>Status
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item active" href="#">Todos</a></li>
                    <li><a class="dropdown-item" href="#">Em Estoque</a></li>
                    <li><a class="dropdown-item" href="#">Vendidos</a></li>
                </ul>
            </div>
            <button class="btn btn-primary" id="add-mcp">
                <i class="fas fa-plus me-2"></i>Novo Produto
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table id="mcp-table" class="table table-striped table-hover" style="width:100%">
                <thead>
                    <tr>
                        <th>Lote</th>
                        <th>PP</th>
                        <th>Produto</th>
                        <th>Número de série</th>
                        <th>Data de venda</th>
                        <th>Cliente</th>
                        <th>Garantia</th>
                        <th style="width: 150px; text-align: center;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product) : ?>
                        <tr>
                            <td>
                                <?php if ($product['batch_number']): ?>
                                    <span class="badge bg-info">
                                        <?= $product['batch_number'] ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Sem Lote</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($product['pp_number']): ?>
                                    <span class="badge bg-primary">
                                        <?= $product['pp_number'] ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-success">Em Estoque</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $product['tipo_name'] ?></td>
                            <td><?= $product['serial_number'] ?></td>
                            <td>
                                <?php if ($product['sale_date']): ?>
                                    <?= (new DateTime($product['sale_date']))->format('d/m/Y') ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($product['client_name'] && $product['client_name'] !== 'Em Estoque'): ?>
                                    <?= $product['client_name'] ?>
                                    <?php if (!empty($product['client_city']) && !empty($product['client_state'])): ?>
                                        <br>
                                        <small class="text-muted">
                                            <?= $product['client_city'] ?>/<?= $product['client_state'] ?>
                                        </small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">Em Estoque</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $product['warranty'] ?></td>
                            <td>
                                <div class="d-flex justify-content-center align-items-center gap-3">
                                    <button class="btn btn-link text-dark p-0 edit-mcp" data-id="<?= $product['id'] ?>" title="Editar">
                                        <i class="fas fa-edit fs-5"></i>
                                    </button>
                                    <button class="btn btn-link text-dark p-0 delete-mcp" data-id="<?= $product['id'] ?>" title="Excluir">
                                        <i class="fas fa-trash fs-5"></i>
                                    </button>
                                    <button class="btn btn-link text-dark p-0 print-qrcode" data-id="<?= $product['id'] ?>" title="Imprimir QR Code">
                                        <i class="fas fa-print fs-5"></i>
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

<style>
.badge {
    font-size: 0.875rem;
    padding: 0.5rem 0.75rem;
}

.badge.bg-info {
    background-color: #0dcaf0 !important;
}

.badge.bg-primary {
    background-color: #0d6efd !important;
}

.badge.bg-success {
    background-color: #198754 !important;
}

.badge.bg-secondary {
    background-color: #6c757d !important;
}

.btn-link:hover {
    opacity: 0.7;
    transform: scale(1.1);
    transition: all 0.2s ease;
}

.btn-link:active {
    transform: scale(0.95);
}

.dropdown-item.active {
    background-color: #0d6efd;
    color: white;
}
</style>

<script>
// Remova qualquer inicialização anterior do DataTable
var existingTable = $('#mcp-table').DataTable();
if (existingTable) {
    existingTable.destroy();
}

// Limpe os eventos anteriores
$('#mcp-table').off();

// Inicialize o DataTable
var table = $('#mcp-table').DataTable({
    pagingType: "full_numbers",
    lengthMenu: [
        [10, 25, 50, -1],
        [10, 25, 50, "Todos"]
    ],
    responsive: true,
    language: {
        url: "//cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json"
    },
    dom: 'Bfrtip',
    buttons: [
        {
            extend: 'excel',
            className: 'btn btn-success',
            text: '<i class="fas fa-file-excel me-2"></i>Excel'
        },
        {
            extend: 'pdf',
            className: 'btn btn-danger',
            text: '<i class="fas fa-file-pdf me-2"></i>PDF'
        },
        {
            extend: 'print',
            className: 'btn btn-info',
            text: '<i class="fas fa-print me-2"></i>Imprimir'
        }
    ],
    order: [[0, 'desc']], // Ordenar por Lote (mais recente primeiro)
    destroy: true
});

// Filtros
$('.dropdown-item').on('click', function(e) {
    e.preventDefault();
    $('.dropdown-item').removeClass('active');
    $(this).addClass('active');
    
    const filter = $(this).text().trim();
    
    if (filter === 'Todos') {
        table.column(1).search('').draw();
    } else if (filter === 'Em Estoque') {
        table.column(1).search('Em Estoque').draw();
    } else if (filter === 'Vendidos') {
        table.column(1).search('^(?!.*Em Estoque).*$', true).draw();
    }
});
</script>