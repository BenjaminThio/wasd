<?php
    require_once __DIR__ . '/../../lib/Auth.php';
    require_once __DIR__ . '/../../lib/View.php';
    require_once __DIR__ . '/../../models/Icon.php';
    require_once __DIR__ . '/../../models/Games.php';

    $user = Auth::getCurrentUser();

    if (!$user) {
        echo '<div class="page"><div class="empty-state">'
           . 'Sign in to open your developer dashboard.<br>'
           . '<a href="' . BASE_URL . '/sign-in">Sign in</a></div></div>';
        return;
    }

    $stats = Games::getUserStats($user->getId());

    $tiles = [
        ['projects',  'Projects',  'folder',   'stat-projects'],
        ['views',     'Views',     'chart',    'stat-views'],
        ['published', 'Published', 'check',    'stat-published'],
        ['downloads', 'Downloads', 'download', 'stat-downloads'],
    ];
?>

<div class="page page--wide dashboard-page">
    <header class="page-head reveal">
        <div class="flex-col gap-2">
            <span class="eyebrow">Creator tools</span>
            <h1 class="page-title">Developer Dashboard</h1>
        </div>

        <a class="btn btn-primary" href="<?= BASE_URL ?>/project">
            <?= Icon::get('plus', 16) ?> Create new project
        </a>
    </header>

    <div class="stat-grid stagger">
        <?php foreach ($tiles as [$key, $label, $icon, $id]): ?>
            <div class="stat-tile">
                <span class="stat-icon"><?= Icon::get($icon, 18) ?></span>
                <span class="stat-value" id="<?= $id ?>"><?= View::compactNumber($stats[$key]) ?></span>
                <span class="stat-label"><?= $label ?></span>
            </div>
        <?php endforeach; ?>
    </div>

    <section class="dashboard-projects panel reveal">
        <header class="dashboard-projects-head">
            <h2 class="section-title">Your projects</h2>
            <span class="badge" id="project-total"><?= (int)$stats['projects'] ?></span>
        </header>

        <div class="project-list" id="dashboard-project-list"></div>

        <div id="dash-scroll-anchor" class="dashboard-anchor"></div>
    </section>
</div>

<script>
(() => {
    const PAGE_SIZE = 6;
    const list = document.getElementById('dashboard-project-list');
    const anchor = document.getElementById('dash-scroll-anchor');

    let offset = 0;
    let scroller;

    list.innerHTML = WASD.skeletonRows(3);

    async function loadProjects() {
        const result = await WASD.api(
            `/src/app/api/dashboard/index.php?limit=${PAGE_SIZE}&offset=${offset}`
        );

        list.querySelectorAll('.skeleton-row').forEach(node => node.remove());

        if (result.status === 401) {
            anchor.textContent = 'Your session expired. Sign in again to see your projects.';
            return false;
        }

        if (result.status === 204 || !result.data) {
            anchor.innerHTML = offset === 0
                ? `<div class="empty-state">
                       Nothing here yet.<br>
                       <a href="${WASD.url('/project')}">Create your first project</a> to get started.
                   </div>`
                : '';
            return false;
        }

        list.insertAdjacentHTML('beforeend', result.data);
        WASD.lazyImages(list);
        offset += PAGE_SIZE;
        anchor.textContent = '';

        return true;
    }

    window.deleteProject = async function (gameId) {
        if (!confirm('Delete this project for good? Its builds, screenshots and stats all go with it.')) {
            return;
        }

        const card = document.getElementById(`project-card-${gameId}`);
        card?.classList.add('is-busy');

        const result = await WASD.api('/src/app/api/project/index.php', {
            method: 'DELETE',
            json: { game_id: gameId }
        });

        if (!result.ok || !result.data || result.data.status !== 'success') {
            card?.classList.remove('is-busy');
            WASD.toast((result.data && result.data.error) || 'The project could not be deleted.', 'error');
            return;
        }

        card?.remove();

        ['stat-projects', 'project-total'].forEach(id => {
            const node = document.getElementById(id);
            if (node) node.textContent = Math.max(0, parseInt(node.textContent, 10) - 1);
        });

        if (!list.querySelector('.project-row')) {
            anchor.innerHTML = `<div class="empty-state">
                No projects left.<br>
                <a href="${WASD.url('/project')}">Create a new one</a>.
            </div>`;
        }

        WASD.toast('Project deleted.', 'success');
    };

    scroller = WASD.infiniteScroll(anchor, loadProjects);
})();
</script>
