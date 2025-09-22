<?php
require 'database.php';
// Carregar usuários para o filtro (ativos primeiro)
$users = [];
try {
  $db = Database::getInstance()->getConnection();
  $stmt = $db->query("SELECT id, name FROM users WHERE active = 1 ORDER BY name ASC");
  $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { $users = []; }
?>
<div class="container-fluid p-4">
  <div class="mb-2">
    <h2 class="mb-1">Auditoria</h2>
    <div class="d-flex align-items-center gap-2 flex-nowrap audit-toolbar" style="overflow:auto;">
      <select id="audit-user" class="form-select-sm" style="width:180px">
        <option value="">Usuário: Todos</option>
        <?php foreach ($users as $u): ?>
          <option value="<?= (int)$u['id'] ?>"><?= htmlspecialchars($u['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <select id="audit-entity" class="form-select-sm" style="width:150px">
        <option value="">Entidade: Todas</option>
        <option value="order">Pedidos</option>
        <option value="product">Produtos</option>
        <option value="batch">Lotes</option>
        <option value="client">Clientes</option>
        <option value="tipo">Tipos</option>
        <option value="status">Status</option>
        <option value="user">Usuários</option>
      </select>
      <select id="audit-action" class="form-select-sm" style="width:150px">
        <option value="">Ação: Todas</option>
        <option value="create">Criado</option>
        <option value="update">Atualizado</option>
        <option value="delete">Excluído</option>
        <option value="add_products">+ Produtos</option>
        <option value="remove_products">- Produtos</option>
        <option value="attach">Vincular</option>
        <option value="detach">Desvincular</option>
        <option value="status_change">Mudança de Status</option>
      </select>
      <div class="input-group input-group-sm audit-daterange">
        <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
        <input type="date" id="audit-df" class="form-control form-control-sm" title="Data início" aria-label="Data início">
        <span class="input-group-text">–</span>
        <input type="date" id="audit-dt" class="form-control form-control-sm" title="Data fim" aria-label="Data fim">
      </div>
      <button class="btn btn-sm btn-primary" id="audit-apply"><i class="fas fa-search me-1"></i>Aplicar</button>
      <button class="btn btn-sm btn-outline-secondary" id="audit-clear"><i class="fas fa-eraser me-1"></i>Limpar</button>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <table id="audit-table" class="table table-striped table-hover" style="width:100%">
        <thead>
          <tr>
            <th>Quando</th>
            <th>Usuário</th>
            <th>Ação</th>
            <th>Entidade</th>
            <th>Resumo</th>
            <th style="width: 80px; text-align:center;">Detalhes</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal Detalhes -->
<div class="modal fade" id="auditDetailsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-info-circle me-2"></i>Detalhes</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="audit-human" class="mb-3 small"></div>
        <div id="audit-extra" class="mb-3" style="max-height: 300px; overflow: auto; display:none;"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
</div>

<script src="public/js/audit.js"></script>
<style>
  #audit-table tbody td { vertical-align: middle; }
  .audit-toolbar { gap: .5rem !important; }
  .audit-daterange { width: 360px; display: flex; flex-wrap: nowrap; }
  .audit-daterange .input-group-text { flex: 0 0 auto; }
  .audit-daterange input[type="date"] { flex: 1 1 0; min-width: 0; }
  #audit-extra table { width: 100%; }
  #audit-extra thead th { position: sticky; top: 0; background: #f8f9fa; }
</style>
