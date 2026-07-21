<div style="background-color:rgba(255,255,255,0.03);border:1px solid var(--stroke);border-radius:1rem;padding:1.5rem;display:flex;flex-direction:column;gap:1rem;">
    <div style="display:flex;justify-content:space-between;">
        <div style="display:flex;align-items:center;gap:0.8rem;">
            <div style="color:var(--violet);background-color:rgba(138, 92, 246, 0.2);display:inline-flex;justify-content:center;align-items:center;padding:0.35rem;border:1px solid rgba(138, 92, 246, 0.5);border-radius:0.5rem;">
                <?= Icon::get('user', 16) ?>
            </div>
            <strong style="font-family:Outfit;font-size:14.5px;">
                <?= htmlspecialchars($review->getUser()->getUsername()) ?>
            </strong>
            <?php if ($review->isEnjoy()): ?>
                <div style="color:var(--green);border:1px solid rgba(52, 211, 153, 0.5);background-color:rgba(52, 211, 153, 0.2);display:inline-flex;justify-content:center;align-items:center;padding:0.35rem;border-radius:0.5rem;">
                    <?= Icon::get('thumbs-up', 16) ?>
                </div>
            <?php else: ?>
                <div style="color:var(--magenta);border:1px solid rgba(255, 45, 118, 0.5);background-color:rgba(255, 45, 118, 0.2);display:inline-flex;justify-content:center;align-items:center;padding:0.35rem;border-radius:0.5rem;">
                    <?= Icon::get('thumbs-down', 16) ?>
                </div>
            <?php endif; ?>
        </div>
        <div style="display:flex;align-items:center;font-size:12px;color:var(--dim);font-family:JetBrains Mono;">
            <?= htmlspecialchars($review->getFormattedDate()) ?>
        </div>
    </div>
    <div style="font-family:'Outfit';color:var(--muted);font-size:15px;line-height:1.7;">
        <?= nl2br(htmlspecialchars($review->getDescription())) ?>
    </div>
</div>