<?php
    $req = $_SERVER['REQUEST_URI'] ?? '';
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $isQrPage = (strpos($req, 'qrcode.php') !== false) || (strpos($script, 'qrcode.php') !== false);
?>

<nav class="navbar navbar-expand-lg bg-white border-bottom">
    <div class="container-fluid" style="position: relative;">
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
        <div class="d-flex align-items-center gap-3 ms-auto">
            <!-- Alerta de Pedidos Vazios -->
            <a href="index.php?page=production_orders&only_empty=1" class="btn btn-sm btn-outline-danger position-relative" title="Pedidos vazios para preencher">
                <i class="fas fa-bell"></i>
                <span id="empty-orders-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none">0</span>
            </a>

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
        <?php endif; ?>
    </div>
</nav>

<?php if (!$isQrPage): ?>
<script>
  (function(){
    try {
      fetch('src/controllers/ProductionOrderController.php?empty_count=1', { cache: 'no-store' })
        .then(function(r){ return r.ok ? r.json() : { count: 0 }; })
        .then(function(data){
          var n = (data && typeof data.count === 'number') ? data.count : 0;
          var badge = document.getElementById('empty-orders-badge');
          if (!badge) return;
          if (n > 0) {
            badge.textContent = n;
            badge.classList.remove('d-none');
          } else {
            badge.classList.add('d-none');
          }
        })
        .catch(function(){});
    } catch(e) {}
  })();
</script>
<?php endif; ?>