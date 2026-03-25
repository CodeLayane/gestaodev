#!/bin/bash
# ============================================================
# PATCH: Fix Relatórios PDF + Tabelas
# ============================================================
# 1. PDF: "PRODUTIVIDADE POR DESENVOLVEDOR" → "DEMANDAS POR DESENVOLVEDOR"
# 2. Tabela "Demandas por Sistema": remover coluna "Canceladas"
# 3. "Média (dias)" → formato min/h em vez de dias decimais
# ============================================================

set -e

echo "============================================"
echo "  PATCH: Fix Relatórios PDF + Tabelas"
echo "============================================"

if [ -f "api.php" ]; then
    DIR="."
elif [ -f "/var/www/html/layane/gestaodev/api.php" ]; then
    DIR="/var/www/html/layane/gestaodev"
elif [ -f "/var/www/html/gestaodev/api.php" ]; then
    DIR="/var/www/html/gestaodev"
else
    echo "❌ Diretório não encontrado."
    exit 1
fi

echo "📁 Diretório: $DIR"

STAMP=$(date +%Y%m%d_%H%M%S)
cp "$DIR/assets/js/app.js" "$DIR/assets/js/app.js.bak_relfix_${STAMP}"
echo "📦 Backup criado"
echo ""

python3 << 'PYEOF'
import sys, os

DIR = "."
for d in [".", "/var/www/html/layane/gestaodev", "/var/www/html/gestaodev"]:
    if os.path.isfile(d + "/assets/js/app.js"):
        DIR = d
        break

with open(DIR + "/assets/js/app.js", "r") as f:
    c = f.read()

changes = 0

# ============================================================
# FIX 1: PDF — Título "PRODUTIVIDADE POR DESENVOLVEDOR" → "DEMANDAS POR DESENVOLVEDOR"
# ============================================================
old_pdf_title = "PRODUTIVIDADE POR DESENVOLVEDOR"
new_pdf_title = "DEMANDAS POR DESENVOLVEDOR"

count = c.count(old_pdf_title)
if count > 0:
    c = c.replace(old_pdf_title, new_pdf_title)
    print(f"   ✅ [1] PDF título corrigido ({count}x)")
    changes += 1
else:
    if new_pdf_title in c:
        print("   ⚠️  [1] Título já corrigido")
    else:
        print("   ❌ [1] Título PDF não encontrado")

# ============================================================
# FIX 2: Tabela web "Demandas por Sistema" — Remover coluna "Canceladas"
# ============================================================

# 2a: Remover header "Canceladas" do thead
old_sys_header = "<th>Abertas</th><th>Andamento</th><th>Concluídas</th><th>Canceladas</th><th>Média (dias)</th>"
new_sys_header = "<th>Abertas</th><th>Andamento</th><th>Concluídas</th><th>Tempo Médio</th>"

if old_sys_header in c:
    c = c.replace(old_sys_header, new_sys_header)
    print("   ✅ [2a] Header 'Canceladas' removido, 'Média (dias)' → 'Tempo Médio'")
    changes += 1
else:
    print("   ⚠️  [2a] Header sistema não encontrado no formato esperado")

# 2b: Remover célula canceladas e converter média dias → min/h no tbody
# O padrão atual renderiza: total, abertas, andamento, concluidas, canceladas, avg_days
old_sys_row = """html+='<tr><td style="font-weight:600">'+esc(s.name||'Sem sistema')+'</td><td style="font-weight:700">'+(s.total||0)+'</td><td>'+(s.abertas||0)+'</td><td style="color:var(--acc)">'+(s.andamento||0)+'</td><td style="color:var(--ok)">'+(s.concluidas||0)+'</td><td style="color:var(--err)">'+(s.canceladas||0)+'</td><td>'+(s.avg_days?parseFloat(s.avg_days).toFixed(1):'—')+'</td></tr>';"""

new_sys_row = """const _sAvgMin=s.avg_days?Math.round(parseFloat(s.avg_days)*24*60):0;const _sAvgTxt=_sAvgMin?(_sAvgMin<60?_sAvgMin+'min':Math.floor(_sAvgMin/60)+'h '+(_sAvgMin%60)+'m'):'—';html+='<tr><td style="font-weight:600">'+esc(s.name||'Sem sistema')+'</td><td style="font-weight:700">'+(s.total||0)+'</td><td>'+(s.abertas||0)+'</td><td style="color:var(--acc)">'+(s.andamento||0)+'</td><td style="color:var(--ok)">'+(s.concluidas||0)+'</td><td>'+_sAvgTxt+'</td></tr>';"""

