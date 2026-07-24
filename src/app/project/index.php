<?php
    require_once __DIR__ . '/../../models/Icon.php';
    require_once __DIR__ . '/../../lib/Auth.php';
    require_once __DIR__ . '/../../lib/Media.php';
    require_once __DIR__ . '/../../models/Games.php';

    $user = Auth::getCurrentUser();
    if (!$user) {
        echo "<div class='main-container' style='padding:2rem;'><h1>Sign in to manage your projects.</h1></div>";
        return;
    }

    $editingGameId = isset($_GET['id']) ? (int)$_GET['id'] : null;
    $game = $editingGameId ? Games::getById($editingGameId) : null;

    if ($editingGameId && (!$game || $game->getUserId() !== $user->getId())) {
        echo "<div class='main-container' style='padding:2rem;'><h1>That project does not exist, or it is not yours.</h1></div>";
        return;
    }

    $isEditMode = $game !== null;
    $isFree     = $isEditMode ? ($game->getPrice() == 0) : true;

    // Handed to the shared components below.
    $builds      = $isEditMode ? $game->getBuilds() : [];
    $screenshots = $isEditMode ? $game->getScreenshots() : [];

    $allCategories      = Games::getAllCategories();
    $selectedCategories = $isEditMode ? Games::getCategoryIds($game->getId()) : [];

    $coverUrl    = $isEditMode ? Media::url($game->getImage()) : '';
    $selectedArt = $isEditMode ? $game->getFallbackArt() : 'art-1';
    $visibility  = $isEditMode ? $game->getVisibility() : 'Draft';

    $inputStyle = 'background-color:rgba(0,0,0,0.7);border:1px solid var(--stroke);border-radius:0.5rem;'
                . 'padding:0.85rem 1rem;color:white;width:100%;box-sizing:border-box;';
?>

<style>
    .field-label {
        font-family: 'JetBrains Mono', monospace;
        letter-spacing: 0.15rem;
        color: var(--violet);
        font-size: 11px;
        margin-bottom: 0.5rem;
    }

    .pricing-btn {
        font-family: 'JetBrains Mono', monospace;
        font-size: 11px;
        letter-spacing: 0.15rem;
        color: var(--violet);
        text-align: center;
        flex: 1;
        border: 1px solid var(--stroke);
        background: transparent;
        border-radius: 0.5rem;
        padding: 0.6rem;
        cursor: pointer;
    }

    .pricing-btn.is-active { border-color: var(--cyan); background: rgba(34, 228, 255, 0.1); }

    .fallback-art-box { height: 3rem; width: 6rem; border-radius: 0.5rem; box-sizing: border-box; border: 1px solid transparent; }
    .fallback-art-label.is-active .fallback-art-box { border: 2px solid var(--cyan); }

    .category-chip {
        font-family: 'JetBrains Mono', monospace;
        font-size: 11px;
        letter-spacing: 0.1rem;
        border: 1px solid var(--stroke);
        border-radius: 0.5rem;
        padding: 0.4rem 0.75rem;
        cursor: pointer;
        user-select: none;
        transition: 0.2s;
    }

    .category-chip.is-active { border-color: var(--cyan); background: rgba(34, 228, 255, 0.12); }
    .category-chip input { display: none; }

    .status-option { display: flex; gap: 0.5rem; align-items: flex-start; accent-color: var(--magenta); cursor: pointer; }

    #save-feedback {
        display: none;
        margin: 0 2rem 1rem;
        padding: 0.9rem 1.1rem;
        border-radius: 0.6rem;
        font-family: 'Outfit', sans-serif;
        font-size: 14px;
        line-height: 1.6;
    }

    #save-feedback.is-error   { display: block; border: 1px solid var(--magenta); background: rgba(223, 0, 111, 0.12); }
    #save-feedback.is-warning { display: block; border: 1px solid var(--violet);  background: rgba(139, 92, 246, 0.12); }
    #save-feedback.is-ok      { display: block; border: 1px solid var(--cyan);    background: rgba(34, 228, 255, 0.1); }
    #save-feedback ul { margin: 0.5rem 0 0; padding-left: 1.2rem; }

    .save-button[disabled] { opacity: 0.5; cursor: progress; transform: none; }
</style>

