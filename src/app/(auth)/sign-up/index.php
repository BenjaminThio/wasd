<div class="page-container">
    <div class="box font">
        <div class="welcome">SIGN UP</div>

        <label for="avatar" class="avatar-label">
            <span class="avatar-plus">+</span>
        </label>
        <input type="file" id="avatar" style="display:none">

        <div class="input-group">
            <label class="field-label" for="username">USERNAME:</label>
            <input type="text" id="username" class="field-input" placeholder="Type here..." autocomplete="username">
        </div>

        <div class="input-group">
            <label class="field-label" for="email">EMAIL ADDRESS:</label>
            <input type="email" id="email" class="field-input" placeholder="Type here..." autocomplete="email">
        </div>

        <div class="input-group">
            <label class="field-label" for="password">PASSWORD:</label>
            <input type="password" id="password" class="field-input" placeholder="Type here..." autocomplete="new-password">
        </div>

        <div class="input-group">
            <label class="field-label" for="confirm">CONFIRM PASSWORD:</label>
            <input type="password" id="confirmPass" class="field-input" placeholder="Type here..." autocomplete="new-password">
        </div>

        <p id="signup-status" class="auth-status" role="alert" hidden></p>

        <button class="signup-button" onclick="signUp()">REGISTER</button>

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

<script>
    async function signUp() {
        const usernameInput = document.getElementById("username");
        const emailInput = document.getElementById("email");
        const passwordInput = document.getElementById("password");
        const confirmPassInput = document.getElementById("confirmPass");

        const username = usernameInput.value;
        const email = emailInput.value;
        const password = passwordInput.value;
        const confirmPass = confirmPassInput.value;


        const response = await fetch(`/wasd/src/app/api/sign-up/index.php?username=${username}&email=${email}&password=${password}&confirmPass=${confirmPass}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (response.ok) {
            let data = await response.json();

            if (!data["success"])
            {
                if (data["error"] === "username") {
                    usernameInput.style.border = "1.5px solid red";
                }

                if (data["error"] === "email") {
                    emailInput.style.border = "1.5px solid red";
                }

                if (data["error"] === "password") {
                    passwordInput.style.border = "1.5px solid red";
                }

                if (data["error"] === "confirmPass") {
                    confirmPassInput.style.border = "1.5px solid red";
                }

                alert(data["message"]);
            }
            else
            {
                // success
            }
        }
    }
</script>
