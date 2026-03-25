#!/bin/bash
# fix_ponto_segundos2.sh
APP="assets/js/app.js"
[ ! -f "$APP" ] && echo "app.js não encontrado" && exit 1
cp "$APP" "${APP}.bak_seg2_$(date +%H%M%S)"

python3 - "$APP" << 'PYEOF'
import sys, re

path = sys.argv[1]
with open(path, 'r', encoding='utf-8') as f:
    content = f.read()

# Encontrar e mostrar a função atual
m = re.search(r'function _pontoFmt\(s\)\{[^}]+\}', content)
if m:
    print(f"Função atual:\n  {m.group()}")
    old = m.group()
    new = "function _pontoFmt(s){const h=Math.floor(s/3600),m=Math.floor((s%3600)/60),sc=s%60;return h+'h '+String(m).padStart(2,'0')+'m '+String(sc).padStart(2,'0')+'s';}"
    content = content.replace(old, new)
    print(f"Substituído por:\n  {new}")
else:
    print("AVISO: _pontoFmt não encontrado, buscando variações...")
    idx = content.find('_pontoFmt')
    if idx != -1:
        print(f"  Encontrado em posição {idx}:")
        print(f"  {repr(content[idx:idx+120])}")

with open(path, 'w', encoding='utf-8') as f:
    f.write(content)
print("Salvo")
PYEOF

echo "✓ Recarregue com Ctrl+Shift+R"
