<?php
    $req = $_SERVER['REQUEST_URI'] ?? '';
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $isQrPage = (strpos($req, 'qrcode.php') !== false) || (strpos($script, 'qrcode.php') !== false);
?>

<nav class="navbar navbar-expand-lg bg-white border-bottom">
    <div class="container-fluid" style="position: relative; min-height: 64px;">
        <?php if ($isQrPage): ?>
            <!-- Logo central absoluta (somente na página de resultado do QRCode) -->
            <img
                src="public/images/logo_ssat.png"
                alt="Central SSAT"
                style="position:absolute; left:26%; top:8px; transform:translateX(-50%); max-height:48px; object-fit:contain; z-index: 1;"
                draggable="false"
            />
        <?php endif; ?>

        <!-- Espaço vazio à esquerda (mantém o dropdown à direita) -->
        <div class="flex-grow-1"></div>

        <?php if (!$isQrPage): ?>
        <!-- Dropdown de usuário alinhado à direita (oculto no QRCode) -->
        <div class="dropdown ms-auto">
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
        <?php endif; ?>
    </div>
</nav>