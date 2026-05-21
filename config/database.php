<?php

class Database {

    private string $host;
    private string $db_name;
    private string $username;
    private string $password;
    private string $port;
    private string $endpoint;

    public $conn;

    public function __construct()
    {
        $this->host     = getenv('DB_HOST') ?: "ep-falling-sun-altskvc5-pooler.c-3.eu-central-1.aws.neon.tech";
        $this->db_name  = getenv('DB_NAME') ?: "nzolanet";
        $this->username = getenv('DB_USER') ?: "neondb_owner";
        $this->password = getenv('DB_PASS') ?: "npg_MuN8ZUpH2ztW";
        $this->port     = getenv('DB_PORT') ?: "5432";
        $this->endpoint = getenv('DB_OPTIONS') ?: "endpoint=ep-falling-sun-altskvc5";
    }

    public function connect(): PDO
    {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "pgsql:host=" . $this->host .
                ";port=" . $this->port .
                ";dbname=" . $this->db_name .
                ";sslmode=require" .
                ";options='" . $this->endpoint . "'",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_PERSISTENT         => true,
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_TIMEOUT            => 5
                ]
            );

        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Erro de conexão: " . $e->getMessage()
            ]);
            exit();
        }

        return $this->conn;
    }
}