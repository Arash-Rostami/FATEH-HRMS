import re

with open('resources/views/livewire/dashboard/navbar/quick-settings.blade.php', 'r', encoding='utf-8') as f:
    content = f.read()

# Replace x-model with :checked since x-model on a getter won't work correctly without a setter in Alpine
old_input = """                            <input type="radio"
                                   name="pattern_selection"
                                   :value="pattern.id"
                                   x-model="activePattern"
                                   @change="setPattern(pattern.id)"
                                   class="w-3.5 h-3.5 text-amber-500 focus:ring-amber-500/50 bg-[var(--md-sys-color-surface-container)] border-[var(--md-sys-color-outline-variant)]">"""

new_input = """                            <input type="radio"
                                   name="pattern_selection"
                                   :value="pattern.id"
                                   :checked="activePattern === pattern.id"
                                   @change="setPattern(pattern.id)"
                                   class="w-3.5 h-3.5 text-amber-500 focus:ring-amber-500/50 bg-[var(--md-sys-color-surface-container)] border-[var(--md-sys-color-outline-variant)]">"""

content = content.replace(old_input, new_input)

with open('resources/views/livewire/dashboard/navbar/quick-settings.blade.php', 'w', encoding='utf-8') as f:
    f.write(content)
