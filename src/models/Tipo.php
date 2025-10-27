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
        $stmt = $this->conn->prepare('INSERT INTO tipos (nome) VALUES (:nome)');
        return $stmt->execute([
            'nome' => $data['nome']
        ]);
    }

    public function update($data)
    {
        $stmt = $this->conn->prepare('UPDATE tipos SET nome = :nome WHERE id = :id');
        return $stmt->execute([
            'id' => $data['id'],
            'nome' => $data['nome']
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
