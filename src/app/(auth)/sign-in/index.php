<div class="page-container">
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
            <button onclick="signIn()" class="sign-in-button font">SIGN IN</button>
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

<script>
    async function signIn() {

        let emailInput = document.getElementById("email");
        let passwordInput = document.getElementById("password");

        let email = emailInput.value;
        let password = passwordInput.value;
        
        const response = await fetch(`/wasd/src/app/api/sign-in/index.php?email=${email}&password=${password}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (response.ok) {
            let data = await response.json();

            console.log(data["emailError"]);
            if (data["emailError"]) {

                emailInput.style.border = "1.5px solid red";
                alert("Invalid email. Please re-type again.");
            }

            console.log(data["passwordError"]);
            if (data["passwordError"]) {

                passwordInput.style.border = "1.5px solid red";
                alert("Invalid password. Please re-type again.");
            }
        }
    }

</script>