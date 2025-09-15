<?php

class ProductionBatch {
    private $conn;
    private $table_name = "production_batches";

    public function __construct($db) {
        $this->conn = $db;
    }

    private function checkIfColumnExists($table, $column) {
        try {
            $sql = "SELECT $column FROM $table LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    private function generateBatchNumber() {
        $monthYear = date('mY'); // Ex: 092025 para setembro de 2025
        
        $query = "SELECT batch_number FROM " . $this->table_name . " 
                 WHERE batch_number LIKE '" . $monthYear . "/%' 
                 ORDER BY batch_number DESC LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Extrair o número sequencial (últimos 4 dígitos após a barra)
            $parts = explode('/', $row['batch_number']);
            if (count($parts) == 2) {
                $lastNumber = intval($parts[1]);
                $newNumber = $lastNumber + 1;
            } else {
                $newNumber = 1;
            }
        } else {
            $newNumber = 1;
        }
        
        return $monthYear . '/' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    public function getNextBatchNumber() {
        return $this->generateBatchNumber();
    }

    // Retorna o próximo número inicial de série para um tipo (máximo atual + 1) ou 1 se não houver
    public function getNextSerialStart($tipoId) {
        $tipoId = intval($tipoId);
        if ($tipoId <= 0) { return 1; }
        try {
            // Importante: usar CAST para comparar numericamente quando serial_number for VARCHAR
            $sql = "SELECT MAX(CAST(serial_number AS UNSIGNED)) AS max_serial FROM products WHERE tipo_id = :tipo_id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':tipo_id', $tipoId, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $max = isset($row['max_serial']) && $row['max_serial'] !== null ? intval($row['max_serial']) : 0;
            return $max > 0 ? $max + 1 : 1;
        } catch (PDOException $e) {
            error_log('getNextSerialStart error: ' . $e->getMessage());
            return 1;
        }
    }

    public function create($data) {
        $this->conn->beginTransaction();

        try {
            // Criar o Lote
            $query = "INSERT INTO " . $this->table_name . "
                    (batch_number, production_date, notes)
                    VALUES
                    (:batch_number, :production_date, :notes)";

            $stmt = $this->conn->prepare($query);

            $batch_number = $this->generateBatchNumber();

            // Sanitize
            $production_date = htmlspecialchars(strip_tags($data['production_date']));
            $notes = htmlspecialchars(strip_tags($data['notes'] ?? ''));

            // Bind
            $stmt->bindParam(":batch_number", $batch_number);
            $stmt->bindParam(":production_date", $production_date);
            $stmt->bindParam(":notes", $notes);

            if(!$stmt->execute()) {
                throw new Exception("Erro ao criar Lote");
            }

            $batch_id = $this->conn->lastInsertId();

            // Verificar se status_id existe na tabela products
            $hasStatusColumn = $this->checkIfColumnExists('products', 'status_id');
            
            if ($hasStatusColumn) {
                $query = "INSERT INTO products 
                        (tipo_id, serial_number, production_batch_id, warranty, destination, sale_date, status_id)
                        VALUES 
                        (:tipo_id, :serial_number, :production_batch_id, :warranty, 'estoque', NOW(), :status_id)";
            } else {
                $query = "INSERT INTO products 
                        (tipo_id, serial_number, production_batch_id, warranty, destination, sale_date)
                        VALUES 
                        (:tipo_id, :serial_number, :production_batch_id, :warranty, 'estoque', NOW())";
            }
            
            $stmt = $this->conn->prepare($query);

            // Criar produtos com base no range de números de série
            // Garantir que o número inicial nunca seja menor que o próximo disponível para o tipo
            $client_start = isset($data['serial_start']) ? intval($data['serial_start']) : 1;
            $min_start = $this->getNextSerialStart($data['tipo_id']);
            $start_serial = max($client_start, $min_start);
            $quantity = $data['quantity'];
            
            for($i = 0; $i < $quantity; $i++) {
                $serial_number = $start_serial + $i;
                
                $stmt->bindParam(":tipo_id", $data['tipo_id']);
                $stmt->bindParam(":serial_number", $serial_number);
                $stmt->bindParam(":production_batch_id", $batch_id);
                $stmt->bindParam(":warranty", $data['warranty']);
                
                if ($hasStatusColumn) {
                    $status_id = $data['status_id'] ?? 1; // Default para "em_estoque"
                    $stmt->bindParam(":status_id", $status_id);
                }

                if(!$stmt->execute()) {
                    throw new Exception("Erro ao criar produto #" . $serial_number);
                }
            }

            $this->conn->commit();
            return ['success' => true, 'batch_number' => $batch_number, 'batch_id' => $batch_id];

        } catch(Exception $e) {
            $this->conn->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getById($id) {
        $query = "SELECT b.*, 
                        COUNT(p.id) as total_products
                 FROM " . $this->table_name . " b
                 LEFT JOIN products p ON p.production_batch_id = b.id
                 WHERE b.id = ?
                 GROUP BY b.id";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAll() {
        try {
            $query = "SELECT b.*, 
                            COUNT(p.id) as total_products,
                            COUNT(CASE WHEN p.production_order_id IS NULL THEN 1 END) as available_products
                     FROM " . $this->table_name . " b
                     LEFT JOIN products p ON p.production_batch_id = b.id
                     GROUP BY b.id
                     ORDER BY b.id DESC";

            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in ProductionBatch::getAll(): " . $e->getMessage());
            return [];
        }
    }

    public function getProducts($id) {
        $query = "SELECT p.*, t.nome as tipo_name
                 FROM products p
                 LEFT JOIN tipos t ON p.tipo_id = t.id
                 WHERE p.production_batch_id = ?
                 ORDER BY p.serial_number ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAvailableProducts($id) {
        $query = "SELECT p.*, t.nome as tipo_name
                 FROM products p
                 LEFT JOIN tipos t ON p.tipo_id = t.id
                 WHERE p.production_batch_id = ? 
                 AND p.production_order_id IS NULL
                 ORDER BY p.serial_number ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function delete($id) {
        $this->conn->beginTransaction();

        try {
            // Buscar informações do lote
            $batch = $this->getById($id);
            if (!$batch) {
                throw new Exception("Lote não encontrado");
            }

            // Buscar todos os produtos do lote
            $query = "SELECT id FROM products WHERE production_batch_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$id]);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $deletedProducts = 0;
            $deletedQRCodes = 0;

            // Excluir QR codes físicos e produtos
            foreach ($products as $product) {
                // Remover arquivo QR code se existir
                $qrCodePath = __DIR__ . '/../../public/qrcodes/' . $product['id'] . '.png';
                if (file_exists($qrCodePath)) {
                    if (unlink($qrCodePath)) {
                        $deletedQRCodes++;
                    }
                }

                // Excluir produto do banco
                $deleteQuery = "DELETE FROM products WHERE id = ?";
                $deleteStmt = $this->conn->prepare($deleteQuery);
                if ($deleteStmt->execute([$product['id']])) {
                    $deletedProducts++;
                }
            }

            // Excluir o lote
            $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            if (!$stmt->execute([$id])) {
                throw new Exception("Erro ao excluir lote");
            }

            $this->conn->commit();

            return [
                'success' => true, 
                'message' => "Lote {$batch['batch_number']} excluído com sucesso",
                'deleted_products' => $deletedProducts,
                'deleted_qrcodes' => $deletedQRCodes,
                'batch_number' => $batch['batch_number']
            ];

        } catch(Exception $e) {
            $this->conn->rollBack();
            error_log("Error in ProductionBatch::delete(): " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
