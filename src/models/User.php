<?php

class User {
    private $conn;
    private $table = 'users';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll() {
        $sql = "SELECT id, name, email, active, created_at, updated_at FROM {$this->table} WHERE active = 1 ORDER BY id DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT id, name, email, active, created_at, updated_at FROM {$this->table} WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function emailExists($email, $excludeId = null) {
        $sql = "SELECT id FROM {$this->table} WHERE email = :email" . ($excludeId ? " AND id <> :id" : "");
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':email', $email);
        if ($excludeId) { $stmt->bindValue(':id', (int)$excludeId, PDO::PARAM_INT); }
        $stmt->execute();
        return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $name = trim($data['name'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $active = isset($data['active']) ? (int)$data['active'] : 1;
        if ($name === '' || $email === '' || $password === '') {
            return ['success' => false, 'message' => 'Nome, e-mail e senha são obrigatórios'];
        }
        if ($this->emailExists($email)) {
            return ['success' => false, 'message' => 'E-mail já cadastrado'];
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->conn->prepare("INSERT INTO {$this->table} (name, email, password_hash, active) VALUES (:name, :email, :hash, :active)");
        $ok = $stmt->execute([':name' => $name, ':email' => $email, ':hash' => $hash, ':active' => $active]);
        if (!$ok) return ['success' => false, 'message' => 'Falha ao criar usuário'];
        return ['success' => true, 'id' => (int)$this->conn->lastInsertId()];
    }

    public function update($data) {
        $id = (int)($data['id'] ?? 0);
        $name = trim($data['name'] ?? '');
        $email = trim($data['email'] ?? '');
        $active = isset($data['active']) ? (int)$data['active'] : 1;
        $password = $data['password'] ?? '';
        if ($id <= 0 || $name === '' || $email === '') {
            return ['success' => false, 'message' => 'Dados inválidos'];
        }
        if ($this->emailExists($email, $id)) {
            return ['success' => false, 'message' => 'E-mail já cadastrado'];
        }
        if ($password !== '') {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE {$this->table} SET name = :name, email = :email, active = :active, password_hash = :hash WHERE id = :id";
            $params = [':name'=>$name, ':email'=>$email, ':active'=>$active, ':hash'=>$hash, ':id'=>$id];
        } else {
            $sql = "UPDATE {$this->table} SET name = :name, email = :email, active = :active WHERE id = :id";
            $params = [':name'=>$name, ':email'=>$email, ':active'=>$active, ':id'=>$id];
        }
        $stmt = $this->conn->prepare($sql);
        $ok = $stmt->execute($params);
        return $ok ? ['success' => true] : ['success' => false, 'message' => 'Falha ao atualizar'];
    }

    public function delete($id) {
        // Soft delete: apenas desativa o usuário
        $stmt = $this->conn->prepare("UPDATE {$this->table} SET active = 0 WHERE id = :id");
        return $stmt->execute([':id' => (int)$id]);
    }

    public function toggleActive($id, $active) {
        $stmt = $this->conn->prepare("UPDATE {$this->table} SET active = :active WHERE id = :id");
        return $stmt->execute([':active' => (int)$active, ':id' => (int)$id]);
    }
}
