#!/bin/bash
# ============================================================
# PATCH: Fix PDF — Tabelas cortando texto
# ============================================================
# 1. TRABALHANDO AGORA: texto longo estourando colunas
# 2. PRÓXIMAS DEMANDAS: títulos cortados
# Fix: truncar textos + ajustar larguras das colunas
# ============================================================

set -e

echo "============================================"
echo "  PATCH: Fix PDF Tabelas Layout"
echo "============================================"

if [ -f "api.php" ]; then DIR=".";
elif [ -f "/var/www/html/layane/gestaodev/api.php" ]; then DIR="/var/www/html/layane/gestaodev";
elif [ -f "/var/www/html/gestaodev/api.php" ]; then DIR="/var/www/html/gestaodev";
else echo "❌ Diretório não encontrado."; exit 1; fi

echo "📁 Diretório: $DIR"
STAMP=$(date +%Y%m%d_%H%M%S)
cp "$DIR/assets/js/app.js" "$DIR/assets/js/app.js.bak_pdflayout_${STAMP}"
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
# FIX 1: TRABALHANDO AGORA — reescrever seção inteira do PDF
# O problema: wkw=[120,30,26,22,20,18] = 236mm mas título com 120mm
# não trunca no jsPDF, e textos longos sobrepõem colunas
# ============================================================

# Encontrar o bloco TRABALHANDO AGORA no PDF
# Começa com: var twid=pw-mg*2;
# Termina antes de: // CHARTS

old_trab_start = "// TRABALHANDO AGORA"
old_trab_header = "var twid=pw-mg*2;"

# Vamos substituir todo o bloco de renderização do TRABALHANDO AGORA
# Procurar desde "// TRABALHANDO AGORA" até "// CHARTS" ou "// ====== CHARTS"

import re

# Encontrar o bloco entre "TRABALHANDO AGORA" e o próximo section header no PDF
trab_pattern = r'// TRABALHANDO AGORA\n.*?(?=// ={10,}|// CHARTS)'
trab_match = re.search(trab_pattern, c, re.DOTALL)

