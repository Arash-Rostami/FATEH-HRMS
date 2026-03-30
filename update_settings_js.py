import re

with open('resources/js/components/alpine/data/settings.js', 'r', encoding='utf-8') as f:
    content = f.read()

# Add imports for all patterns
new_imports = """import particle from "./particle.js";
import parallax from "./parallax.js";
import gradient from "./gradient.js";
import geometry from "./geometry.js";
import ambient from "./ambient.js";

const patterns = {
    particle,
    parallax,
    gradient,
    geometry,
    ambient
};
"""

content = re.sub(r'import particle from "./particle\.js";', new_imports, content)

# Update getters to expose activePattern and patterns
new_getters = """        get activePattern() {
            return Alpine.store('background').activePattern;
        },

        get availablePatterns() {
            return Alpine.store('background').patterns;
        },"""

content = re.sub(r'        get patternEnabled\(\) \{', new_getters + '\n\n        get patternEnabled() {', content)

# Update initPattern to use the active pattern
new_init_pattern = """        initPattern() {
            document.getElementById('interactive-background')?.remove();

            if (this.patternEnabled) {
                const patternFunc = patterns[this.activePattern] || patterns['particle'];
                return patternFunc();
            }
        },

        setPattern(patternId) {
            Alpine.store('background').setPattern(patternId);
            this.initPattern();
        },"""

content = re.sub(
    r"        initPattern\(\) \{\s+return \(this\.patternEnabled\)\s+\? particle\(\) : document\.getElementById\('interactive-background'\)\?\.remove\(\);\s+\},",
    new_init_pattern,
    content
)

with open('resources/js/components/alpine/data/settings.js', 'w', encoding='utf-8') as f:
    f.write(content)