if old_sys_row in c:
    c = c.replace(old_sys_row, new_sys_row)
    print("   ✅ [2b] Linha sistema: removido Canceladas, avg_days → min/h")
    changes += 1
else:
    print("   ⚠️  [2b] Padrão da linha sistema não encontrado — tentando alternativa...")
    # Tentar encontrar a parte da célula canceladas e remover
    cancel_cell = "+'</td><td style=\"color:var(--err)\">'+(s.canceladas||0)"
    if cancel_cell in c:
        c = c.replace(cancel_cell, "", 1)
        print("   ✅ [2b-alt] Célula canceladas removida")
        changes += 1
    
    # Converter avg_days para min/h
    old_avg = "+(s.avg_days?parseFloat(s.avg_days).toFixed(1):'—')+"
    new_avg = "+(_sAvgTxt)+"
    # Precisamos declarar a variável antes — inserir antes do html+=
    # Isso é mais complexo em substituição simples, então vamos fazer de outra forma
    old_avg2 = "'</td><td>'+(s.avg_days?parseFloat(s.avg_days).toFixed(1):'—')+'</td></tr>';"
    new_avg2 = "'</td><td>'+(function(){var m=s.avg_days?Math.round(parseFloat(s.avg_days)*24*60):0;return m?(m<60?m+'min':Math.floor(m/60)+'h '+(m%60)+'m'):'—'})()+'</td></tr>';"
    if old_avg2 in c:
        c = c.replace(old_avg2, new_avg2, 1)
        print("   ✅ [2b-alt2] avg_days convertido para min/h")
        changes += 1

# ============================================================
# FIX 3: PDF — Tabela "Demandas por Sistema" — Remover "Canceladas" e fix Média
# ============================================================

# 3a: Header do PDF
old_pdf_sys_h = "const bsh=['Sistema','Total','Abertas','Andamento','Concluídas','Canceladas','Tempo Médio'];"
new_pdf_sys_h = "const bsh=['Sistema','Total','Abertas','Andamento','Concluídas','Tempo Médio'];"

if old_pdf_sys_h in c:
    c = c.replace(old_pdf_sys_h, new_pdf_sys_h)
    print("   ✅ [3a] PDF header sistema: removido 'Canceladas'")
    changes += 1
else:
    # Tentar variação
    old_v2 = "['Sistema','Total','Abertas','Andamento','Concluídas','Canceladas','Tempo Médio']"
    new_v2 = "['Sistema','Total','Abertas','Andamento','Concluídas','Tempo Médio']"
    if old_v2 in c:
        c = c.replace(old_v2, new_v2, 1)
        print("   ✅ [3a-alt] PDF header sistema corrigido")
        changes += 1
    else:
        print("   ⚠️  [3a] PDF header sistema não encontrado")

# 3b: Column widths do PDF
old_pdf_sys_w = "const bsw=[62,20,20,24,24,24,24];"
new_pdf_sys_w = "const bsw=[72,22,22,26,26,30];"

if old_pdf_sys_w in c:
    c = c.replace(old_pdf_sys_w, new_pdf_sys_w)
    print("   ✅ [3b] PDF larguras colunas ajustadas")
    changes += 1
else:
    print("   ⚠️  [3b] PDF larguras não encontradas")

# 3c: PDF row data — remover canceladas e converter avg_days
old_pdf_sys_vals = "const vals=[s.name||'Sem sistema',String(s.total||0),String(s.abertas||0),String(s.andamento||0),String(s.concluidas||0),String(s.canceladas||0),s.avg_days?parseFloat(s.avg_days).toFixed(1):'—'];"
new_pdf_sys_vals = "const _pdfSysMin=s.avg_days?Math.round(parseFloat(s.avg_days)*24*60):0;const _pdfSysTxt=_pdfSysMin?(_pdfSysMin<60?_pdfSysMin+'min':Math.floor(_pdfSysMin/60)+'h '+(_pdfSysMin%60)+'m'):'—';const vals=[s.name||'Sem sistema',String(s.total||0),String(s.abertas||0),String(s.andamento||0),String(s.concluidas||0),_pdfSysTxt];"

