/**
 * WASD SPA Router
 * Handles seamless page transitions, layout soft-swapping, and script revival.
 */

// Log to ensure the router is loading correctly in development
console.log("SPA Router Loaded! Current Layout is:", window.CURRENT_LAYOUT);

document.addEventListener('click', async (e) => {
    // Check if the user clicked an anchor tag (or something inside an anchor tag)
    const link = e.target.closest('a');
    
    // Ensure the link exists and points to our own internal website routing
    if (link && link.origin === window.location.origin) {
        e.preventDefault(); 
        const url = link.href;

        try {
            // 1. Fetch the new page data silently using the custom header
            const response = await fetch(url, {
                headers: { 'X-SPA-Request': 'true' }
            });
            
            if (!response.ok) throw new Error('Network error or 404/500 status returned');
            const data = await response.json();
            
            // 2. Layout Check: Do we need to change the background/shell?
            if (data.layout !== window.CURRENT_LAYOUT) {
                // Force a hard refresh to completely swap the HTML shell and videos
                window.location.href = url;
                return;
            }
            
            // 3. Soft Swap: Inject the new page content seamlessly
            document.getElementById('page-title').innerText = data.title;
            document.getElementById('dynamic-page-style').href = data.css || '';
            document.getElementById('app-root').innerHTML = data.html;
            
            // ========================================================
            // 4. THE REVIVAL ENGINE: FORCE BROWSER TO RUN INJECTED SCRIPTS
            // ========================================================
            const newScripts = document.getElementById('app-root').querySelectorAll('script');
            newScripts.forEach(script => {
                const freshScript = document.createElement('script');
                
                // Copy all attributes (like src="...")
                Array.from(script.attributes).forEach(attr => {
                    freshScript.setAttribute(attr.name, attr.value);
                });
                
                // Copy the actual inline JS code
                freshScript.textContent = script.textContent;
                
                // Swap the "dead" script with the "live" one to trigger execution
                script.parentNode.replaceChild(freshScript, script);
            });
            // ========================================================
            
            // 5. Update the URL bar and browser history without reloading
            history.pushState({}, '', url);
            window.scrollTo(0, 0);
            
        } catch (err) {
            // Fallback: If anything crashes, the network drops, or JSON parsing fails, 
            // safely do a normal page load.
            console.error("SPA Navigation Failed. Falling back to hard reload:", err);
            window.location.href = url;
        }
    }
});

// Handle the browser's Back and Forward buttons safely
window.addEventListener('popstate', () => {
    window.location.reload(); 
});