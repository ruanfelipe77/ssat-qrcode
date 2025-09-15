<?php

class Client {
    private $conn;
    private $table_name = "clients";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($data) {
        $query = "INSERT INTO " . $this->table_name . " 
                  (name, city, state) 
                  VALUES 
                  (:name, :city, :state)";

        $stmt = $this->conn->prepare($query);

        // Sanitize
        $name = htmlspecialchars(strip_tags($data['name']));
        $city = htmlspecialchars(strip_tags($data['city']));
        $state = htmlspecialchars(strip_tags($data['state']));

        // Bind
        $stmt->bindParam(":name", $name);
        $stmt->bindParam(":city", $city);
        $stmt->bindParam(":state", $state);

        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function update($data) {
        $query = "UPDATE " . $this->table_name . " 
                  SET name = :name, 
                      city = :city, 
                      state = :state 
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        // Sanitize
        $name = htmlspecialchars(strip_tags($data['name']));
        $city = htmlspecialchars(strip_tags($data['city']));
        $state = htmlspecialchars(strip_tags($data['state']));
        $id = htmlspecialchars(strip_tags($data['id']));

        // Bind
        $stmt->bindParam(":name", $name);
        $stmt->bindParam(":city", $city);
        $stmt->bindParam(":state", $state);
        $stmt->bindParam(":id", $id);

        return $stmt->execute();
    }

    public function delete($id) {
        // Primeiro verifica se há pedidos vinculados
        $query = "SELECT COUNT(*) as count FROM production_orders WHERE client_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            return false; // Não pode deletar cliente com pedidos
        }

        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$id]);
    }

    public function getById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAll() {
        try {
            $query = "SELECT c.*, 
                            0 as product_count
                     FROM " . $this->table_name . " c
                     ORDER BY c.name ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in Client::getAll(): " . $e->getMessage());
            return [];
        }
    }

    public function search($term) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE name LIKE :term 
                  OR city LIKE :term 
                  OR state LIKE :term 
                  ORDER BY name ASC";
        
        $term = "%{$term}%";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":term", $term);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
