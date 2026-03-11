import os
with open(r'c:\xampp\htdocs\AquaSafe\Login\index.php', 'r', encoding='utf-8') as f:
    body = f.read()

body = body.replace('href="login.php"', 'href="Login/login.php"')
body = body.replace('src="../assets/logo.png"', 'src="assets/logo.png"')

with open(r'c:\xampp\htdocs\AquaSafe\index.php', 'w', encoding='utf-8') as f:
    f.write(body)
print("Migration completed.")
