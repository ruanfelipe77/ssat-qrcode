<?php
$jsonData = $_GET['data'] ?? '{}';
$data = json_decode($jsonData, true);
require 'database.php';

function getTipoName($tipo_id, $conn) {
  $query = "SELECT nome FROM tipos WHERE id = :tipo_id";
  $stmt = $conn->prepare($query);
  $stmt->bindParam(':tipo_id', $tipo_id, PDO::PARAM_INT);
  $stmt->execute();
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  return $row['nome'] ?? 'Tipo Desconhecido';
}

// Obter a conexão com o banco de dados usando a classe Database
$db = Database::getInstance();
$conn = $db->getConnection();

$tipoName = getTipoName($data['tipo_id'], $conn);
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
    html, body {
      height: 100%;
    }
    #wrapper {
      display: flex;
      flex-direction: column;
      min-height: 100vh;
    }
    #page-content-wrapper {
      flex: 1;
    }
    footer {
      flex-shrink: 0;
    }
  </style>
  
</head>

<body>
  <div class="d-flex flex-column" id="wrapper">
    <div id="page-content-wrapper" class="w-100" style="max-height:280px;">
      <?php include 'src/views/header.php'; ?>
      <div class="container-fluid" style="margin-top:20px;">
        <h2>Informações do Produto</h2>
        <ul>
          <li><strong>Produto:</strong> <?= htmlspecialchars($tipoName) ?></li>
          <li><strong>Número de Série:</strong> <?= htmlspecialchars($data['serial_number']) ?></li>
          <li><strong>Data da Venda:</strong> <?= (new DateTime($data['sale_date']))->format('d/m/Y') ?></li>
          <li><strong>Destino:</strong> <?= htmlspecialchars($data['destination']) ?></li>
          <li><strong>Garantia:</strong> <?= htmlspecialchars($data['warranty']) ?></li>
        </ul>
      </div>
    </div>

    <!-- Rodapé -->
    <footer class="bg-dark text-white mt-4">
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