<div class="page-container">
    <video autoplay muted loop playsinline class="video-bg">
        <source src="<?= BASE_URL ?>/public/assets/gif.mp4" type="video/mp4">
    </video>

    <div class="box font">
        <div class="welcome">SIGN UP</div>

        <label for="avatar" class="avatar-label">
            <span class="avatar-plus">+</span>
        </label>
        <input type="file" id="avatar" style="display:none">

        <div class="input-group">
            <label class="field-label" for="username">USERNAME:</label>
            <input type="text" id="username" class="field-input" placeholder="Type here...">
        </div>

        <div class="input-group">
            <label class="field-label" for="email">EMAIL ADDRESS:</label>
            <input type="email" id="email" class="field-input" placeholder="Type here...">
        </div>

        <div class="input-group">
            <label class="field-label" for="password">PASSWORD:</label>
            <input type="password" id="password" class="field-input" placeholder="Type here...">
        </div>

        <div class="input-group">
            <label class="field-label" for="confirm">CONFIRM PASSWORD:</label>
            <input type="password" id="confirm" class="field-input" placeholder="Type here...">
        </div>

        <button class="signup-button">REGISTER</button>

        <div class="social-container">
            <?php
                require './models/Icon.php';
                echo Icon::get('facebook', 32);
                echo Icon::get('google', 32);
                echo Icon::get('discord', 32);
            ?>
        </div>

        <div class="signup-link">
            Already have an account? <a href="<?= BASE_URL ?>/sign-in">SIGN IN</a>
        </div>
    </div>
</div>