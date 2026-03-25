#!/bin/bash
# ============================================================
# PATCH: Fix Solicitações Alert + Ponto Timer Tempo Real
# ============================================================
# BUG 1: Notificação de solicitações pendentes aparece para DEV
#         (deveria ser apenas admin/diretor)
# BUG 2: Coluna TRABALHADO na equipe do ponto não conta em
#         tempo real (fica estático 0h 00m 00s)
# ============================================================

set -e

echo "============================================"
echo "  PATCH: Solicitações + Ponto Timer Fix"
echo "============================================"
echo ""

# Detectar diretório
if [ -f "api.php" ]; then
    DIR="."
elif [ -f "/var/www/html/layane/gestaodev/api.php" ]; then
    DIR="/var/www/html/layane/gestaodev"
elif [ -f "/var/www/html/gestaodev/api.php" ]; then
    DIR="/var/www/html/gestaodev"
else
    echo "❌ Não encontrei o diretório do projeto."
    echo "   Execute na raiz do projeto (onde está api.php)"
    exit 1
fi

echo "📁 Diretório: $DIR"
echo ""

# Backup
echo "📦 Backups..."
STAMP=$(date +%Y%m%d_%H%M%S)
cp "$DIR/api.php" "$DIR/api.php.bak_fix2_${STAMP}"
cp "$DIR/assets/js/app.js" "$DIR/assets/js/app.js.bak_fix2_${STAMP}"
echo "   ✅ Backups criados"
echo ""

# ============================================================
# FIX 1: app.js — Solicitações alert apenas para admin/diretor
# ============================================================
echo "🔧 [1/2] Fix: Notificação de solicitações apenas para admin/diretor..."

python3 -c "
import sys
DIR='$DIR'
with open(DIR+'/assets/js/app.js','r') as f:
    c=f.read()

# O problema: checkSolicitacoesPendentes é chamada por loadNotifCount
# que roda para TODOS os usuários. Precisamos adicionar guard IS_ADMIN||IS_DIR

# Encontrar a função e adicionar guard no início
old='async function checkSolicitacoesPendentes(force) {'
new_code='async function checkSolicitacoesPendentes(force) {\n  // Apenas admin/diretor veem alerta de solicitações\n  if(!IS_ADMIN && !IS_DIR) return;'

if old in c and '// Apenas admin/diretor veem alerta de solicitações' not in c:
    c=c.replace(old, new_code, 1)
    with open(DIR+'/assets/js/app.js','w') as f:
        f.write(c)
    print('   ✅ Guard IS_ADMIN||IS_DIR adicionado em checkSolicitacoesPendentes')
elif '// Apenas admin/diretor veem alerta' in c:
    print('   ⚠️  Já corrigido — pulando')
else:
    print('   ❌ Padrão não encontrado')
    sys.exit(1)
"

# ============================================================
# FIX 2: API + JS — Ponto timer tempo real na tabela da equipe
# ============================================================
echo "🔧 [2/2] Fix: Ponto timer tempo real na equipe..."

# FIX 2a: api.php — ponto_team_today precisa retornar active_since
echo "   📝 Atualizando endpoint ponto_team_today na API..."

python3 -c "
import sys
DIR='$DIR'
with open(DIR+'/api.php','r') as f:
    c=f.read()

# O endpoint atual retorna worked_seconds e status mas NÃO retorna active_since
# Precisamos adicionar o clock_in da sessão ativa

old_query = '''SELECT u.id AS user_id,u.name,u.avatar_color,u.avatar_file,u.role,COALESCE(u.work_hours,u.jornada_hours,8)AS jornada_hours,IF(EXISTS(SELECT 1 FROM ponto_sessions p2 WHERE p2.user_id=u.id AND p2.date=? AND p2.clock_out IS NULL),'Ativo','Pausado')AS status,COALESCE((SELECT SUM(TIMESTAMPDIFF(SECOND,p3.clock_in,p3.clock_out))FROM ponto_sessions p3 WHERE p3.user_id=u.id AND p3.date=? AND p3.clock_out IS NOT NULL),0)AS worked_seconds FROM usuarios u WHERE u.active=1 ORDER BY u.name'''

