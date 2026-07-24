<?php
    require_once __DIR__ . '/../../models/Icon.php';
    require_once __DIR__ . '/../../lib/Auth.php';
    require_once __DIR__ . '/../../models/Games.php';

    $user = Auth::getCurrentUser();
    if (!$user) {
        echo "<div class='main-container' style='padding:2rem;'><h1>Sign in to open your developer dashboard.</h1></div>";
        return;
    }

    $stats = Games::getUserStats($user->getId());
?>

<div class="main-container">
    <div class="header-bar">
        <div class="header-left-section">
            <div class="title">Developer Dashboard</div>
            <button onclick="window.location.href='<?= BASE_URL ?>/project';" class="create-project-button">
                <?= Icon::get('plus') ?>
                <div class="create-project-button-text">CREATE NEW PROJECT</div>
            </button>
        </div>
        <div class="info-bar">
            <div class="info">
                <div class="info-header" id="stat-projects"><?= $stats['projects'] ?></div>
                <div class="info-body">PROJECTS</div>
            </div>
            <div class="info">
                <div class="info-header"><?= $stats['views'] ?></div>
                <div class="info-body">VIEWS</div>
            </div>
            <div class="info">
                <div class="info-header" id="stat-published"><?= $stats['published'] ?></div>
                <div class="info-body">PUBLISHED</div>
            </div>
            <div class="info">
                <div class="info-header"><?= $stats['downloads'] ?></div>
                <div class="info-body">DOWNLOADS</div>
            </div>
        </div>
    </div>

    <div class="project-list" id="dashboard-project-list"></div>

    <div id="dash-scroll-anchor" style="padding:2rem;text-align:center;color:var(--violet);font-family:Outfit;">
        Loading projects…
    </div>
</div>

<script>
(() => {
    const PAGE_SIZE = 5;
    let offset = 0;
    let isLoading = false;
    let finished = false;

    const anchor = () => document.getElementById('dash-scroll-anchor');

    async function loadProjectChunks() {
        if (isLoading || finished) return;
        isLoading = true;

        try {
            const res = await fetch(`<?= BASE_URL ?>/src/app/api/dashboard/index.php?limit=${PAGE_SIZE}&offset=${offset}`);

            if (res.status === 204) {
                finished = true;
                dashObserver.disconnect();
                anchor().textContent = offset === 0
                    ? 'Nothing here yet. Create your first project to get started.'
                    : '';
                return;
            }

            if (!res.ok) {
                anchor().textContent = 'Could not load your projects. Refresh to try again.';
                return;
            }

            const html = await res.text();
            document.getElementById('dashboard-project-list').insertAdjacentHTML('beforeend', html);
            offset += PAGE_SIZE;
            anchor().textContent = '';
        } catch (err) {
            console.error(err);
            anchor().textContent = 'Could not reach the server. Refresh to try again.';
        } finally {
            isLoading = false;
        }
    }

    window.deleteProject = async function (gameId) {
        if (!confirm('Delete this project for good? Its builds, screenshots and stats all go with it.')) return;

        try {
            const res = await fetch(`<?= BASE_URL ?>/src/app/api/project/index.php`, {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ game_id: gameId })
            });

            const result = await res.json().catch(() => ({}));

            if (!res.ok || result.status !== 'success') {
                alert(result.error || 'The project could not be deleted.');
                return;
            }

            document.getElementById(`project-card-${gameId}`)?.remove();

            const projects = document.getElementById('stat-projects');
            projects.textContent = Math.max(0, parseInt(projects.textContent, 10) - 1);
        } catch (err) {
            alert('Could not reach the server: ' + err);
        }
    };

    const dashObserver = new IntersectionObserver(entries => {
        if (entries[0].isIntersecting) loadProjectChunks();
    });

    dashObserver.observe(anchor());
})();
</script>