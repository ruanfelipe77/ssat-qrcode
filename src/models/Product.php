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
            $hasNotesColumn  = $this->checkIfColumnExists('products', 'notes');
            $hasParentCompositeColumn = $this->checkIfColumnExists('products', 'parent_composite_id');
            
            
            if ($hasBatchColumn && $hasOrderColumn && $hasStatusColumn) {
                // Versão completa com lotes, pedidos e status
                $sql = "SELECT p.id, 
                               p.serial_number, 
                               p.sale_date, 
                               p.destination, 
                               p.warranty, 
                               " . ($hasNotesColumn ? "p.notes, " : "NULL as notes, ") . "
                               p.status_id,
                               COALESCE(p.status, 'in_stock') as status,
                               " . ($hasParentCompositeColumn ? "p.parent_composite_id, " : "NULL as parent_composite_id, ") . "
                               COALESCE(t.nome, 'Sem Tipo') AS tipo_name,
                               CASE 
                                   WHEN EXISTS(SELECT 1 FROM assemblies a WHERE a.composite_product_id = p.id AND a.status = 'finalized') THEN 1
                                   ELSE 0
                               END as is_composite,
                               COALESCE(pb.batch_number, 'Sem Lote') as batch_number,
                               COALESCE(so.order_number, '') AS pp_number,
                               CASE 
                                   WHEN p.status = 'in_composite' OR ps.name = 'em_composicao' THEN 'in_composite'
                                   ELSE COALESCE(ps.name, 'em_estoque')
                               END as status_name,
                               CASE 
                                   WHEN p.status = 'in_composite' OR ps.name = 'em_composicao' THEN '#c65cff'
                                   ELSE COALESCE(ps.color, '#198754')
                               END as status_color,
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
                               " . ($hasNotesColumn ? "p.notes, " : "NULL as notes, ") . "
                               NULL as status_id,
                               COALESCE(p.status, 'in_stock') as status,
                               " . ($hasParentCompositeColumn ? "p.parent_composite_id, " : "NULL as parent_composite_id, ") . "
                               COALESCE(t.nome, 'Sem Tipo') AS tipo_name,
                               CASE 
                                   WHEN EXISTS(SELECT 1 FROM assemblies a WHERE a.composite_product_id = p.id AND a.status = 'finalized') THEN 1
                                   ELSE 0
                               END as is_composite,
                               'Sem Lote' as batch_number,
                               '' AS pp_number,
                               CASE 
                                   WHEN p.status = 'in_composite' THEN 'in_composite'
                                   ELSE 'em_estoque'
                               END as status_name,
                               CASE 
                                   WHEN p.status = 'in_composite' THEN '#c65cff'
                                   ELSE '#198754'
                               END as status_color,
                               CASE 
                                   WHEN p.status = 'in_composite' THEN 'fas fa-cube'
                                   ELSE 'fas fa-check-circle'
                               END as status_icon,
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
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Debug: log da query e resultado
            error_log("Product::getAll() SQL: " . $sql);
            error_log("Product::getAll() Result count: " . count($result));
            
            return $result;
        } catch (PDOException $e) {
            error_log("Error in Product::getAll(): " . $e->getMessage());
            error_log("SQL that failed: " . $sql);
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
        $hasNotesColumn  = $this->checkIfColumnExists('products', 'notes');
        
        if ($hasStatusColumn) {
            $columns = 'serial_number, sale_date, destination, warranty, tipo_id, status_id' . ($hasNotesColumn ? ', notes' : '');
            $place   = ':serial_number, :sale_date, :destination, :warranty, :tipo_id, :status_id' . ($hasNotesColumn ? ', :notes' : '');
            $stmt = $this->conn->prepare("INSERT INTO products ($columns) VALUES ($place)");
            return $stmt->execute([
                'serial_number' => $data['serial_number'],
                'sale_date' => $data['sale_date'],
                'destination' => $data['destination'],
                'warranty' => $data['warranty'],
                'tipo_id' => $data['tipo_id'],
                'status_id' => $data['status_id'] ?? 1, // Default para "em_estoque"
                'notes' => $hasNotesColumn ? ($data['notes'] ?? null) : null
            ]);
        } else {
            $columns = 'serial_number, sale_date, destination, warranty, tipo_id' . ($hasNotesColumn ? ', notes' : '');
            $place   = ':serial_number, :sale_date, :destination, :warranty, :tipo_id' . ($hasNotesColumn ? ', :notes' : '');
            $stmt = $this->conn->prepare("INSERT INTO products ($columns) VALUES ($place)");
            return $stmt->execute([
                'serial_number' => $data['serial_number'],
                'sale_date' => $data['sale_date'],
                'destination' => $data['destination'],
                'warranty' => $data['warranty'],
                'tipo_id' => $data['tipo_id'],
                'notes' => $hasNotesColumn ? ($data['notes'] ?? null) : null
            ]);
        }
    }

    public function update($data)
    {
        // Verificar se status_id existe na tabela
        $hasStatusColumn = $this->checkIfColumnExists('products', 'status_id');
        $hasNotesColumn  = $this->checkIfColumnExists('products', 'notes');
        
        if ($hasStatusColumn) {
            $stmt = $this->conn->prepare('UPDATE products SET serial_number = :serial_number, sale_date = :sale_date, destination = :destination, warranty = :warranty, tipo_id = :tipo_id, status_id = :status_id' . ($hasNotesColumn ? ', notes = :notes' : '') . ' WHERE id = :id');
            return $stmt->execute([
                'id' => $data['id'],
                'serial_number' => $data['serial_number'],
                'sale_date' => $data['sale_date'],
                'destination' => $data['destination'],
                'warranty' => $data['warranty'],
                'tipo_id' => $data['tipo_id'],
                'status_id' => $data['status_id'] ?? 1, // Default para "em_estoque"
                'notes' => $hasNotesColumn ? ($data['notes'] ?? null) : null
            ]);
        } else {
            $stmt = $this->conn->prepare('UPDATE products SET serial_number = :serial_number, sale_date = :sale_date, destination = :destination, warranty = :warranty, tipo_id = :tipo_id' . ($hasNotesColumn ? ', notes = :notes' : '') . ' WHERE id = :id');
            return $stmt->execute([
                'id' => $data['id'],
                'serial_number' => $data['serial_number'],
                'sale_date' => $data['sale_date'],
                'destination' => $data['destination'],
                'warranty' => $data['warranty'],
                'tipo_id' => $data['tipo_id'],
                'notes' => $hasNotesColumn ? ($data['notes'] ?? null) : null
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
        try {
            // Verificar se a coluna production_batch_id existe
            $hasBatchColumn = $this->checkIfColumnExists('products', 'production_batch_id');
            error_log("Product::getByBatchId - Coluna production_batch_id existe: " . ($hasBatchColumn ? 'sim' : 'não'));
            
            if ($hasBatchColumn) {
                $sql = "SELECT p.*, 
                               t.nome AS tipo_name,
                               '' AS pp_number
                        FROM products p 
                        LEFT JOIN tipos t ON p.tipo_id = t.id
                        WHERE p.production_batch_id = ?
                        ORDER BY p.serial_number ASC";
            } else {
                // Fallback: buscar por algum campo relacionado ao lote
                error_log("Product::getByBatchId - Tentando buscar por batch_id alternativo");
                $sql = "SELECT p.*, 
                               t.nome AS tipo_name,
                               '' AS pp_number
                        FROM products p 
                        LEFT JOIN tipos t ON p.tipo_id = t.id
                        WHERE p.batch_id = ? OR p.lote_id = ?
                        ORDER BY p.serial_number ASC";
            }
            
            error_log("Product::getByBatchId - SQL: " . $sql);
            error_log("Product::getByBatchId - Batch ID: " . $batchId);
            
            $stmt = $this->conn->prepare($sql);
            if ($hasBatchColumn) {
                $stmt->execute([$batchId]);
            } else {
                $stmt->execute([$batchId, $batchId]);
            }
            
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("Product::getByBatchId - Produtos encontrados: " . count($result));
            
            return $result;
        } catch (PDOException $e) {
            error_log("Product::getByBatchId - Erro SQL: " . $e->getMessage());
            return [];
        }
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