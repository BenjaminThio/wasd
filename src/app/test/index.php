<div>
    <?php
        require __DIR__ . '/../../lib/Database.php';

        $database = new Database();

        $database->query('
            CREATE TABLE IF NOT EXISTS game (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                description TEXT NOT NULL,
                price DECIMAL(10,2) NOT NULL,
                discount INT,
                image_path VARCHAR(255)
            );
        ');

        $database->query('
            CREATE TABLE IF NOT EXISTS platform (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(50) NOT NULL UNIQUE
            );
        ');

        $database->query('
            CREATE TABLE IF NOT EXISTS game_platform (
                game_id INT NOT NULL,
                platform_id INT NOT NULL,

                PRIMARY KEY (game_id, platform_id),

                FOREIGN KEY (game_id) REFERENCES game(id) ON DELETE CASCADE,
                FOREIGN KEY (platform_id) REFERENCES platform(id) ON DELETE CASCADE
            );
        ');

        $database->query('
            CREATE TABLE IF NOT EXISTS category (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(50) NOT NULL UNIQUE
            );
        ');

        $database->query('
            CREATE TABLE IF NOT EXISTS game_category (
                game_id INT NOT NULL,
                category_id INT NOT NULL,

                PRIMARY KEY (game_id, category_id),

                FOREIGN KEY (game_id) REFERENCES game(id) ON DELETE CASCADE,
                FOREIGN KEY (category_id) REFERENCES category(id) ON DELETE CASCADE
            ); 
        ');
    ?>
</div>