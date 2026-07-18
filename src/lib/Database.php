<?php
    require_once __DIR__ . '/Env.php';
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

        public function insert(string $table, array $data): PDOStatement
        {
            $columns = implode(', ', array_keys($data));
            $placeholders = implode(', ', array_fill(0, count($data), '?'));
            
            $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
            
            return $this->query($sql, array_values($data));
        }

        public function select(string $table, array $conditions = [], string $columns = '*'): array
        {
            $sql = "SELECT {$columns} FROM {$table}";
            $values = [];

            if (!empty($conditions)) {
                $clause = [];
                foreach ($conditions as $column => $value) {
                    $clause[] = "{$column} = ?";
                    $values[] = $value;
                }
                $sql .= " WHERE " . implode(' AND ', $clause);
            }

            return $this->query($sql, $values)->fetchAll();
        }

        public function update(string $table, array $data, array $conditions): PDOStatement
        {
            $setClauses = [];
            $values = [];

            foreach ($data as $column => $value) {
                $setClauses[] = "{$column} = ?";
                $values[] = $value;
            }

            $whereClauses = [];
            foreach ($conditions as $column => $value) {
                $whereClauses[] = "{$column} = ?";
                $values[] = $value;
            }

            $setString = implode(', ', $setClauses);
            $whereString = implode(' AND ', $whereClauses);

            $sql = "UPDATE {$table} SET {$setString} WHERE {$whereString}";

            return $this->query($sql, $values);
        }

        public function delete(string $table, array $conditions): PDOStatement
        {
            $whereClauses = [];
            $values = [];

            foreach ($conditions as $column => $value) {
                $whereClauses[] = "{$column} = ?";
                $values[] = $value;
            }

            $whereString = implode(' AND ', $whereClauses);
            $sql = "DELETE FROM {$table} WHERE {$whereString}";

            return $this->query($sql, $values);
        }
    }
?>