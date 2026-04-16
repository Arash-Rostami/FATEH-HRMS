<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', async () => {
            try {
                const registration = await navigator.serviceWorker.register('/sw.js', {
                    scope: '/',
                    updateViaCache: 'none'
                });
                console.log('SW registered:', registration.scope);
            } catch (err) {
                console.error('SW registration failed:', err);
            }
        });
    }
</script>
