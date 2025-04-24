<?php
require 'database.php';
require 'src/models/Tipo.php';

$database = Database::getInstance();
$db = $database->getConnection();

$TipoModel = new Tipo($db);
$tipos = $TipoModel->getAll();
?>

<div class="container-fluid">
  <h2 class="mt-4">Tipos de Produto</h2>
  <button class="btn btn-primary btn-sm mb-4 mt-4" id="add-produto">Novo</button>
  <table id="mcp-table" class="table table-striped table-bordered" style="width:100%">
    <thead>
      <tr>
        <th>ID</th>
        <th>Nome</th>
        <th style="width: 100px;">Ações</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($tipos as $product) : ?>
        <tr>
          <td><?= $product['id'] ?></td>
          <td><?= $product['nome'] ?></td>
          <td>
            <button class="btn btn-primary btn-sm edit-mcp" data-id="<?= $product['id'] ?>">Editar</button>
            <button class="btn btn-danger btn-sm delete-mcp" data-id="<?= $product['id'] ?>">Excluir</button>
          </td>
        </tr>
      <?php endforeach; ?>

    </tbody>
  </table>
</div>