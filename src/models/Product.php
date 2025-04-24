<?php

class Product
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function getAll()
    {
        $sql = "SELECT p.id, p.serial_number, p.sale_date, p.destination, p.warranty, t.nome AS tipo_name FROM products p JOIN tipos t ON p.tipo_id = t.id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($data)
    {
        $stmt = $this->conn->prepare('INSERT INTO products (serial_number, sale_date, destination, warranty, tipo_id) VALUES (:serial_number, :sale_date, :destination, :warranty, :tipo_id)');
        return $stmt->execute([
            'serial_number' => $data['serial_number'],
            'sale_date' => $data['sale_date'],
            'destination' => $data['destination'],
            'warranty' => $data['warranty'],
            'tipo_id' => $data['tipo_id']
        ]);
    }

    public function update($data)
    {
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
}
