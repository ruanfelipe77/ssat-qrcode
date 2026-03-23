<?php

class Tipo
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function getAll()
    {
        $stmt = $this->conn->query('SELECT * FROM tipos');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($data)
    {
        $stmt = $this->conn->prepare('INSERT INTO tipos (nome, is_composite) VALUES (:nome, :is_composite)');
        return $stmt->execute([
            'nome' => $data['nome'],
            'is_composite' => isset($data['is_composite']) ? 1 : 0
        ]);
    }

    public function update($data)
    {
        $stmt = $this->conn->prepare('UPDATE tipos SET nome = :nome, is_composite = :is_composite WHERE id = :id');
        return $stmt->execute([
            'id' => $data['id'],
            'nome' => $data['nome'],
            'is_composite' => isset($data['is_composite']) ? 1 : 0
        ]);
    }

    public function delete($id)
    {
        $stmt = $this->conn->prepare('DELETE FROM tipos WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    public function getLastInsertId()
    {
        return $this->conn->lastInsertId();
    }

    public function getById($id)
    {
        $stmt = $this->conn->prepare('SELECT * FROM tipos WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
