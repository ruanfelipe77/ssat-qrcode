<div class="container-fluid p-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Produtos Compostos</h2>
            </div>

            <!-- Tabs de navegação -->
            <ul class="nav nav-tabs" id="compositesTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="templates-tab" data-bs-toggle="tab" data-bs-target="#templates" type="button" role="tab">
                        <i class="fas fa-list me-2"></i>Templates
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="assemblies-tab" data-bs-toggle="tab" data-bs-target="#assemblies" type="button" role="tab">
                        <i class="fas fa-tools me-2"></i>Montagens
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="products-tab" data-bs-toggle="tab" data-bs-target="#products" type="button" role="tab">
                        <i class="fas fa-box me-2"></i>Produtos Finalizados
                    </button>
                </li>
            </ul>

            <!-- Conteúdo das tabs -->
            <div class="tab-content" id="compositesTabContent">
                <!-- Tab Templates -->
                <div class="tab-pane fade show active" id="templates" role="tabpanel">
                    <div class="card mt-3">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Templates de Composição</h5>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#templateModal">
                                <i class="fas fa-plus me-2"></i>Novo Template
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="templatesTable" class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Produto Composto</th>
                                            <th>Versão</th>
                                            <th>Status</th>
                                            <th>Itens</th>
                                            <th>Criado em</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab Assemblies -->
                <div class="tab-pane fade" id="assemblies" role="tabpanel">
                    <div class="card mt-3">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Montagens</h5>
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#assemblyModal">
                                <i class="fas fa-plus me-2"></i>Nova Montagem
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="assembliesTable" class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Produto</th>
                                            <th>Status</th>
                                            <th>Componentes</th>
                                            <th>Serial Final</th>
                                            <th>Criado por</th>
                                            <th>Criado em</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab Products -->
                <div class="tab-pane fade" id="products" role="tabpanel">
                    <div class="card mt-3">
                        <div class="card-header">
                            <h5 class="mb-0">Produtos Compostos Finalizados</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="compositeProductsTable" class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Serial</th>
                                            <th>Tipo</th>
                                            <th>Componentes</th>
                                            <th>Criado em</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Template -->
<div class="modal fade" id="templateModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Novo Template de Composição</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="templateForm">
                    <input type="hidden" id="template_id" name="id">
                    
                    <div class="mb-3">
                        <label for="template_tipo_id" class="form-label">Produto Composto *</label>
                        <select class="form-select" id="template_tipo_id" name="tipo_id" required>
                            <option value="">Selecione...</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="template_version" class="form-label">Versão</label>
                        <input type="number" class="form-control" id="template_version" name="version" value="1" min="1">
                    </div>

                    <div class="mb-3">
                        <label for="template_notes" class="form-label">Observações</label>
                        <textarea class="form-control" id="template_notes" name="notes" rows="2"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Componentes Necessários</label>
                        <div id="templateItems">
                            <!-- Itens serão adicionados dinamicamente -->
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="addTemplateItem()">
                            <i class="fas fa-plus me-1"></i>Adicionar Componente
                        </button>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="saveTemplate()">Salvar Template</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Assembly -->
<div class="modal fade" id="assemblyModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nova Montagem</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <style>
                    /* Estilos discretos para manter o modal clean */
                    #assemblyModal .section-title { font-weight: 600; font-size: 0.95rem; }
                    #assemblyModal .scroll-pane { max-height: 260px; overflow-y: auto; border: 1px solid #e9ecef; border-radius: 6px; padding: 10px; background: #fff; }
                    #assemblyModal .subtle { color: #6c757d; font-size: 0.85rem; }
                </style>

                <!-- Linha superior com 3 campos lado a lado -->
                <div class="row g-3 align-items-end mb-2">
                    <div class="col-lg-4">
                        <label for="assembly_template_id" class="form-label mb-1">Template *</label>
                        <select class="form-select" id="assembly_template_id" onchange="showTemplateRequirements()">
                            <option value="">Selecione um template...</option>
                        </select>
                    </div>
                    <div class="col-lg-4">
                        <label for="available_products" class="form-label mb-1">Produtos Disponíveis</label>
                        <div class="d-flex gap-2">
                            <select class="form-select" id="available_products">
                                <option value="">Selecione um produto...</option>
                            </select>
                            <button type="button" class="btn btn-success" onclick="addComponentToAssembly()">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        <div class="subtle mt-1">Selecione e clique em adicionar</div>
                    </div>
                    <div class="col-lg-4">
                        <label for="composite_serial" class="form-label mb-1">Serial do Produto Final *</label>
                        <input type="text" class="form-control" id="composite_serial" placeholder="Ex: CTRL-2025-001">
                    </div>
                </div>

                <!-- Listas lado a lado com scroll -->
                <div class="row g-4 mt-1">
                    <div class="col-md-6">
                        <div id="templateRequirements" style="display: none;">
                            <div class="section-title mb-2">Componentes Necessários</div>
                            <div id="requirementsList" class="scroll-pane"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div id="assemblyComponents">
                            <div class="section-title mb-2">Componentes Adicionados</div>
                            <div id="componentsList" class="scroll-pane"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-warning" id="saveDraftBtn" onclick="saveDraft()" style="display: none;">
                    <i class="fas fa-save me-1"></i>Salvar Rascunho
                </button>
                <button type="button" class="btn btn-success" id="finalizeBtn" onclick="finalizeAssembly()" style="display: none;">
                    <i class="fas fa-check me-1"></i>Finalizar Montagem
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detalhes do Produto Composto -->
<div class="modal fade" id="compositeDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalhes do Produto Composto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="compositeDetailsContent">
                    <!-- Conteúdo será carregado dinamicamente -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-danger" id="disassembleBtn" onclick="disassembleProduct()" style="display: none;">
                    <i class="fas fa-wrench me-1"></i>Desmontar
                </button>
            </div>
        </div>
    </div>
</div>

<script src="public/js/composites.js"></script>