if trab_match:
    old_trab_block = trab_match.group(0)
    
    new_trab_block = '''// TRABALHANDO AGORA
safeY(25);y+=4;
var twid=pw-mg*2;
doc.setFillColor(59,130,246);doc.roundedRect(mg,y-4,twid,10,2,2,'F');
doc.setFont('helvetica','bold');doc.setFontSize(10);doc.setTextColor(255,255,255);
doc.text('TRABALHANDO AGORA',mg+4,y+3);
var _wk=rd.working||[];
doc.setFont('helvetica','normal');doc.setFontSize(7);doc.text(_wk.length+' demanda'+(_wk.length!==1?'s':''),mg+twid-4,y+3,{align:'right'});
y+=12;
if(_wk.length){
  // Column widths: Demanda(80) Sistema(32) Dev(30) Prior(22) Tempo(22) Prazo(12) = 198
  var wkCols=[80,32,30,22,22,12];
  var wkHeaders=['Demanda','Sistema','Dev(s)','Prior.','Exec.','Prazo'];
  doc.setFillColor(40,55,85);doc.roundedRect(mg,y-4,twid,7,1,1,'F');
  var _cx=mg;
  wkHeaders.forEach(function(h,i){doc.setFont('helvetica','bold');doc.setFontSize(6);doc.setTextColor(180,195,220);doc.text(h.toUpperCase(),_cx+2,y);_cx+=wkCols[i]});
  y+=6;
  _wk.forEach(function(dm,ri){
    safeY(8);
    if(ri%2===0){doc.setFillColor(235,240,250);doc.rect(mg,y-3.5,twid,7,'F')}else{doc.setFillColor(245,248,255);doc.rect(mg,y-3.5,twid,7,'F')}
    doc.setFont('helvetica','normal');doc.setFontSize(6);doc.setTextColor(30,40,60);
    var _cx2=mg;
    // Demanda — truncar
    var _title=(dm.title||'').substring(0,48)+(dm.title&&dm.title.length>48?'...':'');
    doc.setFont('helvetica','bold');doc.text(_title,_cx2+2,y,{maxWidth:wkCols[0]-4});_cx2+=wkCols[0];
    // Sistema
    doc.setFont('helvetica','normal');doc.setTextColor(80,90,110);
    doc.text((dm.system_name||'--').substring(0,18),_cx2+2,y,{maxWidth:wkCols[1]-4});_cx2+=wkCols[1];
    // Devs
    var _dNames=(dm.devs||[]).map(function(d){return d.name}).join(', ').substring(0,18);
    doc.text(_dNames||'--',_cx2+2,y,{maxWidth:wkCols[2]-4});_cx2+=wkCols[2];
    // Prioridade badge
    var pc={'Urgente':[220,38,38],'Alta':[234,88,12],'Média':[202,138,4],'Baixa':[22,163,74]};
    var pcc=pc[dm.priority]||[100,100,100];
    doc.setFillColor(pcc[0],pcc[1],pcc[2]);doc.roundedRect(_cx2+1,y-2.5,18,5,1.5,1.5,'F');
    doc.setFont('helvetica','bold');doc.setFontSize(5);doc.setTextColor(255,255,255);doc.text(dm.priority||'--',_cx2+2.5,y+0.3);_cx2+=wkCols[3];
    // Tempo exec
    doc.setFont('helvetica','normal');doc.setFontSize(6);doc.setTextColor(59,130,246);
    var tw2='--';var _tsd2=dm.started_at||dm.start_date||dm.created_at||null;
    if(_tsd2){try{var _ts2=new Date(String(_tsd2).replace(/ /g,'T'));var _tdiff2=Date.now()-_ts2.getTime();if(_tdiff2>0&&!isNaN(_tdiff2)){var _td2=Math.floor(_tdiff2/864e5),_th2=Math.floor((_tdiff2%864e5)/36e5);tw2=_td2>0?_td2+'d '+_th2+'h':_th2+'h'}}catch(e){}}
    doc.text(tw2,_cx2+2,y,{maxWidth:wkCols[4]-4});_cx2+=wkCols[4];
    // Prazo
    doc.setTextColor(100,110,130);
    var dl2='--';if(dm.deadline){var diff3=Math.ceil((new Date(dm.deadline+'T12:00:00')-Date.now())/86400000);if(diff3<0){doc.setTextColor(220,38,38);dl2=Math.abs(diff3)+'d'}else{dl2=diff3+'d'}}
    doc.text(dl2,_cx2+2,y,{maxWidth:wkCols[5]-4});
    y+=6;
  });
  doc.setDrawColor(200,210,230);doc.line(mg,y-2,mg+twid,y-2);
}else{doc.setFont('helvetica','italic');doc.setFontSize(8);doc.setTextColor(140,150,170);doc.text('Nenhuma demanda em andamento',pw/2,y,{align:'center'});y+=6;}
y+=6;

'''
    
    c = c.replace(old_trab_block, new_trab_block, 1)
    print("   ✅ [1] TRABALHANDO AGORA reescrito com truncamento e maxWidth")
    changes += 1
else:
    print("   ⚠️  [1] Bloco TRABALHANDO AGORA não encontrado no PDF")
    # Tentar abordagem alternativa — trocar só os wkw e o truncamento
    old_wkw = "var wkw=[120,30,26,22,20,18];"
    new_wkw = "var wkw=[80,32,30,22,22,12];"
    if old_wkw in c:
        c = c.replace(old_wkw, new_wkw, 1)
        print("   ✅ [1-alt] Larguras colunas TRABALHANDO ajustadas")
        changes += 1
    
    # Truncar título
    old_title_trab = "var title=dm.title||'';"
    new_title_trab = "var title=(dm.title||'').substring(0,48)+(dm.title&&dm.title.length>48?'...':'');"
    if old_title_trab in c:
        c = c.replace(old_title_trab, new_title_trab, 1)
        print("   ✅ [1-alt] Título truncado a 48 chars")
        changes += 1

