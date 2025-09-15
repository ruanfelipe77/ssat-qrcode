<?php
require_once 'src/models/Client.php';
$clientModel = new Client(Database::getInstance()->getConnection());
$clients = $clientModel->getAll();
?>
<!-- Modal -->
<div class="modal fade" id="mcpModal" tabindex="-1" aria-labelledby="mcpModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="mcpModalLabel">
                    <i class="fas fa-box me-2"></i>
                    <span class="modal-title-text">Adicionar Produto</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="mcpForm" class="needs-validation" novalidate>
                    <input type="hidden" id="action" name="action">
                    <input type="hidden" id="id" name="id">

                    <div class="mb-3">
                        <label for="tipo_id" class="form-label">Tipo</label>
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

                    <div class="mb-3">
                        <label for="serial_number" class="form-label">Número de Série</label>
                        <input type="text" class="form-control" id="serial_number" name="serial_number" required>
                        <div class="invalid-feedback">
                            Por favor, informe o número de série.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="sale_date" class="form-label">Data de Venda</label>
                        <input type="date" class="form-control" id="sale_date" name="sale_date" required>
                        <div class="invalid-feedback">
                            Por favor, informe a data de venda.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="destination" class="form-label">Cliente</label>
                        <select class="form-select" name="destination" id="destination" required>
                            <option value="">Selecione um cliente</option>
                            <?php foreach ($clients as $client) : ?>
                                <option value="<?= $client['id'] ?>">
                                    <?= $client['name'] ?> (<?= $client['city'] ?>/<?= $client['state'] ?>)
                                </option>
                            <?php endforeach; ?>
                            <option value="estoque">Em Estoque</option>
                        </select>
                        <div class="invalid-feedback">
                            Por favor, selecione um cliente ou marque como estoque.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="warranty" class="form-label">Garantia</label>
                        <input type="text" class="form-control" id="warranty" name="warranty" required>
                        <div class="invalid-feedback">
                            Por favor, informe a garantia.
                        </div>
                    </div>

                    <div class="modal-footer px-0 pb-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary btn-acao">
                            <i class="fas fa-save me-2"></i>Salvar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.modal-content {
    border: none;
    border-radius: 0.5rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.modal-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    border-top-left-radius: 0.5rem;
    border-top-right-radius: 0.5rem;
}

.modal-footer {
    background-color: #f8f9fa;
    border-top: 1px solid #dee2e6;
    border-bottom-left-radius: 0.5rem;
    border-bottom-right-radius: 0.5rem;
}

.form-label {
    font-weight: 500;
    margin-bottom: 0.5rem;
}

.form-control, .form-select {
    padding: 0.75rem 1rem;
    border-radius: 0.375rem;
}

.form-control:focus, .form-select:focus {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const form = document.getElementById('mcpForm');
    
    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        form.classList.add('was-validated');
    });

    // Reset form validation when modal is hidden
    const modal = document.getElementById('mcpModal');
    modal.addEventListener('hidden.bs.modal', function() {
        form.classList.remove('was-validated');
        form.reset();
    });

    // Update modal title based on action
    document.getElementById('add-mcp').addEventListener('click', function() {
        document.querySelector('.modal-title-text').textContent = 'Adicionar Produto';
    });

    document.querySelectorAll('.edit-mcp').forEach(button => {
        button.addEventListener('click', function() {
            document.querySelector('.modal-title-text').textContent = 'Editar Produto';
        });
    });
});
</script>