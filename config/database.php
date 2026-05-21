<?php

class Database {

    public $conn;

    public function connect(): PDO
    {
        $this->conn = null;

        try {
            // Usar connection string completa do Neon
            $dsn = getenv('DATABASE_URL') ?:
                "pgsql:host=ep-falling-sun-altskvc5-pooler.c-3.eu-central-1.aws.neon.tech;port=5432;dbname=nzolanet;sslmode=require;options=endpoint=ep-falling-sun-altskvc5-pooler";

            // Se DATABASE_URL existe, converter para PDO DSN
            if (getenv('DATABASE_URL')) {
                $url    = parse_url(getenv('DATABASE_URL'));
                $host   = $url['host'];
                $port   = $url['port'] ?? 5432;
                $dbname = ltrim($url['path'], '/');
                $user   = $url['user'];
                $pass   = $url['pass'];

                $this->conn = new PDO(
                    "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require",
                    $user,
                    $pass,
                    [
                        PDO::ATTR_PERSISTENT         => true,
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_TIMEOUT            => 10
                    ]
                );
            } else {
                $this->conn = new PDO(
                    "pgsql:host=" . getenv('DB_HOST') .
                    ";port=" . (getenv('DB_PORT') ?: '5432') .
                    ";dbname=" . getenv('DB_NAME') .
                    ";sslmode=require",
                    getenv('DB_USER'),
                    getenv('DB_PASS'),
                    [
                        PDO::ATTR_PERSISTENT         => true,
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_TIMEOUT            => 10
                    ]
                );
            }

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