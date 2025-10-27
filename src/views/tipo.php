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
// Abertura do modal para NOVO tipo
$(document).on('click', '#add-produto', function () {
    const form = $('#tipoForm');
    form.removeClass('was-validated');
    form[0].reset();
    $('#action_tipo').val('add');
    $('#tipoForm [name="tipo_id"]').val('');
    $('.modal-title-text').text('Adicionar Tipo de Produto');
    $('.btn-acao').html('<i class="fas fa-save me-2"></i>Salvar');
    const modal = new bootstrap.Modal(document.getElementById('tipoModal'));
    modal.show();
});

// Abrir modal para EDITAR tipo
$(document).on('click', '.edit-tipo', function () {
    const id = $(this).data('id');
    console.log('ID do botão clicado:', id);
    $.get('src/controllers/TipoController.php', { id }, function (resp) {
        console.log('Resposta do GET:', resp);
        const tipo = (typeof resp === 'string') ? JSON.parse(resp) : resp;
        console.log('Dados do tipo:', tipo);
        const form = $('#tipoForm');
        form.removeClass('was-validated');
        // Não usar reset(), preencher campos diretamente
        $('#action_tipo').val('edit');
        $('#tipoForm [name="tipo_id"]').val(tipo.id);
        $('#nome').val(tipo.nome);
        console.log('Valores definidos - ID:', $('#tipoForm [name="tipo_id"]').val(), 'Nome:', $('#nome').val());
        $('.modal-title-text').text('Editar Tipo de Produto');
        $('.btn-acao').html('<i class="fas fa-save me-2"></i>Atualizar');
        const modal = new bootstrap.Modal(document.getElementById('tipoModal'));
        modal.show();
    }).fail(function(){
        Swal.fire({ title: 'Erro', text: 'Não foi possível carregar o tipo.', icon: 'error' });
    });
});

// Submissão do formulário (ADD/EDIT)
$(document).on('submit', '#tipoForm', function (e) {
    e.preventDefault();
    const form = this;
    if (!form.checkValidity()) {
        e.stopPropagation();
        form.classList.add('was-validated');
        return;
    }
    const data = $(form).serialize();
    console.log('Dados sendo enviados:', data);
    $.post('src/controllers/TipoController.php', data, function (resp) {
        console.log('Resposta raw do servidor:', resp);
        console.log('Tipo da resposta:', typeof resp);
        
        try {
            const res = (typeof resp === 'string') ? JSON.parse(resp) : resp;
            console.log('Resposta parseada:', res);
            
            if (res.success) {
                const modal = bootstrap.Modal.getInstance(document.getElementById('tipoModal'));
                if (modal) modal.hide();
                Swal.fire({ title: 'Sucesso', text: 'Registro salvo com sucesso!', icon: 'success' })
                    .then(() => location.reload());
            } else {
                Swal.fire({ title: 'Erro', text: res.message || 'Falha ao salvar.', icon: 'error' });
            }
        } catch (e) {
            console.error('Erro ao processar resposta:', e);
            console.log('Resposta que causou erro:', resp);
            console.log('Tamanho da resposta:', resp.length);
            console.log('Primeiros 500 caracteres:', resp.substring(0, 500));
            Swal.fire({ title: 'Erro', text: 'Resposta inválida do servidor.', icon: 'error' });
        }
    }).fail(function(xhr, status, error){
        console.error('Erro na requisição:', { xhr, status, error });
        Swal.fire({ title: 'Erro', text: 'Falha na comunicação com o servidor.', icon: 'error' });
    });
});

// Deleção
$(document).on('click', '.delete-tipo', function () {
    const id = $(this).data('id');
    Swal.fire({
        title: 'Confirmar exclusão',
        text: 'Esta ação não poderá ser desfeita.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fas fa-trash me-2"></i>Excluir',
        cancelButtonText: '<i class="fas fa-times me-2"></i>Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('src/controllers/TipoController.php', { action_tipo: 'delete', id }, function (resp) {
                const res = (typeof resp === 'string') ? JSON.parse(resp) : resp;
                if (res.success) {
                    Swal.fire({ title: 'Excluído', text: 'Tipo excluído com sucesso.', icon: 'success' })
                        .then(() => location.reload());
                } else {
                    Swal.fire({ title: 'Erro', text: res.message || 'Falha ao excluir.', icon: 'error' });
                }
            }).fail(function(){
                Swal.fire({ title: 'Erro', text: 'Falha na comunicação com o servidor.', icon: 'error' });
            });
        }
    });
});

// Aguarda o DOM estar pronto
$(document).ready(function() {
    // Verifica se já existe uma instância do DataTable antes de tentar destruir
    if ($.fn.DataTable.isDataTable('#tipos-table')) {
        $('#tipos-table').DataTable().destroy();
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
        dom: "frtip",
        destroy: true // Permite reinicialização
    });
});
</script>