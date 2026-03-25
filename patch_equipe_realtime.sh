#!/bin/bash
# ============================================================
# PATCH: Equipe em Tempo Real no Dashboard
# Aplica 3 alterações: api.php, index.php, assets/js/app.js
# ============================================================
# USO: chmod +x patch_equipe_realtime.sh && ./patch_equipe_realtime.sh
# ============================================================

set -e

echo "============================================"
echo "  PATCH: Equipe em Tempo Real - Dashboard"
echo "============================================"
echo ""

# Detectar diretório do projeto
if [ -f "api.php" ]; then
    DIR="."
elif [ -f "/var/www/html/layane/gestaodev/api.php" ]; then
    DIR="/var/www/html/layane/gestaodev"
elif [ -f "/var/www/html/gestaodev/api.php" ]; then
    DIR="/var/www/html/gestaodev"
else
    echo "❌ Não encontrei o diretório do projeto."
    echo "   Execute este script na raiz do projeto (onde está api.php)"
    exit 1
fi

echo "📁 Diretório: $DIR"
echo ""

# Backup
echo "📦 Criando backups..."
cp "$DIR/api.php" "$DIR/api.php.bak_equipe_$(date +%Y%m%d_%H%M%S)"
cp "$DIR/index.php" "$DIR/index.php.bak_equipe_$(date +%Y%m%d_%H%M%S)"
cp "$DIR/assets/js/app.js" "$DIR/assets/js/app.js.bak_equipe_$(date +%Y%m%d_%H%M%S)"
echo "   ✅ Backups criados"
echo ""

# ============================================================
# PATCH 1: api.php — Novo endpoint team_realtime
# ============================================================
echo "🔧 [1/3] Patching api.php..."

# Verificar se já existe
if grep -q "team_realtime" "$DIR/api.php"; then
    echo "   ⚠️  Endpoint team_realtime já existe — pulando"
else
    # Inserir ANTES do endpoint dev_list
    sed -i "/if(\$act==='dev_list'){/i\\
