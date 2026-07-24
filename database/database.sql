-- 1. Temporarily turn off foreign key security guard
SET FOREIGN_KEY_CHECKS = 0;

-- 2. Drop all existing tables (Clean Slate Execution)
DROP TABLE IF EXISTS cart;
DROP TABLE IF EXISTS wishlist;
DROP TABLE IF EXISTS game_build;
DROP TABLE IF EXISTS game_screenshot;
DROP TABLE IF EXISTS game_category;
DROP TABLE IF EXISTS game_platform;
DROP TABLE IF EXISTS review;
DROP TABLE IF EXISTS category;
DROP TABLE IF EXISTS platform;
DROP TABLE IF EXISTS user;
DROP TABLE IF EXISTS game;

-- 3. Turn security guard back on
SET FOREIGN_KEY_CHECKS = 1;

-- ==========================================
-- 1. TABLES CREATION
-- ==========================================

-- User Table
CREATE TABLE IF NOT EXISTS user (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(191) NOT NULL UNIQUE, 
    password VARCHAR(255) NOT NULL, 
    avatar_path VARCHAR(255) NULL
);

-- Game Table (Upgraded for Developer Dashboard & Project Engine)
CREATE TABLE IF NOT EXISTS game (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    developer VARCHAR(255) DEFAULT "WASD Studios",
    release_date DATE NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    discount INT DEFAULT 0,
    image_path VARCHAR(255) NULL,
    fallback_art VARCHAR(50) DEFAULT "art-1",
    build_path VARCHAR(255) NULL,
    visibility ENUM("Draft", "Restricted", "Public") DEFAULT "Public",
    views INT DEFAULT 0,
    downloads INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
);

-- Game Builds Table (Supports multiple build uploads per project)
CREATE TABLE IF NOT EXISTS game_build (
    id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL,
    display_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_size VARCHAR(50) NOT NULL DEFAULT "10MB",
    downloads INT DEFAULT 0,
    platforms VARCHAR(255) NOT NULL DEFAULT "Windows",
    is_hidden BOOLEAN NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 0,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (game_id) REFERENCES game(id) ON DELETE CASCADE
);

-- Game Screenshots Table
CREATE TABLE IF NOT EXISTS game_screenshot (
    id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    FOREIGN KEY (game_id) REFERENCES game(id) ON DELETE CASCADE
);

-- Platform Table
CREATE TABLE IF NOT EXISTS platform (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
);

-- Game-Platform Junction Table
CREATE TABLE IF NOT EXISTS game_platform (
    game_id INT NOT NULL,
    platform_id INT NOT NULL,
    PRIMARY KEY (game_id, platform_id),
    FOREIGN KEY (game_id) REFERENCES game(id) ON DELETE CASCADE,
    FOREIGN KEY (platform_id) REFERENCES platform(id) ON DELETE CASCADE
);

-- Category Table
CREATE TABLE IF NOT EXISTS category (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
);

-- Game-Category Junction Table
CREATE TABLE IF NOT EXISTS game_category (
    game_id INT NOT NULL,
    category_id INT NOT NULL,
    PRIMARY KEY (game_id, category_id),
    FOREIGN KEY (game_id) REFERENCES game(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES category(id) ON DELETE CASCADE
);

-- Review Table
CREATE TABLE IF NOT EXISTS review (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    game_id INT NOT NULL,
    enjoy BOOLEAN NOT NULL,
    description TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
    FOREIGN KEY (game_id) REFERENCES game(id) ON DELETE CASCADE
);

-- Cart Table
CREATE TABLE IF NOT EXISTS cart (
    user_id INT NOT NULL,
    game_id INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, game_id),
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
    FOREIGN KEY (game_id) REFERENCES game(id) ON DELETE CASCADE
);

-- Wishlist Table
CREATE TABLE IF NOT EXISTS wishlist (
    user_id INT NOT NULL,
    game_id INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, game_id),
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
    FOREIGN KEY (game_id) REFERENCES game(id) ON DELETE CASCADE
);

-- ==========================================
-- 2. DATA POPULATION
-- ==========================================

-- Seed Users First (Required for Foreign Keys in Game table)
INSERT INTO user (id, username, email, password, avatar_path) VALUES
    (1, 'GamerGuy99', 'gamerguy@example.com', '$2y$10$dummyhash12345678901234567890', NULL),
    (2, 'NoobMaster', 'noob@example.com', '$2y$10$dummyhash12345678901234567890', NULL),
    (3, 'ProSniper', 'sniper@example.com', '$2y$10$dummyhash12345678901234567890', NULL),
    (4, 'CasualPlayer', 'casual@example.com', '$2y$10$dummyhash12345678901234567890', NULL);

