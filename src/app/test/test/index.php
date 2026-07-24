<button onclick="callApi()">
    Submit
</button>

<script>
    async function callApi() {
        const response = await fetch('/wasd/src/app/api/test/index.php?name=John&email=john@gmail.com', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        if (response.ok){
            let data = await response.json();
            console.log(data);
        }
    }
</script>