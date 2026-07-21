<div class="page-container">
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

<script>
    async function signUp() {

        let usernameInput = document.getElementById("username");
        let emailInput = document.getElementById("email");
        let passwordInput = document.getElementVyId("password");
        let confirmPassInput = document.getElementById("confirmPass");

        let username = usernameInput.value;
        let email = emailInput.value;
        let password = passwordInput.value;
        let confirmPass = confirmPassInput.value;


        const response = await fetch(`/wasd/src/app/api/sign-up/index.php?username=${username}&email=${username}&password=${password}&confirmPass=${confirmPass}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (response.ok) {
            let data = await response.json();

            console.log(data["usernameError"]);
            if (data["usernameError"]) {
                usernameInput.style.border = "1.5px solid red";
                alert("Invalid username. Please re-type again.");
            }

            console.log(data["emailError"]);
            if (data["emailError"]) {
                emailInput.style.border = "1.5px solid red";
                alert("Invalid email. Please re-type again.");
            }

            console.log(data["passwordError"]);
            if (data["passwordError"]) {
                passwordInput.style.border = "1.5px solid red";
                alert("Invalid password. Please re-type again.")
            }

            console.log(data["confirmPassError"]);
            if (data["confirmPassError"]) {
                confirmPassInput.style.border = "1.5px solid red";
                alert("The password is not the same. Please re-type again.")
            }
        }
    }

</script>