<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', async () => {
            try {
                const existing = await navigator.serviceWorker.getRegistration('/');
                if (existing) {
                    await existing.update();
                    return;
                }
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
