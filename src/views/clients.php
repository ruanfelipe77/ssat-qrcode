<?php
require 'database.php';
require 'src/models/Client.php';
require 'src/models/Product.php';

$database = Database::getInstance();
$db = $database->getConnection();

$clientModel = new Client($db);
$clients = $clientModel->getAll();

$productModel = new Product($db);
?>

<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Clientes</h2>
        <button class="btn btn-primary" id="add-client">
            <i class="fas fa-plus me-2"></i>Novo Cliente
        </button>
    </div>

    <div class="card">
        <div class="card-body">
            <table id="clients-table" class="table table-striped table-hover" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Cidade</th>
                        <th>Estado</th>
                        <th style="width: 100px; text-align: center;">Produtos</th>
                        <th style="width: 120px; text-align: center;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clients as $client) : 
                        $products = $productModel->getByClientId($client['id']);
                        $totalProducts = count($products);
                    ?>
                        <tr>
                            <td><?= $client['id'] ?></td>
                            <td><?= $client['name'] ?></td>
                            <td><?= $client['city'] ?></td>
                            <td><?= $client['state'] ?></td>
                            <td class="text-center">
                                <?php if ($totalProducts > 0) : ?>
                                    <button class="btn btn-link text-primary p-0 view-products" 
                                            data-id="<?= $client['id'] ?>" 
                                            data-name="<?= htmlspecialchars($client['name']) ?>"
                                            data-location="<?= htmlspecialchars($client['city'] . '/' . $client['state']) ?>"
                                            data-bs-toggle="tooltip" 
                                            title="Ver Produtos">
                                        <span class="position-relative">
                                            <i class="fas fa-box-open fs-5"></i>
                                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary">
                                                <?= $totalProducts ?>
                                            </span>
                                        </span>
                                    </button>
                                <?php else: ?>
                                    <span class="text-muted">
                                        <i class="fas fa-box fs-5 opacity-50"></i>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex justify-content-center align-items-center gap-3">
                                    <button class="btn btn-link text-dark p-0 edit-client" data-id="<?= $client['id'] ?>" title="Editar">
                                        <i class="fas fa-edit fs-5"></i>
                                    </button>
                                    <button class="btn btn-link text-dark p-0 delete-client" data-id="<?= $client['id'] ?>" title="Excluir">
                                        <i class="fas fa-trash fs-5"></i>
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
                        <div class="client-avatar me-3">
                            <i class="fas fa-user-circle text-primary" style="font-size: 3rem;"></i>
                        </div>
                        <div>
                            <h4 class="client-name mb-1"></h4>
                            <p class="client-location text-muted mb-0">
                                <i class="fas fa-map-marker-alt me-1"></i>
                                <span></span>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="products-list">
                    <!-- Produtos serão carregados aqui via JavaScript -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">
                    <i class="fas fa-check me-2"></i>Fechar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- DataTables Buttons -->
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.70/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.70/vfs_fonts.js"></script>

<!-- Custom JS -->
<script src="public/js/clients.js"></script>

<style>
/* Estilo para o badge de contagem */
.badge {
    font-size: 0.65rem;
    padding: 0.25rem 0.5rem;
}

/* Estilo para o modal de produtos */
.products-list .product-card {
    transition: all 0.2s ease;
    border-left: 4px solid transparent;
}

.products-list .product-card:hover {
    background-color: #f8f9fa;
    border-left-color: #0d6efd;
    transform: translateX(5px);
}

.product-card .product-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #e9ecef;
    border-radius: 8px;
}

.client-avatar {
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background-color: #e9ecef;
}

/* Tooltip customizado */
.tooltip {
    font-size: 0.875rem;
}

/* Animação para os badges */
@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

.badge {
    animation: pulse 2s infinite;
}
</style>

<?php include 'src/views/modal_client.php'; ?>
