with open('database/factories/UserFactory.php', 'r') as f:
    content = f.read()

content = content.replace("['remote', 'office']", "['remote', 'onsite']")

with open('database/factories/UserFactory.php', 'w') as f:
    f.write(content)
