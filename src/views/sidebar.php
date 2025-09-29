<div class="sidebar bg-dark" id="sidebar-wrapper">
    <div class="sidebar-header p-3 text-center">
        <img src="public/images/logo_ssat.png" alt="SSAT Logo" class="img-fluid mb-3" style="max-height: 50px;">
    </div>
    
    <div class="list-group list-group-flush">
        <a href="index.php?page=batches" class="list-group-item list-group-item-action bg-dark text-white py-3">
            <i class="fas fa-layer-group me-2"></i> Lotes
        </a>
        <a href="index.php?page=main" class="list-group-item list-group-item-action bg-dark text-white py-3">
            <i class="fas fa-box me-2"></i> Produtos
        </a>
        <!-- Produtos Compostos (submenu colapsável) -->
        <a class="list-group-item list-group-item-action bg-dark text-white py-3 d-flex justify-content-between align-items-center"
           data-bs-toggle="collapse" href="#submenu-composites" role="button" aria-expanded="false" aria-controls="submenu-composites">
            <span><i class="fas fa-cubes me-2"></i> Produtos Compostos</span>
            <i class="fas fa-chevron-right submenu-caret"></i>
        </a>
        <div class="collapse" id="submenu-composites">
            <div class="list-group list-group-flush bg-submenu">
                <a href="index.php?page=composite-templates" class="list-group-item list-group-item-action bg-dark text-white py-2 ps-4">
                    <i class="fas fa-list me-2"></i> Templates
                </a>
                <a href="index.php?page=composite-assemblies" class="list-group-item list-group-item-action bg-dark text-white py-2 ps-4">
                    <i class="fas fa-tools me-2"></i> Montagens
                </a>
                <a href="index.php?page=composite-products" class="list-group-item list-group-item-action bg-dark text-white py-2 ps-4">
                    <i class="fas fa-box me-2"></i> Produtos Finalizados
                </a>
            </div>
        </div>
        <a href="index.php?page=production_orders" class="list-group-item list-group-item-action bg-dark text-white py-3">
            <i class="fas fa-clipboard-list me-2"></i> Pedidos
        </a>
        <a href="index.php?page=kanban" class="list-group-item list-group-item-action bg-dark text-white py-3">
            <i class="fas fa-columns me-2"></i> Kanban
        </a>
        <!-- Cadastros (submenu colapsável) -->
        <a class="list-group-item list-group-item-action bg-dark text-white py-3 d-flex justify-content-between align-items-center"
           data-bs-toggle="collapse" href="#submenu-cadastros" role="button" aria-expanded="false" aria-controls="submenu-cadastros">
            <span><i class="fas fa-folder-open me-2"></i> Cadastros</span>
            <i class="fas fa-chevron-right submenu-caret"></i>
        </a>
        <div class="collapse" id="submenu-cadastros">
            <div class="list-group list-group-flush bg-submenu">
                <a href="index.php?page=clients" class="list-group-item list-group-item-action bg-dark text-white py-2 ps-4">
                    <i class="fas fa-users me-2"></i> Clientes
                </a>
                <a href="index.php?page=tipos" class="list-group-item list-group-item-action bg-dark text-white py-2 ps-4">
                    <i class="fas fa-tags me-2"></i> Tipos
                </a>
                <a href="index.php?page=product_status" class="list-group-item list-group-item-action bg-dark text-white py-2 ps-4">
                    <i class="fas fa-flag me-2"></i> Status
                </a>
                <a href="index.php?page=users" class="list-group-item list-group-item-action bg-dark text-white py-2 ps-4">
                    <i class="fas fa-user-cog me-2"></i> Usuários
                </a>
            </div>
        </div>
        <a href="index.php?page=audit" class="list-group-item list-group-item-action bg-dark text-white py-2">
            <i class="fas fa-clipboard-list me-2"></i> Auditoria
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

<style>
/* Submenu visual e transições */
#submenu-cadastros.collapse, #submenu-composites.collapse { transition: height 0.25s ease; }
.bg-submenu .list-group-item { background-color: #212529 !important; }
.submenu-caret { transition: transform 0.2s ease; }
a[aria-controls="submenu-cadastros"].collapsed .submenu-caret,
a[aria-controls="submenu-composites"].collapsed .submenu-caret { transform: rotate(0deg); }
a[aria-controls="submenu-cadastros"]:not(.collapsed) .submenu-caret,
a[aria-controls="submenu-composites"]:not(.collapsed) .submenu-caret { transform: rotate(90deg); }
</style>