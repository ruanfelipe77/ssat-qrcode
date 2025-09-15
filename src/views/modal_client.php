<!-- Modal de Cliente -->
<div class="modal fade" id="clientModal" tabindex="-1" aria-labelledby="clientModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="clientModalLabel">
                    <i class="fas fa-user me-2"></i>
                    <span id="clientModalTitle">Novo Cliente</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="clientForm" class="needs-validation" novalidate>
                    <input type="hidden" name="action" value="add" id="action">
                    <input type="hidden" name="id" id="id">

                    <div class="mb-3">
                        <label for="name" class="form-label">Nome do Cliente</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                        <div class="invalid-feedback">
                            Por favor, informe o nome do cliente.
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label for="city" class="form-label">Cidade</label>
                            <input type="text" class="form-control" id="city" name="city" required>
                            <div class="invalid-feedback">
                                Por favor, informe a cidade.
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="state" class="form-label">Estado</label>
                            <input type="text" class="form-control" id="state" name="state" required maxlength="2" style="text-transform: uppercase;">
                            <div class="invalid-feedback">
                                Por favor, informe o estado.
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer px-0 pb-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i><span id="submitButtonText">Salvar Cliente</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Produtos do Cliente -->
<div class="modal fade" id="productsModal" tabindex="-1" aria-labelledby="productsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="productsModalLabel">
                    <i class="fas fa-box-open me-2"></i>
                    <span class="modal-title-text">Produtos do Cliente</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="client-info mb-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="client-icon me-3">
                            <i class="fas fa-user-circle text-primary" style="font-size: 3rem;"></i>
                        </div>
                        <div>
                            <h4 class="client-name mb-1"></h4>
                            <p class="text-muted mb-0 client-location"></p>
                        </div>
                    </div>
                </div>
                <div class="products-list">
                    <!-- Produtos serão carregados aqui via JavaScript -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Fechar
                </button>
            </div>
        </div>
    </div>
</div>
