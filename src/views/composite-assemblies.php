<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Montagens</h2>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#assemblyModal" onclick="resetAssemblyModal()">
            <i class="fas fa-plus me-1"></i>Nova Montagem
        </button>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="assembliesTable" class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Produto</th>
                            <th>Status</th>
                            <th>Componentes</th>
                            <th>Serial</th>
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
                    /* Aparência discreta e moderna */
                    #assemblyModal .scroll-pane { max-height: 350px; overflow-y: auto; border: 1px solid #e9ecef; border-radius: 6px; padding: 10px; background: #fff; }
                    #assemblyModal .modal-body { min-height: 500px;  }
                </style>
                <div class="row">
                    <div class="col-4">
                        <div class="mb-3">
                            <label for="composite_serial" class="form-label">Número Serial*</label>
                            <input type="text" class="form-control" id="composite_serial">
                            <div class="form-text">Este será o serial único do produto montado</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="assembly_template_id" class="form-label">Template *</label>
                            <select class="form-select" id="assembly_template_id" onchange="showTemplateRequirements()">
                                <option value="">Selecione um template...</option>
                            </select>
                        </div>

                        <div id="templateRequirements" class="mb-3" style="display: none;">
                            <h6>Componentes Necessários</h6>
                            <div id="requirementsList" class="scroll-pane"></div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="available_products" class="form-label">Produtos Disponíveis</label>
                            <div class="d-flex gap-2 align-items-stretch">
                                <select class="form-select" id="available_products">
                                    <option value="">Selecione um produto...</option>
                                </select>
                                <button type="button" class="btn btn-success" onclick="addComponentToAssembly()" title="Adicionar">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>

                        <div id="assemblyComponents">
                            <h6>Componentes Adicionados</h6>
                            <div id="componentsList" class="scroll-pane"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-warning" id="saveDraftBtn" onclick="saveDraftAssembly()" disabled>
                    <i class="fas fa-save me-1"></i>Salvar Progresso
                </button>
                <button type="button" class="btn btn-success" id="finalizeBtn" onclick="finalizeAssembly()" disabled>
                    <i class="fas fa-check me-1"></i>Finalizar Montagem
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Variáveis globais
let assembliesTable;
let currentAssemblyId = null;

$(document).ready(function() {
    initializeAssembliesTable();
    loadInitialData();
});

function initializeAssembliesTable() {
    window.assembliesTable = assembliesTable = $('#assembliesTable').DataTable({
        ajax: {
            url: 'src/controllers/CompositeController.php?action=get_assemblies',
            dataSrc: function(json) {
                if (typeof json === 'string') {
                    try { json = JSON.parse(json); } catch (e) { console.error('Erro ao parsear JSON:', e); return []; }
                }
                return Array.isArray(json) ? json : [];
            },
            error: function(xhr, error, thrown) {
                console.error('Erro na requisição de assemblies:', error, thrown);
                Swal.fire('Erro!', 'Erro ao carregar montagens.', 'error');
            }
        },
        columns: [
            { data: 'id' },
            { data: 'composite_tipo_name' },
            { 
                data: 'status',
                render: function(data) {
                    const statusMap = {
                        'draft': '<span class="badge bg-secondary">Rascunho</span>',
                        'in_progress': '<span class="badge bg-warning">Em Progresso</span>',
                        'finalized': '<span class="badge bg-success">Finalizada</span>',
                        'disassembled': '<span class="badge bg-danger">Desmontada</span>'
                    };
                    return statusMap[data] || data;
                }
            },
            { data: 'components_count' },
            { data: 'composite_serial' },
            { data: 'created_by_name' },
            { 
                data: 'created_at',
                render: function(data) { return new Date(data).toLocaleDateString('pt-BR'); }
            },
            {
                data: null,
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    let buttons = `
                        <button class="btn btn-sm btn-outline-primary" onclick="viewAssembly(${row.id})" title="Visualizar">
                            <i class="fas fa-eye"></i>
                        </button>`;
                    
                    if (row.status === 'draft' || row.status === 'in_progress') {
                        buttons += `
                            <button class="btn btn-sm btn-outline-warning" onclick="editAssembly(${row.id})" title="Continuar Montagem">
                                <i class="fas fa-tools"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteAssembly(${row.id})" title="Excluir">
                                <i class="fas fa-trash"></i>
                            </button>`;
                    }
                    
                    if (row.status === 'finalized') {
                        buttons += `
                            <button class="btn btn-sm btn-outline-danger" onclick="disassembleAssembly(${row.id})" title="Desmontar">
                                <i class="fas fa-wrench"></i>
                            </button>`;
                    }
                    
                    return buttons;
                }
            }
        ],
        pagingType: "full_numbers",
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Todos"]],
        responsive: true,
        dom: "frtip",
        order: [[6, 'desc']],
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json'
        }
    });
}

function loadInitialData() {
    // Carregar templates ativos para o modal de assembly
    $.get('src/controllers/CompositeController.php?action=get_templates', function(response) {
        try {
            const data = typeof response === 'string' ? JSON.parse(response) : response;
            const select = $('#assembly_template_id');
            select.empty().append('<option value="">Selecione um template...</option>');
            if (Array.isArray(data)) {
                data.filter(t => t.is_active == 1).forEach(template => {
                    select.append(`<option value="${template.id}">${template.tipo_name} v${template.version}</option>`);
                });
            }
        } catch (e) {
            console.error('Erro ao carregar templates:', e);
        }
    }).fail(function() {
        console.error('Erro na requisição de templates');
    });
}
</script>

<script src="public/js/composites.js"></script>