# ============================================================
# FIX 2: PRÓXIMAS DEMANDAS — ajustar larguras e truncar
# ============================================================

old_prox_h = "const ph2 = ['Demanda', 'Sistema', 'Prioridade', 'Dev(s)', 'Prazo'];"
new_prox_h = "const ph2 = ['Demanda', 'Sistema', 'Dev(s)', 'Prior.', 'Prazo'];"

if old_prox_h in c:
    c = c.replace(old_prox_h, new_prox_h, 1)
    print("   ✅ [2a] Header PRÓXIMAS ajustado")
    changes += 1

old_prox_w = "const pw2 = [70, 38, 24, 38, 28];"
new_prox_w = "const pw2 = [82, 34, 34, 24, 24];"

if old_prox_w in c:
    c = c.replace(old_prox_w, new_prox_w, 1)
    print("   ✅ [2b] Larguras PRÓXIMAS ajustadas")
    changes += 1

# Truncar título nas próximas demandas
old_prox_title = "const title = (d.title || '').substring(0, 35) + (d.title && d.title.length > 35 ? '...' : '');"
new_prox_title = "const title = (d.title || '').substring(0, 50) + (d.title && d.title.length > 50 ? '...' : '');"

if old_prox_title in c:
    c = c.replace(old_prox_title, new_prox_title, 1)
    print("   ✅ [2c] Título PRÓXIMAS truncado a 50 chars")
    changes += 1

# ============================================================
# FIX 3: Usar maxWidth em TODAS as cells do PDF para prevenir overflow
# Nas PRÓXIMAS DEMANDAS, adicionar maxWidth nos doc.text
# ============================================================

# Encontrar o bloco de renderização das próximas demandas
# O vals.forEach que renderiza cada célula
old_prox_render = """vals.forEach((v, i) => {
        doc.setTextColor(40, 50, 70);
        if(i === 4 && d.deadline) {
          const diff2 = Math.ceil((new Date(d.deadline + 'T12:00:00') - Date.now()) / 86400000);
          if(diff2 < 0) doc.setTextColor(220, 38, 38);
          else if(diff2 <= 7) doc.setTextColor(234, 88, 12);
        }
        doc.text(String(v), cx + 3, y + 4.2);
        cx += pw2[i];
      });"""

new_prox_render = """vals.forEach((v, i) => {
        doc.setTextColor(40, 50, 70);
        if(i === 3 && d.deadline) {
          const diff2 = Math.ceil((new Date(d.deadline + 'T12:00:00') - Date.now()) / 86400000);
          if(diff2 < 0) doc.setTextColor(220, 38, 38);
          else if(diff2 <= 7) doc.setTextColor(234, 88, 12);
        }
        doc.text(String(v), cx + 3, y + 4.2, {maxWidth: pw2[i] - 4});
        cx += pw2[i];
      });"""

if old_prox_render in c:
    c = c.replace(old_prox_render, new_prox_render, 1)
    print("   ✅ [3] maxWidth adicionado em PRÓXIMAS DEMANDAS")
    changes += 1
else:
    print("   ⚠️  [3] Bloco render PRÓXIMAS não encontrado")

# ============================================================
# SAVE
# ============================================================
with open(DIR + "/assets/js/app.js", "w") as f:
    f.write(c)

print(f"\n   📊 Total: {changes} correções aplicadas")
PYEOF

echo ""
echo "============================================"
echo "  ✅ PATCH APLICADO!"
echo "============================================"
echo ""
echo "  Correções no PDF:"
echo "  1. TRABALHANDO AGORA:"
echo "     → Títulos truncados (max 48 chars)"
echo "     → Colunas rebalanceadas"
echo "     → maxWidth em cada célula (previne overflow)"
echo ""
echo "  2. PRÓXIMAS DEMANDAS:"
echo "     → Títulos truncados (max 50 chars)"
echo "     → Colunas rebalanceadas"  
echo "     → maxWidth em cada célula"
echo ""
echo "  Suba: assets/js/app.js"
echo "  Ctrl+F5 e gere novo PDF para testar!"
echo "============================================"
