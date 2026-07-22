<div class="page-container">
    <div class="box font">
        <div class="register">Profile Update Setting</div>

        <div class="avat">
            <label for="avatar" class="avatar-label">
                <span class="avatar-plus">+</span>
            </label>
            <input type="file" id="avatar" style="display:none">
        </div>

        <div class="input-group">
    <label class="field-label" for="username">USERNAME:</label>
    <input type="text" id="username" class="field-input" placeholder="Type here..." autocomplete="username">
</div>

<div class="input-group">
    <label class="field-label" for="email">EMAIL ADDRESS:</label>
    <input type="email" id="email" class="field-input" value="micheal.a@gmail.com" disabled autocomplete="email">
</div>

<div class="input-group" style="position: relative;">
    <label class="field-label" for="password">PASSWORD:</label>
    <input type="password" id="password" class="field-input" placeholder="Type here..." autocomplete="new-password">
    
    <span id="errorIcon" style="display:none; position: absolute; right: 15px; top: 38px; color: red; font-weight: bold; font-size: 18px;">
        !
    </span>         
    
    <p id="errorMessage" class="error-message">
        Password Must be at least 6 Characters contain (uppercase,lowercase,number,symbol)
    </p>
</div>

<div class="input-group">
    <label class="field-label" for="confirm">RE-CONFIRM PASSWORD:</label>
    <input type="password" id="confirm" class="field-input" placeholder="Type here..." autocomplete="new-password">
    <p id="matchErrorMessage" class="error-message" style="display: none;">
        Passwords do not match!
    </p>
</div>

        <div class="buttonsave">
            <button onclick="createApi()" class="Save-button">SAVE</button>
        </div>

   <script>
            let h1 = document.getElementById("password");
            let h2 = document.getElementById("errorIcon");
            let h3 = document.getElementById("errorMessage");

            function tellwwarnig() {
                h1.classList.add("error-border");
                h2.style.display = 'block';
                h3.style.display = 'block';
            }
                //condition for password checking and username { email not touch}
            async function createApi(){
                let userI = document.getElementById("username");
                let emailI = document.getElementById("email");
                let passI = document.getElementById("password");
                let confirmI = document.getElementById("confirm");
                let matchErrorMsg = document.getElementById("matchErrorMessage");

                if (userI.value.trim() === "" || passI.value.trim() === "") {
                    alert("Username and password cannot be null!");
                    return;
                }

                let UpCase = /[A-Z]/.test(passI.value);
                let LoCase = /[a-z]/.test(passI.value);
                let nuum = /[0-9]/.test(passI.value);
                let sym = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]+/.test(passI.value);

                if(passI.value.length < 6 || !UpCase || !LoCase || !nuum || !sym){
                    tellwwarnig();
                    return;
                }

                if (passI.value !== confirmI.value) {
                    confirmI.classList.add("error-border");
                    matchErrorMsg.style.display = 'block';
                    return;
                } else {
                    confirmI.classList.remove("error-border");
                    matchErrorMsg.style.display = 'none';
                }

                const response = await fetch('/wasd/src/app/api/profiles/index.php', {
                    method: 'PUT',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        username: userI.value,
                        email: emailI.value,
                        password: passI.value
                    })
                });

                let data = await response.json();
                console.log(data);
            }
        </script>
    </div>
</div>