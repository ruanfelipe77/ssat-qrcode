<?php
require_once 'src/models/Client.php';
require_once 'src/models/Tipo.php';

$clientModel = new Client(Database::getInstance()->getConnection());
$clients = $clientModel->getAll();

$tipoModel = new Tipo(Database::getInstance()->getConnection());
$tipos = $tipoModel->getAll();
?>

<!-- Modal de Pedido de Produção -->
<div class="modal fade" id="ppModal" tabindex="-1" aria-labelledby="ppModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ppModalLabel">
                    <i class="fas fa-clipboard-list me-2"></i>
                    <span class="modal-title-text">Novo Pedido de Produção</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="ppForm" class="needs-validation" novalidate>
                    <input type="hidden" id="action" name="action" value="create_pp">

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="client_id" class="form-label">Cliente</label>
                            <select class="form-select" name="client_id" id="client_id" required>
                                <option value="">Selecione um cliente</option>
                                <?php foreach ($clients as $client) : ?>
                                    <option value="<?= $client['id'] ?>">
                                        <?= $client['name'] ?> (<?= $client['city'] ?>/<?= $client['state'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">
                                Por favor, selecione um cliente.
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="order_date" class="form-label">Data do Pedido</label>
                            <input type="date" class="form-control" id="order_date" name="order_date" required>
                            <div class="invalid-feedback">
                                Por favor, informe a data do pedido.
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="tipo_id" class="form-label">Tipo de Produto</label>
                            <select class="form-select" name="tipo_id" id="tipo_id" required>
                                <option value="">Selecione um tipo</option>
                                <?php foreach ($tipos as $tipo) : ?>
                                    <option value="<?= $tipo['id'] ?>">
                                        <?= $tipo['nome'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">
                                Por favor, selecione um tipo.
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="warranty" class="form-label">Garantia</label>
                            <input type="text" class="form-control" id="warranty" name="warranty" required>
                            <div class="invalid-feedback">
                                Por favor, informe a garantia.
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="serial_start" class="form-label">Número de Série Inicial</label>
                            <input type="number" class="form-control" id="serial_start" name="serial_start" required>
                            <div class="invalid-feedback">
                                Por favor, informe o número inicial.
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="quantity" class="form-label">Quantidade</label>
                            <input type="number" class="form-control" id="quantity" name="quantity" required min="1">
                            <div class="invalid-feedback">
                                Por favor, informe a quantidade.
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="serial_preview" class="form-label">Preview</label>
                            <input type="text" class="form-control" id="serial_preview" readonly>
                            <small class="text-muted">Range de números de série</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label">Observações</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>

                    <div class="modal-footer px-0 pb-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Criar Pedido
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Preview do range de números de série
    function updateSerialPreview() {
        const start = parseInt($("#serial_start").val()) || 0;
        const quantity = parseInt($("#quantity").val()) || 0;
        
        if (start && quantity) {
            const end = start + quantity - 1;
            $("#serial_preview").val(`${start} até ${end}`);
        } else {
            $("#serial_preview").val('');
        }
    }

    $("#serial_start, #quantity").on('input', updateSerialPreview);

    // Submissão do formulário
    $("#ppForm").on('submit', function(e) {
        e.preventDefault();
        
        if (!this.checkValidity()) {
            e.stopPropagation();
            $(this).addClass('was-validated');
            return;
        }

        const formData = $(this).serialize();

        Swal.fire({
            title: 'Confirmação',
            html: `
                Você está prestes a criar um pedido com:<br>
                <b>${$("#quantity").val()}</b> produtos<br>
                Números de série: <b>${$("#serial_preview").val()}</b><br><br>
                Deseja continuar?
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sim, criar pedido',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    type: 'POST',
                    url: 'src/controllers/ProductionOrderController.php',
                    data: formData,
                    success: function(response) {
                        const res = JSON.parse(response);
                        if (res.success) {
                            Swal.fire({
                                title: 'Sucesso!',
                                text: `Pedido ${res.pp_number} criado com sucesso!`,
                                icon: 'success'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'Erro!',
                                text: res.message || 'Erro ao criar pedido',
                                icon: 'error'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            title: 'Erro!',
                            text: 'Erro ao criar pedido',
                            icon: 'error'
                        });
                    }
                });
            }
        });
    });
});
</script>
