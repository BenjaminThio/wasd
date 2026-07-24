<?php
    require_once __DIR__ . '/build-card.php';

    $builds = isset($builds) && is_array($builds) ? $builds : [];
?>

<style>
    .uploads-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.5rem;
    }

    .uploads-label {
        font-family: 'JetBrains Mono', monospace;
        letter-spacing: 0.15rem;
        color: var(--violet);
        font-size: 11px;
    }

    .uploads-add-btn {
        font-family: 'JetBrains Mono', monospace;
        font-size: 11px;
        letter-spacing: 0.05rem;
        padding: 0.35rem 0.7rem;
        background: transparent;
        color: white;
        border: 1px solid var(--stroke);
        border-radius: 0.4rem;
        cursor: pointer;
        transition: border-color 0.2s, background 0.2s;
    }

    .uploads-add-btn:hover { border-color: var(--cyan); background: rgba(34, 228, 255, 0.08); }
    .uploads-add-btn:focus-visible { outline: 2px solid var(--cyan); outline-offset: 2px; }

    #uploads-wrapper {
        background-color: rgba(0, 0, 0, 0.7);
        border: 1px solid var(--stroke);
        border-radius: 0.5rem;
        color: white;
        width: 100%;
        box-sizing: border-box;
        min-height: 15rem;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .uploads-empty {
        margin: auto;
        padding: 2rem;
        text-align: center;
        font-family: 'Outfit', sans-serif;
        font-size: 14px;
        color: var(--violet);
        line-height: 1.6;
    }

    .build-card-item {
        background-color: rgba(255, 255, 255, 0.025);
        border-bottom: 1px solid var(--stroke);
    }

    .build-card-body {
        position: relative;
        border-bottom: 1px solid var(--stroke);
        padding: 1rem 1.5rem;
        display: flex;
        flex-direction: column;
        gap: 0.7rem;
    }

    .build-display-name {
        font-family: 'Unbounded', sans-serif;
        font-weight: 600;
        font-size: 15px;
        word-break: break-all;
        padding-right: 4.5rem;
    }

    .build-card-meta-row {
        display: inline-flex;
        gap: 0.5rem;
        align-items: center;
        flex-wrap: wrap;
        font-family: 'JetBrains Mono', monospace;
        letter-spacing: 0.15rem;
        font-size: 12px;
    }

    .build-size-badge,
    .build-move {
        border: 1px solid var(--stroke);
        padding: 0.2rem 0.4rem;
        border-radius: 0.4rem;
    }

    .build-move { display: inline-flex; align-items: center; gap: 0.3rem; }
    .build-move-btn { cursor: pointer; padding: 0 2px; display: inline-flex; }
    .build-move-btn:hover { color: var(--cyan); }

    .build-rename {
        text-decoration: underline;
        text-underline-offset: 0.1rem;
        cursor: pointer;
    }

    .build-rename:hover { color: var(--cyan); }

    .build-card-stats {
        font-family: 'JetBrains Mono', monospace;
        letter-spacing: 0.15rem;
        font-size: 12px;
    }

    .platform-toggle-group { display: flex; gap: 0.7rem; margin-top: 1rem; }

    .platform-btn {
        display: inline-flex;
        justify-content: center;
        align-items: center;
        padding: 0.3rem;
        border: 1px solid var(--stroke);
        background: transparent;
        border-radius: 0.5rem;
        cursor: pointer;
        color: white;
        opacity: 0.45;
        transition: 0.2s;
    }

    .platform-btn.is-active {
        border-color: var(--cyan);
        background: rgba(34, 228, 255, 0.2);
        opacity: 1;
    }

    .platform-btn:focus-visible { outline: 2px solid var(--cyan); outline-offset: 2px; }

    .build-delete {
        border-radius: 0 0.45rem 0 0.45rem;
        font-family: 'Outfit', sans-serif;
        font-weight: 600;
        font-size: 12px;
        position: absolute;
        background-color: var(--magenta);
        color: white;
        border: none;
        top: 0;
        right: 0;
        padding: 0.3rem 0.8rem;
        cursor: pointer;
    }

    .build-hide-row {
        padding: 1rem;
        display: flex;
        gap: 0.5rem;
        align-items: center;
        cursor: pointer;
        font-family: 'JetBrains Mono', monospace;
        letter-spacing: 0.15rem;
        font-size: 12px;
    }

    .build-hide-checkbox { accent-color: var(--magenta); }
</style>

