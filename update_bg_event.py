import re

with open('resources/js/components/alpine/stores/background.js', 'r', encoding='utf-8') as f:
    content = f.read()

# Add pattern-changed listener to store init
new_init = """        init() {
            window.addEventListener('background-toggled', (e) => {
                this.enabled = e.detail;
                if (this.enabled) this.patternEnabled = false;
            });

            window.addEventListener('pattern-toggled', (e) => {
                this.patternEnabled = e.detail;
                if (this.patternEnabled) this.enabled = false;
            });

            window.addEventListener('pattern-changed', (e) => {
                this.activePattern = e.detail;
            });
        }"""

content = re.sub(r"        init\(\) \{\s+window\.addEventListener\('background-toggled', \(e\) => \{\s+this\.enabled = e\.detail;\s+if \(this\.enabled\) this\.patternEnabled = false;\s+\}\);\s+window\.addEventListener\('pattern-toggled', \(e\) => \{\s+this\.patternEnabled = e\.detail;\s+if \(this\.patternEnabled\) this\.enabled = false;\s+\}\);\s+\}", new_init, content)

with open('resources/js/components/alpine/stores/background.js', 'w', encoding='utf-8') as f:
    f.write(content)