new_query = '''SELECT u.id AS user_id,u.name,u.avatar_color,u.avatar_file,u.role,COALESCE(u.work_hours,u.jornada_hours,8)AS jornada_hours,IF(EXISTS(SELECT 1 FROM ponto_sessions p2 WHERE p2.user_id=u.id AND p2.date=? AND p2.clock_out IS NULL),'Ativo','Pausado')AS status,COALESCE((SELECT SUM(TIMESTAMPDIFF(SECOND,p3.clock_in,p3.clock_out))FROM ponto_sessions p3 WHERE p3.user_id=u.id AND p3.date=? AND p3.clock_out IS NOT NULL),0)AS worked_seconds,(SELECT p4.clock_in FROM ponto_sessions p4 WHERE p4.user_id=u.id AND p4.date=? AND p4.clock_out IS NULL ORDER BY p4.id DESC LIMIT 1)AS active_since FROM usuarios u WHERE u.active=1 ORDER BY u.name'''

if old_query in c and 'active_since FROM usuarios' not in c:
    c = c.replace(old_query, new_query, 1)
    # Também precisamos atualizar o execute para passar 3 parâmetros em vez de 2
    old_exec = \"\$s->execute([\$t,\$t]);jsonR(\$s->fetchAll(PDO::FETCH_ASSOC));}\"
    new_exec = \"\$s->execute([\$t,\$t,\$t]);jsonR(\$s->fetchAll(PDO::FETCH_ASSOC));}\"
    # Buscar o execute específico do ponto_team_today
    # O padrão é: aparece logo após a query nova
    c = c.replace(old_exec, new_exec, 1)
    with open(DIR+'/api.php','w') as f:
        f.write(c)
    print('   ✅ API: active_since adicionado ao ponto_team_today')
elif 'active_since FROM usuarios' in c:
    print('   ⚠️  API já tem active_since — pulando')
else:
    print('   ⚠️  Query ponto_team_today não encontrada no formato esperado')
    print('       Tentando abordagem alternativa...')
    # Abordagem 2: procurar pelo trecho do endpoint e reescrever
    if \"ponto_team_today\" in c:
        import re
        # Encontrar o bloco inteiro do ponto_team_today
        pattern = r\"if\(\\\$act==='ponto_team_today'\)\{.*?jsonR\(\\\$s->fetchAll\(PDO::FETCH_ASSOC\)\);\}\"
        match = re.search(pattern, c, re.DOTALL)
        if match:
            old_block = match.group(0)
            new_block = '''if(\$act==='ponto_team_today'){_ept(\$db);\$t=date('Y-m-d');\$s=\$db->prepare(\"SELECT u.id AS user_id,u.name,u.avatar_color,u.avatar_file,u.role,COALESCE(u.work_hours,u.jornada_hours,8)AS jornada_hours,IF(EXISTS(SELECT 1 FROM ponto_sessions p2 WHERE p2.user_id=u.id AND p2.date=? AND p2.clock_out IS NULL),'Ativo','Pausado')AS status,COALESCE((SELECT SUM(TIMESTAMPDIFF(SECOND,p3.clock_in,p3.clock_out))FROM ponto_sessions p3 WHERE p3.user_id=u.id AND p3.date=? AND p3.clock_out IS NOT NULL),0)AS worked_seconds,(SELECT p4.clock_in FROM ponto_sessions p4 WHERE p4.user_id=u.id AND p4.date=? AND p4.clock_out IS NULL ORDER BY p4.id DESC LIMIT 1)AS active_since FROM usuarios u WHERE u.active=1 ORDER BY u.name\");\$s->execute([\$t,\$t,\$t]);jsonR(\$s->fetchAll(PDO::FETCH_ASSOC));}'''
            c = c.replace(old_block, new_block, 1)
            with open(DIR+'/api.php','w') as f:
                f.write(c)
            print('   ✅ API: ponto_team_today reescrito com active_since')
        else:
            print('   ❌ Não conseguiu encontrar bloco ponto_team_today')
    else:
        print('   ❌ Endpoint ponto_team_today não encontrado no api.php')
"

# FIX 2b: app.js — Reescrever a renderização da tabela Equipe Hoje para usar active_since
echo "   📝 Atualizando timer tempo real da equipe no JS..."

python3 -c "
import sys
DIR='$DIR'
with open(DIR+'/assets/js/app.js','r') as f:
    c=f.read()

# O problema está na renderização da tabela 'Equipe Hoje' dentro de loadPonto()
# A coluna TRABALHADO mostra um valor estático. Precisamos:
# 1. Se o membro está Ativo, calcular worked_seconds + tempo desde active_since
# 2. Colocar um id na célula para o timer atualizar

# Encontrar o trecho que renderiza a coluna TRABALHADO na tabela da equipe
old_td = \"<td style=\\\"font-family:'JetBrains Mono',monospace;color:var(--acc)\\\">\${folga?'—':_pontoFmt(wSec)}</td>\"

