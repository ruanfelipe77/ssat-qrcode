<?php

class Product
{
    private $conn;

    public function __construct($db)
    {
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

    public function getAll()
    {
        try {
            // Verifica se as colunas existem
            $hasBatchColumn = $this->checkIfColumnExists('products', 'production_batch_id');
            $hasOrderColumn = $this->checkIfColumnExists('products', 'production_order_id');
            $hasStatusColumn = $this->checkIfColumnExists('products', 'status_id');
            
            
            if ($hasBatchColumn && $hasOrderColumn && $hasStatusColumn) {
                // Versão completa com lotes, pedidos e status
                $sql = "SELECT p.id, 
                               p.serial_number, 
                               p.sale_date, 
                               p.destination, 
                               p.warranty, 
                               p.status,
                               COALESCE(t.nome, 'Sem Tipo') AS tipo_name,
                               COALESCE(pb.batch_number, 'Sem Lote') as batch_number,
                               COALESCE(so.order_number, '') AS pp_number,
                               COALESCE(ps.name, 'em_estoque') as status_name,
                               COALESCE(ps.color, '#198754') as status_color,
                               CASE 
                                   WHEN p.destination = 'estoque' THEN 'Em Estoque'
                                   WHEN c.name IS NOT NULL THEN c.name
                                   WHEN p.destination REGEXP '^[0-9]+$' THEN CONCAT('Cliente ID: ', p.destination)
                                   ELSE p.destination
                               END as client_name,
                               COALESCE(c.city, '') as client_city,
                               COALESCE(c.state, '') as client_state
                        FROM products p 
                        LEFT JOIN tipos t ON p.tipo_id = t.id
                        LEFT JOIN production_batches pb ON p.production_batch_id = pb.id
                        LEFT JOIN sales_orders so ON p.production_order_id = so.id
                        LEFT JOIN product_status ps ON p.status_id = ps.id
                        LEFT JOIN clients c ON (p.destination REGEXP '^[0-9]+$' AND p.destination = c.id)
                        ORDER BY p.id DESC";
            } else {
                // Versão básica que funciona com estrutura original
                $sql = "SELECT p.id, 
                               p.serial_number, 
                               p.sale_date, 
                               p.destination, 
                               p.warranty, 
                               COALESCE(t.nome, 'Sem Tipo') AS tipo_name,
                               'Sem Lote' as batch_number,
                               '' AS pp_number,
                               'em_estoque' as status_name,
                               '#198754' as status_color,
                               CASE 
                                   WHEN p.destination = 'estoque' THEN 'Em Estoque'
                                   WHEN c.name IS NOT NULL THEN c.name
                                   WHEN p.destination REGEXP '^[0-9]+$' THEN CONCAT('Cliente ID: ', p.destination)
                                   ELSE p.destination
                               END as client_name,
                               COALESCE(c.city, '') as client_city,
                               COALESCE(c.state, '') as client_state
                        FROM products p 
                        LEFT JOIN tipos t ON p.tipo_id = t.id
                        LEFT JOIN clients c ON (p.destination REGEXP '^[0-9]+$' AND p.destination = c.id)
                        ORDER BY p.id DESC";
            }
            
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error in Product::getAll(): " . $e->getMessage());
            return [];
        }
    }

    public function getAvailableProducts()
    {
        try {
            $hasBatchColumn = $this->checkIfColumnExists('products', 'production_batch_id');
            $hasOrderColumn = $this->checkIfColumnExists('products', 'production_order_id');
            
            if ($hasBatchColumn && $hasOrderColumn) {
                $sql = "SELECT p.id, 
                               p.serial_number, 
                               p.warranty, 
                               COALESCE(t.nome, 'Sem Tipo') AS tipo_name,
                               COALESCE(pb.batch_number, 'Sem Lote') as batch_number
                        FROM products p 
                        LEFT JOIN tipos t ON p.tipo_id = t.id
                        LEFT JOIN production_batches pb ON p.production_batch_id = pb.id
                        WHERE p.production_order_id IS NULL
                        ORDER BY p.serial_number ASC";
            } else {
                $sql = "SELECT p.id, 
                               p.serial_number, 
                               p.warranty, 
                               COALESCE(t.nome, 'Sem Tipo') AS tipo_name,
                               'Sem Lote' as batch_number
                        FROM products p 
                        LEFT JOIN tipos t ON p.tipo_id = t.id
                        WHERE p.destination = 'estoque'
                        ORDER BY p.serial_number ASC";
            }
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in Product::getAvailableProducts(): " . $e->getMessage());
            return [];
        }
    }

    public function create($data)
    {
        // Verificar se status_id existe na tabela
        $hasStatusColumn = $this->checkIfColumnExists('products', 'status_id');
        
        if ($hasStatusColumn) {
            $stmt = $this->conn->prepare('INSERT INTO products (serial_number, sale_date, destination, warranty, tipo_id, status_id) VALUES (:serial_number, :sale_date, :destination, :warranty, :tipo_id, :status_id)');
            return $stmt->execute([
                'serial_number' => $data['serial_number'],
                'sale_date' => $data['sale_date'],
                'destination' => $data['destination'],
                'warranty' => $data['warranty'],
                'tipo_id' => $data['tipo_id'],
                'status_id' => $data['status_id'] ?? 1 // Default para "em_estoque"
            ]);
        } else {
            $stmt = $this->conn->prepare('INSERT INTO products (serial_number, sale_date, destination, warranty, tipo_id) VALUES (:serial_number, :sale_date, :destination, :warranty, :tipo_id)');
            return $stmt->execute([
                'serial_number' => $data['serial_number'],
                'sale_date' => $data['sale_date'],
                'destination' => $data['destination'],
                'warranty' => $data['warranty'],
                'tipo_id' => $data['tipo_id']
            ]);
        }
    }

    public function update($data)
    {
        // Verificar se status_id existe na tabela
        $hasStatusColumn = $this->checkIfColumnExists('products', 'status_id');
        
        if ($hasStatusColumn) {
            $stmt = $this->conn->prepare('UPDATE products SET serial_number = :serial_number, sale_date = :sale_date, destination = :destination, warranty = :warranty, tipo_id = :tipo_id, status_id = :status_id WHERE id = :id');
            return $stmt->execute([
                'id' => $data['id'],
                'serial_number' => $data['serial_number'],
                'sale_date' => $data['sale_date'],
                'destination' => $data['destination'],
                'warranty' => $data['warranty'],
                'tipo_id' => $data['tipo_id'],
                'status_id' => $data['status_id'] ?? 1 // Default para "em_estoque"
            ]);
        } else {
            $stmt = $this->conn->prepare('UPDATE products SET serial_number = :serial_number, sale_date = :sale_date, destination = :destination, warranty = :warranty, tipo_id = :tipo_id WHERE id = :id');
            return $stmt->execute([
                'id' => $data['id'],
                'serial_number' => $data['serial_number'],
                'sale_date' => $data['sale_date'],
                'destination' => $data['destination'],
                'warranty' => $data['warranty'],
                'tipo_id' => $data['tipo_id']
            ]);
        }
    }

    public function delete($id)
    {
        $stmt = $this->conn->prepare('DELETE FROM products WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    public function getLastInsertId()
    {
        return $this->conn->lastInsertId();
    }

    public function getById($id)
    {
        $stmt = $this->conn->prepare('SELECT * FROM products WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getByBatchId($batchId)
    {
        $sql = "SELECT p.*, 
                       t.nome AS tipo_name,
                       po.order_number AS pp_number
                FROM products p 
                JOIN tipos t ON p.tipo_id = t.id
                LEFT JOIN production_orders po ON p.production_order_id = po.id
                WHERE p.production_batch_id = ?
                ORDER BY p.serial_number ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$batchId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByOrderId($orderId)
    {
        $sql = "SELECT p.*, 
                       t.nome AS tipo_name,
                       pb.batch_number
                FROM products p 
                JOIN tipos t ON p.tipo_id = t.id
                LEFT JOIN production_batches pb ON p.production_batch_id = pb.id
                WHERE p.production_order_id = ?
                ORDER BY p.serial_number ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$orderId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByClientId($clientId) {
        try {
            $sql = "SELECT p.id, 
                           p.serial_number, 
                           p.warranty, 
                           COALESCE(t.nome, 'Sem Tipo') AS tipo_name
                    FROM products p 
                    LEFT JOIN tipos t ON p.tipo_id = t.id
                    WHERE p.destination = ?
                    ORDER BY p.serial_number ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$clientId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in Product::getByClientId(): " . $e->getMessage());
            return [];
        }
    }
}