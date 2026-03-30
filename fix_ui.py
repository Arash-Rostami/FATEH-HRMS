import re

with open('resources/views/livewire/dashboard/navbar/quick-settings.blade.php', 'r', encoding='utf-8') as f:
    content = f.read()

# Make sure the UI has RTL support by using right margin/border instead of left
content = content.replace('border-r-2 border-amber-500/30 space-y-1', 'border-r-2 border-amber-500/30 mr-4 pr-3 space-y-1')
content = content.replace('ml-2 pl-4 pr-2', '')

with open('resources/views/livewire/dashboard/navbar/quick-settings.blade.php', 'w', encoding='utf-8') as f:
    f.write(content)
