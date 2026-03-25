#!/bin/bash
# ============================================================
# PATCH: Auto Almoço 12:00-13:00
# ============================================================
# Se depois das 15h o usuário tem uma sessão contínua que
# cruza 12:00-13:00 sem pausa, o sistema automaticamente:
# 1. Fecha a sessão original às 12:00
# 2. Abre nova sessão às 13:00
# Isso é feito no lado do servidor (api.php) como uma função
# chamada nos endpoints ponto_today e ponto_status.
# ============================================================

set -e

echo "============================================"
echo "  PATCH: Auto Almoço 12:00-13:00"
echo "============================================"

# Detectar diretório
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

# Backup
STAMP=$(date +%Y%m%d_%H%M%S)
cp "$DIR/api.php" "$DIR/api.php.bak_almoco_${STAMP}"
echo "📦 Backup criado"
echo ""

echo "🔧 Adicionando auto-almoço no api.php..."

python3 << 'PYEOF'
import sys, os

DIR = os.environ.get("DIR_PATCH", ".")
# Fallback detection
for d in [".", "/var/www/html/layane/gestaodev", "/var/www/html/gestaodev"]:
    if os.path.isfile(d + "/api.php"):
        DIR = d
        break

with open(DIR + "/api.php", "r") as f:
    c = f.read()

# Check if already patched
if "autoLunchBreak" in c or "auto_lunch" in c:
    print("   ⚠️  Auto-almoço já existe — pulando")
    sys.exit(0)

# ============================================================
# 1. Adicionar a função autoLunchBreak após a função _ept
# ============================================================

lunch_function = '''
// Auto-insert lunch break 12:00-13:00 if user forgot
function autoLunchBreak($db, $userId, $date) {
    $now = new DateTime();
    $today = $now->format('Y-m-d');
    
    // Só aplica para o dia de hoje e após 15:00
    if ($date !== $today) return;
    if ((int)$now->format('H') < 15) return;
    
    // Verificar se já tem uma pausa que cobre 12:00-13:00
    // (ou seja, alguma sessão termina entre 11:30-12:30 E outra começa entre 12:30-13:30)
    $lunchStart = $date . ' 12:00:00';
    $lunchEnd = $date . ' 13:00:00';
    
    // Buscar sessões do dia
    $st = $db->prepare("SELECT id, clock_in, clock_out FROM ponto_sessions WHERE user_id = ? AND date = ? ORDER BY clock_in ASC");
    $st->execute([$userId, $date]);
    $sessions = $st->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($sessions)) return;
    
    // Verificar se já existe uma gap que cobre ~12:00-13:00
    for ($i = 0; $i < count($sessions) - 1; $i++) {
        $curOut = $sessions[$i]['clock_out'];
        $nextIn = $sessions[$i + 1]['clock_in'];
        if ($curOut && $nextIn) {
            $outTime = strtotime($curOut);
            $inTime = strtotime($nextIn);
            // Se existe um gap entre 11:30 e 13:30 de pelo menos 30min, consideramos como almoço já feito
            $gapStart = strtotime($date . ' 11:30:00');
            $gapEnd = strtotime($date . ' 13:30:00');
            if ($outTime >= $gapStart && $outTime <= $gapEnd && $inTime >= $gapStart && $inTime <= $gapEnd) {
                $gapMinutes = ($inTime - $outTime) / 60;
                if ($gapMinutes >= 30) return; // Já tem pausa de almoço
            }
        }
    }
    
    // Verificar se alguma sessão CRUZA o período 12:00-13:00 continuamente
    $lunchStartTs = strtotime($lunchStart);
    $lunchEndTs = strtotime($lunchEnd);
    
    foreach ($sessions as $sess) {
        $inTs = strtotime($sess['clock_in']);
        $outTs = $sess['clock_out'] ? strtotime($sess['clock_out']) : time();
        
        // Sessão começou antes das 12:00 e termina (ou ainda ativa) depois das 13:00
        if ($inTs < $lunchStartTs && $outTs > $lunchEndTs) {
            // Esta sessão cruza 12:00-13:00 sem pausa — inserir almoço automático
            $sessId = $sess['id'];
            $wasActive = empty($sess['clock_out']); // sessão ainda ativa?
            
            // 1. Fechar sessão original às 12:00
            $db->prepare("UPDATE ponto_sessions SET clock_out = ? WHERE id = ?")
                ->execute([$lunchStart, $sessId]);
            
            // 2. Criar nova sessão começando às 13:00
            if ($wasActive) {
                // Sessão estava ativa — nova sessão às 13:00 sem clock_out (continua ativa)
                $db->prepare("INSERT INTO ponto_sessions (user_id, date, clock_in) VALUES (?, ?, ?)")
                    ->execute([$userId, $date, $lunchEnd]);
            } else {
                // Sessão já estava fechada — nova sessão 13:00 até o clock_out original
                $db->prepare("INSERT INTO ponto_sessions (user_id, date, clock_in, clock_out) VALUES (?, ?, ?, ?)")
                    ->execute([$userId, $date, $lunchEnd, $sess['clock_out']]);
            }
            
            // Log
            try {
                $db->prepare("INSERT INTO registro_atividades (user_id, action, entity_type, entity_id, details) VALUES (?, ?, ?, ?, ?)")
                    ->execute([$userId, 'Almoço automático 12:00-13:00', 'ponto', $sessId, 'Sessão dividida automaticamente (esqueceu de pausar)']);
            } catch (Exception $e) {}
            
            return; // Só precisa tratar uma sessão
        }
    }
}

'''

