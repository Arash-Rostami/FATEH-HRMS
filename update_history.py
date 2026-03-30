import re

with open('resources/views/livewire/dashboard/reservation/partials/history.blade.php', 'r', encoding='utf-8') as f:
    content = f.read()

# Replace the button with wire:confirm
new_button = """<button x-on:click="$dispatch('open-confirmation', {
                                title: 'لغو رزرو',
                                message: 'آیا از لغو این رزرو اطمینان دارید؟',
                                confirmText: 'بله، لغو شود',
                                cancelText: 'خیر',
                                method: 'cancel',
                                params: {{ $reservation->id }}
                            })"
                            class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center text-[var(--md-sys-color-error)] bg-[var(--md-sys-color-error-container)]/0 hover:bg-[var(--md-sys-color-error-container)] opacity-100 lg:opacity-0 group-hover:opacity-100 transition-all focus:opacity-100 focus:outline-none focus:ring-2 focus:ring-[var(--md-sys-color-error)] shrink-0 ml-1"
                            title="لغو رزرو">"""

# Replace the exact block
content = re.sub(
    r'<button wire:click="cancel\({{ \$reservation->id }}\)"\s+wire:confirm="آیا از لغو این رزرو اطمینان دارید؟"\s+class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center text-\[var\(--md-sys-color-error\)\] bg-\[var\(--md-sys-color-error-container\)\]/0 hover:bg-\[var\(--md-sys-color-error-container\)\] opacity-100 lg:opacity-0 group-hover:opacity-100 transition-all focus:opacity-100 focus:outline-none focus:ring-2 focus:ring-\[var\(--md-sys-color-error\)\] shrink-0 ml-1"\s+title="لغو رزرو">',
    new_button,
    content
)

with open('resources/views/livewire/dashboard/reservation/partials/history.blade.php', 'w', encoding='utf-8') as f:
    f.write(content)
