<?php

class Database {

    private $host     = "ep-patient-mud-aco73537.sa-east-1.aws.neon.tech";
    private $db_name  = "nzolanet";
    private $username = "neondb_owner";
    private $password = "npg_wVjypcOeK94o";
    private $port     = "5432";

    public $conn;

    public function connect() {

        $this->conn = null;

        try {

            $dsn =
                "pgsql:" .
                "host={$this->host} " .
                "port={$this->port} " .
                "dbname={$this->db_name} " .
                "sslmode=require " .
                "options=endpoint=ep-patient-mud-aco73537";

            $this->conn = new PDO(
                $dsn,
                $this->username,
                $this->password
            );

            $this->conn->setAttribute(
                PDO::ATTR_ERRMODE,
                PDO::ERRMODE_EXCEPTION
            );


        } catch (PDOException $e) {
            echo "Erro: " . $e->getMessage();
        }

        return $this->conn;
    }
}