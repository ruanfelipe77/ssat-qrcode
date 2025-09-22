<?php
require_once 'src/models/Client.php';
require_once 'src/models/Product.php';

$clientModel = new Client(Database::getInstance()->getConnection());
$clients = $clientModel->getAll();

$productModel = new Product(Database::getInstance()->getConnection());
$availableProducts = $productModel->getAvailableProducts();
?>

<!-- Modal de Novo Pedido -->
<div class="modal fade" id="orderModal" tabindex="-1" aria-labelledby="orderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="orderModalLabel">
                    <i class="fas fa-clipboard-list me-2"></i>
                    <span class="order-modal-title-text">Novo Pedido</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="orderForm" class="needs-validation" novalidate>
                    <input type="hidden" name="action" id="order_action" value="create_order">
                    <input type="hidden" name="id" id="order_id" value="">
                    <div class="row mb-4">
                        <div class="col-md-5">
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
                        <div class="col-md-3">
                            <label for="order_date" class="form-label">Data do Pedido</label>
                            <input type="date" class="form-control" id="order_date" name="order_date" required>
                            <div class="invalid-feedback">
                                Por favor, informe a data do pedido.
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label for="warranty" class="form-label">Garantia</label>
                            <input type="text" class="form-control" id="warranty" name="warranty" required>
                            <div class="invalid-feedback">
                                Por favor, informe a garantia.
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label for="order_number" class="form-label">Número do Pedido</label>
                            <input type="text" class="form-control" id="order_number" name="order_number" placeholder="Informe o número do PP" required>
                            <div class="invalid-feedback">Informe o número do PP.</div>
                        </div>
                        <div class="col-md-2">
                            <label for="nfe" class="form-label">NFe (opcional)</label>
                            <input type="text" class="form-control" id="nfe" name="nfe" placeholder="Número/Chave da NFe">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-12">
                            <!-- Header com filtros -->
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="d-flex align-items-center">
                                    <label class="form-label mb-0 me-3">
                                        <i class="fas fa-box-open me-2"></i>Produtos Disponíveis
                                    </label>
                                    <span class="badge bg-primary me-2" id="total-products"><?= count($availableProducts) ?></span>
                                    <span class="badge bg-success" id="selected-count">0 selecionados</span>
                                </div>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-primary" id="expand-all">
                                        <i class="fas fa-expand-alt me-1"></i>Expandir
                                    </button>
                                    <button type="button" class="btn btn-outline-primary" id="collapse-all">
                                        <i class="fas fa-compress-alt me-1"></i>Recolher
                                    </button>
                                    <button type="button" class="btn btn-outline-success" id="select-all">
                                        <i class="fas fa-check-double me-1"></i>Todos
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" id="clear-all">
                                        <i class="fas fa-times me-1"></i>Limpar
                                    </button>
                                </div>
                            </div>

                            <!-- Filtros -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">
                                            <i class="fas fa-search"></i>
                                        </span>
                                        <input type="text" class="form-control" 
                                               id="search-products" 
                                               placeholder="Buscar por tipo ou número de série...">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <select class="form-select form-select-sm" id="filter-batch">
                                        <option value="">📦 Todos os lotes</option>
                                        <?php 
                                        $batches = array_unique(array_filter(array_column($availableProducts, 'batch_number')));
                                        sort($batches);
                                        foreach ($batches as $batch): 
                                        ?>
                                            <option value="<?= htmlspecialchars($batch) ?>"><?= $batch ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Lista de produtos agrupados -->
                            <div id="products-container" class="products-container" style="max-height: 400px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 0.375rem; background: #f8f9fa;">
                                <?php if (empty($availableProducts)): ?>
                                    <div class="text-center text-muted py-5">
                                        <i class="fas fa-box-open mb-3" style="font-size: 3rem; opacity: 0.3;"></i>
                                        <h5>Nenhum produto disponível</h5>
                                        <p class="mb-0">Crie um lote primeiro para ter produtos em estoque</p>
                                    </div>
                                <?php else: ?>
                                    <?php 
                                    // Agrupar produtos por lote
                                    $productsByBatch = [];
                                    foreach ($availableProducts as $product) {
                                        $batchName = !empty($product['batch_number']) ? $product['batch_number'] : 'Sem Lote';
                                        $productsByBatch[$batchName][] = $product;
                                    }
                                    ksort($productsByBatch); // Ordenar lotes
                                    ?>
                                    
                                    <div id="batches-accordion">
                                        <?php foreach ($productsByBatch as $batchName => $products): ?>
                                            <div class="batch-group mb-2" data-batch="<?= htmlspecialchars($batchName) ?>">
                                                <!-- Header do lote -->
                                                <div class="batch-header p-3 bg-white border-bottom cursor-pointer d-flex justify-content-between align-items-center" 
                                                     data-bs-toggle="collapse" 
                                                     data-bs-target="#batch-<?= md5($batchName) ?>"
                                                     style="cursor: pointer; transition: all 0.2s;">
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-chevron-down me-3 batch-chevron text-primary" style="transition: transform 0.2s;"></i>
                                                        <div>
                                                            <h6 class="mb-1 text-primary">
                                                                <i class="fas fa-layer-group me-2"></i>
                                                                <?= $batchName ?>
                                                            </h6>
                                                            <small class="text-muted">
                                                                <span class="batch-count"><?= count($products) ?></span> produtos • 
                                                                <span class="batch-selected">0</span> selecionados
                                                            </small>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <button type="button" class="btn btn-sm btn-outline-success select-batch" 
                                                                data-batch="<?= htmlspecialchars($batchName) ?>" 
                                                                title="Selecionar todos do lote"
                                                                onclick="event.stopPropagation();">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                        <span class="badge bg-info"><?= count($products) ?></span>
                                                    </div>
                                                </div>
                                                
                                                <!-- Produtos do lote -->
                                                <div class="collapse show" id="batch-<?= md5($batchName) ?>">
                                                    <div class="batch-products p-2" style="background: #f8f9fa;">
                                                        <div class="row g-2">
                                                            <?php foreach ($products as $product): ?>
                                                                <div class="col-md-6">
                                                                    <div class="product-card p-2 bg-white border rounded shadow-sm h-100" 
                                                                         data-type="<?= strtolower($product['tipo_name']) ?>"
                                                                         data-serial="<?= $product['serial_number'] ?>"
                                                                         data-batch="<?= htmlspecialchars($batchName) ?>"
                                                                         style="transition: all 0.2s; cursor: pointer;">
                                                                        <div class="form-check h-100">
                                                                            <input class="form-check-input product-select" 
                                                                                   type="checkbox" 
                                                                                   value="<?= $product['id'] ?>" 
                                                                                   id="product_<?= $product['id'] ?>"
                                                                                   name="products[]">
                                                                            <label class="form-check-label w-100 h-100 d-flex flex-column" 
                                                                                   for="product_<?= $product['id'] ?>">
                                                                                <div class="d-flex align-items-center justify-content-between mb-2">
                                                                                    <div class="d-flex align-items-center">
                                                                                        <i class="fas fa-microchip text-primary me-2"></i>
                                                                                        <strong class="product-type" style="font-size: 0.9rem;">
                                                                                            <?= $product['tipo_name'] ?>
                                                                                        </strong>
                                                                                    </div>
                                                                                    <i class="fas fa-plus-circle text-success"></i>
                                                                                </div>
                                                                                <div class="flex-grow-1">
                                                                                    <div class="d-flex flex-wrap gap-1 mb-1">
                                                                                        <span class="badge bg-dark" style="font-size: 0.7rem;">
                                                                                            <i class="fas fa-hashtag me-1"></i>
                                                                                            <?= $product['serial_number'] ?>
                                                                                        </span>
                                                                                        <span class="badge bg-warning text-dark" style="font-size: 0.7rem;">
                                                                                            <i class="fas fa-shield-alt me-1"></i>
                                                                                            <?= $product['warranty'] ?>
                                                                                        </span>
                                                                                    </div>
                                                                                </div>
                                                                            </label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
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

