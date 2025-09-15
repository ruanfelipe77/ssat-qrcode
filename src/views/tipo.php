<?php
require 'database.php';
require 'src/models/Tipo.php';

$database = Database::getInstance();
$db = $database->getConnection();

$TipoModel = new Tipo($db);
$tipos = $TipoModel->getAll();
?>

<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Tipos de Produto</h2>
        <button class="btn btn-primary" id="add-produto">
            <i class="fas fa-plus me-2"></i>Novo Tipo
        </button>
    </div>

    <div class="card">
        <div class="card-body">
            <table id="tipos-table" class="table table-striped table-hover" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th style="width: 120px; text-align: center;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tipos as $tipo) : ?>
                        <tr>
                            <td><?= $tipo['id'] ?></td>
                            <td><?= $tipo['nome'] ?></td>
                            <td>
                                <div class="d-flex justify-content-center align-items-center gap-3">
                                    <button class="btn btn-link text-dark p-0 edit-tipo" data-id="<?= $tipo['id'] ?>" title="Editar">
                                        <i class="fas fa-edit fs-5"></i>
                                    </button>
                                    <button class="btn btn-link text-dark p-0 delete-tipo" data-id="<?= $tipo['id'] ?>" title="Excluir">
                                        <i class="fas fa-trash fs-5"></i>
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

<script>
// Remova qualquer inicialização anterior do DataTable
var existingTable = $('#tipos-table').DataTable();
if (existingTable) {
    existingTable.destroy();
}

// Limpe os eventos anteriores
$('#tipos-table').off();

// Inicialize o DataTable
var table = $('#tipos-table').DataTable({
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
    destroy: true // Permite reinicialização
});
</script>