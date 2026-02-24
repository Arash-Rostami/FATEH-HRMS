<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js').then((registration) => {
                console.log('✅ Service Worker registered! Relax, assets are served from cache.');
            }, (err) => {
                console.log('ServiceWorker registration failed: ', err);
            });
        });
    }
</script>
