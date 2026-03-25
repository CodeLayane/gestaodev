#!/bin/bash
# ============================================================
# PATCH: Fix tempo trabalhado da equipe (incluir sessão ativa)
# ============================================================
# O problema: worked_seconds só soma sessões FECHADAS.
# A sessão ATIVA (sem clock_out) é ignorada, então quem está
# trabalhando aparece com tempo menor que o real.
# Fix: somar TAMBÉM o TIMESTAMPDIFF da sessão aberta até NOW()
# ============================================================

set -e

echo "============================================"
echo "  PATCH: Fix Ponto Equipe - Sessão Ativa"
echo "============================================"

# Detectar diretório
if [ -f "api.php" ]; then
    DIR="."
elif [ -f "/var/www/html/layane/gestaodev/api.php" ]; then
    DIR="/var/www/html/layane/gestaodev"
elif [ -f "/var/www/html/gestaodev/api.php" ]; then
    DIR="/var/www/html/gestaodev"
else
    echo "❌ Diretório não encontrado. Execute na raiz do projeto."
    exit 1
fi

echo "📁 Diretório: $DIR"

# Backup
STAMP=$(date +%Y%m%d_%H%M%S)
cp "$DIR/api.php" "$DIR/api.php.bak_ponto_${STAMP}"
echo "📦 Backup criado"
echo ""

echo "🔧 Corrigindo endpoint ponto_team_today..."

python3 << 'PYEOF'
import sys, re

DIR = "$DIR".strip()
if not DIR or DIR == "$DIR":
    # fallback
    import os
    for d in [".", "/var/www/html/layane/gestaodev", "/var/www/html/gestaodev"]:
        if os.path.isfile(d + "/api.php"):
            DIR = d
            break

with open(DIR + "/api.php", "r") as f:
    c = f.read()

# Encontrar o bloco inteiro do ponto_team_today usando regex
pattern = r"if\(\$act==='ponto_team_today'\)\{.*?\}"
match = re.search(pattern, c, re.DOTALL)

if not match:
    print("   ❌ Endpoint ponto_team_today não encontrado")
    sys.exit(1)

old_block = match.group(0)

# Nova versão: worked_seconds soma sessões fechadas + sessão ativa (até NOW())
new_block = """if($act==='ponto_team_today'){_ept($db);$t=date('Y-m-d');$s=$db->prepare("SELECT u.id AS user_id,u.name,u.avatar_color,u.avatar_file,u.role,COALESCE(u.work_hours,u.jornada_hours,8)AS jornada_hours,IF(EXISTS(SELECT 1 FROM ponto_sessions p2 WHERE p2.user_id=u.id AND p2.date=? AND p2.clock_out IS NULL),'Ativo','Pausado')AS status,( COALESCE((SELECT SUM(TIMESTAMPDIFF(SECOND,p3.clock_in,p3.clock_out)) FROM ponto_sessions p3 WHERE p3.user_id=u.id AND p3.date=? AND p3.clock_out IS NOT NULL),0) + COALESCE((SELECT TIMESTAMPDIFF(SECOND,p5.clock_in,NOW()) FROM ponto_sessions p5 WHERE p5.user_id=u.id AND p5.date=? AND p5.clock_out IS NULL ORDER BY p5.id DESC LIMIT 1),0) )AS worked_seconds,(SELECT p4.clock_in FROM ponto_sessions p4 WHERE p4.user_id=u.id AND p4.date=? AND p4.clock_out IS NULL ORDER BY p4.id DESC LIMIT 1)AS active_since FROM usuarios u WHERE u.active=1 ORDER BY u.name");$s->execute([$t,$t,$t,$t]);jsonR($s->fetchAll(PDO::FETCH_ASSOC));}"""

c = c.replace(old_block, new_block, 1)

with open(DIR + "/api.php", "w") as f:
    f.write(c)

print("   ✅ ponto_team_today agora inclui sessão ativa no worked_seconds")
PYEOF

echo ""
echo "============================================"
echo "  ✅ CORRIGIDO!"
echo "============================================"
echo ""
echo "  Antes: worked_seconds = só sessões fechadas"
echo "  Agora: worked_seconds = fechadas + ativa (até NOW())"
echo ""
echo "  O tempo da equipe agora bate com o card HOJE."
echo "  Ctrl+F5 para ver!"
echo "============================================"
