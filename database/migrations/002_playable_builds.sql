-- ==========================================================================
-- 002 - Browser-playable (HTML5) builds
--
-- A developer can mark one uploaded .zip as playable in the browser, the way
-- itch.io does. The archive is unpacked once at save time and the entry file
-- it found is recorded here, so the game page can embed it directly instead of
-- unpacking on every request.
--
--   is_playable  the developer ticked "playable in browser"
--   play_path    relative path of the entry document, e.g.
--                public/uploads/games/18/play_12/index.html
--
-- Safe to run more than once.
-- ==========================================================================

ALTER TABLE game_build ADD COLUMN is_playable BOOLEAN NOT NULL DEFAULT 0;
ALTER TABLE game_build ADD COLUMN play_path VARCHAR(255) NULL;