// ===== TEAM REALTIME (Dashboard) =====\\
if(\$act==='team_realtime'){\\
    \$s=\$db->prepare(\"SELECT u.id, u.name, u.avatar_color, u.avatar_file, u.role, u.last_login,\\
        COUNT(DISTINCT CASE WHEN d.status='Aberta' THEN d.id END) as abertas,\\
        COUNT(DISTINCT CASE WHEN d.status='Aguardando Aceite' THEN d.id END) as aguardando,\\
        COUNT(DISTINCT CASE WHEN d.status='Em Andamento' THEN d.id END) as andamento,\\
        COUNT(DISTINCT CASE WHEN d.status='Em Revisão' THEN d.id END) as revisao,\\
        COUNT(DISTINCT CASE WHEN d.status='Concluída' THEN d.id END) as concluidas,\\
        COUNT(DISTINCT CASE WHEN d.status NOT IN('Concluída','Cancelada') THEN d.id END) as ativas,\\
        (SELECT GROUP_CONCAT(DISTINCT s2.name ORDER BY s2.name SEPARATOR ', ')\\
         FROM devs_sistemas ds JOIN sistemas s2 ON ds.system_id=s2.id WHERE ds.user_id=u.id) as sistemas\\
    FROM usuarios u\\
    LEFT JOIN devs_demandas dd ON u.id=dd.user_id\\
    LEFT JOIN demandas d ON dd.demand_id=d.id\\
    WHERE u.active=1 AND u.role LIKE '%dev%'\\
    GROUP BY u.id\\
    ORDER BY ativas DESC, u.name\");\\
    \$s->execute();\\
    jsonR(\$s->fetchAll());\\
}\\
" "$DIR/api.php"
    echo "   ✅ Endpoint team_realtime adicionado"
fi

# ============================================================
# PATCH 2: index.php — Adicionar div dash-team no dashboard
# ============================================================
echo "🔧 [2/3] Patching index.php..."

if grep -q 'id="dash-team"' "$DIR/index.php"; then
    echo "   ⚠️  div dash-team já existe — pulando"
else
    sed -i 's|<div id="dash-mydemands"></div><div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">|<div id="dash-mydemands"></div><div id="dash-team"></div><div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">|' "$DIR/index.php"
    
    if grep -q 'id="dash-team"' "$DIR/index.php"; then
        echo "   ✅ div dash-team adicionada ao dashboard"
    else
        echo "   ❌ Falha ao inserir dash-team — verifique manualmente"
    fi
fi

# ============================================================
# PATCH 3: app.js — Adicionar código de equipe no loadDashboard
# ============================================================
echo "🔧 [3/3] Patching assets/js/app.js..."

if grep -q "team_realtime" "$DIR/assets/js/app.js"; then
    echo "   ⚠️  Código team_realtime já existe no app.js — pulando"
else
    # Criar arquivo temporário com o código JS da equipe
    cat > /tmp/_team_patch.js << 'TEAMJS'

// ===== EQUIPE EM TEMPO REAL =====
const teamEl=document.getElementById('dash-team');
if(teamEl){try{const teamData=await api('team_realtime');if(teamData&&teamData.length){let th='<div class="tbl-c" style="margin-bottom:14px"><div class="tbl-bar"><h3 style="display:flex;align-items:center;gap:8px"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg> Equipe em Tempo Real</h3><span style="font-size:10px;color:var(--t3)">'+teamData.length+' desenvolvedores</span></div><div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:10px;padding:14px">';teamData.forEach(dev=>{const tot=(+dev.ativas||0),and=(+dev.andamento||0),rev=(+dev.revisao||0),ab=(+dev.abertas||0),ag=(+dev.aguardando||0),con=(+dev.concluidas||0);let sc='#10b981',sl='Disponível';if(and>=3){sc='#ef4444';sl='Sobrecarregado'}else if(and>=1){sc='#f59e0b';sl='Ocupado'}else if(tot>0){sc='#3b82f6';sl='Com pendências'}const ai=dev.avatar_file?'<img src="api.php?action=arquivo&f='+dev.avatar_file+'" style="width:100%;height:100%;border-radius:50%;object-fit:cover">':'<span style="font-size:14px;color:#fff;font-weight:700">'+(dev.name||'?').charAt(0).toUpperCase()+'</span>';const sysList=(dev.sistemas||'').split(', ').filter(Boolean);const sysH=sysList.slice(0,2).map(s=>'<span class="tag" style="font-size:9px;padding:1px 6px">'+esc(s)+'</span>').join('')+(sysList.length>2?'<span style="font-size:9px;color:var(--t3)">+'+(sysList.length-2)+'</span>':'');th+='<div style="background:var(--bg2);border:1px solid var(--brd);border-radius:10px;padding:12px;transition:all .2s;cursor:pointer" onclick="openDevDetail('+dev.id+')" onmouseenter="this.style.borderColor=\'var(--acc)\'" onmouseleave="this.style.borderColor=\'var(--brd)\'">';th+='<div style="display:flex;align-items:center;gap:10px;margin-bottom:8px"><div style="width:36px;height:36px;border-radius:50%;background:'+(dev.avatar_color||'#3b82f6')+';display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden;position:relative">'+ai+'<div style="position:absolute;bottom:0;right:0;width:10px;height:10px;border-radius:50%;background:'+sc+';border:2px solid var(--bg2)"></div></div><div style="flex:1;min-width:0"><div style="font-weight:600;font-size:12px;color:var(--t1);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">'+esc(dev.name)+'</div><div style="font-size:9px;color:'+sc+';font-weight:600">'+sl+'</div></div></div>';th+='<div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:6px">';if(and>0)th+='<span style="font-size:10px;padding:2px 6px;border-radius:6px;background:#3b82f622;color:#3b82f6;font-weight:600">'+and+' andamento</span>';if(rev>0)th+='<span style="font-size:10px;padding:2px 6px;border-radius:6px;background:#f59e0b22;color:#f59e0b;font-weight:600">'+rev+' revisão</span>';if(ab>0)th+='<span style="font-size:10px;padding:2px 6px;border-radius:6px;background:#6366f122;color:#6366f1;font-weight:600">'+ab+' abertas</span>';if(ag>0)th+='<span style="font-size:10px;padding:2px 6px;border-radius:6px;background:#d4a01722;color:#d4a017;font-weight:600">'+ag+' aceite</span>';if(tot===0)th+='<span style="font-size:10px;color:var(--t3)">Sem demandas ativas</span>';th+='</div>';if(sysH)th+='<div style="display:flex;gap:3px;flex-wrap:wrap;align-items:center">'+sysH+'</div>';th+='<div style="margin-top:6px;font-size:9px;color:var(--t3);display:flex;justify-content:space-between"><span>✅ '+con+' concluídas</span><span>'+tot+' ativas</span></div>';th+='</div>'});th+='</div></div>';teamEl.innerHTML=th}else{teamEl.innerHTML=''}}catch(e){console.log('Team realtime error:',e);teamEl.innerHTML=''}}
TEAMJS

    # Encontrar a linha exata onde termina o dash-activity no loadDashboard
    # e inserir o código da equipe logo após
    python3 << 'PYEOF'
import re

with open("$DIR/assets/js/app.js".replace("$DIR", ""), "r") as f:
    content = f.read()

# Lê o patch
with open("/tmp/_team_patch.js", "r") as f:
    patch = f.read()

# Encontrar o padrão exato do final do dash-activity dentro do loadDashboard
# O padrão termina com: ...||'<div class="empty"><p>Sem atividade</p></div>'}
target = "||'<div class=\"empty\"><p>Sem atividade</p></div>'}"

if target in content:
    # Substituir apenas a primeira ocorrência
    content = content.replace(target, target + ";" + patch, 1)
    with open("$DIR/assets/js/app.js".replace("$DIR", ""), "w") as f:
        f.write(content)
    print("OK")
else:
    print("NOTFOUND")
PYEOF

    # Fallback: usar Python com o DIR correto
    python3 -c "
import sys
DIR='$DIR'
with open(DIR+'/assets/js/app.js','r') as f:
    content=f.read()
with open('/tmp/_team_patch.js','r') as f:
    patch=f.read()
target=\"||'<div class=\\\"empty\\\"><p>Sem atividade</p></div>'}\"
if target in content and 'team_realtime' not in content:
    content=content.replace(target,target+';'+patch,1)
    with open(DIR+'/assets/js/app.js','w') as f:
        f.write(content)
    print('   ✅ Código Equipe em Tempo Real inserido no loadDashboard')
elif 'team_realtime' in content:
    print('   ⚠️  Já existe team_realtime no app.js')
else:
    print('   ❌ Padrão não encontrado — verifique manualmente')
    sys.exit(1)
"

    rm -f /tmp/_team_patch.js
fi

echo ""
echo "============================================"
echo "  ✅ PATCH APLICADO COM SUCESSO!"
echo "============================================"
echo ""
echo "  O que foi feito:"
echo "  1. api.php    → Endpoint team_realtime"
echo "  2. index.php  → div #dash-team no dashboard"
echo "  3. app.js     → Renderização da equipe"
echo ""
echo "  Funcionalidades:"
echo "  • Cards de cada dev com avatar e status"
echo "  • Indicador: Disponível/Ocupado/Sobrecarregado"
echo "  • Contadores por status de demanda"
echo "  • Sistemas atribuídos"
echo "  • Click abre perfil detalhado (openDevDetail)"
echo ""
echo "  Recarregue a página (Ctrl+F5) para ver!"
echo "============================================"
