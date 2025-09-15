<div class="sidebar bg-dark" id="sidebar-wrapper">
    <div class="sidebar-header p-3 text-center">
        <img src="public/images/logo_ssat.png" alt="SSAT Logo" class="img-fluid mb-3" style="max-height: 50px;">
    </div>
    
    <div class="list-group list-group-flush">
        <a href="index.php?page=batches" class="list-group-item list-group-item-action bg-dark text-white py-3">
            <i class="fas fa-layer-group me-2"></i> Lotes
        </a>
        <a href="index.php?page=production_orders" class="list-group-item list-group-item-action bg-dark text-white py-3">
            <i class="fas fa-clipboard-list me-2"></i> Pedidos
        </a>
        <a href="index.php?page=main" class="list-group-item list-group-item-action bg-dark text-white py-3">
            <i class="fas fa-box me-2"></i> Produtos
        </a>
        <a href="index.php?page=clients" class="list-group-item list-group-item-action bg-dark text-white py-3">
            <i class="fas fa-users me-2"></i> Clientes
        </a>
        <a href="index.php?page=tipos" class="list-group-item list-group-item-action bg-dark text-white py-3">
            <i class="fas fa-tags me-2"></i> Tipos
        </a>
        <a href="index.php?page=product_status" class="list-group-item list-group-item-action bg-dark text-white py-3">
            <i class="fas fa-flag me-2"></i> Status
        </a>
    </div>

    <div class="sidebar-footer mt-auto p-3">
        <form method="POST" action="src/controllers/LogoutController.php">
            <button type="submit" class="btn btn-danger w-100">
                <i class="fas fa-sign-out-alt me-2"></i> Sair
            </button>
        </form>
    </div>
</div>