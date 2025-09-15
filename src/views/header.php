<nav class="navbar navbar-expand-lg bg-white border-bottom">
    <div class="container-fluid">
        <!-- Espaço vazio à esquerda -->
        <div class="flex-grow-1"></div>

        <!-- Dropdown de usuário alinhado à direita -->
        <div class="dropdown">
            <button class="btn btn-link text-dark dropdown-toggle d-flex align-items-center gap-2" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-user-circle fs-5"></i>
                <span>Usuário</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                <li>
                    <form method="POST" action="src/controllers/LogoutController.php">
                        <button type="submit" class="dropdown-item text-danger">
                            <i class="fas fa-sign-out-alt me-2"></i> Sair
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</nav>