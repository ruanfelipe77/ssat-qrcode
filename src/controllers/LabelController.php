<?php
// Ajustar caminho base para funcionar a partir de src/controllers/
$basePath = dirname(dirname(__DIR__));
require_once $basePath . '/database.php';
require_once $basePath . '/src/models/Product.php';

// Debug: log dos parâmetros recebidos
error_log("LabelController chamado com parâmetros: " . print_r($_GET, true));

// Params:
// - id: imprime etiqueta individual do produto
// - order_id: imprime etiquetas de todos os produtos do pedido
// - batch_id: imprime etiquetas de todos os produtos do lote
// - ids: lista separada por vírgulas para imprimir etiquetas específicas (ex: ids=1,2,3)
// - dpi (opcional): apenas para ajustar o tamanho do PNG gerado (CSS fixa 14mm); default 300

$id = isset($_GET['id']) ? trim($_GET['id']) : null;
$orderId = isset($_GET['order_id']) ? trim($_GET['order_id']) : null;
$batchId = isset($_GET['batch_id']) ? trim($_GET['batch_id']) : null;
$idsParam = isset($_GET['ids']) ? trim($_GET['ids']) : null;
$dpi = isset($_GET['dpi']) ? max(96, min(600, (int)$_GET['dpi'])) : 300;
// Offsets e espaçamento (mm) opcionais para ajuste fino na impressão
$mtMm = isset($_GET['mt_mm']) ? max(0, (float)$_GET['mt_mm']) : 0; // margem/offset superior extra
$mlMm = isset($_GET['ml_mm']) ? max(0, (float)$_GET['ml_mm']) : 0; // margem/offset esquerdo extra
$gapMm = isset($_GET['gap_mm']) ? max(0, (float)$_GET['gap_mm']) : 0; // espaçamento entre etiquetas

try {
    $db = Database::getInstance()->getConnection();
    $productModel = new Product($db);
    error_log("LabelController: Database conectado com sucesso");
} catch (Exception $e) {
    error_log("LabelController: Erro ao conectar database: " . $e->getMessage());
    die("Erro de conexão com o banco de dados");
}

$products = [];
if ($id) {
    $p = $productModel->getById($id);
    if ($p) { $products = [$p]; }
} elseif ($orderId) {
    $products = $productModel->getByOrderId($orderId);
} elseif ($batchId) {
    error_log("LabelController: Buscando produtos do lote ID: " . $batchId);
    try {
        $products = $productModel->getByBatchId($batchId);
        error_log("LabelController: Encontrados " . count($products) . " produtos");
        if (empty($products)) {
            error_log("LabelController: AVISO - Nenhum produto encontrado para o lote " . $batchId);
        }
    } catch (Exception $e) {
        error_log("LabelController: ERRO ao buscar produtos do lote: " . $e->getMessage());
        $products = [];
    }
} elseif ($idsParam) {
    // Sanitizar e montar consulta IN
    $raw = array_filter(array_map('trim', explode(',', $idsParam)), function($v){ return $v !== ''; });
    $ids = array_values(array_unique(array_map('intval', $raw)));
    if (count($ids) > 0) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $db->prepare("SELECT * FROM products WHERE id IN ($placeholders) ORDER BY serial_number ASC");
        $stmt->execute($ids);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Helper para escapar
function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// Tamanho do QR em pixels para boa definição de impressão; CSS força 14mm
$qrPixels = (int)round(($dpi / 25.4) * 14); // 14mm em px
if ($qrPixels < 150) { $qrPixels = 150; }

?><!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <title>Impressão de Etiquetas</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    @page {
      size: 100mm 20mm;
      margin: 0;
    }
    * { box-sizing: border-box; }
    html, body {
      padding: 0; margin: 0;
      font-family: Arial, Helvetica, sans-serif;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }
    /* Grade de 5 colunas, cada etiqueta 20x20mm, espaçamento configurável */
    .grid {
      display: grid;
      grid-template-columns: repeat(5, 20mm);
      grid-auto-rows: 20mm;
      gap: <?= number_format($gapMm, 2, '.', '') ?>mm;
      width: fit-content;
      margin-left: 1mm; /* pequena margem para centralizar melhor */
    }
    /* Cada etiqueta 20x20mm */
    .label {
      width: 20mm;
      height: 20mm;
      border: none; /* sem borda externa da etiqueta */
      position: relative;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: flex-start;
      padding: 0; /* sem padding para respeitar a área 20x20 */
      overflow: hidden;
    }
    /* Compensação progressiva para deslocamento da impressora */
    .label:nth-child(5n-2) { margin-left: 0.5mm; } /* 3ª posição de cada linha (3, 8, 13...) */
    .label:nth-child(5n-1) { margin-left: 1.0mm; } /* 4ª posição de cada linha (4, 9, 14...) */
    .label:nth-child(5n) { margin-left: 1.5mm; } /* 5ª posição de cada linha (5, 10, 15...) */
    /* Área do serial (4mm de altura) */
    .serial {
      height: 4mm;
      width: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2.2mm; /* ajustar para caber bem */
      line-height: 1;
      text-align: center;
      white-space: nowrap;
      padding: 0 0.5mm;
    }
    /* Área do QR (14mm + 2mm margem inferior) */
    .qr-area {
      width: 100%;
      height: 16mm; /* 14mm QR + 2mm margem inferior */
      display: flex;
      align-items: flex-start; /* QR encostado no topo dessa área */
      justify-content: center;
      position: relative;
    }
    .qr {
      width: 14mm;
      height: 14mm;
      border: 0.2mm solid #000; /* borda ao redor do QR */
    }
    @media print {
      .no-print { display: none; }
    }
  </style>
</head>
<body>
  <div class="no-print" style="padding:8px; background:#f7f7f7; border-bottom:1px solid #ddd;">
    <button onclick="window.print()">Imprimir</button>
  </div>

  <?php
  $total = count($products);
  if ($total === 0) {
      echo '<div style="padding:12px">Nenhum produto encontrado para impressão.</div>';
  } else {
      $style = '';
      if ($mtMm > 0) { $style .= 'margin-top:' . number_format($mtMm, 2, '.', '') . 'mm;'; }
      if ($mlMm > 0) { $style .= 'margin-left:' . number_format($mlMm, 2, '.', '') . 'mm;'; }
      echo '<div class="grid" style="' . e($style) . '">';
      foreach ($products as $prod) {
          $sid = $prod['id'];
          $serial = $prod['serial_number'] ?? '';
          // A imagem do QR vem do QrController, mas o tamanho final é controlado por CSS (14mm)
          $qrUrl = 'QrController.php?id=' . rawurlencode($sid) . '&s=' . (int)$qrPixels;
          echo '<div class="label">';
          echo '  <div class="serial">' . e($serial) . '</div>';
          echo '  <div class="qr-area">';
          echo '    <img class="qr" src="' . e($qrUrl) . '" alt="QR" />';
          echo '  </div>';
          echo '</div>';
      }
      echo '</div>';
  }
  ?>
</body>
</html>
