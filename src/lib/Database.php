<?php
    require_once __DIR__ . '/Env.php';

    class Database
    {
        private PDO $pdo;

        public function __construct()
        {
            $host     = Env::load('DB_HOST');
            $name     = Env::load('DB_NAME');
            $username = Env::load('DB_USER');
            $password = Env::load('DB_PASSWORD');
            $charset  = 'utf8mb4';

            // The four Console::log() calls that used to sit here printed
            // <script> tags — including the database password — into every single
            // page and into the body of every JSON API response. That is what made
            // the API replies unparseable, and it leaked the credentials to anyone
            // who opened View Source.

            $dsn = "mysql:host=$host;dbname=$name;charset=$charset";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false
            ];

            try {
                $this->pdo = new PDO($dsn, $username, $password, $options);
            } catch (PDOException $e) {
                // die() mid-response produced half-JSON. Throw instead so the
                // caller can decide how to report it.
                throw new RuntimeException('Database connection failed: ' . $e->getMessage(), 0, $e);
            }
        }

        public function query(string $sql, array $params = []): PDOStatement
        {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            return $stmt;
        }

        /** Id of the row just inserted, on this connection. */
        public function lastInsertId(): int
        {
            return (int)$this->pdo->lastInsertId();
        }

        public function insert(string $table, array $data): PDOStatement
        {
            $columns      = implode(', ', array_keys($data));
            $placeholders = implode(', ', array_fill(0, count($data), '?'));

            $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";

            return $this->query($sql, array_values($data));
        }

        public function select(string $table, array $conditions = [], string $columns = '*'): array
        {
            $sql    = "SELECT {$columns} FROM {$table}";
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
            $values     = [];

            foreach ($data as $column => $value) {
                $setClauses[] = "{$column} = ?";
                $values[]     = $value;
            }

            $whereClauses = [];
            foreach ($conditions as $column => $value) {
                $whereClauses[] = "{$column} = ?";
                $values[]       = $value;
            }

            $sql = "UPDATE {$table} SET " . implode(', ', $setClauses)
                 . " WHERE " . implode(' AND ', $whereClauses);

            return $this->query($sql, $values);
        }

        public function delete(string $table, array $conditions): PDOStatement
        {
            $whereClauses = [];
            $values       = [];

            foreach ($conditions as $column => $value) {
                $whereClauses[] = "{$column} = ?";
                $values[]       = $value;
            }

            $sql = "DELETE FROM {$table} WHERE " . implode(' AND ', $whereClauses);

            return $this->query($sql, $values);
        }
    }
?>