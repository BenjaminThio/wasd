<div class="page-container">
    <video autoplay muted loop playsinline class="video-bg">
        <source src="<?= BASE_URL ?>/public/assets/gif.mp4" type="video/mp4">
    </video>

    <div class="box font">
        <div class="welcome">WELCOME</div>

        <div class="input-group">Your wishlist, cart and reviews are waiting where you left them.</div>

        <div class="input-group">
            <label class="field-label" for="email">EMAIL ADDRESS:</label>
            <input type="email" id="email" class="field-input" placeholder="Type here...">
        </div>

        <div class="input-group">
            <label class="field-label" for="password">PASSWORD:</label>
            <input type="password" id="password" class="field-input" placeholder="Type here...">
        </div>

        <div class="remember-me">
            <input type="checkbox" id="remember-box">
            <label for="remember-box">REMEMBER ME</label>
        </div>

        <div>
            <button class="login-button font">LOGIN</button>
        </div>

        <div class="social-container">
            <?php
                require './models/Icon.php';

                echo Icon::get('facebook', 32);
                echo Icon::get('google', 32);
                echo Icon::get('discord', 32);
            ?>
        </div>

        <div class="signup-link">
            New to WASD? <a href="<?= BASE_URL ?>/sign-up">SIGN UP</a>
        </div>
    </div>
</div>
