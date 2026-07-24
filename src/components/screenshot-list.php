<?php
    require_once __DIR__ . '/../lib/Media.php';

    $screenshots = isset($screenshots) && is_array($screenshots) ? $screenshots : [];

    if (!function_exists('render_screenshot_item')) {
        function render_screenshot_item(string $storedPath = ''): string
        {
            $isTemplate = $storedPath === '';
            $url = $isTemplate ? '' : Media::url($storedPath);

            $style = $isTemplate ? '' : "background-image:url('" . htmlspecialchars($url, ENT_QUOTES) . "');";

            return '<div class="ss-item-box" data-kind="' . ($isTemplate ? 'new' : 'existing') . '"'
                 . ' data-path="' . htmlspecialchars($storedPath) . '" data-file-key="" style="' . $style . '">'
                 . '<button type="button" class="ss-delete" onclick="removeScreenshot(this)">Delete</button>'
                 . '</div>';
        }
    }
?>

<style>
    .uploads-label {
        font-family: 'JetBrains Mono', monospace;
        letter-spacing: 0.15rem;
        color: var(--violet);
        font-size: 11px;
    }

    #screenshots-list {
        flex: 1;
        min-height: 0;
        overflow-y: auto;
        gap: 1rem;
        box-sizing: border-box;
        width: 100%;
        border: 1px solid var(--stroke);
        border-radius: 0.5rem;
        display: flex;
        flex-direction: column;
        padding: 1rem;
    }

    .ss-empty {
        margin: auto;
        text-align: center;
        font-family: 'Outfit', sans-serif;
        font-size: 14px;
        color: var(--violet);
        line-height: 1.6;
    }

    .ss-item-box {
        box-sizing: border-box;
        flex: none;
        border: 1px solid var(--stroke);
        width: 100%;
        height: 12rem;
        border-radius: 0.5rem;
        background-color: rgba(0, 0, 0, 0.4);
        background-size: cover;
        background-position: center;
        position: relative;
    }

    .ss-delete {
        position: absolute;
        top: 0.5rem;
        right: 0.5rem;
        background: var(--magenta);
        color: white;
        border: none;
        padding: 0.25rem 0.7rem;
        border-radius: 0.4rem;
        font-family: 'Outfit', sans-serif;
        font-size: 11px;
        cursor: pointer;
    }

    .ss-add-btn {
        padding: 0.6rem 1.2rem;
        border-radius: 0.5rem;
        background: transparent;
        color: white;
        border: 1px solid var(--stroke);
        cursor: pointer;
        font-family: 'Outfit', sans-serif;
        transition: border-color 0.2s, background 0.2s;
    }

    .ss-add-btn:hover { border-color: var(--cyan); background: rgba(34, 228, 255, 0.08); }
    .ss-add-btn:focus-visible { outline: 2px solid var(--cyan); outline-offset: 2px; }
</style>

<div style="flex:1;min-height:0;display:flex;flex-direction:column;gap:0.5rem;">
    <div class="uploads-label">SCREENSHOTS</div>

    <div style="flex:1;min-height:0;display:flex;flex-direction:column;gap:1rem;">
        <div id="screenshots-list">
            <div class="ss-empty" id="screenshots-empty-state" <?= empty($screenshots) ? '' : 'hidden' ?>>
                No screenshots yet.<br>Show players what the game looks like.
            </div>

            <?php foreach ($screenshots as $shot): ?>
                <?= render_screenshot_item(Media::store(is_array($shot) ? ($shot['image_path'] ?? '') : $shot)) ?>
            <?php endforeach; ?>
        </div>

        <input type="file" id="screenshot-file-input" multiple accept="image/*"
               style="display:none;" onchange="handleScreenshotsAdded(this)">

        <div style="display:flex;justify-content:center;align-items:center;">
            <button type="button" class="ss-add-btn"
                    onclick="document.getElementById('screenshot-file-input').click()">
                Add screenshots
            </button>
        </div>
    </div>
</div>

<template id="screenshot-item-template">
    <?= render_screenshot_item() ?>
</template>

<script>
(() => {
    const pendingScreenshots = window.__pendingScreenshots = window.__pendingScreenshots || new Map();
    let keyCounter = 0;

    const list = () => document.getElementById('screenshots-list');
    const emptyState = () => document.getElementById('screenshots-empty-state');

    function refreshEmptyState() {
        emptyState().hidden = list().querySelector('.ss-item-box') !== null;
    }

    window.handleScreenshotsAdded = function (input) {
        Array.from(input.files).forEach(file => {
            const key = 'ns' + (keyCounter++);
            pendingScreenshots.set(key, file);

            const fragment = document.getElementById('screenshot-item-template').content.cloneNode(true);
            const item = fragment.querySelector('.ss-item-box');

            item.dataset.kind = 'new';
            item.dataset.fileKey = key;
            item.style.backgroundImage = `url('${URL.createObjectURL(file)}')`;

            list().appendChild(fragment);
        });

        input.value = '';
        refreshEmptyState();
    };

    window.removeScreenshot = function (btn) {
        const item = btn.closest('.ss-item-box');
        if (item.dataset.kind === 'new') pendingScreenshots.delete(item.dataset.fileKey);
        item.remove();
        refreshEmptyState();
    };

    window.collectScreenshots = function () {
        const manifest = [];
        const files = [];

        list().querySelectorAll('.ss-item-box').forEach(item => {
            if (item.dataset.kind === 'existing') {
                manifest.push({ kind: 'existing', path: item.dataset.path });
            } else {
                const file = pendingScreenshots.get(item.dataset.fileKey);
                if (!file) return;
                manifest.push({ kind: 'new', fileKey: item.dataset.fileKey });
                files.push(['screenshot_file_' + item.dataset.fileKey, file]);
            }
        });

        return { manifest, files };
    };

    refreshEmptyState();
})();
</script>