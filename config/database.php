<?php

class Database {

    private $host;
    private $db_name;
    private $username;
    private $password;
    private $port;
    private $sslmode;
    private $options;

    public $conn = null;

    public function __construct() {
        // Usar variáveis de ambiente do Render (ou fallback para local)
        $this->host     = getenv('DB_HOST')     ?: "ep-falling-sun-altskvc5-pooler.c-3.eu-central-1.aws.neon.tech";
        $this->db_name  = getenv('DB_NAME')    ?: "nzolanet";
        $this->username = getenv('DB_USER')    ?: "neondb_owner";
        $this->password = getenv('DB_PASS')    ?: "npg_MuN8ZUpH2ztW";
        $this->port     = getenv('DB_PORT')    ?: "5432";
        $this->sslmode  = getenv('DB_SSLMODE')  ?: "require";
        $this->options  = getenv('DB_OPTIONS') ?: "endpoint=ep-falling-sun-altskvc5";
    }

    public function connect() {

        if ($this->conn instanceof PDO) {
            return $this->conn;
        }

        try {

            $dsn =
                "pgsql:host={$this->host};" .
                "port={$this->port};" .
                "dbname={$this->db_name};" .
                "sslmode={$this->sslmode};" .
                "options={$this->options}";

            $this->conn = new PDO(
                $dsn,
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );

            return $this->conn;

        } catch (PDOException $e) {
            die("Erro na conexão: " . $e->getMessage());
        }
    }
}