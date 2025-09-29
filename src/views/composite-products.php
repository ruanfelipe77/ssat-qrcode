<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Produtos Compostos Finalizados</h2>
    </div>

    <div class="card">
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

<script>
// Variáveis globais
let compositeProductsTable;

$(document).ready(function() {
    initializeCompositeProductsTable();
});

function initializeCompositeProductsTable() {
    compositeProductsTable = $('#compositeProductsTable').DataTable({
        ajax: {
            url: 'src/controllers/CompositeController.php?action=get_composite_products',
            dataSrc: function(json) {
                if (typeof json === 'string') {
                    try { json = JSON.parse(json); } catch (e) { console.error('Erro ao parsear JSON:', e); return []; }
                }
                return Array.isArray(json) ? json : [];
            },
            error: function(xhr, error, thrown) {
                console.error('Erro na requisição de produtos compostos:', error, thrown);
                Swal.fire('Erro!', 'Erro ao carregar produtos compostos.', 'error');
            }
        },
        columns: [
            { data: 'id' },
            { data: 'serial_number' },
            { data: 'tipo_name' },
            { data: 'components_count' },
            { 
                data: 'created_at',
                render: function(data) { return new Date(data).toLocaleDateString('pt-BR'); }
            },
            {
                data: null,
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    let buttons = ``;
                    
                    if (row.assembly_status === 'finalized') {
                        buttons += `
                            <button class="btn btn-sm btn-outline-danger" onclick="disassembleAssembly(${row.assembly_id})" title="Desmontar">
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
        order: [[4, 'desc']],
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json'
        }
    });
}
</script>

<script src="public/js/composites.js"></script>