<div class="main-container">
    <div class="header-bar">
        <div class="header-left-section">
            <div class="title"><?= $isEditMode ? 'Edit project' : 'New project' ?></div>
        </div>
        <button type="button" class="save-button" id="save-button" onclick="saveProject()">Save project</button>
    </div>

    <div id="save-feedback" role="status" aria-live="polite"></div>

    <form id="project-form" style="display:grid;grid-template-columns:6fr 4fr;flex:1 1 auto;" onsubmit="return false;">
        <input type="hidden" name="game_id" id="game-id-input" value="<?= $isEditMode ? (int)$game->getId() : '' ?>">

        <!-- LEFT: DETAILS -->
        <div class="project-list" style="padding:2rem;">

            <label>
                <div class="field-label">GAME NAME</div>
                <input name="title" required placeholder="Cool Game" style="<?= $inputStyle ?>"
                       value="<?= $isEditMode ? htmlspecialchars($game->getTitle()) : '' ?>">
            </label>

            <label>
                <div class="field-label">DESCRIPTION</div>
                <textarea name="description" placeholder="Tell players what the game is about."
                          style="<?= $inputStyle ?>min-height:15rem;line-height:1.6;resize:vertical;"><?= $isEditMode ? htmlspecialchars($game->getDescription()) : '' ?></textarea>
            </label>

            <div>
                <div class="field-label">PRICING</div>
                <input type="hidden" name="is_free" id="is-free-input" value="<?= $isFree ? '1' : '0' ?>">

                <div style="display:flex;flex-direction:column;gap:1rem;">
                    <div style="display:flex;gap:1rem;">
                        <button type="button" id="btn-pricing-free" onclick="setPricingType(true)"
                                class="pricing-btn<?= $isFree ? ' is-active' : '' ?>">FREE</button>
                        <button type="button" id="btn-pricing-paid" onclick="setPricingType(false)"
                                class="pricing-btn<?= $isFree ? '' : ' is-active' ?>">PAID</button>
                    </div>

                    <div id="paid-pricing-container" style="display:<?= $isFree ? 'none' : 'flex' ?>;flex-direction:column;gap:1rem;border:1px solid var(--stroke);border-radius:0.5rem;padding:1rem;">
                        <div>
                            <div class="field-label">ORIGINAL PRICE</div>
                            <input name="price" type="number" step="0.01" min="0" style="<?= $inputStyle ?>"
                                   value="<?= $isEditMode ? htmlspecialchars((string)$game->getPrice()) : '0.00' ?>">
                        </div>
                        <div>
                            <div class="field-label">DISCOUNT (%)</div>
                            <input name="discount" type="number" min="0" max="100" style="<?= $inputStyle ?>"
                                   value="<?= $isEditMode ? (int)$game->getDiscount() : 0 ?>">
                        </div>
                    </div>
                </div>
            </div>

            <?php require __DIR__ . '/../../components/build-list.php'; ?>

            <div>
                <div class="field-label">CATEGORY TAGS</div>
                <div style="display:flex;flex-wrap:wrap;gap:0.6rem;">
                    <?php foreach ($allCategories as $category): ?>
                        <?php $on = in_array((int)$category['id'], $selectedCategories, true); ?>
                        <label class="category-chip<?= $on ? ' is-active' : '' ?>">
                            <input type="checkbox" name="categories[]" value="<?= (int)$category['id'] ?>" <?= $on ? 'checked' : '' ?>
                                   onchange="this.closest('.category-chip').classList.toggle('is-active', this.checked)">
                            <?= htmlspecialchars($category['name']) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div>
                <div class="field-label">STATUS</div>
                <div style="display:inline-flex;flex-direction:column;gap:0.5rem;">
                    <?php
                        $statuses = [
                            'Draft' => 'Draft - only people who can edit the project can open the page',
                            'Restricted' => 'Restricted - only owners and authorised people can open the page',
                            'Public' => 'Public - anyone can open the page',
                        ];
                    ?>
                    <?php foreach ($statuses as $value => $copy): ?>
                        <label class="status-option">
                            <input type="radio" name="visibility" value="<?= $value ?>" <?= $visibility === $value ? 'checked' : '' ?>>
                            <span><?= $copy ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- RIGHT: MEDIA -->
        <div style="position:relative;">
            <div style="position:absolute;inset:0;padding:2rem 2rem 2rem 0;display:flex;flex-direction:column;gap:1rem;box-sizing:border-box;">

                <div>
                    <div class="field-label">GAME IMAGE</div>
                    <label style="cursor:pointer;display:block;">
                        <div id="cover-preview" class="<?= $coverUrl === '' ? htmlspecialchars($selectedArt) : '' ?>"
                             style="height:20rem;width:100%;border-radius:1rem;display:flex;justify-content:center;align-items:center;background-size:cover;background-position:center;<?= $coverUrl !== '' ? "background-image:url('" . htmlspecialchars($coverUrl, ENT_QUOTES) . "');" : '' ?>">
                            <div style="border:1px dashed white;padding:2rem;border-radius:50%;display:flex;justify-content:center;align-items:center;background:rgba(0,0,0,0.5);">
                                <?= Icon::get('camera', 40) ?>
                            </div>
                        </div>
                        <input type="file" id="cover-input" accept="image/*" style="display:none">
                    </label>
                </div>

                <div>
                    <div class="field-label">FALLBACK ART</div>
                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:1rem;">
                        <?php for ($i = 1; $i <= 8; $i++): ?>
                            <label class="fallback-art-label<?= $selectedArt === "art-$i" ? ' is-active' : '' ?>"
                                   onclick="highlightFallbackArt(this)" style="cursor:pointer;">
                                <div class="fallback-art-box art-<?= $i ?>"></div>
                                <input type="radio" style="display:none" name="fallback_art" value="art-<?= $i ?>"
                                       <?= $selectedArt === "art-$i" ? 'checked' : '' ?>>
                            </label>
                        <?php endfor; ?>
                    </div>
                </div>

                <?php require __DIR__ . '/../../components/screenshot-list.php'; ?>
            </div>
        </div>
    </form>
