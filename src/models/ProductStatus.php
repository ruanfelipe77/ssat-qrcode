<?php

class ProductStatus {
    private $conn;
    private $table_name = "product_status";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($data) {
        try {
            $query = "INSERT INTO " . $this->table_name . " 
                      (name, description, color, icon, is_active) 
                      VALUES 
                      (:name, :description, :color, :icon, :is_active)";

            $stmt = $this->conn->prepare($query);

            // Sanitize
            $name = htmlspecialchars(strip_tags($data['name']));
            $description = htmlspecialchars(strip_tags($data['description'] ?? ''));
            $color = htmlspecialchars(strip_tags($data['color']));
            $icon = htmlspecialchars(strip_tags($data['icon']));
            $is_active = intval($data['is_active']);

            // Bind
            $stmt->bindParam(":name", $name);
            $stmt->bindParam(":description", $description);
            $stmt->bindParam(":color", $color);
            $stmt->bindParam(":icon", $icon);
            $stmt->bindParam(":is_active", $is_active);

            if($stmt->execute()) {
                return ['success' => true, 'message' => 'Status criado com sucesso', 'id' => $this->conn->lastInsertId()];
            }
            return ['success' => false, 'message' => 'Erro ao criar status'];
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                return ['success' => false, 'message' => 'Já existe um status com este nome'];
            }
            return ['success' => false, 'message' => 'Erro ao criar status: ' . $e->getMessage()];
        }
    }

    public function update($data) {
        try {
            $query = "UPDATE " . $this->table_name . " 
                      SET name = :name, 
                          description = :description, 
                          color = :color, 
                          icon = :icon, 
                          is_active = :is_active 
                      WHERE id = :id";

            $stmt = $this->conn->prepare($query);

            // Sanitize
            $id = intval($data['id']);
            $name = htmlspecialchars(strip_tags($data['name']));
            $description = htmlspecialchars(strip_tags($data['description'] ?? ''));
            $color = htmlspecialchars(strip_tags($data['color']));
            $icon = htmlspecialchars(strip_tags($data['icon']));
            $is_active = intval($data['is_active']);

            // Bind
            $stmt->bindParam(":id", $id);
            $stmt->bindParam(":name", $name);
            $stmt->bindParam(":description", $description);
            $stmt->bindParam(":color", $color);
            $stmt->bindParam(":icon", $icon);
            $stmt->bindParam(":is_active", $is_active);

            if($stmt->execute()) {
                return ['success' => true, 'message' => 'Status atualizado com sucesso'];
            }
            return ['success' => false, 'message' => 'Erro ao atualizar status'];
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                return ['success' => false, 'message' => 'Já existe um status com este nome'];
            }
            return ['success' => false, 'message' => 'Erro ao atualizar status: ' . $e->getMessage()];
        }
    }

    public function delete($id) {
        try {
            // Verificar se há produtos usando este status
            $query = "SELECT COUNT(*) as count FROM products WHERE status_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                return ['success' => false, 'message' => 'Não é possível excluir este status pois há produtos utilizando-o'];
            }

            // Verificar se não é um status padrão (IDs 1-6)
            if ($id <= 6) {
                return ['success' => false, 'message' => 'Não é possível excluir status padrão do sistema'];
            }

            $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            
            if($stmt->execute([$id])) {
                return ['success' => true, 'message' => 'Status excluído com sucesso'];
            }
            return ['success' => false, 'message' => 'Erro ao excluir status'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Erro ao excluir status: ' . $e->getMessage()];
        }
    }

    public function getById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAll() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY id ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getActive() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE is_active = 1 ORDER BY name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function search($term) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE name LIKE :term 
                  OR description LIKE :term 
                  ORDER BY name ASC";
        
        $term = "%{$term}%";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":term", $term);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
