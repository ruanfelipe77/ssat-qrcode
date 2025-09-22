<?php

class ProductionOrder {
    private $conn;
    private $table_name = "sales_orders";

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

    private function orderNumberExists($orderNumber, $excludeId = null) {
        $sql = "SELECT id FROM " . $this->table_name . " WHERE order_number = :onum";
        if ($excludeId) { $sql .= " AND id <> :id"; }
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':onum', $orderNumber);
        if ($excludeId) { $stmt->bindValue(':id', $excludeId, PDO::PARAM_INT); }
        $stmt->execute();
        return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        // DEBUG: Log dos dados recebidos
        error_log("DEBUG ProductionOrder::create() - Dados recebidos: " . json_encode($data));
        
        $this->conn->beginTransaction();

        try {
            // Criar o PP
            $hasNfe = $this->checkIfColumnExists($this->table_name, 'nfe');
            $cols = 'order_number, client_id, order_date, warranty, notes, status' . ($hasNfe ? ', nfe' : '');
            $vals = ':order_number, :client_id, :order_date, :warranty, :notes, :status' . ($hasNfe ? ', :nfe' : '');
            $query = "INSERT INTO " . $this->table_name . " ($cols) VALUES ($vals)";

            $stmt = $this->conn->prepare($query);

            // order_number agora vem do formulário (campo texto)
            $order_number = trim($data['order_number'] ?? '');
            if ($order_number === '') {
                throw new Exception('Número do PP é obrigatório');
            }
            if ($this->orderNumberExists($order_number)) {
                throw new Exception('Já existe um PP com este número');
            }
            $status = 'pending';

            // Sanitize
            $client_id = htmlspecialchars(strip_tags($data['client_id']));
            $order_date = htmlspecialchars(strip_tags($data['order_date']));
            $warranty = htmlspecialchars(strip_tags($data['warranty']));
            $notes = htmlspecialchars(strip_tags($data['notes'] ?? ''));
            $nfe = isset($data['nfe']) ? htmlspecialchars(strip_tags($data['nfe'])) : null;

            // Bind
            $stmt->bindParam(":order_number", $order_number);
            $stmt->bindParam(":client_id", $client_id);
            $stmt->bindParam(":order_date", $order_date);
            $stmt->bindParam(":warranty", $warranty);
            $stmt->bindParam(":notes", $notes);
            $stmt->bindParam(":status", $status);
            if ($hasNfe) { $stmt->bindParam(":nfe", $nfe); }

            if(!$stmt->execute()) {
                throw new Exception("Erro ao criar PP");
            }

            $pp_id = $this->conn->lastInsertId();

            // Vincular produtos existentes ao pedido
            if (!isset($data['products']) || empty($data['products'])) {
                throw new Exception("Nenhum produto selecionado");
            }

            $query = "UPDATE products 
                    SET production_order_id = :pp_id,
                        sale_date = :sale_date,
                        destination = :client_id,
                        warranty = :warranty
                    WHERE id = :product_id
                    AND production_order_id IS NULL";
            
            $stmt = $this->conn->prepare($query);
            
            foreach ($data['products'] as $productId) {
                $stmt->bindParam(":pp_id", $pp_id);
                $stmt->bindParam(":sale_date", $order_date);
                $stmt->bindParam(":client_id", $client_id);
                $stmt->bindParam(":warranty", $warranty);
                $stmt->bindParam(":product_id", $productId);

                if(!$stmt->execute()) {
                    throw new Exception("Erro ao vincular produto #" . $productId);
                }
            }

            $this->conn->commit();
            return ['success' => true, 'order_number' => $order_number, 'pp_id' => $pp_id];

        } catch(Exception $e) {
            $this->conn->rollBack();
            error_log("DEBUG ProductionOrder::create() - Erro: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function delete($id) {
        $this->conn->beginTransaction();

        try {
            // Primeiro, pegar todos os produtos deste pedido
            $query = "SELECT id FROM products WHERE production_order_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$id]);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Remover os QR codes físicos
            foreach ($products as $product) {
                $qrCodePath = __DIR__ . '/../../public/qrcodes/' . $product['id'] . '.png';
                if (file_exists($qrCodePath)) {
                    unlink($qrCodePath);
                }
            }

            // Atualizar os produtos para remover a referência ao pedido
            $query = "UPDATE products 
                     SET production_order_id = NULL, 
                         destination = 'estoque',
                         sale_date = NULL 
                     WHERE production_order_id = ?";
            $stmt = $this->conn->prepare($query);
            if (!$stmt->execute([$id])) {
                throw new Exception("Erro ao atualizar produtos");
            }

            // Deletar o pedido
            $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            if (!$stmt->execute([$id])) {
                throw new Exception("Erro ao deletar pedido");
            }

            $this->conn->commit();
            return ['success' => true];

        } catch(Exception $e) {
            $this->conn->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getById($id) {
        $hasNfe = $this->checkIfColumnExists($this->table_name, 'nfe');
        $query = "SELECT po.*, 
                        c.name as client_name, 
                        c.city as client_city, 
                        c.state as client_state,
                        COUNT(p.id) as total_products
                 FROM " . $this->table_name . " po
                 LEFT JOIN clients c ON po.client_id = c.id
                 LEFT JOIN products p ON p.production_order_id = po.id
                 WHERE po.id = ?
                 GROUP BY po.id";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAll() {
        try {
            $hasNfe = $this->checkIfColumnExists($this->table_name, 'nfe');
            $query = "SELECT po.*, 
                            COALESCE(c.name, 'Cliente Não Encontrado') as client_name, 
                            COALESCE(c.city, '') as client_city, 
                            COALESCE(c.state, '') as client_state,
                            COUNT(p.id) as total_products
                     FROM " . $this->table_name . " po
                     LEFT JOIN clients c ON po.client_id = c.id
                     LEFT JOIN products p ON p.production_order_id = po.id
                     GROUP BY po.id
                     ORDER BY po.id DESC";

            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in ProductionOrder::getAll(): " . $e->getMessage());
            return [];
        }
    }

    public function getProducts($id) {
        $query = "SELECT p.*, t.nome as tipo_name, pb.batch_number
                 FROM products p
                 LEFT JOIN tipos t ON p.tipo_id = t.id
                 LEFT JOIN production_batches pb ON p.production_batch_id = pb.id
                 WHERE p.production_order_id = ?
                 ORDER BY p.serial_number ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStatus($id, $status) {
        $this->conn->beginTransaction();
        try {
            $query = "UPDATE " . $this->table_name . "
                     SET status = :status
                     WHERE id = :id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":status", $status);
            $stmt->bindParam(":id", $id);
            if (!$stmt->execute()) {
                throw new Exception('Falha ao atualizar status do pedido');
            }

            // Se o pedido foi entregue, atualizar status dos produtos para 'Externo' (id=3)
            if ($status === 'delivered') {
                $up = $this->conn->prepare("UPDATE products SET status_id = 3 WHERE production_order_id = :id");
                if (!$up->execute([':id' => $id])) {
                    throw new Exception('Falha ao atualizar status dos produtos para Externo');
                }
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log('updateStatus error: ' . $e->getMessage());
            return false;
        }
    }

    public function update($data) {
        $this->conn->beginTransaction();

        try {
            $id = intval($data['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('ID do pedido inválido');
            }

            // Atualizar cabeçalho do pedido
            $hasNfe = $this->checkIfColumnExists($this->table_name, 'nfe');
            $query = "UPDATE " . $this->table_name . "
                     SET order_number = :order_number,
                         client_id = :client_id,
                         order_date = :order_date,
                         warranty = :warranty,
                         notes = :notes" . ($hasNfe ? ", nfe = :nfe" : "") . "
                     WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $order_number = trim($data['order_number'] ?? '');
            if ($order_number === '') { throw new Exception('Número do PP é obrigatório'); }
            if ($this->orderNumberExists($order_number, $id)) { throw new Exception('Já existe um PP com este número'); }
            $client_id = htmlspecialchars(strip_tags($data['client_id']));
            $order_date = htmlspecialchars(strip_tags($data['order_date']));
            $warranty = htmlspecialchars(strip_tags($data['warranty']));
            $notes = htmlspecialchars(strip_tags($data['notes'] ?? ''));
            $nfe = isset($data['nfe']) ? htmlspecialchars(strip_tags($data['nfe'])) : null;
            $stmt->bindParam(':order_number', $order_number);
            $stmt->bindParam(':client_id', $client_id);
            $stmt->bindParam(':order_date', $order_date);
            $stmt->bindParam(':warranty', $warranty);
            $stmt->bindParam(':notes', $notes);
            if ($hasNfe) { $stmt->bindParam(':nfe', $nfe); }
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            if (!$stmt->execute()) {
                throw new Exception('Erro ao atualizar cabeçalho do pedido');
            }

            // Produtos atualmente no pedido
            $stmt = $this->conn->prepare("SELECT id FROM products WHERE production_order_id = ?");
            $stmt->execute([$id]);
            $current = array_map(fn($r) => intval($r['id']), $stmt->fetchAll(PDO::FETCH_ASSOC));

            // Produtos desejados pelo formulário
            $desired = isset($data['products']) ? array_map('intval', (array)$data['products']) : [];
            $desired = array_values(array_unique($desired));

            // Remover: estavam e agora saíram
            $toRemove = array_diff($current, $desired);
            if (!empty($toRemove)) {
                $placeholders = implode(',', array_fill(0, count($toRemove), '?'));
                $sql = "UPDATE products SET production_order_id = NULL, destination = 'estoque', sale_date = NULL WHERE id IN ($placeholders)";
                $stmt = $this->conn->prepare($sql);
                if (!$stmt->execute(array_values($toRemove))) {
                    throw new Exception('Erro ao remover produtos do pedido');
                }
            }

            // Adicionar: não estavam e agora entraram
            $toAdd = array_diff($desired, $current);
            if (!empty($toAdd)) {
                $placeholders = implode(',', array_fill(0, count($toAdd), '?'));
                // Construir query com CASE para atualizar múltiplos
                // Mais simples: loop por segurança
                $sql = "UPDATE products SET production_order_id = :pp_id, sale_date = :sale_date, destination = :client_id, warranty = :warranty WHERE id = :pid AND (production_order_id IS NULL OR production_order_id = :pp_id_null)";
                $stmt = $this->conn->prepare($sql);
                foreach ($toAdd as $pid) {
                    $stmt->bindValue(':pp_id', $id, PDO::PARAM_INT);
                    $stmt->bindValue(':sale_date', $order_date);
                    $stmt->bindValue(':client_id', $client_id);
                    $stmt->bindValue(':warranty', $warranty);
                    $stmt->bindValue(':pid', $pid, PDO::PARAM_INT);
                    $stmt->bindValue(':pp_id_null', $id, PDO::PARAM_INT);
                    if (!$stmt->execute()) {
                        throw new Exception('Erro ao adicionar produto #' . $pid . ' ao pedido');
                    }
                }
            }

            // Manter: atualizar dados se cabeçalho mudou
            $toKeep = array_intersect($current, $desired);
            if (!empty($toKeep)) {
                $placeholders = implode(',', array_fill(0, count($toKeep), '?'));
                $params = array_merge([$order_date, $client_id, $warranty], array_values($toKeep));
                $sql = "UPDATE products SET sale_date = ?, destination = ?, warranty = ? WHERE id IN ($placeholders)";
                $stmt = $this->conn->prepare($sql);
                if (!$stmt->execute($params)) {
                    throw new Exception('Erro ao atualizar produtos do pedido');
                }
            }

            $this->conn->commit();
            return ['success' => true];
        } catch (Exception $e) {
            $this->conn->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function generatePDF($id) {
        $pp = $this->getById($id);
        $products = $this->getProducts($id);

        // Aqui implementaremos a geração do PDF
        // Retornaremos o caminho do arquivo gerado
        return ['success' => true, 'path' => 'path/to/pdf'];
    }
}