-- Seed All 15 Games with user_id, fallback_art, and visibility mapping
INSERT INTO game (id, user_id, title, description, developer, release_date, price, discount, image_path, fallback_art, visibility, views, downloads) VALUES
    (1, 1, 'Minecraft', 'Let''s build and dream together.', 'Mojang Studios', '2011-11-18', 0.00, 0, NULL, 'art-1', 'Public', 1250, 450),
    (2, 1, 'Left 4 Dead 2', 'Escape the zombies and survive!', 'Valve', '2009-11-17', 2.00, 0, NULL, 'art-2', 'Public', 890, 310),
    (3, 2, 'Undertale', 'A sikina accidentally fell into a pit and discovered a magical place.', 'tobyfox', '2015-09-15', 520.00, 10, NULL, 'art-3', 'Public', 2100, 800),
    (4, 2, 'R.E.P.O', 'Acts as a robot, explore and steal abandoned places :D', 'WASD Studios', '2026-05-12', 17.00, 77, '/public/repo.jpg', 'art-4', 'Public', 450, 120),
    (5, 3, 'Stardew Valley', 'Inherit your grandfather''s farm plot and build your legacy.', 'ConcernedApe', '2016-02-26', 14.99, 0, NULL, 'art-5', 'Public', 1780, 620),
    (6, 3, 'Cyberpunk 2077', 'An open-world, action-adventure RPG set in the dark future.', 'CD PROJEKT RED', '2020-12-10', 59.99, 50, NULL, 'art-6', 'Public', 3400, 1100),
    (7, 4, 'Hollow Knight', 'Forge your own path in this award-winning action adventure.', 'Team Cherry', '2017-02-24', 14.99, 20, NULL, 'art-7', 'Public', 1560, 540),
    (8, 4, 'Portal 2', 'The wildly innovative physics puzzle game returns with co-op.', 'Valve', '2011-04-18', 9.99, 0, NULL, 'art-8', 'Public', 980, 410),
    (9, 1, 'Doom Eternal', 'Hell’s armies have invaded Earth. Become the Slayer.', 'id Software', '2020-03-20', 39.99, 75, NULL, 'art-1', 'Public', 2300, 890),
    (10, 1, 'Phasmophobia', 'A 4-player online co-op psychological horror game.', 'Kinetic Games', '2020-09-18', 13.99, 0, NULL, 'art-2', 'Draft', 110, 0),
    (11, 2, 'Terraria', 'Dig, fight, explore, build! Nothing is impossible.', 'Re-Logic', '2011-05-16', 9.99, 50, NULL, 'art-3', 'Public', 1890, 720),
    (12, 2, 'Among Us', 'An online and local party game of teamwork and betrayal.', 'Innersloth', '2018-11-16', 4.99, 0, NULL, 'art-4', 'Public', 4100, 1500),
    (13, 3, 'The Witcher 3', 'Story-driven open world RPG set in a visually stunning universe.', 'CD PROJEKT RED', '2015-05-18', 39.99, 80, NULL, 'art-5', 'Public', 2900, 950),
    (14, 3, 'Celeste', 'Help Madeline survive her inner demons on her journey.', 'Extremely OK Games', '2018-01-25', 19.99, 0, NULL, 'art-6', 'Public', 870, 230),
    (15, 4, 'Half-Life 2', 'The classic story of Gordon Freeman.', 'Valve', '2004-11-16', 9.99, 90, NULL, 'art-7', 'Public', 1400, 600);

-- Platforms
INSERT INTO platform (id, name) VALUES
    (1, 'Windows'),
    (2, 'Linux'),
    (3, 'Apple'),
    (4, 'Browser'),
    (5, 'Android');

-- Game-Platform Relations
INSERT INTO game_platform (game_id, platform_id) VALUES
    (1, 1), (1, 2), (1, 3), 
    (2, 1), (2, 2), (2, 3),
    (3, 1), (3, 2), (3, 3),
    (4, 1), 
    (5, 1), (5, 2), (5, 3),
    (6, 1),
    (7, 1), (7, 2), (7, 3),
    (8, 1), (8, 2), (8, 3),
    (9, 1),
    (10, 1),
    (11, 1), (11, 2), (11, 3), (11, 5),
    (12, 1), (12, 5),
    (13, 1),
    (14, 1), (14, 2), (14, 3),
    (15, 1), (15, 2), (15, 3);

-- Categories
INSERT INTO category (id, name) VALUES
    (1, 'Action'),
    (2, 'Action-Adventure'),
    (3, 'Adventure'),
    (4, 'RPG'),
    (5, 'Simulation'),
    (6, 'Strategy'),
    (7, 'Sports'),
    (8, 'Racing'),
    (9, 'Puzzle'),
    (10, 'Horror'),
    (11, 'Shooter');

-- Game-Category Relations
INSERT INTO game_category (game_id, category_id) VALUES
    (1, 3), (1, 5),
    (2, 1), (2, 11),
    (3, 4),
    (4, 3), (4, 9),
    (5, 4), (5, 5),
    (6, 4), (6, 11),
    (7, 2),
    (8, 9),
    (9, 1), (9, 11),
    (10, 10),
    (11, 3), (11, 5),
    (12, 6),
    (13, 4), (13, 2),
    (14, 1), (14, 3),
    (15, 1), (15, 11);

-- Reviews
INSERT INTO review (game_id, user_id, enjoy, description, created_at) VALUES
    (1, 1, TRUE, 'Idk what to say, so I decided to like this game anyway.', '2026-07-10 14:32:00'),
    (1, 2, FALSE, 'Hmm not fun to play :(', '2026-07-12 09:15:22'),
    (2, 3, TRUE, 'Best zombie shooter ever made.', '2026-07-15 22:45:10'),
    (3, 4, TRUE, 'The music is absolutely incredible.', '2026-07-17 18:05:45');

-- Cart
INSERT INTO cart (user_id, game_id) VALUES 
    (1, 1), 
    (1, 3);

-- Wishlist
INSERT INTO wishlist (user_id, game_id) VALUES 
    (1, 2);