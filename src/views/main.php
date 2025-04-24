<?php
require 'database.php';
require 'src/models/Product.php';
require 'src/models/Tipo.php';

$database = Database::getInstance();
$db = $database->getConnection();

$productModel = new Product($db);
$products = $productModel->getAll();

$tipoModel = new Tipo($db);
$tipos = $tipoModel->getAll();
?>

<div class="container-fluid">
  <h2 class="mt-4">Lista de Produtos</h2>
  <button class="btn btn-primary btn-sm mb-4 mt-4" id="add-mcp">Novo Produto</button>
  <table id="mcp-table" class="table table-striped table-bordered" style="width:100%">
    <thead>
      <tr>
        <th>ID</th>
        <td>Produto</td>
        <th>Número de série</th>
        <th>Data de venda</th>
        <th>Destino</th>
        <th>Garantia</th>
        <th style="width: 180px;">Ações</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($products as $product) : ?>
        <tr>
          <td><?= $product['id'] ?></td>
          <td><?= $product['tipo_name'] ?></td>
          <td><?= $product['serial_number'] ?></td>
          <td><?= (new DateTime($product['sale_date']))->format('d/m/Y') ?></td>
          <td><?= $product['destination'] ?></td>
          <td><?= $product['warranty'] ?></td>
          <td>
            <button class="btn btn-primary btn-sm edit-mcp" data-id="<?= $product['id'] ?>">Editar</button>
            <button class="btn btn-danger btn-sm delete-mcp" data-id="<?= $product['id'] ?>">Excluir</button>
            <button class="btn btn-info btn-sm print-qrcode" data-id="<?= $product['id']; ?>">QR-Code</button>
          </td>
        </tr>
      <?php endforeach; ?>

    </tbody>
  </table>
</div>