<?php
    require __DIR__ . '/Env.php';
    class Database
    {
        private PDO $pdo;

        public function __construct()
        {
            $host = ENV::load('DB_HOST');
            $name = ENV::load('DB_NAME');
            $username = Env::load('DB_USER');
            $password = Env::load('DB_PASSWORD');
            $charset = 'utf8mb4';

            Console::log($host);
            Console::log($name);
            Console::log($username);
            Console::log($password);

            $dsn = "mysql:host=$host;dbname=$name;charset=$charset";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ];

            try {
                $this->pdo = new PDO($dsn, $username, $password, $options);
            } catch (PDOException $e) {
                die("Database connect failed: {$e->getMessage()}");
            }
        }

        public function query(string $sql, array $params = []): PDOStatement
        {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            return $stmt;
        }
    }
?>