<?php

class Database {
    private static $instance = null;
    private $conn;
    
    private function __construct() {
        $dsn = "mysql:host=localhost;dbname=qrcode_ssat";
        $this->conn = new PDO($dsn, 'root', '');
        $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->conn;
    }
}
?>
