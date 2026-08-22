document.addEventListener('livewire:navigated', () => {
    document.documentElement.classList.add('topbar-force-hidden');
    setTimeout(() => document.documentElement.classList.remove('topbar-force-hidden'), 400);
});
