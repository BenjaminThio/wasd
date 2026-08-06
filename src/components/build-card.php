<?php
    require_once __DIR__ . '/../models/Icon.php';
    require_once __DIR__ . '/../lib/Media.php';

    if (!function_exists('render_build_card')) {
        function render_build_card(array $build = []): string
        {
            $allPlatforms = ['Windows', 'Linux', 'Apple', 'Android', 'Browser'];

            $isTemplate = empty($build);

            $displayName = (string)($build['display_name'] ?? '');
            $storedPath  = Media::store($build['file_path'] ?? '');
            $fileSize    = (string)($build['file_size'] ?? '');
            $downloads   = (int)($build['downloads'] ?? 0);
            $isHidden    = !empty($build['is_hidden']);
            $isPlayable  = !empty($build['is_playable']);
            $playPath    = (string)($build['play_path'] ?? '');

            // Only a .zip can be unpacked and served as a web page.
            $isZip = strtolower(pathinfo($storedPath, PATHINFO_EXTENSION)) === 'zip';

            $uploadedAt = !empty($build['uploaded_at'])
                ? date('F jS Y', strtotime((string)$build['uploaded_at']))
                : '';

            $active = !empty($build['platforms'])
                ? array_filter(array_map('trim', explode(',', (string)$build['platforms'])))
                : ['Windows'];

            $meta = $isTemplate
                ? '0 downloads, just added'
                : $downloads . ' download' . ($downloads === 1 ? '' : 's')
                  . ($uploadedAt !== '' ? ', ' . $uploadedAt : '');

            ob_start();
            ?>
            <div class="build-card-item"
                 data-kind="<?= $isTemplate ? 'new' : 'existing' ?>"
                 data-path="<?= htmlspecialchars($storedPath) ?>"
                 data-file-key=""
                 data-zip="<?= $isTemplate || $isZip ? '1' : '0' ?>"
                 data-platforms="<?= htmlspecialchars(implode(',', $active)) ?>">

                <div class="build-card-body">
                    <div class="build-display-name"><?= htmlspecialchars($displayName) ?></div>

                    <div class="build-card-meta-row">
                        <span class="build-size-badge"><?= htmlspecialchars($fileSize) ?></span>
                        <span class="build-dot">&bull;</span>
                        <span class="build-rename" onclick="renameBuild(this)">Change display name</span>
                        <span class="build-move">
                            MOVE
                            <span class="build-move-btn" onclick="moveBuildUp(this)" title="Move up">
                                <?= Icon::get('triangle', 10, [], '0 0 24 24') ?>
                            </span>
                            <span class="build-move-btn" onclick="moveBuildDown(this)" title="Move down">
                                <?= Icon::get('triangle', 10, ['style' => 'transform: rotate(180deg);'], '0 0 24 24') ?>
                            </span>
                        </span>
                    </div>

                    <div class="build-card-stats"><?= htmlspecialchars($meta) ?></div>

                    <div class="platform-toggle-group">
                        <?php foreach ($allPlatforms as $platform): ?>
                            <?php $on = in_array($platform, $active, true); ?>
                            <button type="button"
                                    class="platform-btn<?= $on ? ' is-active' : '' ?>"
                                    data-platform="<?= htmlspecialchars($platform) ?>"
                                    aria-pressed="<?= $on ? 'true' : 'false' ?>"
                                    title="<?= htmlspecialchars($platform) ?>"
                                    onclick="togglePlatform(this)">
                                <?= Icon::get($platform) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <button type="button" class="build-delete" onclick="removeBuildCard(this)">Delete</button>
                </div>

                <!--
                    A .zip holding an index.html can be unpacked and embedded on
                    the game page, the way itch.io does it. The row is only
                    offered for zips; anything else has nothing to unpack.
                -->
                <label class="build-play-row" <?= $isTemplate || $isZip ? '' : 'hidden' ?>>
                    <input type="checkbox" class="build-play-checkbox"
                           onchange="togglePlayable(this)" <?= $isPlayable ? 'checked' : '' ?>>
                    <span>
                        <strong>Playable in the browser</strong>
                        <span class="build-play-hint">
                            Unpack this .zip and embed it on the game page. It needs an index.html.
                            <?php if ($isPlayable && $playPath !== ''): ?>
                                <span class="build-play-ok">Currently serving <?= htmlspecialchars(basename($playPath)) ?></span>
                            <?php endif; ?>
                        </span>
                    </span>
                </label>

                <label class="build-hide-row">
                    <input type="checkbox" class="build-hide-checkbox" <?= $isHidden ? 'checked' : '' ?>>
                    <span>Hide this file and prevent it from being downloaded</span>
                </label>
            </div>
            <?php
            return trim(ob_get_clean());
        }
    }
?>