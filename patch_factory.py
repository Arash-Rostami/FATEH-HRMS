with open('database/factories/UserFactory.php', 'r') as f:
    content = f.read()

content = content.replace('$this->faker', 'fake()')

with open('database/factories/UserFactory.php', 'w') as f:
    f.write(content)
