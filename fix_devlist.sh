#!/bin/bash
API="/var/www/html/layane/gestaodev/api.php"

python3 << 'PYEOF'
API = "/var/www/html/layane/gestaodev/api.php"
with open(API, 'r') as f:
    c = f.read()

old = """$s=$db->query("SELECT id,name,avatar_color,avatar_file,role,work_hours FROM usuarios WHERE active=1 AND role LIKE '%dev%' ORDER BY name");"""
new = """$s=$db->query("SELECT id,name,avatar_color,avatar_file,role,work_hours FROM usuarios WHERE active=1 AND (role LIKE '%dev%' OR role LIKE '%analista%' OR role LIKE '%diretor%') ORDER BY name");"""

if old in c:
    c = c.replace(old, new, 1)
    print("  ✅ dev_list: inclui analista e diretor")
else:
    print("  ❌ Padrão não encontrado")

with open(API, 'w') as f:
    f.write(c)
PYEOF