# Inserir a função APÓS a função _ept
# Encontrar o fim da função _ept
ept_marker = "function _ept($db){"
if ept_marker not in c:
    # Tentar formato alternativo
    ept_marker = "function _ept($db)"

if ept_marker in c:
    # Encontrar o fim da linha da função _ept (ela é uma one-liner terminando em })
    idx = c.index(ept_marker)
    # Encontrar o próximo \n após essa linha
    end_idx = c.index("\n", idx)
    # Inserir a função lunch logo depois
    c = c[:end_idx+1] + lunch_function + c[end_idx+1:]
    print("   ✅ Função autoLunchBreak adicionada")
else:
    print("   ❌ Não encontrou função _ept — inserindo antes dos endpoints de ponto")
    # Fallback: inserir antes do primeiro endpoint de ponto
    ponto_marker = "if($act==='ponto_status')"
    if ponto_marker in c:
        c = c.replace(ponto_marker, lunch_function + "\n" + ponto_marker, 1)
        print("   ✅ Função autoLunchBreak adicionada (fallback)")
    else:
        print("   ❌ Não conseguiu inserir a função")
        sys.exit(1)

# ============================================================
# 2. Chamar autoLunchBreak nos endpoints ponto_today e ponto_status
# ============================================================

# ponto_status: adicionar chamada após _ept($db)
# Formato atual: if($act==='ponto_status'){_ept($db);$t=date('Y-m-d');
old_status = "if($act==='ponto_status'){_ept($db);$t=date('Y-m-d');"
new_status = "if($act==='ponto_status'){_ept($db);$t=date('Y-m-d');autoLunchBreak($db,$UID,$t);"

if old_status in c:
    c = c.replace(old_status, new_status, 1)
    print("   ✅ autoLunchBreak chamado em ponto_status")
else:
    print("   ⚠️  Padrão ponto_status não encontrado — tentando alternativo")

# ponto_today: adicionar chamada
old_today = "if($act==='ponto_today'){_ept($db);$t=date('Y-m-d');"
new_today = "if($act==='ponto_today'){_ept($db);$t=date('Y-m-d');autoLunchBreak($db,$UID,$t);"

if old_today in c:
    c = c.replace(old_today, new_today, 1)
    print("   ✅ autoLunchBreak chamado em ponto_today")
else:
    print("   ⚠️  Padrão ponto_today não encontrado")

# ponto_team_today: chamar para cada membro
# Isso é mais complexo — vamos adicionar um loop após buscar os dados
old_team = "if($act==='ponto_team_today'){_ept($db);$t=date('Y-m-d');"
new_team = "if($act==='ponto_team_today'){_ept($db);$t=date('Y-m-d');$_allU=$db->query(\"SELECT id FROM usuarios WHERE active=1\");foreach($_allU->fetchAll()as$_u)autoLunchBreak($db,$_u['id'],$t);"

if old_team in c:
    c = c.replace(old_team, new_team, 1)
    print("   ✅ autoLunchBreak chamado para toda equipe em ponto_team_today")
else:
    print("   ⚠️  Padrão ponto_team_today não encontrado")

with open(DIR + "/api.php", "w") as f:
    f.write(c)

print("")
print("   🎯 Resumo da lógica:")
print("   • Após 15:00, verifica sessões do dia")
print("   • Se sessão cruza 12:00-13:00 sem pausa:")
print("     → Fecha sessão às 12:00")
print("     → Abre nova às 13:00 (mantém ativa se estava)")
print("   • Se já tem gap de ≥30min entre 11:30-13:30: ignora")
print("   • Registra no log de atividades")
PYEOF

echo ""
echo "============================================"
echo "  ✅ PATCH APLICADO!"
echo "============================================"
echo ""
echo "  Como funciona:"
echo "  • Após 15h, ao acessar o Ponto Digital"
echo "  • Se alguém bateu ponto de manhã e não pausou:"
echo "    Sessão: 08:00 ──────────── 17:00 (contínua)"
echo "    Vira:   08:00 ── 12:00 | 13:00 ── 17:00"
echo "  • O almoço 12:00-13:00 é inserido automaticamente"
echo "  • Se já pausou manualmente: nada muda"
echo "  • Registrado no log de auditoria"
echo ""
echo "  Ctrl+F5 para ver!"
echo "============================================"