</div>

<script>
(() => {
    const API = '<?= BASE_URL ?>/src/app/api/project/index.php';
    let coverFile = null;
    let hasCoverImage = <?= $coverUrl !== '' ? 'true' : 'false' ?>;

    window.setPricingType = function (free) {
        document.getElementById('is-free-input').value = free ? '1' : '0';
        document.getElementById('btn-pricing-free').classList.toggle('is-active', free);
        document.getElementById('btn-pricing-paid').classList.toggle('is-active', !free);
        document.getElementById('paid-pricing-container').style.display = free ? 'none' : 'flex';
    };

    window.highlightFallbackArt = function (label) {
        document.querySelectorAll('.fallback-art-label').forEach(l => l.classList.remove('is-active'));
        label.classList.add('is-active');

        if (!hasCoverImage) {
            const artValue = label.querySelector('input[name="fallback_art"]').value;
            const preview = document.getElementById('cover-preview');
            preview.className = artValue;
            preview.style.backgroundImage = '';
        }
    };

    document.getElementById('cover-input').addEventListener('change', e => {
        const file = e.target.files[0];
        if (!file) return;
        coverFile = file;
        hasCoverImage = true;
        const preview = document.getElementById('cover-preview');
        preview.style.backgroundImage = `url('${URL.createObjectURL(file)}')`;
        preview.className = '';
    });

    const escapeHtml = value => String(value).replace(/[&<>"']/g,
        c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    function showFeedback(kind, headline, items = []) {
        const box = document.getElementById('save-feedback');
        box.className = 'is-' + kind;
        box.innerHTML = `<strong>${escapeHtml(headline)}</strong>` +
            (items.length ? `<ul>${items.map(i => `<li>${escapeHtml(i)}</li>`).join('')}</ul>` : '');
        box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    window.saveProject = async function () {
        const button = document.getElementById('save-button');
        const form = document.getElementById('project-form');

        if (!form.title.value.trim()) {
            showFeedback('error', 'Give the project a name before saving.');
            form.title.focus();
            return;
        }

        const builds = window.collectBuilds();
        const shots  = window.collectScreenshots();

        const data = new FormData();
        data.append('game_id', document.getElementById('game-id-input').value);
        data.append('title', form.title.value);
        data.append('description', form.description.value);
        data.append('is_free', form.is_free.value);
        data.append('price', form.price.value || '0');
        data.append('discount', form.discount.value || '0');
        data.append('visibility', form.querySelector('input[name="visibility"]:checked')?.value || 'Draft');
        data.append('fallback_art', form.querySelector('input[name="fallback_art"]:checked')?.value || 'art-1');

        form.querySelectorAll('input[name="categories[]"]:checked')
            .forEach(cb => data.append('categories[]', cb.value));

        data.append('builds_manifest', JSON.stringify(builds.manifest));
        data.append('screenshots_manifest', JSON.stringify(shots.manifest));

        builds.files.forEach(([key, file]) => data.append(key, file));
        shots.files.forEach(([key, file]) => data.append(key, file));
        if (coverFile) data.append('cover_image', coverFile);

        button.disabled = true;
        button.textContent = 'Saving…';

        try {
            const response = await fetch(API, { method: 'POST', body: data });
            const raw = await response.text();

            let result;
            try {
                result = JSON.parse(raw);
            } catch {
                showFeedback('error', 'The server sent back something that is not JSON:', [raw.slice(0, 400)]);
                return;
            }

            if (!response.ok || result.status !== 'success') {
                showFeedback('error', result.error || 'The project could not be saved.', result.warnings || []);
                return;
            }

            if (result.warnings && result.warnings.length) {
                document.getElementById('game-id-input').value = result.game_id;
                showFeedback('warning',
                    `Saved ${result.builds_saved} build(s) and ${result.screenshots_saved} screenshot(s), but some files were skipped:`,
                    result.warnings);
                return;
            }

            showFeedback('ok', 'Project saved. Taking you back to the dashboard…');
            setTimeout(() => { window.location.href = '<?= BASE_URL ?>/dashboard'; }, 700);

        } catch (err) {
            showFeedback('error', 'Could not reach the server.', [String(err)]);
        } finally {
            button.disabled = false;
            button.textContent = 'Save project';
        }
    };
})();
</script>

<!--<?php require_once __DIR__ . '/../../models/Icon.php'; ?>
<div class="main-container">
    <div class="header-bar">
        <div class="header-left-section">
            <div class="title">Developer Dashboard</div>
        </div>
        <div class="save-button">
            Save Project
        </div>
    </div>
    <div style="display:grid;grid-template-columns:6fr 4fr;flex:1 1 auto;">
        <div class="project-list" style="padding:2rem;">
            <label>
                <div style="font-family:JetBrains Mono;letter-spacing:0.15rem;color:var(--violet);font-size:11px;margin-bottom:0.5rem;">GAME NAME</div>
                <input placeholder="Cool Game" style="
                    background-color: rgba(0, 0, 0, 0.7);
                    border: 1px solid var(--stroke);
                    border-radius: 0.5rem;
                    padding-top: 0.85rem;
                    padding-bottom: 0.85rem;
                    padding-left: 1rem;
                    padding-right: 1rem;
                    color:white;
                    width:100%;
                    box-sizing: border-box;
                ">
            </label>
            <label>
                <div style="font-family:JetBrains Mono;letter-spacing:0.15rem;color:var(--violet);font-size:11px;margin-bottom:0.5rem;">
                    DESCRIPTION
                </div>
                <textarea placeholder="Please type something about the game." style="
                    background-color: rgba(0, 0, 0, 0.7);
                    border: 1px solid var(--stroke);
                    border-radius: 0.5rem;
                    padding-top: 0.85rem;
                    padding-bottom: 0.85rem;
                    padding-left: 1rem;
                    padding-right: 1rem;
                    color:white;
                    width:100%;
                    box-sizing: border-box;
                    min-height:15rem;
                    line-height:1.6;
                    resize: vertical;
                "></textarea>
            </label>
            <div>
                <div style="font-family:JetBrains Mono;letter-spacing:0.15rem;color:var(--violet);font-size:11px;margin-bottom:0.5rem;">PRICING</div>
                <div style="display:flex;flex-direction:column;gap:1rem;">
                    <div style="display:flex;gap:1rem;">
                        <div style="font-size:11px;letter-spacing:0.15rem;color:var(--violet);text-align:center;flex:1;border:1px solid var(--stroke);border-radius:0.5rem;padding:0.6rem;">
                            FREE
                        </div>
                        <div style="font-size:11px;letter-spacing:0.15rem;color:var(--violet);text-align:center;flex:1;border:1px solid var(--stroke);border-radius:0.5rem;padding:0.6rem;">
                            PAID
                        </div>
                    </div>
                    <div style="display:flex;flex-direction:column;gap:1rem;border:1px solid var(--stroke);border-radius:0.5rem;;padding:1rem;">
                        <div>
                            <div style="font-family:JetBrains Mono;letter-spacing:0.15rem;color:var(--violet);font-size:11px;margin-bottom:0.5rem;">ORIGINAL PRICE</div>
                            <input placeholder="" style="
                                background-color: rgba(0, 0, 0, 0.7);
                                border: 1px solid var(--stroke);
                                border-radius: 0.5rem;
                                padding-top: 0.85rem;
                                padding-bottom: 0.85rem;
                                padding-left: 1rem;
                                padding-right: 1rem;
                                color:white;
                                width:100%;
                                box-sizing: border-box;
                            ">
                        </div>
                        <div>
                            <div style="font-family:JetBrains Mono;letter-spacing:0.15rem;color:var(--violet);font-size:11px;margin-bottom:0.5rem;">DISCOUNT</div>
                            <input placeholder="" style="
                                background-color: rgba(0, 0, 0, 0.7);
                                border: 1px solid var(--stroke);
                                border-radius: 0.5rem;
                                padding-top: 0.85rem;
                                padding-bottom: 0.85rem;
                                padding-left: 1rem;
                                padding-right: 1rem;
                                color:white;
                                width:100%;
                                box-sizing: border-box;
                            ">
                        </div>
                    </div>
                </div>
            </div>
            <div>
                <div style="font-family:JetBrains Mono;letter-spacing:0.15rem;color:var(--violet);font-size:11px;margin-bottom:0.5rem;">
                    UPLOADS
                </div>
                <div style="
                    background-color: rgba(0, 0, 0, 0.7);
                    border: 1px solid var(--stroke);
                    border-radius: 0.5rem;
                    color:white;
                    width:100%;
                    box-sizing: border-box;
                    min-height:15rem;
                    display: flex;
                    flex-direction: column;
                    overflow: hidden;
                ">
                    <div style="background-color:rgba(255, 255, 255, 0.025);border-bottom:1px solid var(--stroke)">
                        <div style="position:relative;border-bottom:1px solid var(--stroke);padding:1rem 1.5rem;display:flex;flex-direction:column;gap:0.7rem;">
                            <div style="font-family:Unbounded;font-weight:600;font-size:15px;">
                                game.zip
                            </div>
                            <div style="display:inline-flex;gap:0.5rem;align-items:center;">
                                <span style='
                                    font-family:JetBrains Mono;
                                    letter-spacing:0.15rem;
                                    font-size:12px;
                                    border:1px solid var(--stroke);
                                    padding-top: 0.2rem;
                                    padding-bottom: 0.2rem;
                                    padding-left: 0.4rem;
                                    padding-right: 0.4rem;
                                    border-radius: 0.4rem;'
                                >
                                    10MB
                                </span>
                                <span>
                                    •
                                </span>
                                <span style="
                                    font-family:JetBrains Mono;
                                    letter-spacing:0.15rem;
                                    font-size:12px;
                                    text-decoration: underline;
                                    text-underline-offset: 0.1rem;
                                ">
                                    Change display name
                                </span>
                                <span style='
                                    font-family:JetBrains Mono;
                                    letter-spacing:0.15rem;
                                    font-size:12px;
                                    border:1px solid var(--stroke);
                                    padding-top: 0.2rem;
                                    padding-bottom: 0.2rem;
                                    padding-left: 0.4rem;
                                    padding-right: 0.4rem;
                                    border-radius: 0.4rem;'
                                >
                                    MOVE
                                    <span>
                                        <?php
                                            echo Icon::get('triangle', 10, [], '0 0 24 24');
                                        ?>
                                    </span>
                                    <span>
                                        <?php
                                            echo Icon::get('triangle', 10, [
                                                'style' => 'transform: rotate(180deg);'
                                            ], '0 0 24 24');
                                        ?>
                                    </span>
                                </span>
                            </div>
                            <div style="
                                font-family:JetBrains Mono;
                                letter-spacing:0.15rem;
                                font-size:12px;"
                            >
                                7 Downloads, January 16th 2026
                            </div>
                            <div style="display:flex;gap:0.7rem;margin-top:1rem;">
                                <div style="display:inline-flex;justify-content:center;align-items:center;padding:0.3rem;border:1px solid var(--stroke);border-radius:0.5rem;">
                                    <?php
                                        echo Icon::get('Windows');
                                    ?>
                                </div>
                                <div style="display:inline-flex;justify-content:center;align-items:center;padding:0.3rem;border:1px solid var(--stroke);border-radius:0.5rem;">
                                    <?php
                                        echo Icon::get('Linux');
                                    ?>
                                </div>
                                <div style="display:inline-flex;justify-content:center;align-items:center;padding:0.3rem;border:1px solid var(--stroke);border-radius:0.5rem;">
                                    <?php
                                        echo Icon::get('Apple');
                                    ?>
                                </div>
                                <div style="display:inline-flex;justify-content:center;align-items:center;padding:0.3rem;border:1px solid var(--stroke);border-radius:0.5rem;">
                                    <?php
                                        echo Icon::get('Android');
                                    ?>
                                </div>
                                <div style="display:inline-flex;justify-content:center;align-items:center;padding:0.3rem;border:1px solid var(--stroke);border-radius:0.5rem;">
                                    <?php
                                        echo Icon::get('Browser');
                                    ?>
                                </div>
                            </div>
                            <div style="border-radius:0 0 0 0.45rem;font-family:Outfit;font-weight:600;font-size:12px;position:absolute;background-color:var(--magenta);top:0;right:0;padding-top:0.2rem;padding-bottom:0.4rem;padding-left:0.8rem;padding-right:0.8rem;">
                                Delete
                            </div>
                        </div>
                        <label style="display:block;padding:1rem;display:flex;gap:0.5rem;align-items:center;">
                            <input style="accent-color:var(--magenta)" type="checkbox">
                            <span style="
                                font-family:JetBrains Mono;
                                letter-spacing:0.15rem;
                                font-size:12px;"
                            >
                                Hide this file and prevent it from being downloaded
                            </span>
                        </label>
                    </div>
                </div>
            </div>
            <div>
                <div style="font-family:JetBrains Mono;letter-spacing:0.15rem;color:var(--violet);font-size:11px;margin-bottom:0.5rem;">CATEGORY TAGS</div>

            </div>
            <div>
                <div style="font-family:JetBrains Mono;letter-spacing:0.15rem;color:var(--violet);font-size:11px;margin-bottom:0.5rem;">STATUS</div>
                <div style="display:inline-flex;flex-direction:column;gap:0.5rem;">
                    <label style="accent-color:var(--magenta);">
                        <input type="radio" name="visibility-option">
                        Draft - Only those who can edit the project can view the page
                    </label>
                    <label style="accent-color:var(--magenta);">
                        <input type="radio" name="visibility-option">
                        Restricted - Only owners & authorized people can view the page
                    </label>
                    <label style="accent-color:var(--magenta);">
                        <input type="radio" name="visibility-option">
                        Public - Anyone can view the page
                    </label>
                </div>
            </div>
        </div>
        <div style="position:relative;">
            <div style="position:absolute;inset:0;padding-top:2rem;padding-right:2rem;padding-bottom:2rem;display:flex;flex-direction:column;gap:1rem;box-sizing:border-box;">
                <div>
                    <div style="font-family:JetBrains Mono;letter-spacing:0.15rem;color:var(--violet);font-size:11px;margin-bottom:0.5rem;">GAME IMAGE</div>
                    <div class="art-1" style="height:20rem;width:100%;border-radius:1rem;display:flex;justify-content:center;align-items:center;">
                        <div style="border: 1px dashed white;padding:2rem;border-radius:50%;display:flex;justify-content:center;align-items:center;">
                            <?= Icon::get('camera', 40); ?>
                        </div>
                    </div>
                </div>
                <div>
                    <div style="font-family:JetBrains Mono;letter-spacing:0.15rem;color:var(--violet);font-size:11px;margin-bottom:0.5rem;">FALLBACK ART</div>
                    <div style="display:grid;grid-template-columns: 1fr 1fr 1fr 1fr;gap:1rem;">
                        <?php
                            for ($i = 1; $i <= 8; $i++)
                            {
                                echo <<<HTML
                                    <label style="accent-color:var(--magenta);">
                                        <div style="height:3rem;width:6rem;border-radius:0.5rem;" class="art-{$i}"></div>
                                        <input type="radio" style="display:none" name="art-option">
                                    </label>
                                HTML;
                            }
                        ?>
                    </div>
                </div>
                <div style="flex:1;min-height:0;display:flex;flex-direction:column;gap:0.5rem;">
                    <div style="font-family:JetBrains Mono;letter-spacing:0.15rem;color:var(--violet);font-size:11px;">SCREENSHOTS</div>
                    <div style="flex:1;min-height:0;display:flex;flex-direction:column;gap:1rem;">
                        <div style="flex:1;min-height:0;overflow-y:auto;gap:1rem;box-sizing:border-box;width:100%;border:1px solid var(--stroke);border-radius:0.5rem;display:flex;flex-direction:column;padding:1rem;">
                            <?php
                                for ($i = 0; $i <= 10; $i++)
                                {
                                    echo <<<HTML
                                        <div style="box-sizing:border-box;flex:none;border:1px solid var(--stroke);width:100%;height:12rem;border-radius:0.5rem;">
                                        </div>
                                    HTML;
                                }
                            ?>
                        </div>
                        <div style="display:flex;justify-content:center;align-items:center;">
                            <button>Add Screenshots</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>-->