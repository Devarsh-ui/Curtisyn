<?php

define('BASE_URL', '/Curtisyn/');

define('DB_HOST', 'localhost');
define('DB_NAME', 'curtains_ecommerce');
define('DB_USER', 'root');
define('DB_PASS', '');

class Database {
    private $connection;
    
    public function connect() {
        $this->connection = null;
        
        try {
            $this->connection = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME,
                DB_USER,
                DB_PASS
            );
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Connection Error: ' . $e->getMessage());
        }
        
        return $this->connection;
    }
}
