<main style="display:flex;flex-direction:column;gap:2rem;">
    <!-- Your Search Bar (Untouched) -->
    <div style="display:flex;gap:1rem;font-family:Outfit;background-color:rgba(255, 255, 255, 0.05);padding:1rem;border-radius:1rem;border:1px solid var(--stroke)">
        <input type="text" placeholder="test" style="flex:1;background-color:var(--bg);border:none;outline:none;padding:0.5rem;border:1px solid var(--stroke);border-radius:0.5rem;color:white;">
        <select style="font-family:monospace;min-width:9rem;padding-left:0.5rem;padding-right:0.5rem;border-radius:0.5rem;">
            <option>Test</option>
            <option>Test</option>
        </select>
        <select style="font-family:monospace;min-width:9rem;padding-left:0.5rem;padding-right:0.5rem;border-radius:0.5rem;">
            <option>Test</option>
            <option>Test</option>
        </select>
        <button style="min-width:5rem;border-radius:0.5rem;">
            Test
        </button>
    </div>

    <!-- The Game Grid -->
    <div class="main-container" id="game-grid">
        <?php
            // Load your actual Database Repository
            require_once __DIR__ . '/../../models/Games.php';
            require_once __DIR__ . '/../../models/Icon.php';
            require_once __DIR__ . '/../../models/Category.php';
            require_once __DIR__ . '/../../lib/utils.php';

            // 1. Fetch 12 games instead of 10 to perfectly balance the initial grid!
            $games = Games::getChunk(12, 0);

            // 2. Loop and render using your custom component
            foreach ($games as $game)
            {
                require __DIR__ . '/../../components/game-card.php';
            }
        ?>
    </div>

    <!-- The Scroll Anchor for JavaScript -->
    <div id="scroll-anchor" style="height: 50px; display: flex; justify-content: center; align-items: center; margin-top: 1rem;">
        <p id="loading-spinner" style="display: none; color: white; font-family: Outfit;">Loading more games...</p>
    </div>
</main>

<!-- The Infinite Scroll Script -->
<script>
    // Start offset at 12 because PHP already loaded the first 12!
    let currentOffset = 12; 
    let isLoading = false;

    // Calculate the exact number of columns to fetch perfectly flush rows
    function getOptimalBatchSize() {
        const grid = document.getElementById('game-grid');
        const gridStyle = window.getComputedStyle(grid);
        
        // Count how many columns the CSS is currently displaying
        const columnCount = gridStyle.getPropertyValue('grid-template-columns').split(' ').length;
        
        // Fetch 3 rows at a time
        const targetRows = 3; 
        return (columnCount > 0 ? columnCount : 1) * targetRows;
    }

    async function loadMoreGames() {
        if (isLoading) return;
        isLoading = true;
        
        const spinner = document.getElementById('loading-spinner');
        spinner.style.display = 'block';

        const dynamicLimit = getOptimalBatchSize();

        try {
            // Pass BOTH offset and dynamic limit to the API
            //const response = await fetch(`<?= BASE_URL ?>/src/app/api/games/index.php?offset=${currentOffset}&limit=${dynamicLimit}`);
            const response = await fetch(`<?= BASE_URL ?>/src/api/games/index.php?limit=12&offset=${currentOffset}`, {
                method: 'GET',
                cache: 'no-store',
                headers: {
                    'Accept': 'application/json'
                }
            });

            // If the server sends a 204 No Content status, we are out of games!
            if (response.status === 204) {
                observer.disconnect();
                spinner.innerText = "No more games!";
                return;
            }

            // Instead of JSON, we wait for raw HTML text!
            const htmlSnippet = await response.text();

            // Inject the new pre-built game-card components directly into the grid
            document.getElementById('game-grid').insertAdjacentHTML('beforeend', htmlSnippet);

            // Increase offset by exactly what we just fetched
            currentOffset += dynamicLimit;
        } catch (error) {
            console.error("Failed to fetch games:", error);
            spinner.innerText = "Error loading games.";
        } finally {
            isLoading = false;
            if (spinner.innerText === "Loading more games...") {
                spinner.style.display = 'none';
            }
        }
    }

    const observer = new IntersectionObserver((entries) => {
        if (entries[0].isIntersecting) {
            loadMoreGames();
        }
    });

    observer.observe(document.getElementById('scroll-anchor'));
</script>