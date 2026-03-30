import re

with open('resources/js/components/alpine/stores/background.js', 'r', encoding='utf-8') as f:
    content = f.read()

# Add activePattern and availablePatterns array
store_start = r"Alpine.store\('background', \{"
new_store_start = """Alpine.store('background', {
        enabled: localStorage.getItem('backgroundEnabled') === 'true',
        patternEnabled: localStorage.getItem('patternEnabled') === 'true',
        activePattern: localStorage.getItem('activePattern') || 'particle',

        patterns: [
            { id: 'particle', name: 'ذرات معلق' },
            { id: 'parallax', name: 'پوشش سه بعدی (پارالاکس)' },
            { id: 'gradient', name: 'طیف رنگی روان' },
            { id: 'geometry', name: 'اشکال هندسی' },
            { id: 'ambient', name: 'نورپردازی محیطی' }
        ],"""

content = re.sub(
    r"Alpine\.store\('background', \{\s+enabled: localStorage\.getItem\('backgroundEnabled'\) === 'true',\s+patternEnabled: localStorage\.getItem\('patternEnabled'\) === 'true',",
    new_store_start,
    content
)

# Add setPattern method before init()
set_pattern_method = """
        setPattern(patternId) {
            this.activePattern = patternId;
            localStorage.setItem('activePattern', patternId);
            window.dispatchEvent(new CustomEvent('pattern-changed', {detail: patternId}));
        },

        init() {"""

content = re.sub(r"\s+init\(\) \{", set_pattern_method, content)

with open('resources/js/components/alpine/stores/background.js', 'w', encoding='utf-8') as f:
    f.write(content)