<style>
.table-responsive {
    max-height: 400px;
    overflow-y: auto;
}

.table th {
    position: sticky;
    top: 0;
    background-color: white;
    z-index: 1;
}

.selected-count {
    font-size: 0.9rem;
}

.selected-count .badge {
    font-size: 0.9rem;
    padding: 0.35rem 0.65rem;
}

/* Estilos modernos para o modal de pedidos */
.product-card {
    transition: all 0.2s ease;
    border: 1px solid #dee2e6 !important;
}

.product-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1) !important;
}

.product-card.border-success {
    border-color: #198754 !important;
    box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.25);
}

.batch-header {
    transition: all 0.2s ease;
}

.batch-header:hover {
    background-color: #f8f9fa !important;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.batch-chevron {
    font-size: 0.8rem;
}

.products-container {
    scrollbar-width: thin;
    scrollbar-color: #6c757d #f1f1f1;
}

.products-container::-webkit-scrollbar {
    width: 6px;
}

.products-container::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.products-container::-webkit-scrollbar-thumb {
    background: #6c757d;
    border-radius: 10px;
}

.products-container::-webkit-scrollbar-thumb:hover {
    background: #495057;
}

.form-check-input:checked {
    background-color: #198754;
    border-color: #198754;
}

.badge {
    font-weight: 500;
}

