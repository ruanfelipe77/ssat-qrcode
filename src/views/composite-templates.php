<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Templates de Composição</h2>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#templateModal">
            <i class="fas fa-plus me-2"></i>Novo Template
        </button>
    </div>

    <div class="card">
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

<script>
// Evitar carregar composites.js para evitar conflitos
// Definir apenas as funções necessárias para esta página

$(document).ready(function() {
    // Destruir tabela existente se houver
    if ($.fn.DataTable.isDataTable('#templatesTable')) {
        $('#templatesTable').DataTable().destroy();
    }
    
    // Inicializar tabela de templates
    window.templatesTable = $('#templatesTable').DataTable({
        ajax: {
            url: 'src/controllers/CompositeController.php?action=get_templates',
            dataSrc: function(json) {
                if (typeof json === 'string') {
                    try { json = JSON.parse(json); } catch (e) { console.error('Erro ao parsear JSON:', e); return []; }
                }
                return Array.isArray(json) ? json : [];
            },
            error: function(xhr, error, thrown) {
                console.error('Erro na requisição de templates:', error, thrown);
                Swal.fire('Erro!', 'Erro ao carregar templates.', 'error');
            }
        },
        columns: [
            { data: 'id' },
            { data: 'tipo_name' },
            { data: 'version' },
            {
                data: 'is_active',
                render: function(data) {
                    return data == 1 ? 
                        '<span class="badge bg-success">Ativo</span>' : 
                        '<span class="badge bg-secondary">Inativo</span>';
                }
            },
            { data: 'items_count' },
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
                        <button class="btn btn-sm btn-outline-warning" onclick="editTemplate(${row.id})" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>`;
                    if (parseInt(row.is_active, 10) === 0) {
                        buttons += `
                            <button class="btn btn-sm btn-outline-success" onclick="activateTemplate(${row.id})" title="Ativar">
                                <i class="fas fa-check"></i>
                            </button>`;
                    }
                    buttons += `
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteTemplate(${row.id})" title="Excluir">
                            <i class="fas fa-trash"></i>
                        </button>`;
                    return buttons;
                }
            }
        ],
        pagingType: "full_numbers",
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Todos"]],
        responsive: true,
        dom: "frtip",
        order: [[5, 'desc']],
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json'
        }
    });
    
    // Carregar dados iniciais
    loadTemplateInitialData();
});

// Função para carregar dados iniciais
function loadTemplateInitialData() {
    $.get('src/controllers/CompositeController.php?action=get_composite_tipos', function(response) {
        try {
            const data = typeof response === 'string' ? JSON.parse(response) : response;
            const select = $('#template_tipo_id');
            select.empty().append('<option value="">Selecione...</option>');
            if (Array.isArray(data)) {
                data.forEach(tipo => {
                    select.append(`<option value="${tipo.id}">${tipo.nome}</option>`);
                });
            }
        } catch (e) {
            console.error('Erro ao carregar tipos compostos:', e);
        }
    }).fail(function() {
        console.error('Erro na requisição de tipos compostos');
    });
}
</script>

<script src="public/js/composites.js"></script>
