// The Next.js SPA Router Logic
// Bake the current layout path into the JS memory on initial load
document.addEventListener('click', async (e) => {
    const link = e.target.closest('a');
    
    if (link && link.origin === window.location.origin) {
        e.preventDefault(); 
        const url = link.href;

        try {
            const response = await fetch(url, {
                headers: { 'X-SPA-Request': 'true' }
            });
            
            if (!response.ok) throw new Error('Network error');
            const data = await response.json();

            // Check whether the layout change and if navigating from Store -> Auth, the layouts won't match.
            if (data.layout !== CURRENT_LAYOUT) {
                // Force a hard refresh to switch layouts entirely
                window.location.href = url;
                return;
            }

            // If the layout is the same, seamlessly swap the injection zone
            document.getElementById('page-title').innerText = data.title;
            document.getElementById('dynamic-page-style').href = data.css || '';
            document.getElementById('app-root').innerHTML = data.html;
            
            history.pushState({}, '', url);
            window.scrollTo(0, 0);
            
        } catch (err) {
            window.location.href = url;
        }
    }
});

window.addEventListener('popstate', () => {
    window.location.reload(); 
});