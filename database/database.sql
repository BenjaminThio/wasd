-- 1. Temporarily turn off the security guard
SET FOREIGN_KEY_CHECKS = 0;

-- 2. Drop all tables
DROP TABLE IF EXISTS cart;
DROP TABLE IF EXISTS wishlist;
DROP TABLE IF EXISTS game_category;
DROP TABLE IF EXISTS game_platform;
DROP TABLE IF EXISTS review;
DROP TABLE IF EXISTS category;
DROP TABLE IF EXISTS platform;
DROP TABLE IF EXISTS user;
DROP TABLE IF EXISTS game;

-- 3. Turn the security guard back on
SET FOREIGN_KEY_CHECKS = 1;

-- 1. TABLES CREATION
CREATE TABLE IF NOT EXISTS game (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    developer VARCHAR(255) NOT NULL,
    release_date DATE NULL,
    price DECIMAL(10,2) NOT NULL,
    discount INT DEFAULT 0,
    image_path VARCHAR(255) NULL
);

CREATE TABLE IF NOT EXISTS platform (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS game_platform (
    game_id INT NOT NULL,
    platform_id INT NOT NULL,
    PRIMARY KEY (game_id, platform_id),
    FOREIGN KEY (game_id) REFERENCES game(id) ON DELETE CASCADE,
    FOREIGN KEY (platform_id) REFERENCES platform(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS category (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS game_category (
    game_id INT NOT NULL,
    category_id INT NOT NULL,
    PRIMARY KEY (game_id, category_id),
    FOREIGN KEY (game_id) REFERENCES game(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES category(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS user (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(191) NOT NULL UNIQUE, 
    password VARCHAR(255) NOT NULL, 
    avatar_path VARCHAR(255) NULL
);

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

CREATE TABLE IF NOT EXISTS cart (
    user_id INT NOT NULL,
    game_id INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, game_id),
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
    FOREIGN KEY (game_id) REFERENCES game(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS wishlist (
    user_id INT NOT NULL,
    game_id INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, game_id),
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
    FOREIGN KEY (game_id) REFERENCES game(id) ON DELETE CASCADE
);

-- 2. DATA POPULATION
INSERT INTO game (title, description, developer, release_date, price, discount, image_path) VALUES
    ('Minecraft', 'Let''s build and dream together.', 'Mojang Studios', '2011-11-18', 0.00, 0, NULL),
    ('Left 4 Dead 2', 'Escape the zombies and survive!', 'Valve', '2009-11-17', 2.00, 0, NULL),
    ('Undertale', 'A sikina accidentally fell into a pit and discovered a magical place.', 'tobyfox', '2015-09-15', 520.00, 10, NULL),
    ('R.E.P.O', 'Acts as a robot, explore and steal abandoned places :D', 'WASD Studios', '2026-05-12', 17.00, 77, '/public/repo.jpg'),
    ('Stardew Valley', 'Inherit your grandfather''s farm plot and build your legacy.', 'ConcernedApe', '2016-02-26', 14.99, 0, NULL),
    ('Cyberpunk 2077', 'An open-world, action-adventure RPG set in the dark future.', 'CD PROJEKT RED', '2020-12-10', 59.99, 50, NULL),
    ('Hollow Knight', 'Forge your own path in this award-winning action adventure.', 'Team Cherry', '2017-02-24', 14.99, 20, NULL),
    ('Portal 2', 'The wildly innovative physics puzzle game returns with co-op.', 'Valve', '2011-04-18', 9.99, 0, NULL),
    ('Doom Eternal', 'Hell’s armies have invaded Earth. Become the Slayer.', 'id Software', '2020-03-20', 39.99, 75, NULL),
    ('Phasmophobia', 'A 4-player online co-op psychological horror game.', 'Kinetic Games', '2020-09-18', 13.99, 0, NULL),
    ('Terraria', 'Dig, fight, explore, build! Nothing is impossible.', 'Re-Logic', '2011-05-16', 9.99, 50, NULL),
    ('Among Us', 'An online and local party game of teamwork and betrayal.', 'Innersloth', '2018-11-16', 4.99, 0, NULL),
    ('The Witcher 3', 'Story-driven open world RPG set in a visually stunning universe.', 'CD PROJEKT RED', '2015-05-18', 39.99, 80, NULL),
    ('Celeste', 'Help Madeline survive her inner demons on her journey.', 'Extremely OK Games', '2018-01-25', 19.99, 0, NULL),
    ('Half-Life 2', 'The classic story of Gordon Freeman.', 'Valve', '2004-11-16', 9.99, 90, NULL);

INSERT INTO platform (name) VALUES
    ('Windows'),
    ('Linux'),
    ('Apple'),
    ('Browser'),
    ('Android');

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

INSERT INTO category (name) VALUES
    ('Action'),
    ('Action-Adventure'),
    ('Adventure'),
    ('RPG'),
    ('Simulation'),
    ('Strategy'),
    ('Sports'),
    ('Racing'),
    ('Puzzle'),
    ('Horror'),
    ('Shooter');

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

INSERT INTO user (username, email, password, avatar_path) VALUES
    ('GamerGuy99', 'gamerguy@example.com', '$2y$10$dummyhash12345678901234567890', NULL),
    ('NoobMaster', 'noob@example.com', '$2y$10$dummyhash12345678901234567890', NULL),
    ('ProSniper', 'sniper@example.com', '$2y$10$dummyhash12345678901234567890', NULL),
    ('CasualPlayer', 'casual@example.com', '$2y$10$dummyhash12345678901234567890', NULL);

INSERT INTO review (game_id, user_id, enjoy, description, created_at) VALUES
    (1, 1, TRUE, 'Idk what to say, so I decided to like this game anyway.', '2026-07-10 14:32:00'),
    (1, 2, FALSE, 'Hmm not fun to play :(', '2026-07-12 09:15:22'),
    (2, 3, TRUE, 'Best zombie shooter ever made.', '2026-07-15 22:45:10'),
    (3, 4, TRUE, 'The music is absolutely incredible.', '2026-07-17 18:05:45');

INSERT INTO cart (user_id, game_id) VALUES 
    (1, 1), 
    (1, 3);

INSERT INTO wishlist (user_id, game_id) VALUES 
    (1, 2);