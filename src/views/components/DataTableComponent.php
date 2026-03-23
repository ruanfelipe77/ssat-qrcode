<!-- 
    Componente de Tabela Padrão Reutilizável
    
    Uso:
    <?php 
    $tableConfig = [
        'id' => 'my-table',
        'columns' => [
            ['title' => 'ID', 'data' => 'id', 'width' => '80px'],
            ['title' => 'Nome', 'data' => 'name'],
            ['title' => 'Email', 'data' => 'email'],
            ['title' => 'Ações', 'data' => 'actions', 'orderable' => false, 'searchable' => false]
        ],
        'order' => [[0, 'desc']], // Ordenação padrão
        'pageLength' => 25,
        'ajax' => 'src/controllers/MyController.php?action=list', // Opcional para AJAX
        'dom' => 'Bfrtip', // Layout com botões
        'buttons' => ['copy', 'excel', 'pdf', 'print'] // Botões de exportação
    ];
    include 'src/views/components/DataTableComponent.php';
    ?>
-->

<?php
// Configurações padrão
$defaultConfig = [
    'id' => 'datatable',
    'class' => 'table table-striped table-hover',
    'pageLength' => 25,
    'lengthMenu' => [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'Todos']],
    'order' => [[0, 'desc']],
    'dom' => 'Bfrtip',
    'buttons' => [
        [
            'extend' => 'copy',
            'text' => '<i class="fas fa-copy me-1"></i>Copiar',
            'className' => 'btn btn-secondary btn-sm'
        ],
        [
            'extend' => 'excel',
            'text' => '<i class="fas fa-file-excel me-1"></i>Excel',
            'className' => 'btn btn-success btn-sm'
        ],
        [
            'extend' => 'pdf',
            'text' => '<i class="fas fa-file-pdf me-1"></i>PDF',
            'className' => 'btn btn-danger btn-sm'
        ],
        [
            'extend' => 'print',
            'text' => '<i class="fas fa-print me-1"></i>Imprimir',
            'className' => 'btn btn-info btn-sm'
        ]
    ],
    'language' => [
        'url' => '//cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json'
    ],
    'responsive' => true,
    'processing' => true,
    'autoWidth' => false
];

// Merge configurações customizadas
$config = array_merge($defaultConfig, $tableConfig ?? []);
$tableId = $config['id'];
?>

<div class="table-responsive">
    <!-- Loader -->
    <div class="table-loading" id="<?= $tableId ?>-loading">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Carregando dados...</span>
        </div>
        <p class="mt-2 mb-0">Carregando dados...</p>
    </div>
    
    <!-- Tabela -->
    <table id="<?= $tableId ?>" class="<?= $config['class'] ?>" style="width:100%; display:none;">
        <thead>
            <tr>
                <?php foreach ($config['columns'] as $column): ?>
                    <th <?= isset($column['width']) ? 'style="width: ' . $column['width'] . ';"' : '' ?>>
                        <?= $column['title'] ?>
                    </th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (isset($config['data']) && is_array($config['data'])): ?>
                <?php foreach ($config['data'] as $row): ?>
                    <tr>
                        <?php foreach ($config['columns'] as $column): ?>
                            <td>
                                <?php 
                                if (isset($column['render']) && is_callable($column['render'])) {
                                    echo $column['render']($row);
                                } else {
                                    echo $row[$column['data']] ?? '';
                                }
                                ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
$(document).ready(function() {
    // Destruir instância anterior se existir
    if ($.fn.DataTable.isDataTable('#<?= $tableId ?>')) {
        $('#<?= $tableId ?>').DataTable().destroy();
    }
    
    // Configuração do DataTable
    var tableConfig = {
        destroy: true,
        pageLength: <?= $config['pageLength'] ?>,
        lengthMenu: <?= json_encode($config['lengthMenu']) ?>,
        order: <?= json_encode($config['order']) ?>,
        dom: '<?= $config['dom'] ?>',
        buttons: <?= json_encode($config['buttons']) ?>,
        language: <?= json_encode($config['language']) ?>,
        responsive: <?= $config['responsive'] ? 'true' : 'false' ?>,
        processing: <?= $config['processing'] ? 'true' : 'false' ?>,
        autoWidth: <?= $config['autoWidth'] ? 'true' : 'false' ?>,
        <?php if (isset($config['ajax'])): ?>
        ajax: {
            url: '<?= $config['ajax'] ?>',
            type: 'GET',
            dataSrc: ''
        },
        <?php endif; ?>
        columns: <?= json_encode(array_map(function($col) {
            return [
                'data' => $col['data'],
                'orderable' => $col['orderable'] ?? true,
                'searchable' => $col['searchable'] ?? true,
                'className' => $col['className'] ?? ''
            ];
        }, $config['columns'])) ?>,
        initComplete: function() {
            $('#<?= $tableId ?>-loading').fadeOut(300, function() {
                $(this).remove();
            });
            $('#<?= $tableId ?>').fadeIn(300);
        },
        drawCallback: function() {
            // Reinicializar tooltips do Bootstrap após cada redesenho
            $('[data-bs-toggle="tooltip"]').tooltip();
        }
    };
    
    // Inicializar DataTable
    var table = $('#<?= $tableId ?>').DataTable(tableConfig);
    
    // Eventos customizados
    <?php if (isset($config['onRowClick'])): ?>
    $('#<?= $tableId ?> tbody').on('click', 'tr', function() {
        var data = table.row(this).data();
        if (data) {
            <?= $config['onRowClick'] ?>(data);
        }
    });
    <?php endif; ?>
});
</script>

<style>
/* Estilos do componente de tabela */
#<?= $tableId ?>-loading {
    text-align: center;
    padding: 3rem;
    color: #6c757d;
}

#<?= $tableId ?>-loading .spinner-border {
    width: 3rem;
    height: 3rem;
}

/* Melhorias visuais da tabela */
#<?= $tableId ?> thead th {
    background-color: #f8f9fa;
    font-weight: 600;
    border-top: none;
    vertical-align: middle;
    white-space: nowrap;
}

#<?= $tableId ?> tbody tr {
    transition: background-color 0.2s ease;
}

#<?= $tableId ?> tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.05);
}

/* Botões do DataTables */
.dt-buttons {
    margin-bottom: 1rem;
    gap: 0.5rem;
    display: flex;
    flex-wrap: wrap;
}

.dt-buttons .btn {
    margin-right: 0.5rem;
    margin-bottom: 0.5rem;
}

/* Paginação */
.dataTables_wrapper .dataTables_paginate .paginate_button {
    padding: 0.375rem 0.75rem;
    margin: 0 0.125rem;
    border-radius: 0.25rem;
}

.dataTables_wrapper .dataTables_paginate .paginate_button.current {
    background: #007bff !important;
    color: white !important;
    border: 1px solid #007bff !important;
}

/* Filtro de pesquisa */
.dataTables_wrapper .dataTables_filter input {
    border: 1px solid #ced4da;
    border-radius: 0.25rem;
    padding: 0.375rem 0.75rem;
    margin-left: 0.5rem;
}

/* Info e length */
.dataTables_wrapper .dataTables_info,
.dataTables_wrapper .dataTables_length {
    padding: 0.5rem 0;
}

/* Responsividade */
@media (max-width: 768px) {
    .dt-buttons {
        justify-content: center;
    }
    
    .dt-buttons .btn {
        font-size: 0.875rem;
        padding: 0.25rem 0.5rem;
    }
}
</style>
