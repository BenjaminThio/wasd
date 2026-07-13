<div class="main-div">
    <div class="intro">
        <div class="neon-text">PLAYER ONE, READY</div>

        <div class="WASD">
            <div class="W">W</div>
            <div class="A">A</div>
            <div class="S">S</div>
            <div class="D">D</div>
        </div>

        <div class="neon-text">
            "BEYOND THE KEYS: YOUR JOURNEY STARTS HERE"
        </div>

        <div class="caption">
            Your next obessesion <span class="start-here">starts here.</span>
        </div>

        <div class="caption2">
            WASD is the game store built for players. Browse the catalog, wishlist what you're watching
            and check out in seconds — all in one dark, neon-soaked storefront.
        </div>

        <div class="button">
            <button>Browse the store</button>
            <button>Create free account</button>
        </div>

        <div class="stats-minimal">
            <span><strong>1.2M+</strong> Downloads</span>
            <span><strong>25K</strong> Community</span>
            <span><strong>450+</strong> Active Discussions</span>
            <span class="discount"><strong>New</strong> Flash Sale</span>
        </div>

        <div class="trending"></div>
        <div class="promises">Store Promises</div>
    </div>

    <div class="cards">
        <div class="card-box">
            <span class="card-title">Secure & Verified</span>
            <div class="icon-text-wrapper">
                <div class="big-icon">
                <?php
                    require './models/Icon.php';

                    echo Icon::get('shield-halved', 60);
                ?>
                </div>
                <p>Your payments are encrypted and every game is officially licensed.</p>
            </div>
        </div>

        <div class="card-box">
            <span class="card-title">Transparent Pricing</span>
            <div class="icon-text-wrapper">
                <div class="big-icon">
                <?php
                    echo Icon::get('tag', 60);
                ?>
                </div>
                <p>The price you see is the price you pay. No hidden service fees.</p>
            </div>
        </div>

        <div class="card-box">
            <span class="card-title">Always Available</span>
            <div class="icon-text-wrapper">
                <div class="big-icon">
                <?php
                    echo Icon::get('bolt', 60);
                ?>
                </div>
                <p>24/7 support and servers optimized for fast, reliable downloads.</p>
            </div>
        </div>
    </div>

    <div class="start-library">
        <div><span class="free">Free forever</span></div>
        <div><span class="press">Press start on your library</span></div>
        <div>Join WASD to unlock your wishlist, cart and reviews.</div>
        <button onclick="window.location.href='login.php';">Sign Up Free</button>
        <button onclick="window.location.href='login.php';">I already have account</button>
    </div>
</div>