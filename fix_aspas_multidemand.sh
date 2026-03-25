#!/bin/bash
# ============================================================
# FIX DEFINITIVO: Aspas quebradas no app.js
# Corrige 5 padrões de closeM/reviewMultiDemand
# ============================================================

if [ -f "assets/js/app.js" ]; then DIR=".";
elif [ -f "/var/www/html/layane/gestaodev/assets/js/app.js" ]; then DIR="/var/www/html/layane/gestaodev";
else echo "❌ Não encontrado"; exit 1; fi

FILE="$DIR/assets/js/app.js"
cp "$FILE" "$FILE.bak_aspas_$(date +%s)"
echo "📁 Backup criado"

python3 << 'PYEOF'
import sys

FILE = None
import os
for d in [".", "/var/www/html/layane/gestaodev", "/var/www/html/gestaodev"]:
    f = d + "/assets/js/app.js"
    if os.path.isfile(f):
        FILE = f
        break

if not FILE:
    print("ERRO: app.js não encontrado")
    sys.exit(1)

with open(FILE, 'r') as f:
    c = f.read()

fixes = 0

# ===== FIX 1: openMultiAuthModal innerHTML =====
old1 = 'onclick="closeM(\'m-multi-auth\')">\xd7</button></div><div class="modal-b" id="mma-body"></div><div class="modal-f" id="mma-foot"></div></div>\';'
new1 = 'onclick="closeM(\\\'m-multi-auth\\\')">\xd7</button></div><div class="modal-b" id="mma-body"></div><div class="modal-f" id="mma-foot"></div></div>\';'
if old1 in c:
    c = c.replace(old1, new1, 1)
    fixes += 1
    print("  ✅ FIX 1: innerHTML m-multi-auth")

# ===== FIX 2: Cancelar button =====
old2 = 'onclick="closeM(\'m-multi-auth\')">Cancelar</button>\' +'
new2 = 'onclick="closeM(\\\'m-multi-auth\\\')">Cancelar</button>\' +'
if old2 in c:
    c = c.replace(old2, new2, 1)
    fixes += 1
    print("  ✅ FIX 2: Cancelar m-multi-auth")

# ===== FIX 3: openMultiDemandReview innerHTML =====
old3 = 'onclick="closeM(\'m-multi-review\')">\xd7</button></div><div class="modal-b" id="mmr-body"></div><div class="modal-f" id="mmr-foot"></div></div>\';'
new3 = 'onclick="closeM(\\\'m-multi-review\\\')">\xd7</button></div><div class="modal-b" id="mmr-body"></div><div class="modal-f" id="mmr-foot"></div></div>\';'
if old3 in c:
    c = c.replace(old3, new3, 1)
    fixes += 1
    print("  ✅ FIX 3: innerHTML m-multi-review")

# ===== FIX 4: Rejeitada button =====
old4 = ",\'Rejeitada\')\">"
new4 = ",\\\'Rejeitada\\\')\">"
if old4 in c:
    c = c.replace(old4, new4, 1)
    fixes += 1
    print("  ✅ FIX 4: Rejeitada button")

# ===== FIX 5: Aprovada button =====
old5 = ",\'Aprovada\')\">"
new5 = ",\\\'Aprovada\\\')\">"
if old5 in c:
    c = c.replace(old5, new5, 1)
    fixes += 1
    print("  ✅ FIX 5: Aprovada button")

# ===== Se os padrões acima não matcharam, tentar o formato RAW =====
if fixes == 0:
    print("  ⚠️ Padrões com \\' não encontrados, tentando formato raw...")
    
    # Formato raw: aspas simples literais dentro de aspas simples (o bug original)
    # closeM('m-multi-auth') dentro de string '...'
    # Isso causa: '...closeM('m-multi-auth')...' → quebra
    
    # Approach: find the functions and rewrite them completely
    import re
    
    # Find openMultiAuthModal function and fix all closeM calls inside it
    # Pattern: onclick="closeM('m-multi-  (without escape)
    raw_old = "closeM('m-multi-auth')"
    raw_new = "closeM(\\'m-multi-auth\\')"
    count1 = c.count(raw_old)
    if count1 > 0:
        c = c.replace(raw_old, raw_new)
        fixes += count1
        print(f"  ✅ Raw fix: closeM m-multi-auth ({count1}x)")
    
    raw_old2 = "closeM('m-multi-review')"
    raw_new2 = "closeM(\\'m-multi-review\\')"
    count2 = c.count(raw_old2)
    if count2 > 0:
        c = c.replace(raw_old2, raw_new2)
        fixes += count2
        print(f"  ✅ Raw fix: closeM m-multi-review ({count2}x)")
    
    # Fix reviewMultiDemand('Rejeitada') and ('Aprovada')  
    raw_old3 = ",'Rejeitada')\""
    raw_new3 = ",\\'Rejeitada\\')\""
    count3 = c.count(raw_old3)
    if count3 > 0:
        c = c.replace(raw_old3, raw_new3)
        fixes += count3
        print(f"  ✅ Raw fix: Rejeitada ({count3}x)")
    
    raw_old4 = ",'Aprovada')\""
    raw_new4 = ",\\'Aprovada\\')\""
    count4 = c.count(raw_old4)
    if count4 > 0:
        c = c.replace(raw_old4, raw_new4)
        fixes += count4
        print(f"  ✅ Raw fix: Aprovada ({count4}x)")

print(f"\n  Total: {fixes} correções")

if fixes > 0:
    with open(FILE, 'w') as f:
        f.write(c)
    print(f"  ✅ Arquivo salvo: {FILE}")
else:
    print("  ❌ NENHUMA correção — pode já estar corrigido ou formato inesperado")
    # Debug: show lines around the problem
    lines = c.split('\n')
    for i, line in enumerate(lines):
        if 'm-multi-auth' in line or 'm-multi-review' in line:
            print(f"  Linha {i+1}: ...{line[max(0,line.index('m-multi')-20):line.index('m-multi')+40]}...")
PYEOF

echo ""
echo "============================================"
echo "  Subir: assets/js/app.js"
echo "  Ctrl+F5 para testar!"
echo "============================================"
