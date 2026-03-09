<?php

class Database {
    private string $host = '127.0.0.1';
    private string $dbname = 'transport';
    private string $username = 'root';
    private string $password = ''; 
    private string $charset = 'utf8mb4';
    
    private ?PDO $pdo = null;

    /**
     * Get a secure PDO database connection
     * @return PDO|null
     * @throws Exception
     */
    public function getConnection(): ?PDO {
        if ($this->pdo === null) {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
            
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, 
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       
                PDO::ATTR_EMULATE_PREPARES   => false,                  
                PDO::ATTR_PERSISTENT         => false                   
            ];

            try {
                $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
            } catch (PDOException $e) {
                error_log("Database connection failed: " . $e->getMessage());
                throw new Exception("Database connection failed. Please try again later.");
            }
        }

        return $this->pdo;
    }
}