<div>
    <div class="uploads-head">
        <div class="uploads-label">UPLOADS</div>
        <button type="button" class="uploads-add-btn"
                onclick="document.getElementById('build-file-input').click()">
            + Add file build
        </button>
    </div>

    <input type="file" id="build-file-input" multiple
           accept=".zip,.rar,.7z,.tar,.gz,.tgz,.exe,.msi,.apk,.aab,.dmg,.pkg,.jar,.love,.pck,.appimage,.bin"
           style="display:none;" onchange="handleBuildFilesAdded(this)">

    <div id="uploads-wrapper">
        <div class="uploads-empty" id="builds-empty-state" <?= empty($builds) ? '' : 'hidden' ?>>
            No builds yet.<br>Add a file so players have something to download.
        </div>

        <?php foreach ($builds as $build): ?>
            <?= render_build_card($build) ?>
        <?php endforeach; ?>
    </div>
</div>

<template id="build-card-template">
    <?= render_build_card() ?>
</template>

<script>
(() => {
    const pendingBuilds = window.__pendingBuilds = window.__pendingBuilds || new Map();
    let keyCounter = 0;

    const wrapper = () => document.getElementById('uploads-wrapper');
    const emptyState = () => document.getElementById('builds-empty-state');

    function refreshEmptyState() {
        const hasCards = wrapper().querySelector('.build-card-item') !== null;
        emptyState().hidden = hasCards;
    }

    function humanSize(bytes) {
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        if (bytes < 1024 * 1024 * 1024) return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
        return (bytes / (1024 * 1024 * 1024)).toFixed(2) + ' GB';
    }

    window.handleBuildFilesAdded = function (input) {
        Array.from(input.files).forEach(file => {
            const key = 'nb' + (keyCounter++);
            pendingBuilds.set(key, file);

            const fragment = document.getElementById('build-card-template').content.cloneNode(true);
            const card = fragment.querySelector('.build-card-item');

            card.dataset.kind = 'new';
            card.dataset.fileKey = key;
            card.querySelector('.build-display-name').textContent = file.name;
            card.querySelector('.build-size-badge').textContent = humanSize(file.size);

            wrapper().appendChild(fragment);
        });

        input.value = '';
        refreshEmptyState();
    };

    window.removeBuildCard = function (btn) {
        const card = btn.closest('.build-card-item');
        if (card.dataset.kind === 'new') pendingBuilds.delete(card.dataset.fileKey);
        card.remove();
        refreshEmptyState();
    };

    window.renameBuild = function (el) {
        const card = el.closest('.build-card-item');
        const nameEl = card.querySelector('.build-display-name');
        const next = prompt('Display name for this build:', nameEl.textContent.trim());
        if (next && next.trim() !== '') nameEl.textContent = next.trim();
    };

    window.moveBuildUp = function (el) {
        const card = el.closest('.build-card-item');
        const prev = card.previousElementSibling;
        if (prev && prev.classList.contains('build-card-item')) card.parentNode.insertBefore(card, prev);
    };

    window.moveBuildDown = function (el) {
        const card = el.closest('.build-card-item');
        const next = card.nextElementSibling;
        if (next && next.classList.contains('build-card-item')) card.parentNode.insertBefore(next, card);
    };

    window.togglePlatform = function (btn) {
        const card = btn.closest('.build-card-item');
        const on = btn.classList.toggle('is-active');
        btn.setAttribute('aria-pressed', on ? 'true' : 'false');

        const active = Array.from(card.querySelectorAll('.platform-btn.is-active'))
                            .map(b => b.dataset.platform);

        if (active.length === 0) {
            btn.classList.add('is-active');
            btn.setAttribute('aria-pressed', 'true');
            active.push(btn.dataset.platform);
        }

        card.dataset.platforms = active.join(',');
    };

    window.collectBuilds = function () {
        const manifest = [];
        const files = [];

        wrapper().querySelectorAll('.build-card-item').forEach(card => {
            const entry = {
                kind: card.dataset.kind,
                name: card.querySelector('.build-display-name').textContent.trim(),
                platforms: card.dataset.platforms || 'Windows',
                hidden: card.querySelector('.build-hide-checkbox').checked
            };

            if (entry.kind === 'existing') {
                entry.path = card.dataset.path;
            } else {
                const file = pendingBuilds.get(card.dataset.fileKey);
                if (!file) return;
                entry.fileKey = card.dataset.fileKey;
                files.push(['build_file_' + card.dataset.fileKey, file]);
            }

            manifest.push(entry);
        });

        return { manifest, files };
    };

    refreshEmptyState();
})();
</script>