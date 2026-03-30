import re

with open('resources/js/app.js', 'r', encoding='utf-8') as f:
    content = f.read()

# Let's inspect app.js to see if pattern initialization happens on boot
print(content)
