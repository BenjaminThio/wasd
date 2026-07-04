<div style="height: 20rem; width: 15rem; border: 1px solid black; border-radius: 1rem; padding: 0.5rem; display: flex; flex-direction: column; gap: 0.5rem;">
    <div style="height: 13rem; border: 1px solid black; border-radius: 0.5rem; display: flex; flex-direction: column; overflow: hidden;">
        <div style="height: 10rem;">
            Image
        </div>
        <div style="flex: 1; border-top: 1px solid black; display: flex; justify-content: center; align-items: center; font-weight: bold; font-size: large;">
            <?= htmlspecialchars($game->getTitle()) ?>
        </div>
    </div>
    <div style="flex: 1; border: 1px solid black; border-radius: 0.5rem; padding: 0.5rem;">
        <?= htmlspecialchars($game->getDescription()) ?>
    </div>
</div>