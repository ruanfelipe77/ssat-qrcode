<?php
require 'database.php';
require 'src/models/Product.php';
require 'src/models/ProductStatus.php';
require 'src/models/Tipo.php';
require 'src/models/Client.php';

$db = Database::getInstance()->getConnection();

$statusModel = new ProductStatus($db);
$statuses = $statusModel->getActive(); // id, name, color, icon

$productModel = new Product($db);
$products = $productModel->getAll();

// Index statuses by id for quick lookup
$statusById = [];
foreach ($statuses as $s) { $statusById[(int)$s['id']] = $s; }

// Group products by status_id (unknown go to 0 bucket)
$grouped = [];
foreach ($products as $p) {
    $sid = isset($p['status_id']) && $p['status_id'] !== null ? (int)$p['status_id'] : 0;
    if (!isset($grouped[$sid])) $grouped[$sid] = [];
    $grouped[$sid][] = $p;
}
?>

<div class="container-fluid p-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">Kanban de Produtos</h2>
    <div class="d-flex gap-2 align-items-center">
      <div class="input-group" style="max-width: 320px;">
        <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
        <input type="text" id="kanban-search" class="form-control" placeholder="Buscar por série, tipo, cliente...">
      </div>
    </div>
  </div>

  <div class="kanban-wrapper">
    <?php foreach ($statuses as $s): $sid = (int)$s['id']; $items = $grouped[$sid] ?? []; ?>
      <div class="kanban-column">
        <div class="kanban-header" style="background-color: <?= htmlspecialchars($s['color']) ?>33; border-color: <?= htmlspecialchars($s['color']) ?>;">
          <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
              <i class="<?= htmlspecialchars($s['icon']) ?>" style="color: <?= htmlspecialchars($s['color']) ?>;"></i>
              <strong><?= ucfirst(str_replace('_', ' ', htmlspecialchars($s['name']))) ?></strong>
            </div>
            <span class="badge rounded-pill" style="background-color: <?= htmlspecialchars($s['color']) ?>;"><?= count($items) ?></span>
          </div>
        </div>
        <div class="kanban-body" data-status-id="<?= $sid ?>">
          <?php foreach ($items as $p): ?>
            <div class="kanban-card" data-id="<?= (int)$p['id'] ?>">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <div class="d-flex align-items-center gap-2">
                  <i class="fas fa-microchip text-primary"></i>
                  <span class="fw-semibold"><?= htmlspecialchars($p['tipo_name'] ?? '') ?></span>
                </div>
                <span class="badge text-bg-light">#<?= htmlspecialchars($p['serial_number'] ?? '') ?></span>
              </div>
              <div class="small text-muted mb-2">
                <?php
                  $city = $p['client_city'] ?? '';
                  $state = $p['client_state'] ?? '';
                  if (($p['destination'] ?? '') === 'estoque' || ($p['client_name'] ?? '') === 'Em Estoque') {
                    echo '<span class="text-secondary">Em Estoque</span>';
                  } else {
                    echo htmlspecialchars(trim($city . ($state ? '/' . $state : '')));
                  }
                ?>
              </div>
              <div class="d-flex justify-content-between">
                <span class="badge bg-dark"><i class="fas fa-shield-alt me-1"></i><?= htmlspecialchars($p['warranty'] ?? '') ?></span>
                <?php if (!empty($p['sale_date'])): ?>
                  <span class="badge bg-secondary"><i class="fas fa-calendar me-1"></i><?= (new DateTime($p['sale_date']))->format('d/m/Y') ?></span>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<style>
.kanban-wrapper {
  display: flex;
  gap: 16px;
  overflow-x: auto;
  padding-bottom: 8px;
}
.kanban-column {
  flex: 0 0 320px; /* largura fixa por coluna */
  display: flex;
  flex-direction: column;
  max-height: calc(100vh - 220px);
}
.kanban-header {
  border-left: 4px solid var(--bs-primary);
  border-radius: 8px;
  padding: 10px 12px;
  margin-bottom: 8px;
  background: #f8f9fa;
}
.kanban-body {
  overflow-y: auto;
  padding-right: 4px;
}
.kanban-card {
  background: #ffffff;
  border: 1px solid #e9ecef;
  border-radius: 8px;
  padding: 10px 12px;
  margin-bottom: 10px;
  box-shadow: 0 1px 2px rgba(0,0,0,0.06);
}
.kanban-card:hover { box-shadow: 0 4px 10px rgba(0,0,0,0.12); transform: translateY(-1px); transition: all .15s ease; }

/* Scrollbar sutil */
.kanban-wrapper::-webkit-scrollbar, .kanban-body::-webkit-scrollbar { height: 8px; width: 8px; }
.kanban-wrapper::-webkit-scrollbar-thumb, .kanban-body::-webkit-scrollbar-thumb { background: #c2c7d0; border-radius: 8px; }
</style>

<script>
$(function(){
  // Busca rápida por texto dentro dos cards
  $('#kanban-search').on('input', function(){
    const q = $(this).val().toLowerCase();
    $('.kanban-card').each(function(){
      const txt = $(this).text().toLowerCase();
      $(this).toggle(txt.indexOf(q) !== -1);
    });
  });
});
</script>