.btn-group-sm .btn {
    border-radius: 0.25rem;
    font-size: 0.8rem;
}

.input-group-sm .form-control,
.input-group-sm .input-group-text {
    border-radius: 0.25rem;
}

/* Animações suaves */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.batch-group {
    animation: fadeIn 0.3s ease;
}

/* Estados dos cards */
.product-card .fa-plus-circle,
.product-card .fa-check-circle {
    transition: all 0.2s ease;
}

.product-card:not(:hover) .fa-plus-circle {
    opacity: 0.6;
}

.product-card:hover .fa-plus-circle {
    opacity: 1;
    transform: scale(1.1);
}
</style>

<script>
$(document).ready(function() {
    // Animação do chevron nos lotes
    $(document).on('show.bs.collapse', '.collapse', function() {
        $(this).siblings('.batch-header').find('.batch-chevron').css('transform', 'rotate(180deg)');
    });
    
    $(document).on('hide.bs.collapse', '.collapse', function() {
        $(this).siblings('.batch-header').find('.batch-chevron').css('transform', 'rotate(0deg)');
    });

    // Funcionalidades dos botões
    $('#expand-all').on('click', function() {
        $('.collapse').collapse('show');
    });

    $('#collapse-all').on('click', function() {
        $('.collapse').collapse('hide');
    });

    $('#select-all').on('click', function() {
        $('.product-select').prop('checked', true);
        updateAllCounts();
        updateProductCardStyles();
    });

    $('#clear-all').on('click', function() {
        $('.product-select').prop('checked', false);
        updateAllCounts();
        updateProductCardStyles();
    });

    // Selecionar todos de um lote
    $(document).on('click', '.select-batch', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const batchName = $(this).data('batch');
        const batchGroup = $(`.batch-group[data-batch="${batchName}"]`);
        const checkboxes = batchGroup.find('.product-select');
        
        checkboxes.prop('checked', true);
        updateAllCounts();
        updateProductCardStyles();
    });

    // Filtro de busca
    $('#search-products').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        filterProducts();
    });

    // Filtro por lote
    $('#filter-batch').on('change', function() {
        filterProducts();
    });

    function filterProducts() {
        const searchTerm = $('#search-products').val().toLowerCase();
        const batchFilter = $('#filter-batch').val();
        
        $('.batch-group').each(function() {
            const batchGroup = $(this);
            const batchName = batchGroup.data('batch');
            let hasVisibleProducts = false;
            
            // Filtrar por lote
            if (batchFilter && batchName !== batchFilter) {
                batchGroup.hide();
                return;
            }
            
            // Filtrar produtos dentro do lote
            batchGroup.find('.product-card').each(function() {
                const productCard = $(this);
                const type = productCard.data('type');
                const serial = productCard.data('serial').toString();
                
                const matchesSearch = !searchTerm || 
                    type.includes(searchTerm) || 
                    serial.includes(searchTerm);
                
                if (matchesSearch) {
                    productCard.parent().show();
                    hasVisibleProducts = true;
                } else {
                    productCard.parent().hide();
                }
            });
            
            // Mostrar/ocultar lote baseado se tem produtos visíveis
            if (hasVisibleProducts) {
                batchGroup.show();
            } else {
                batchGroup.hide();
            }
        });
    }

    // Atualizar estilo dos cards selecionados
    function updateProductCardStyles() {
        $('.product-card').each(function() {
            const card = $(this);
            const checkbox = card.find('.product-select');
            const icon = card.find('.fa-plus-circle');
            
            if (checkbox.is(':checked')) {
                card.addClass('border-success bg-light').removeClass('bg-white');
                icon.removeClass('fa-plus-circle text-success').addClass('fa-check-circle text-success');
            } else {
                card.removeClass('border-success bg-light').addClass('bg-white');
                icon.removeClass('fa-check-circle').addClass('fa-plus-circle');
            }
        });
    }

    // Atualizar contadores
    function updateAllCounts() {
        // Contador global
        const totalSelected = $('.product-select:checked').length;
        $('#selected-count').text(`${totalSelected} selecionados`);
        
        // Contadores por lote
        $('.batch-group').each(function() {
            const batchGroup = $(this);
            const totalInBatch = batchGroup.find('.product-select').length;
            const selectedInBatch = batchGroup.find('.product-select:checked').length;
            
            batchGroup.find('.batch-selected').text(selectedInBatch);
            
            // Atualizar badge do botão de seleção
            const selectButton = batchGroup.find('.select-batch');
            if (selectedInBatch === totalInBatch && totalInBatch > 0) {
                selectButton.removeClass('btn-outline-success').addClass('btn-success');
                selectButton.find('i').removeClass('fa-check').addClass('fa-check-double');
            } else {
                selectButton.removeClass('btn-success').addClass('btn-outline-success');
                selectButton.find('i').removeClass('fa-check-double').addClass('fa-check');
            }
        });
    }

    // Event listeners para mudanças nos checkboxes
    $(document).on('change', '.product-select', function() {
        updateAllCounts();
        updateProductCardStyles();
    });

    // Clique no card seleciona o produto
    $(document).on('click', '.product-card', function(e) {
        if (!$(e.target).is('input[type="checkbox"]')) {
            const checkbox = $(this).find('.product-select');
            checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
        }
    });

    // Hover effects
    $(document).on('mouseenter', '.product-card', function() {
        $(this).addClass('shadow');
    });
    
    $(document).on('mouseleave', '.product-card', function() {
        $(this).removeClass('shadow');
    });

    $(document).on('mouseenter', '.batch-header', function() {
        $(this).addClass('bg-light');
    });
    
    $(document).on('mouseleave', '.batch-header', function() {
        $(this).removeClass('bg-light');
    });

    // Inicializar contadores
    updateAllCounts();
    updateProductCardStyles();

    // Submissão do formulário
    $('#orderForm').on('submit', function(e) {
        e.preventDefault();
        
        if (!this.checkValidity()) {
            e.stopPropagation();
            $(this).addClass('was-validated');
            return;
        }

        const selectedProducts = $('.product-select:checked').length;
        const isUpdate = $('#order_action').val() === 'update_order';
        if (!isUpdate && selectedProducts === 0) {
            Swal.fire({
                title: 'Atenção!',
                text: 'Selecione pelo menos um produto para o pedido.',
                icon: 'warning'
            });
            return;
        }

        const formData = $(this).serialize();
        
        Swal.fire({
            title: isUpdate ? 'Atualizar pedido' : 'Confirmação',
            html: isUpdate
                ? 'Deseja salvar as alterações deste pedido?'
                : `Você está prestes a criar um pedido com:<br><b>${selectedProducts}</b> produtos<br><br>Deseja continuar?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: isUpdate ? 'Salvar alterações' : 'Sim, criar pedido',
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
                            const modal = bootstrap.Modal.getInstance(
                                document.getElementById('orderModal')
                            );
                            modal.hide();

                            if (isUpdate) {
                                Swal.fire({
                                    title: 'Sucesso!',
                                    text: 'Pedido atualizado com sucesso!',
                                    icon: 'success'
                                }).then(() => { location.reload(); });
                            } else {
                                Swal.fire({
                                    title: 'Sucesso!',
                                    text: `Pedido ${res.order_number} criado com sucesso!`,
                                    icon: 'success'
                                }).then(() => { location.reload(); });
                            }
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
                            text: isUpdate ? 'Erro ao atualizar pedido' : 'Erro ao criar pedido',
                            icon: 'error'
                        });
                    }
                });
            }
        });
    });
});
</script>
