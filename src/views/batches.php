<?php
require 'database.php';
require 'src/models/ProductionBatch.php';
require 'src/models/Tipo.php';
require 'src/models/ProductStatus.php';

$database = Database::getInstance();
$db = $database->getConnection();

$batchModel = new ProductionBatch($db);
$batches = $batchModel->getAll();

$tipoModel = new Tipo($db);
$tipos = $tipoModel->getAll();

$statusModel = new ProductStatus($db);
$statuses = $statusModel->getActive();
?>

<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Lotes de Produção</h2>
        <button class="btn btn-primary" id="add-batch">
            <i class="fas fa-plus me-2"></i>Novo Lote
        </button>
    </div>

    <div class="card">
        <div class="card-body">
            <table id="batches-table" class="table table-striped table-hover" style="width:100%">
                <thead>
                    <tr>
                        <th>Lote</th>
                        <th>Data Produção</th>
                        <th>Produto</th>
                        <th>Total Produtos</th>
                        <th>Disponíveis</th>
                        <th>Observações</th>
                        <th style="width: 150px; text-align: center;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($batches as $batch) : ?>
                        <tr>
                            <td>
                                <span class="badge bg-info">
                                    <?= $batch['batch_number'] ?>
                                </span>
                            </td>
                            <td><?= (new DateTime($batch['production_date']))->format('d/m/Y') ?></td>
                            <td>
                                <span class="text-dark fw-semibold"><?= htmlspecialchars($batch['tipo_name']) ?></span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-primary">
                                    <?= $batch['total_products'] ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <?php if ($batch['available_products'] > 0): ?>
                                    <span class="badge bg-success">
                                        <?= $batch['available_products'] ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">0</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($batch['notes'])): ?>
                                    <button class="btn btn-link text-dark p-0 view-notes" 
                                            data-notes="<?= htmlspecialchars($batch['notes']) ?>"
                                            title="Ver Observações">
                                        <i class="fas fa-sticky-note"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex justify-content-center align-items-center gap-3">
                                    <button class="btn btn-link text-dark p-0 view-products" 
                                            data-id="<?= $batch['id'] ?>"
                                            data-batch="<?= htmlspecialchars($batch['batch_number']) ?>"
                                            title="Ver Produtos">
                                        <i class="fas fa-box-open fs-5"></i>
                                    </button>
                                    <button class="btn btn-link text-dark p-0 print-all" 
                                            data-id="<?= $batch['id'] ?>"
                                            title="Imprimir QR Codes">
                                        <i class="fas fa-print fs-5"></i>
                                    </button>
                                    <button class="btn btn-link text-danger p-0 delete-batch" 
                                            data-id="<?= $batch['id'] ?>"
                                            data-batch="<?= htmlspecialchars($batch['batch_number']) ?>"
                                            data-products="<?= $batch['total_products'] ?>"
                                            title="Excluir Lote">
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

<!-- Modal de Novo Lote -->
<div class="modal fade" id="batchModal" tabindex="-1" aria-labelledby="batchModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="batchModalLabel">
                    <i class="fas fa-layer-group me-2"></i>
                    Novo Lote de Produção
                    <small class="text-muted ms-2" id="batch-preview"><?= date('mY') ?>/0001</small>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info d-flex align-items-center mb-3">
                    <i class="fas fa-info-circle me-2"></i>
                    <small>
                        <strong>Numeração automática:</strong> O lote será criado com formato 
                        <code><?= date('mY') ?>/XXXX</code> (mês/ano + sequencial)
                    </small>
                </div>

                <form id="batchForm" class="needs-validation" novalidate>
                    <input type="hidden" name="action" value="create_batch">

                    <div class="row mb-3">
                        <div class="col-md-8">
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
                        <div class="col-md-4">
                            <label for="production_date" class="form-label">Data de Produção</label>
                            <input type="date" class="form-control" id="production_date" name="production_date" required>
                            <div class="invalid-feedback">
                                Por favor, informe a data de produção.
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="serial_start" class="form-label">Número Inicial (automático)</label>
                            <input type="number" class="form-control" id="serial_start" name="serial_start" required readonly>
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
                            <input type="text" class="form-control" id="serial_preview" readonly 
                                   style="background-color: #f8f9fa; font-weight: 500;">
                            <small class="text-muted">Range de números</small>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="status_id" class="form-label">Status Padrão</label>
                            <select class="form-select" name="status_id" id="status_id" required>
                                <option value="">Selecione um status</option>
                                <?php foreach ($statuses as $status) : ?>
                                    <option value="<?= $status['id'] ?>" 
                                            data-color="<?= $status['color'] ?>" 
                                            data-icon="<?= $status['icon'] ?>"
                                            <?= $status['name'] === 'em_estoque' ? 'selected' : '' ?>>
                                        <?= ucfirst(str_replace('_', ' ', $status['name'])) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">
                                Por favor, selecione um status.
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="warranty" class="form-label">Garantia Padrão (opcional)</label>
                            <input type="text" class="form-control" id="warranty" name="warranty"
                                   placeholder="ex: 12 meses, 2 anos">
                        </div>
                        <div class="col-md-4">
                            <label for="notes" class="form-label">Observações</label>
                            <textarea class="form-control" id="notes" name="notes" rows="2" 
                                      placeholder="Observações opcionais sobre o lote"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancelar
                </button>
                <button type="submit" form="batchForm" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Criar Lote
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Produtos do Lote -->
<div class="modal fade" id="productsModal" tabindex="-1" aria-labelledby="productsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="productsModalLabel">
                    <i class="fas fa-box-open me-2"></i>
                    <span class="modal-title-text">Produtos do Lote</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="batch-info mb-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="batch-icon me-3">
                            <i class="fas fa-layer-group text-info" style="font-size: 3rem;"></i>
                        </div>
                        <div>
                            <h4 class="batch-number mb-1"></h4>
                            <p class="text-muted mb-0">
                                <span class="total-products"></span> produtos
                                (<span class="available-products"></span> disponíveis)
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

<!-- Modal de Observações -->
<div class="modal fade" id="notesModal" tabindex="-1" aria-labelledby="notesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="notesModalLabel">
                    <i class="fas fa-sticky-note me-2"></i>
                    Observações
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="notes-content"></p>
            </div>
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

.badge.bg-success {
    background-color: #198754 !important;
}

.badge.bg-primary {
    background-color: #0d6efd !important;
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

/* Custom SweetAlert styles */
.swal-wide {
    width: 600px !important;
}

.swal-wide .swal2-content {
    text-align: left;
}

.delete-batch {
    transition: all 0.2s ease;
}

.delete-batch:hover {
    transform: scale(1.1);
}
</style>

