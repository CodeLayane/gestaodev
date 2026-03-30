#!/bin/bash
FILE="/var/www/html/layane/gestaodev/assets/js/app.js"

python3 << 'PYEOF'
FILE = "/var/www/html/layane/gestaodev/assets/js/app.js"
with open(FILE, 'r') as f:
    c = f.read()

fixes = 0

# 1. Hide ponto in sidebar (applyPerms)
old1 = "  // Ponto Digital ativo\n  const map={"
new1 = "  // Ponto Digital oculto\n  document.querySelectorAll('.sb-i[data-page=\"ponto\"]').forEach(el=>el.style.display='none');\n  const map={"
if old1 in c:
    c = c.replace(old1, new1, 1)
    fixes += 1
    print("  ✅ 1. Sidebar: ponto oculto")

# 2. Block page access (showPage)
old2 = "  // Ponto Digital ativo\n  const permMap={"
new2 = "  if(pg==='ponto'){showToast('Ponto Digital temporariamente desativado');return;}\n  const permMap={"
if old2 in c:
    c = c.replace(old2, new2, 1)
    fixes += 1
    print("  ✅ 2. Página: acesso bloqueado")

# 3. Disable initPonto
old3 = "async function initPonto(){\n  const st=await api('ponto_status');"
new3 = "async function initPonto(){ return; // DESATIVADO\n  const st=await api('ponto_status');"
if old3 in c:
    c = c.replace(old3, new3, 1)
    fixes += 1
    print("  ✅ 3. initPonto desativado")

# 4. Disable topbar timer
old4 = "async function initTopbarPontoTimer() {\n  try {"
new4 = "async function initTopbarPontoTimer() { return; // DESATIVADO\n  try {"
if old4 in c:
    c = c.replace(old4, new4, 1)
    fixes += 1
    print("  ✅ 4. Timer topbar desativado")

# 5. Remove existing timer chip if visible
old5 = "document.addEventListener('DOMContentLoaded', () => {\n  setTimeout(initTopbarPontoTimer, 1500);\n});"
new5 = "document.addEventListener('DOMContentLoaded', () => {\n  // setTimeout(initTopbarPontoTimer, 1500); // DESATIVADO\n});"
if old5 in c:
    c = c.replace(old5, new5, 1)
    fixes += 1
    print("  ✅ 5. DOMContentLoaded desativado")

old6 = "if (document.readyState !== 'loading') {\n  setTimeout(initTopbarPontoTimer, 1500);\n}"
new6 = "if (document.readyState !== 'loading') {\n  // setTimeout(initTopbarPontoTimer, 1500); // DESATIVADO\n}"
if old6 in c:
    c = c.replace(old6, new6, 1)
    fixes += 1
    print("  ✅ 6. Fallback desativado")

with open(FILE, 'w') as f:
    f.write(c)
print(f"\n  Total: {fixes}")
PYEOF

echo "  Subir: assets/js/app.js + Ctrl+F5"