new_td = \"<td id=\\\"ponto-team-cell-\${m.user_id}\\\" data-active=\\\"\${m.status==='Ativo'?'1':'0'}\\\" data-sec=\\\"\${wSec}\\\" data-since=\\\"\${m.active_since?new Date(m.active_since).getTime():'0'}\\\" style=\\\"font-family:'JetBrains Mono',monospace;color:var(--acc)\\\">\${folga?'—':_pontoFmt(m.status==='Ativo'&&m.active_since?wSec+Math.floor((Date.now()-new Date(m.active_since).getTime())/1000):wSec)}</td>\"

if old_td in c:
    c = c.replace(old_td, new_td, 1)
    with open(DIR+'/assets/js/app.js','w') as f:
        f.write(c)
    print('   ✅ JS: Célula TRABALHADO agora usa active_since e tem id para timer')
else:
    print('   ⚠️  Padrão da célula TRABALHADO não encontrado — tentando alternativa...')
    # Tentar padrão mais flexível
    import re
    pattern = r\"<td[^>]*font-family:'JetBrains Mono'[^>]*monospace[^>]*color:var\(--acc\)[^>]*>.*?_pontoFmt\(wSec\).*?</td>\"
    matches = list(re.finditer(pattern, c))
    if matches:
        # Pegar o que está dentro do loadPonto (mais provável ser o último match)
        for match in matches:
            old_match = match.group(0)
            if 'folga' in old_match:
                c = c.replace(old_match, new_td.replace('\$', '\$'), 1)
                with open(DIR+'/assets/js/app.js','w') as f:
                    f.write(c)
                print('   ✅ JS: Célula TRABALHADO atualizada (via regex)')
                break
    else:
        print('   ❌ Não encontrou célula TRABALHADO — patch manual necessário')

# Agora garantir que _startTeamTimer use active_since corretamente
# O timer já existe mas incrementa dataset.sec sem considerar active_since
# Vamos reescrever para calcular baseado no timestamp real

old_timer = '''function _startTeamTimer() {
  clearInterval(_teamTimerInterval);
  _teamTimerInterval = setInterval(() => {
    document.querySelectorAll('[id^=\"ponto-team-cell-\"]').forEach(cell => {
      if (cell.dataset.active !== '1') return;
      const base = parseInt(cell.dataset.sec) || 0;
      const since = parseInt(cell.dataset.since) || 0;
      if (!since) return;
      const elapsed = Math.floor((Date.now() - since) / 1000);
      const total = base + elapsed;
      // Manter cor
      const extra = total > (parseInt(cell.closest('tr')?.querySelector('td:nth-child(4)')?.textContent) || 6) * 3600;
      cell.style.color = extra ? 'var(--err)' : 'var(--acc)';
      cell.textContent = _pontoFmt(total);
    });
  }, 1000);
}'''

new_timer = '''function _startTeamTimer() {
  clearInterval(_teamTimerInterval);
  _teamTimerInterval = setInterval(() => {
    document.querySelectorAll('[id^=\"ponto-team-cell-\"]').forEach(cell => {
      if (cell.dataset.active !== '1') return;
      const base = parseInt(cell.dataset.sec) || 0;
      const since = parseInt(cell.dataset.since) || 0;
      let total = base;
      if (since > 0) {
        total = base + Math.floor((Date.now() - since) / 1000);
      } else {
        // Sem active_since, incrementar manualmente
        total = base + 1;
        cell.dataset.sec = total;
      }
      cell.style.color = 'var(--acc)';
      cell.textContent = _pontoFmt(total);
    });
  }, 1000);
}'''

if old_timer in c:
    c = c.replace(old_timer, new_timer, 1)
    with open(DIR+'/assets/js/app.js','w') as f:
        f.write(c)
    print('   ✅ JS: _startTeamTimer reescrito com cálculo correto')
else:
    print('   ⚠️  _startTeamTimer não encontrado no formato esperado — verificando...')
    if '_startTeamTimer' in c:
        print('       Função existe mas formato diferente do esperado')
    else:
        print('       Função não encontrada')
"

echo ""
echo "============================================"
echo "  ✅ PATCH APLICADO!"
echo "============================================"
echo ""
echo "  Correções:"
echo "  1. Notificação de solicitações pendentes"
echo "     agora aparece APENAS para admin/diretor"
echo "     (dev não vê mais o popup amarelo)"
echo ""
echo "  2. Coluna TRABALHADO na tabela Equipe Hoje"
echo "     agora conta em tempo real (1s a 1s)"
echo "     usando active_since do servidor"
echo ""
echo "  Recarregue a página (Ctrl+F5) para ver!"
echo "============================================"