if old_pdf_sys_vals in c:
    c = c.replace(old_pdf_sys_vals, new_pdf_sys_vals)
    print("   ✅ [3c] PDF dados sistema: sem canceladas, avg → min/h")
    changes += 1
else:
    print("   ⚠️  [3c] PDF dados sistema não encontrado no formato esperado")

# ============================================================
# FIX 4: PDF — "DEMANDAS POR SISTEMA" título (garantir consistência)
# ============================================================
# Já está como DEMANDAS POR SISTEMA no PDF — verificar
if "DEMANDAS POR SISTEMA" in c:
    print("   ✅ [4] Título PDF 'DEMANDAS POR SISTEMA' OK")
else:
    print("   ⚠️  [4] Título 'DEMANDAS POR SISTEMA' não encontrado no PDF")

# ============================================================
# FIX 5: Tabela web "Por Desenvolvedor" — header "% Conclusão" → sem ele
#         (consistência com o PDF que agora é "Demandas por Desenvolvedor")
# ============================================================
# Na web a tabela já mostra "Demandas por Desenvolvedor" - OK
# Verificar se o header da web tem "Tempo Médio" com dias
old_dev_header = "<th>Tempo Médio</th></tr></thead><tbody>';\n(byDev||[]).forEach(d=>{"
# Isso pode variar, vamos procurar o bloco inteiro

# FIX 5b: Na tabela web de devs, o tempo médio já é convertido para min/h
# Verificar se já está convertido
if "_avgMin=d.avg_days?" in c and "_avgTxt=" in c:
    print("   ✅ [5] Tabela web dev: tempo médio já em min/h")
else:
    print("   ⚠️  [5] Verificar tempo médio na tabela web de devs")

# ============================================================
# FIX 6: Excel export — Remover canceladas da aba "Por Sistema"
# ============================================================
old_excel_sys = "const sr=[['Sistema','Total','Abertas','Andamento','Concluídas','Canceladas','Tempo Médio']];"
new_excel_sys = "const sr=[['Sistema','Total','Abertas','Andamento','Concluídas','Tempo Médio']];"

if old_excel_sys in c:
    c = c.replace(old_excel_sys, new_excel_sys)
    print("   ✅ [6a] Excel header sistema corrigido")
    changes += 1
else:
    print("   ⚠️  [6a] Excel header sistema não encontrado")

# Excel row data
old_excel_sys_row = "sr.push([s.name||'—',s.total||0,s.abertas||0,s.andamento||0,s.concluidas||0,s.canceladas||0,s.avg_days||'']);"
new_excel_sys_row = "var _exSysMin=s.avg_days?Math.round(parseFloat(s.avg_days)*24*60):0;sr.push([s.name||'—',s.total||0,s.abertas||0,s.andamento||0,s.concluidas||0,_exSysMin?(_exSysMin<60?_exSysMin+'min':Math.floor(_exSysMin/60)+'h '+(_exSysMin%60)+'m'):'—']);"

if old_excel_sys_row in c:
    c = c.replace(old_excel_sys_row, new_excel_sys_row)
    print("   ✅ [6b] Excel dados sistema: sem canceladas, avg → min/h")
    changes += 1
else:
    print("   ⚠️  [6b] Excel dados sistema não encontrado")

# ============================================================
# SAVE
# ============================================================
with open(DIR + "/assets/js/app.js", "w") as f:
    f.write(c)

print(f"\n   📊 Total de correções aplicadas: {changes}")
PYEOF

echo ""
echo "============================================"
echo "  ✅ PATCH APLICADO!"
echo "============================================"
echo ""
echo "  Correções:"
echo "  1. PDF: 'PRODUTIVIDADE POR DESENVOLVEDOR'"
echo "     → 'DEMANDAS POR DESENVOLVEDOR'"
echo ""
echo "  2. Tabela 'Demandas por Sistema':"
echo "     → Coluna 'Canceladas' removida"
echo "     → 'Média (dias)' → 'Tempo Médio' em min/h"
echo ""
echo "  3. Aplica em: Web + PDF + Excel"
echo ""
echo "  Ctrl+F5 para ver!"
echo "============================================"
