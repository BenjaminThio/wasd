-- ==========================================================================
-- 001 - Ownership ("library") + indexes for store search
--
-- The game page needs to answer "does this player already own this game?" so
-- it can swap the purchase buttons for a Download button. Nothing in the
-- schema recorded a completed purchase before: checkout simply emptied the
-- cart, so a bought game was indistinguishable from one never seen.
--
-- Safe to run more than once.
-- ==========================================================================

CREATE TABLE IF NOT EXISTS library (
    user_id INT NOT NULL,
    game_id INT NOT NULL,
    price_paid DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    acquired_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, game_id),
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
    FOREIGN KEY (game_id) REFERENCES game(id) ON DELETE CASCADE
);

-- Store listings always filter on visibility and order by id/price/downloads.
CREATE INDEX idx_game_visibility ON game (visibility);
CREATE INDEX idx_game_title ON game (title);
CREATE INDEX idx_game_developer ON game (developer);

-- Every free game is owned by everyone, so only paid titles are seeded here.
INSERT IGNORE INTO library (user_id, game_id, price_paid)
SELECT 1, id, price * (100 - discount) / 100 FROM game WHERE id IN (2);
