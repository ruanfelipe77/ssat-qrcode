<?php
require 'database.php';
require 'src/models/User.php';

$db = Database::getInstance()->getConnection();
$userModel = new User($db);
$users = $userModel->getAll();
?>

<div class="container-fluid p-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Usuários</h2>
    <button class="btn btn-primary" id="add-user">
      <i class="fas fa-user-plus me-2"></i>Novo Usuário
    </button>
  </div>

  <div class="card">
    <div class="card-body">
      <table id="users-table" class="table table-striped table-hover" style="width:100%">
        <thead>
          <tr>
            <th>ID</th>
            <th>Nome</th>
            <th>E-mail</th>
            <th style="width: 160px; text-align:center;">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): ?>
            <tr>
              <td><?= (int)$u['id'] ?></td>
              <td><?= htmlspecialchars($u['name']) ?></td>
              <td><?= htmlspecialchars($u['email']) ?></td>
              <td>
                <div class="d-flex justify-content-center align-items-center gap-3">
                  <button class="btn btn-link text-dark p-0 edit-user" data-id="<?= (int)$u['id'] ?>" title="Editar">
                    <i class="fas fa-edit fs-5"></i>
                  </button>
                  <button class="btn btn-link text-danger p-0 delete-user" data-id="<?= (int)$u['id'] ?>" title="Excluir">
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

<?php include 'src/views/modal_user.php'; ?>

<script src="public/js/users.js"></script>
