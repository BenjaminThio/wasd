<?php require_once __DIR__ . '/../../models/Icon.php'; ?>
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
</div>