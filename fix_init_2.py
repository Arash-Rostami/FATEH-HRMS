import re

with open('resources/js/components/alpine/data/settings.js', 'r', encoding='utf-8') as f:
    content = f.read()

# Make settings component initialize the pattern on component init
new_init = """        init() {
            this.initPattern();
        },

        get backgroundEnabled() {"""

content = re.sub(r"        get backgroundEnabled\(\) \{", new_init, content)

with open('resources/js/components/alpine/data/settings.js', 'w', encoding='utf-8') as f:
    f.write(content)
