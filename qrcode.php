<?php
// Preferirá buscar por ID; fallback para JSON em ?data=
$id = isset($_GET['id']) ? trim($_GET['id']) : null;
$jsonData = $_GET['data'] ?? null;
$data = $jsonData ? json_decode($jsonData, true) : null;
require 'database.php';

function getTipoName($tipo_id, $conn) {
  $query = "SELECT nome FROM tipos WHERE id = :tipo_id";
  $stmt = $conn->prepare($query);
  $stmt->bindParam(':tipo_id', $tipo_id, PDO::PARAM_INT);
  $stmt->execute();
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  return $row['nome'] ?? 'Tipo Desconhecido';
}

function formatDestination($conn, $destination) {
  // Estoque
  if ($destination === 'estoque' || strtolower($destination) === 'em estoque') {
    return 'Em Estoque';
  }
  // Se for um ID numérico de cliente, buscar cidade/UF
  if (preg_match('/^\d+$/', (string)$destination)) {
    $stmt = $conn->prepare('SELECT city, state FROM clients WHERE id = :id');
    $stmt->execute(['id' => $destination]);
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $city = trim($row['city'] ?? '');
      $state = trim($row['state'] ?? '');
      if ($city !== '' || $state !== '') {
        return htmlspecialchars($city . ($state ? '/' . $state : ''));
      }
    }
  }
  // Caso já seja texto (ex.: "Lages/SC")
  return htmlspecialchars((string)$destination);
}

// Obter a conexão com o banco de dados usando a classe Database
$db = Database::getInstance();
$conn = $db->getConnection();

// Buscar produto por ID (preferido); caso contrário, usar dados do JSON
$product = null;
$orderNfe = null;
if ($id) {
  $stmt = $conn->prepare('SELECT * FROM products WHERE id = :id');
  $stmt->execute(['id' => $id]);
  $product = $stmt->fetch(PDO::FETCH_ASSOC);
  // Tentar obter NFe do pedido associado, se houver coluna e vinculação
  if ($product && !empty($product['production_order_id'])) {
    try {
      // Verificar se a coluna nfe existe na tabela de pedidos
      $probe = $conn->prepare('SELECT nfe FROM sales_orders WHERE id = :oid LIMIT 1');
      $probe->execute(['oid' => $product['production_order_id']]);
      if ($row = $probe->fetch(PDO::FETCH_ASSOC)) {
        $orderNfe = $row['nfe'] ?? null;
      }
    } catch (Throwable $e) {
      // Silenciosamente ignorar se a coluna não existir
    }
  }
}

// Montar fonte de dados para exibição
if ($product) {
  $display = [
    'tipo_id' => $product['tipo_id'] ?? null,
    'serial_number' => $product['serial_number'] ?? '',
    'sale_date' => $product['sale_date'] ?? '',
    'destination' => $product['destination'] ?? '',
    'warranty' => $product['warranty'] ?? '',
  ];
  if ($orderNfe) { $display['nfe'] = $orderNfe; }
} elseif (is_array($data)) {
  $display = [
    'tipo_id' => $data['tipo_id'] ?? null,
    'serial_number' => $data['serial_number'] ?? '',
    'sale_date' => $data['sale_date'] ?? '',
    'destination' => $data['destination'] ?? '',
    'warranty' => $data['warranty'] ?? '',
  ];
} else {
  $display = [
    'tipo_id' => null,
    'serial_number' => '',
    'sale_date' => '',
    'destination' => '',
    'warranty' => '',
  ];
}

$tipoName = $display['tipo_id'] !== null ? getTipoName($display['tipo_id'], $conn) : 'Tipo Desconhecido';
$destinationText = formatDestination($conn, $display['destination']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SSAT</title>
  <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css">
  <link href="public/css/app.css" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
  <script src="public/js/app.js"></script>
  
  <style>
    html, body { height: 100%; }
    #wrapper {
      display: flex;
      flex-direction: column;
      min-height: 100vh; /* ensure full viewport height */
    }
    #page-content-wrapper { flex: 1 0 auto; }
    footer {
      flex-shrink: 0;
      margin-top: auto; /* push footer to bottom when content is short */
    }
  </style>
  
</head>

<body>
  <div class="d-flex flex-column" id="wrapper">
    <div id="page-content-wrapper" class="w-100">
      <?php include 'src/views/header.php'; ?>
      <div class="container-fluid" style="margin-top:20px;">
        <h2>Informações do Produto</h2>
        <ul>
          <li><strong>Produto:</strong> <?= htmlspecialchars($tipoName) ?></li>
          <li><strong>Número de Série:</strong> <?= htmlspecialchars($display['serial_number']) ?></li>
          <li><strong>Data da Venda:</strong> <?= $display['sale_date'] ? (new DateTime($display['sale_date']))->format('d/m/Y') : '' ?></li>
          <li><strong>Destino:</strong> <?= $destinationText ?></li>
          <li><strong>Garantia:</strong> <?= htmlspecialchars($display['warranty']) ?></li>
          <?php if (!empty($display['nfe'])): ?>
          <li><strong>Nota Fiscal:</strong> <?= htmlspecialchars($display['nfe']) ?></li>
          <?php endif; ?>
        </ul>
      </div>
    </div>

    <!-- Rodapé -->
    <footer class="bg-dark text-white mt-5">
      <div class="container py-4">
        <div class="row">
          <div class="col-md-12">
            <h5>QUEM SOMOS</h5>
            <p>
              Somos uma empresa brasileira que, desde 1998, atua no ramo de produtos e serviços para Sinalização Viária Semafórica, Vertical e Horizontal de alta qualidade, tecnologia e performance.
            </p>
          </div>
          <div class="col-md-12">
            <h5>FALE COM A GENTE</h5>
            <ul class="list-unstyled">
              <li>T: +55 (47) 3521-3245</li>
              <li>E: ssat@ssat.srv.br</li>
              <li>Rua Júlio Schlupp, nº 767, sala 01 - Bairro Bela Aliança Rio do Sul SC - CEP 89.161-424</li>
            </ul>
          </div>
        </div>
      </div>
    </footer>
  </div>
</body>

</html>