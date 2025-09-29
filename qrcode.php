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
$isComposite = false;
$compositeComponents = [];
$assemblyInfo = null;

if ($id) {
  // Buscar produto com informações do tipo
  $stmt = $conn->prepare('SELECT p.*, t.nome as tipo_name, t.is_composite FROM products p LEFT JOIN tipos t ON p.tipo_id = t.id WHERE p.id = :id');
  $stmt->execute(['id' => $id]);
  $product = $stmt->fetch(PDO::FETCH_ASSOC);
  
  if ($product) {
    $isComposite = ($product['is_composite'] == 1);
    
    // Se for produto composto, buscar componentes
    if ($isComposite) {
      $stmt = $conn->prepare('
        SELECT ac.*, p.serial_number as component_serial, t.nome as component_tipo_name,
               a.created_at as assembly_date, u.name as assembled_by
        FROM assemblies a
        JOIN assembly_components ac ON a.id = ac.assembly_id
        JOIN products p ON ac.component_product_id = p.id
        JOIN tipos t ON p.tipo_id = t.id
        LEFT JOIN users u ON a.created_by = u.id
        WHERE a.composite_product_id = :product_id AND a.status = "finalized"
        ORDER BY ac.added_at
      ');
      $stmt->execute(['product_id' => $id]);
      $compositeComponents = $stmt->fetchAll(PDO::FETCH_ASSOC);
      
      // Buscar informações da assembly
      $stmt = $conn->prepare('
        SELECT a.*, ct.version as template_version, u.name as assembled_by
        FROM assemblies a
        LEFT JOIN composite_templates ct ON a.template_id = ct.id
        LEFT JOIN users u ON a.created_by = u.id
        WHERE a.composite_product_id = :product_id AND a.status = "finalized"
        LIMIT 1
      ');
      $stmt->execute(['product_id' => $id]);
      $assemblyInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Tentar obter NFe do pedido associado, se houver coluna e vinculação
    if (!empty($product['production_order_id'])) {
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

// Helpers de formatação: quando vazio, marcar em vermelho como "Não informado"
$fmtText = function($v) {
  $s = is_string($v) ? trim($v) : '';
  return $s !== '' ? htmlspecialchars($s) : '<span class="text-danger">NÃO INFORMADO</span>';
};
$fmtDate = function($v) {
  $s = is_string($v) ? trim($v) : '';
  if ($s === '' || $s === '0000-00-00') {
    return '<span class="text-danger">NÃO INFORMADO</span>';
  }
  try {
    return (new DateTime($s))->format('d/m/Y');
  } catch (Throwable $e) {
    return '<span class="text-danger">NÃO INFORMADO</span>';
  }
};

$produtoVal  = $fmtText($tipoName);
$serialVal   = $fmtText($display['serial_number'] ?? '');
$saleVal     = $fmtDate($display['sale_date'] ?? '');
$destVal     = $fmtText($destinationText);
$warrantyVal = $fmtText($display['warranty'] ?? '');
$nfeVal      = $fmtText($display['nfe'] ?? '');

// Flags de campos faltantes (sem dados)
$missingSerial   = trim($display['serial_number'] ?? '') === '';
$missingSale     = ($d = trim($display['sale_date'] ?? '')) === '' || $d === '0000-00-00';
$missingDest     = trim($display['destination'] ?? '') === '';
$missingWarranty = trim($display['warranty'] ?? '') === '';
$missingNfe      = trim($display['nfe'] ?? '') === '';
$missingAny      = $missingSerial || $missingSale || $missingDest || $missingWarranty || $missingNfe;
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
        <?php if ($isComposite): ?>
          <h2><i class="fas fa-cubes me-2"></i>Produto Composto</h2>
          
          <div class="alert alert-info d-flex align-items-center" role="alert">
            <i class="fas fa-info-circle me-2"></i>
            <div>
              Este é um produto composto, montado a partir de componentes individuais.
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="card">
                <div class="card-header">
                  <h5 class="mb-0">Informações Gerais</h5>
                </div>
                <div class="card-body">
                  <ul class="list-unstyled">
                    <li class="mb-2">
                      <strong>Produto:</strong>
                      <?= $produtoVal ?>
                    </li>
                    <li class="mb-2">
                      <strong>Número de Série:</strong>
                      <?php if ($missingSerial): ?><i class="fas fa-exclamation-circle text-danger me-1"></i><?php endif; ?>
                      <?= $serialVal ?>
                    </li>
                    <li class="mb-2">
                      <strong>Data da Venda:</strong>
                      <?php if ($missingSale): ?><i class="fas fa-exclamation-circle text-danger me-1"></i><?php endif; ?>
                      <?= $saleVal ?>
                    </li>
                    <li class="mb-2">
                      <strong>Destino:</strong>
                      <?php if ($missingDest): ?><i class="fas fa-exclamation-circle text-danger me-1"></i><?php endif; ?>
                      <?= $destVal ?>
                    </li>
                    <li class="mb-2">
                      <strong>Garantia:</strong>
                      <?php if ($missingWarranty): ?><i class="fas fa-exclamation-circle text-danger me-1"></i><?php endif; ?>
                      <?= $warrantyVal ?>
                    </li>
                    <?php if ($assemblyInfo): ?>
                    <li class="mb-2">
                      <strong>Montado em:</strong>
                      <?= (new DateTime($assemblyInfo['created_at']))->format('d/m/Y H:i') ?>
                    </li>
                    <li class="mb-2">
                      <strong>Montado por:</strong>
                      <?= htmlspecialchars($assemblyInfo['assembled_by'] ?? 'N/A') ?>
                    </li>
                    <?php endif; ?>
                  </ul>
                </div>
              </div>
            </div>
            
            <div class="col-md-6">
              <div class="card">
                <div class="card-header">
                  <h5 class="mb-0">Componentes (<?= count($compositeComponents) ?>)</h5>
                </div>
                <div class="card-body">
                  <?php if (empty($compositeComponents)): ?>
                    <p class="text-muted">Nenhum componente encontrado.</p>
                  <?php else: ?>
                    <div class="table-responsive">
                      <table class="table table-sm">
                        <thead>
                          <tr>
                            <th>Tipo</th>
                            <th>Serial</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php foreach ($compositeComponents as $component): ?>
                          <tr>
                            <td><?= htmlspecialchars($component['component_tipo_name']) ?></td>
                            <td>
                              <a href="qrcode.php?id=<?= $component['component_product_id'] ?>" class="text-decoration-none">
                                <?= htmlspecialchars($component['component_serial']) ?>
                              </a>
                            </td>
                          </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
          
        <?php else: ?>
          <h2><i class="fas fa-box me-2"></i>Informações do Produto</h2>

          <?php if ($missingAny): ?>
            <div class="alert alert-warning d-flex align-items-center" role="alert">
              <i class="fas fa-exclamation-triangle me-2"></i>
              <div>
                Este produto possui informações pendentes. Campos marcados como <span class="text-danger fw-bold">NÃO INFORMADO</span> devem ser preenchidos.
              </div>
            </div>
          <?php endif; ?>

          <?php 
          // Verificar se este produto individual faz parte de um composto
          $parentComposite = null;
          if ($product && $product['parent_composite_id']) {
            $stmt = $conn->prepare('SELECT p.*, t.nome as tipo_name FROM products p LEFT JOIN tipos t ON p.tipo_id = t.id WHERE p.id = :id');
            $stmt->execute(['id' => $product['parent_composite_id']]);
            $parentComposite = $stmt->fetch(PDO::FETCH_ASSOC);
          }
          ?>

          <?php if ($parentComposite): ?>
            <div class="alert alert-info d-flex align-items-center" role="alert">
              <i class="fas fa-link me-2"></i>
              <div>
                Este componente faz parte do produto composto: 
                <a href="qrcode.php?id=<?= $parentComposite['id'] ?>" class="alert-link">
                  <?= htmlspecialchars($parentComposite['tipo_name']) ?> - <?= htmlspecialchars($parentComposite['serial_number']) ?>
                </a>
              </div>
            </div>
          <?php endif; ?>

          <ul class="list-unstyled">
            <li class="mb-1">
              <strong>Produto:</strong>
              <?= $produtoVal ?>
            </li>
            <li class="mb-1">
              <strong>Número de Série:</strong>
              <?php if ($missingSerial): ?><i class="fas fa-exclamation-circle text-danger me-1"></i><?php endif; ?>
              <?= $serialVal ?>
            </li>
            <li class="mb-1">
              <strong>Data da Venda:</strong>
              <?php if ($missingSale): ?><i class="fas fa-exclamation-circle text-danger me-1"></i><?php endif; ?>
              <?= $saleVal ?>
            </li>
            <li class="mb-1">
              <strong>Destino:</strong>
              <?php if ($missingDest): ?><i class="fas fa-exclamation-circle text-danger me-1"></i><?php endif; ?>
              <?= $destVal ?>
            </li>
            <li class="mb-1">
              <strong>Garantia:</strong>
              <?php if ($missingWarranty): ?><i class="fas fa-exclamation-circle text-danger me-1"></i><?php endif; ?>
              <?= $warrantyVal ?>
            </li>
            <li class="mb-1">
              <strong>Nota Fiscal:</strong>
              <?php if ($missingNfe): ?><i class="fas fa-exclamation-circle text-danger me-1"></i><?php endif; ?>
              <?= $nfeVal ?>
            </li>
          </ul>
        <?php endif; ?>
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