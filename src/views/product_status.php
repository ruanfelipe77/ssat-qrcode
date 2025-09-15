<?php
require 'database.php';
require 'src/models/ProductStatus.php';

$database = Database::getInstance();
$db = $database->getConnection();

$statusModel = new ProductStatus($db);
$statuses = $statusModel->getAll();
?>

<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">
            <i class="fas fa-tags me-2"></i>
            Status de Produtos
        </h2>
        <button class="btn btn-primary" id="add-status">
            <i class="fas fa-plus me-2"></i>Novo Status
        </button>
    </div>

    <div class="card">
        <div class="card-body">
            <table id="status-table" class="table table-striped table-hover" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Status</th>
                        <th>Descrição</th>
                        <th>Cor</th>
                        <th>Ícone</th>
                        <th>Ativo</th>
                        <th style="width: 120px; text-align: center;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($statuses as $status) : ?>
                        <tr>
                            <td><?= $status['id'] ?></td>
                            <td>
                                <span class="badge d-flex align-items-center" 
                                      style="background-color: <?= $status['color'] ?>; width: fit-content;">
                                    <i class="<?= $status['icon'] ?> me-2"></i>
                                    <?= ucfirst(str_replace('_', ' ', $status['name'])) ?>
                                </span>
                            </td>
                            <td><?= $status['description'] ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="color-preview me-2" 
                                         style="width: 20px; height: 20px; background-color: <?= $status['color'] ?>; border-radius: 3px; border: 1px solid #dee2e6;"></div>
                                    <code><?= $status['color'] ?></code>
                                </div>
                            </td>
                            <td>
                                <i class="<?= $status['icon'] ?> me-2"></i>
                                <code><?= $status['icon'] ?></code>
                            </td>
                            <td>
                                <?php if ($status['is_active']): ?>
                                    <span class="badge bg-success">Ativo</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inativo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex justify-content-center align-items-center gap-3">
                                    <button class="btn btn-link text-dark p-0 edit-status" 
                                            data-id="<?= $status['id'] ?>" 
                                            title="Editar">
                                        <i class="fas fa-edit fs-5"></i>
                                    </button>
                                    <?php if ($status['id'] > 6): // Não permitir deletar status padrão ?>
                                        <button class="btn btn-link text-danger p-0 delete-status" 
                                                data-id="<?= $status['id'] ?>"
                                                data-name="<?= htmlspecialchars($status['name']) ?>"
                                                title="Excluir">
                                            <i class="fas fa-trash fs-5"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'src/views/modal_product_status.php'; ?>

<style>
.badge {
    font-size: 0.875rem;
    padding: 0.5rem 0.75rem;
}

.btn-link:hover {
    opacity: 0.7;
    transform: scale(1.1);
    transition: all 0.2s ease;
}

.btn-link:active {
    transform: scale(0.95);
}

.color-preview {
    box-shadow: 0 1px 3px rgba(0,0,0,0.2);
}
</style>

<script>
$(document).ready(function () {
    // Inicialização do DataTable
    if ($.fn.DataTable.isDataTable("#status-table")) {
        $("#status-table").DataTable().destroy();
    }
    
    var table = $("#status-table").DataTable({
        destroy: true,
        pagingType: "full_numbers",
        lengthMenu: [
            [10, 25, 50, -1],
            [10, 25, 50, "Todos"],
        ],
        responsive: true,
        language: {
            url: "//cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json",
        },
        dom: "Bfrtip",
        buttons: [
            {
                extend: "excel",
                className: "btn btn-success",
                text: '<i class="fas fa-file-excel me-2"></i>Excel',
            },
            {
                extend: "pdf",
                className: "btn btn-danger",
                text: '<i class="fas fa-file-pdf me-2"></i>PDF',
            },
            {
                extend: "print",
                className: "btn btn-info",
                text: '<i class="fas fa-print me-2"></i>Imprimir',
            },
        ],
        order: [[0, "asc"]],
    });
});
</script>
