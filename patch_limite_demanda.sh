#!/bin/bash
# ============================================================
# PATCH: Limite 1 demanda em andamento por dev
# ============================================================
# Regra: Dev só pode ter 1 demanda "Em Andamento" por vez.
# Se tentar iniciar outra:
#   1. API bloqueia e retorna as demandas atuais
#   2. JS mostra modal para dev justificar
#   3. Admin recebe notificação + modal para aprovar/rejeitar
#   4. Se aprovado, dev pode ter 2 simultâneas (temporário)
# ============================================================

set -e

echo "============================================"
echo "  PATCH: Limite 1 Demanda Simultânea"
echo "============================================"

if [ -f "api.php" ]; then DIR=".";
elif [ -f "/var/www/html/layane/gestaodev/api.php" ]; then DIR="/var/www/html/layane/gestaodev";
elif [ -f "/var/www/html/gestaodev/api.php" ]; then DIR="/var/www/html/gestaodev";
else echo "❌ Diretório não encontrado."; exit 1; fi

echo "📁 Diretório: $DIR"
STAMP=$(date +%Y%m%d_%H%M%S)
cp "$DIR/api.php" "$DIR/api.php.bak_limit_${STAMP}"
cp "$DIR/assets/js/app.js" "$DIR/assets/js/app.js.bak_limit_${STAMP}"
cp "$DIR/index.php" "$DIR/index.php.bak_limit_${STAMP}"
echo "📦 Backups criados"
echo ""

# ============================================================
# PART 1: api.php — tabela + função de verificação + endpoints
# ============================================================
echo "🔧 [1/3] Patching api.php..."

python3 << 'PYEOF'
import sys, os

DIR = "."
for d in [".", "/var/www/html/layane/gestaodev", "/var/www/html/gestaodev"]:
    if os.path.isfile(d + "/api.php"):
        DIR = d
        break

with open(DIR + "/api.php", "r") as f:
    c = f.read()

if "multi_demand_requests" in c:
    print("   ⚠️  Já existe sistema de limite — pulando api.php")
    sys.exit(0)

# 1a: Adicionar tabela multi_demand_requests na migração
migration_marker = "$_SESSION[\"_migrated\"]=1;"
if migration_marker in c:
    migration_code = '''
    // Tabela para solicitações de múltiplas demandas simultâneas
    try{$db->exec("CREATE TABLE IF NOT EXISTS multi_demand_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        demand_id INT NOT NULL,
        justification TEXT NOT NULL,
        status ENUM('Pendente','Aprovada','Rejeitada') DEFAULT 'Pendente',
        reviewed_by INT DEFAULT NULL,
        review_notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        reviewed_at DATETIME DEFAULT NULL,
        UNIQUE KEY uq_user_demand (user_id, demand_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");}catch(Exception $e){}
'''
    c = c.replace(migration_marker, migration_code + "\n    " + migration_marker, 1)
    print("   ✅ Tabela multi_demand_requests adicionada na migração")

# 1b: Adicionar função de verificação após checkDevWorkLimit
old_check = "function checkDevWorkLimit($db,$userId,$demandId=null){"
if old_check in c:
    # Reescrever a função checkDevWorkLimit
    new_check = '''function checkDevWorkLimit($db, $userId, $demandId=null) {
    // Contar demandas em andamento do dev (excluindo a atual)
    $sql = "SELECT d.id, d.title FROM devs_demandas dd 
            JOIN demandas d ON d.id = dd.demand_id 
            WHERE dd.user_id = ? AND d.status = 'Em Andamento'";
    $params = [$userId];
    if ($demandId) {
        $sql .= " AND d.id != ?";
        $params[] = $demandId;
    }
    $s = $db->prepare($sql);
    $s->execute($params);
    $activeList = $s->fetchAll(PDO::FETCH_ASSOC);
    $count = count($activeList);
    
    if ($count === 0) return ['allowed' => true];
    
    // Verificar se tem autorização aprovada para esta demanda específica
    if ($demandId) {
        $chk = $db->prepare("SELECT status FROM multi_demand_requests WHERE user_id = ? AND demand_id = ? AND status = 'Aprovada'");
        $chk->execute([$userId, $demandId]);
        if ($chk->fetch()) return ['allowed' => true];
    }
    
    // Admin pode sempre (tem todas as permissões)
    $role = $db->prepare("SELECT role FROM usuarios WHERE id = ?");
    $role->execute([$userId]);
    $userRole = $role->fetchColumn() ?: '';
    if (strpos($userRole, 'admin') !== false) return ['allowed' => true];
    
    return [
        'allowed' => false,
        'count' => $count,
        'active_demands' => $activeList
    ];
}'''
    
    # Encontrar o fim da função antiga (até o próximo })
    import re
    pattern = r'function checkDevWorkLimit\(\$db,\$userId,\$demandId=null\)\{.*?\n\}'
    match = re.search(pattern, c, re.DOTALL)
    if match:
        c = c.replace(match.group(0), new_check, 1)
        print("   ✅ checkDevWorkLimit reescrita")
    else:
        print("   ⚠️  Não conseguiu reescrever checkDevWorkLimit")

# 1c: Modificar demand_accept para verificar limite
# Encontrar o bloco onde acceptance==='Aceita' muda status para Em Andamento
old_accept_block = """if($acceptance==='Aceita'){
        if(in_array($demRow['status'],['Aberta','Aguardando Aceite'])){
            $db->prepare("UPDATE demandas SET status='Em Andamento', started_at=COALESCE(started_at,NOW()) WHERE id=?")->execute([$id]);"""

new_accept_block = """if($acceptance==='Aceita'){
        if(in_array($demRow['status'],['Aberta','Aguardando Aceite'])){
            // Verificar limite de demandas simultâneas
            $wlCheck = checkDevWorkLimit($db, $UID, $id);
            if (!$wlCheck['allowed']) {
                // Reverter aceite — precisa de autorização
                $db->prepare("UPDATE devs_demandas SET acceptance='Pendente' WHERE demand_id=? AND user_id=?")->execute([$id, $UID]);
                jsonR([
                    'needs_multi_auth' => true,
                    'active_count' => $wlCheck['count'],
                    'active_demands' => $wlCheck['active_demands'],
                    'demand_id' => $id,
                    'demand_title' => $demTitle,
                    'message' => 'Você já tem '.$wlCheck['count'].' demanda(s) em andamento. Justifique para solicitar autorização.'
                ]);
            }
            $db->prepare("UPDATE demandas SET status='Em Andamento', started_at=COALESCE(started_at,NOW()) WHERE id=?")->execute([$id]);"""

if old_accept_block in c:
    c = c.replace(old_accept_block, new_accept_block, 1)
    print("   ✅ Verificação adicionada em demand_accept")
else:
    print("   ⚠️  Bloco demand_accept não encontrado no formato esperado")

# 1d: Modificar demand_claim para verificar limite
# No demand_claim, quando muda para Em Andamento
old_claim_start = """if(in_array($demRow['status'],['Aberta','Aguardando Aceite'])){
            $db->prepare("UPDATE demandas SET status='Em Andamento', started_at=COALESCE(started_at,NOW()) WHERE id=?")->execute([$id]);
            $db->prepare("INSERT INTO historico_demandas (demand_id,user_id,action,old_value,new_value) VALUES (?,?,'Status alterado',?,'Em Andamento')")->execute([$id,$UID,$demRow['status']]);
            $admins=$db->query("SELECT id FROM usuarios WHERE (role LIKE '%admin%' OR role LIKE '%diretor%') AND active=1")->fetchAll();
            foreach($admins as $a){
                if($a['id']!=$UID){
                    notify($a['id'],'demand_status',"{$ME['name']} iniciou: {$demRow['title']}",'','demand:'.$id,'demand',$id);"""

new_claim_start = """if(in_array($demRow['status'],['Aberta','Aguardando Aceite'])){
            // Verificar limite de demandas simultâneas
            $wlCheck = checkDevWorkLimit($db, $UID, $id);
            if (!$wlCheck['allowed'] && !($d['multi_authorized']??false)) {
                jsonR([
                    'needs_multi_auth' => true,
                    'active_count' => $wlCheck['count'],
                    'active_demands' => $wlCheck['active_demands'],
                    'demand_id' => $id,
                    'demand_title' => $demRow['title'],
                    'message' => 'Você já tem '.$wlCheck['count'].' demanda(s) em andamento. Justifique para solicitar autorização.'
                ]);
            }
            $db->prepare("UPDATE demandas SET status='Em Andamento', started_at=COALESCE(started_at,NOW()) WHERE id=?")->execute([$id]);
            $db->prepare("INSERT INTO historico_demandas (demand_id,user_id,action,old_value,new_value) VALUES (?,?,'Status alterado',?,'Em Andamento')")->execute([$id,$UID,$demRow['status']]);
            $admins=$db->query("SELECT id FROM usuarios WHERE (role LIKE '%admin%' OR role LIKE '%diretor%') AND active=1")->fetchAll();
            foreach($admins as $a){
                if($a['id']!=$UID){
                    notify($a['id'],'demand_status',"{$ME['name']} iniciou: {$demRow['title']}",'','demand:'.$id,'demand',$id);"""

if old_claim_start in c:
    c = c.replace(old_claim_start, new_claim_start, 1)
    print("   ✅ Verificação adicionada em demand_claim")
else:
    print("   ⚠️  Bloco demand_claim não encontrado")

# 1e: Adicionar endpoints de solicitação/aprovação multi-demanda
# Inserir antes do "// ===== 404 FALLBACK ====="
fallback_marker = "// ===== 404 FALLBACK ====="
if fallback_marker in c:
    multi_endpoints = '''
// ===== MULTI-DEMAND AUTHORIZATION =====
if($act==='multi_demand_request'&&$method==='POST'){
    $d=json_decode(file_get_contents('php://input'),true);
    $demandId=(int)($d['demand_id']??0);
    $justification=trim($d['justification']??'');
    if(!$demandId||!$justification) jsonR(['error'=>'Demanda e justificativa obrigatórios'],400);
    
    $dem=$db->prepare("SELECT title FROM demandas WHERE id=?");$dem->execute([$demandId]);$demTitle=$dem->fetchColumn();
    
    $db->prepare("INSERT INTO multi_demand_requests (user_id,demand_id,justification) VALUES (?,?,?) ON DUPLICATE KEY UPDATE justification=VALUES(justification),status='Pendente',reviewed_by=NULL,reviewed_at=NULL")
        ->execute([$UID,$demandId,$justification]);
    
    // Notificar admins
    $admins=$db->query("SELECT id FROM usuarios WHERE (role LIKE '%admin%') AND active=1")->fetchAll();
    foreach($admins as $a){
        if($a['id']!=$UID){
            notify($a['id'],'demand_new',"{$ME['name']} solicita autorização para demanda simultânea","Demanda: {$demTitle} — Justificativa: {$justification}","demand:{$demandId}",'demand',$demandId);
            sendPushToUser($db,(int)$a['id'],['title'=>'⚠️ Autorização Necessária','message'=>"{$ME['name']} quer iniciar demanda simultânea: {$demTitle}",'url'=>'/index.php#demandas']);
        }
    }
    logActivity($UID,"Solicitou demanda simultânea: {$demTitle}",'demand',$demandId);
    jsonR(['success'=>true,'message'=>'Solicitação enviada! Aguarde aprovação do admin.']);
}

if($act==='multi_demand_review'&&$method==='POST'){
    if(!$IS_ADMIN) jsonR(['error'=>'Apenas admin pode aprovar'],403);
    $d=json_decode(file_get_contents('php://input'),true);
    $requestId=(int)($d['request_id']??0);
    $status=$d['status']??'';
    $notes=trim($d['review_notes']??'');
    if(!$requestId||!in_array($status,['Aprovada','Rejeitada'])) jsonR(['error'=>'Dados inválidos'],400);
    
    $req=$db->prepare("SELECT mr.*,u.name as user_name,d.title as demand_title FROM multi_demand_requests mr JOIN usuarios u ON mr.user_id=u.id JOIN demandas d ON mr.demand_id=d.id WHERE mr.id=?");
    $req->execute([$requestId]);$reqRow=$req->fetch();
    if(!$reqRow) jsonR(['error'=>'Solicitação não encontrada'],404);
    
    $db->prepare("UPDATE multi_demand_requests SET status=?,reviewed_by=?,review_notes=?,reviewed_at=NOW() WHERE id=?")
        ->execute([$status,$UID,$notes,$requestId]);
    
    $msg=$status==='Aprovada'
        ?"Autorização APROVADA para demanda simultânea: {$reqRow['demand_title']}"
        :"Autorização REJEITADA para demanda simultânea: {$reqRow['demand_title']}".($notes?" — {$notes}":'');
    
    notify($reqRow['user_id'],$status==='Aprovada'?'demand_status':'demand_status',$msg,$notes,"demand:{$reqRow['demand_id']}",'demand',$reqRow['demand_id']);
    sendPushToUser($db,(int)$reqRow['user_id'],['title'=>$status==='Aprovada'?'✅ Autorização Aprovada':'❌ Autorização Rejeitada','message'=>$msg,'url'=>'/index.php#demandas']);
    
    // Se aprovada, automaticamente aceitar e iniciar a demanda
    if($status==='Aprovada'){
        $db->prepare("UPDATE devs_demandas SET acceptance='Aceita',assigned_at=NOW() WHERE demand_id=? AND user_id=?")->execute([$reqRow['demand_id'],$reqRow['user_id']]);
        $curStatus=$db->prepare("SELECT status FROM demandas WHERE id=?");$curStatus->execute([$reqRow['demand_id']]);$cs=$curStatus->fetchColumn();
        if(in_array($cs,['Aberta','Aguardando Aceite'])){
            $db->prepare("UPDATE demandas SET status='Em Andamento',started_at=COALESCE(started_at,NOW()) WHERE id=?")->execute([$reqRow['demand_id']]);
            $db->prepare("INSERT INTO historico_demandas (demand_id,user_id,action,new_value,details) VALUES (?,?,'Status alterado','Em Andamento','Autorizado para demanda simultânea')")->execute([$reqRow['demand_id'],$reqRow['user_id']]);
        }
    }
    
    logActivity($UID,"Multi-demanda {$status}: {$reqRow['demand_title']} para {$reqRow['user_name']}",'demand',$reqRow['demand_id']);
    jsonR(['success'=>true]);
}

if($act==='multi_demand_pending'){
    if(!$IS_ADMIN) jsonR(['error'=>'Sem permissão'],403);
    $s=$db->prepare("SELECT mr.*,u.name as user_name,u.avatar_color,u.avatar_file,d.title as demand_title,d.priority,d.system_id,s.name as system_name,
        (SELECT COUNT(*) FROM devs_demandas dd2 JOIN demandas d2 ON dd2.demand_id=d2.id WHERE dd2.user_id=mr.user_id AND d2.status='Em Andamento') as current_active
        FROM multi_demand_requests mr 
        JOIN usuarios u ON mr.user_id=u.id 
        JOIN demandas d ON mr.demand_id=d.id 
        LEFT JOIN sistemas s ON d.system_id=s.id
        WHERE mr.status='Pendente' 
        ORDER BY mr.created_at DESC");
    $s->execute();
    jsonR($s->fetchAll());
}

'''
    c = c.replace(fallback_marker, multi_endpoints + "\n" + fallback_marker, 1)
    print("   ✅ Endpoints multi_demand_request/review/pending adicionados")

with open(DIR + "/api.php", "w") as f:
    f.write(c)

print("   ✅ api.php atualizado")
PYEOF

# ============================================================
# PART 2: app.js — Modais e lógica no frontend
# ============================================================
echo "🔧 [2/3] Patching assets/js/app.js..."

python3 << 'PYEOF'
import sys, os

DIR = "."
for d in [".", "/var/www/html/layane/gestaodev", "/var/www/html/gestaodev"]:
    if os.path.isfile(d + "/assets/js/app.js"):
        DIR = d
        break

with open(DIR + "/assets/js/app.js", "r") as f:
    c = f.read()

if "needs_multi_auth" in c and "openMultiAuthModal" in c:
    print("   ⚠️  Já existe lógica multi-auth no JS — pulando")
    sys.exit(0)

# 2a: Modificar claimDemand para tratar needs_multi_auth
old_claim_js = """async function claimDemand(id,force=false){const r=await api('demand_claim',{method:'POST',params:{id},body:{force}});if(r?.conflict){if(confirm(r.message+'\\n\\nDeseja assumir mesmo assim?')){return claimDemand(id,true)}return}if(r?.error&&!r?.success){return showToast(r.error)}closeM('m-detail');if(r?.started)showToast(IC.check+' Demanda assumida! Desenvolvimento iniciado.');else if(r?.already)showToast(IC.check+' Você já está nesta demanda.');else showToast(IC.check+' Demanda atribuída a você!"""

new_claim_js = """async function claimDemand(id,force=false,multiAuth=false){const r=await api('demand_claim',{method:'POST',params:{id},body:{force,multi_authorized:multiAuth}});if(r?.needs_multi_auth){openMultiAuthModal(r);return}if(r?.conflict){if(confirm(r.message+'\\n\\nDeseja assumir mesmo assim?')){return claimDemand(id,true)}return}if(r?.error&&!r?.success){return showToast(r.error)}closeM('m-detail');if(r?.started)showToast(IC.check+' Demanda assumida! Desenvolvimento iniciado.');else if(r?.already)showToast(IC.check+' Você já está nesta demanda.');else showToast(IC.check+' Demanda atribuída a você!"""

if old_claim_js in c:
    c = c.replace(old_claim_js, new_claim_js, 1)
    print("   ✅ claimDemand atualizado com multi_auth")
else:
    print("   ⚠️  claimDemand não encontrado no formato esperado")

# 2b: Modificar acceptDemand para tratar needs_multi_auth
old_accept_js = "async function acceptDemand(id,accept){let reason='';if(!accept){reason=prompt('Motivo da recusa:');if(reason===null)return}await api('demand_accept',{method:'POST',params:{id},body:{acceptance:accept?'Aceita':'Recusada',reason}});closeM('m-detail');"

new_accept_js = "async function acceptDemand(id,accept){let reason='';if(!accept){reason=prompt('Motivo da recusa:');if(reason===null)return}const r=await api('demand_accept',{method:'POST',params:{id},body:{acceptance:accept?'Aceita':'Recusada',reason}});if(r?.needs_multi_auth){openMultiAuthModal(r);return}closeM('m-detail');"

if old_accept_js in c:
    c = c.replace(old_accept_js, new_accept_js, 1)
    print("   ✅ acceptDemand atualizado com multi_auth")
else:
    print("   ⚠️  acceptDemand não encontrado")

# 2c: Adicionar função openMultiAuthModal + admin review
# Inserir antes de "// ===== INIT ====="
init_marker = "// ===== INIT ====="
if init_marker in c:
    multi_auth_code = '''
// ===== MULTI-DEMAND AUTHORIZATION =====
function openMultiAuthModal(data) {
  closeM('m-detail');
  let mo = document.getElementById('m-multi-auth');
  if (!mo) {
    mo = document.createElement('div');
    mo.id = 'm-multi-auth';
    mo.className = 'modal-o';
    mo.innerHTML = '<div class="modal" style="max-width:520px"><div class="modal-h"><h3 id="mma-title">Autorização Necessária</h3><button class="modal-x" onclick="closeM(\'m-multi-auth\')">×</button></div><div class="modal-b" id="mma-body"></div><div class="modal-f" id="mma-foot"></div></div>';
    document.body.appendChild(mo);
  }

  const activeList = (data.active_demands || []).map(d =>
    '<div style="display:flex;align-items:center;gap:8px;padding:8px 10px;background:var(--bg3);border-radius:8px;margin-bottom:4px;border-left:3px solid #3b82f6">' +
    '<span style="font-size:11px;font-weight:600;color:var(--t1)">#' + d.id + ' ' + esc(d.title) + '</span>' +
    '<span class="badge s-andamento" style="font-size:9px;margin-left:auto">Em Andamento</span></div>'
  ).join('');

  document.getElementById('mma-title').innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--warn)" stroke-width="2" stroke-linecap="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg> Limite de Demandas Simultâneas';

  document.getElementById('mma-body').innerHTML =
    '<div style="background:rgba(245,158,11,.08);border:1px solid var(--warn);border-radius:10px;padding:14px;margin-bottom:16px">' +
    '<div style="font-weight:700;font-size:13px;color:var(--warn);margin-bottom:6px">Você já tem ' + data.active_count + ' demanda(s) em andamento</div>' +
    '<div style="font-size:12px;color:var(--t2);line-height:1.5">A regra permite apenas <strong>1 demanda por vez</strong>. Para iniciar uma nova, é necessário autorização do administrador.</div></div>' +
    '<div style="margin-bottom:14px"><div style="font-size:10px;font-weight:700;color:var(--t3);text-transform:uppercase;margin-bottom:6px">Suas demandas ativas:</div>' + activeList + '</div>' +
    '<div style="background:var(--bg3);border-radius:10px;padding:14px;margin-bottom:14px">' +
    '<div style="font-size:12px;font-weight:600;color:var(--t1);margin-bottom:8px">Nova demanda solicitada:</div>' +
    '<div style="font-weight:700;font-size:14px;color:var(--acc)">#' + data.demand_id + ' — ' + esc(data.demand_title) + '</div></div>' +
    '<div class="fg"><label style="font-weight:600">Justificativa *</label>' +
    '<textarea id="mma-justification" rows="3" placeholder="Explique por que precisa trabalhar em duas demandas simultaneamente..." style="font-size:12px"></textarea></div>';

  document.getElementById('mma-foot').innerHTML =
    '<button class="btn btn-g" onclick="closeM(\'m-multi-auth\')">Cancelar</button>' +
    '<button class="btn btn-p" onclick="submitMultiAuthRequest(' + data.demand_id + ')">Solicitar Autorização</button>';

  openM('m-multi-auth');
}

async function submitMultiAuthRequest(demandId) {
  const justification = document.getElementById('mma-justification')?.value?.trim();
  if (!justification) return showToast('Justificativa obrigatória');
  const r = await api('multi_demand_request', {method: 'POST', body: {demand_id: demandId, justification}});
  if (r?.error) return showToast('⚠️ ' + r.error);
  closeM('m-multi-auth');
  showToast('📨 Solicitação enviada! Aguarde aprovação do admin.');
}

// Admin: verificar pendências de multi-demanda periodicamente
async function checkMultiDemandPending() {
  if (!IS_ADMIN) return;
  try {
    const pending = await api('multi_demand_pending');
    if (!pending || !pending.length) return;
    const badge = document.getElementById('b-multi');
    if (badge) { badge.textContent = pending.length; badge.style.display = ''; }
    // Mostrar toast para admin se houver novas pendências
    if (pending.length > 0 && !window._lastMultiCount) {
      showToast('⚠️ ' + pending.length + ' solicitação(ões) de demanda simultânea pendente(s)');
    }
    window._lastMultiCount = pending.length;
  } catch(e) {}
}

function openMultiDemandReview(requestId, userName, demandTitle, justification, currentActive) {
  let mo = document.getElementById('m-multi-review');
  if (!mo) {
    mo = document.createElement('div');
    mo.id = 'm-multi-review';
    mo.className = 'modal-o';
    mo.innerHTML = '<div class="modal" style="max-width:500px"><div class="modal-h"><h3>Autorizar Demanda Simultânea</h3><button class="modal-x" onclick="closeM(\'m-multi-review\')">×</button></div><div class="modal-b" id="mmr-body"></div><div class="modal-f" id="mmr-foot"></div></div>';
    document.body.appendChild(mo);
  }

  document.getElementById('mmr-body').innerHTML =
    '<div style="background:var(--bg3);border-radius:10px;padding:14px;margin-bottom:14px">' +
    '<div style="font-size:12px;color:var(--t3)">Solicitante</div>' +
    '<div style="font-size:15px;font-weight:700;color:var(--t1)">' + esc(userName) + '</div>' +
    '<div style="font-size:11px;color:var(--t3);margin-top:4px">Já possui <strong style="color:var(--warn)">' + currentActive + '</strong> demanda(s) em andamento</div></div>' +
    '<div style="background:var(--accg);border-radius:10px;padding:14px;margin-bottom:14px">' +
    '<div style="font-size:12px;color:var(--t3)">Demanda solicitada</div>' +
    '<div style="font-size:14px;font-weight:700;color:var(--acc)">' + esc(demandTitle) + '</div></div>' +
    '<div style="background:rgba(245,158,11,.08);border:1px solid var(--warn);border-radius:10px;padding:14px;margin-bottom:14px">' +
    '<div style="font-size:10px;font-weight:700;color:var(--warn);text-transform:uppercase;margin-bottom:4px">Justificativa do dev</div>' +
    '<div style="font-size:13px;color:var(--t1);font-style:italic;line-height:1.5">"' + esc(justification) + '"</div></div>' +
    '<div class="fg"><label>Observações (opcional)</label><textarea id="mmr-notes" rows="2" placeholder="Motivo da decisão..." style="font-size:12px"></textarea></div>';

  document.getElementById('mmr-foot').innerHTML =
    '<button class="btn btn-d" onclick="reviewMultiDemand(' + requestId + ',\'Rejeitada\')">Rejeitar</button>' +
    '<button class="btn btn-ok" onclick="reviewMultiDemand(' + requestId + ',\'Aprovada\')">' + IC.check + ' Autorizar</button>';

  openM('m-multi-review');
}

async function reviewMultiDemand(requestId, status) {
  const notes = document.getElementById('mmr-notes')?.value?.trim() || '';
  const r = await api('multi_demand_review', {method: 'POST', body: {request_id: requestId, status, review_notes: notes}});
  if (r?.error) return showToast('⚠️ ' + r.error);
  closeM('m-multi-review');
  showToast(status === 'Aprovada' ? '✅ Autorização concedida!' : '❌ Autorização negada');
  if (typeof loadDashboard === 'function') loadDashboard();
}

// Hook no polling para verificar multi-demand
if (typeof IS_ADMIN !== 'undefined' && IS_ADMIN) {
  setInterval(checkMultiDemandPending, 30000);
  setTimeout(checkMultiDemandPending, 3000);
}

'''
    c = c.replace(init_marker, multi_auth_code + "\n" + init_marker, 1)
    print("   ✅ Modais multi-auth adicionados no JS")

with open(DIR + "/assets/js/app.js", "w") as f:
    f.write(c)

print("   ✅ app.js atualizado")
PYEOF

# ============================================================
# PART 3: Nada no index.php — modais criados dinamicamente
# ============================================================
echo "🔧 [3/3] index.php não precisa de alteração (modais são dinâmicos)"

echo ""
echo "============================================"
echo "  ✅ PATCH APLICADO!"
echo "============================================"
echo ""
echo "  Fluxo:"
echo "  ┌─────────────────────────────────────┐"
echo "  │ Dev tenta assumir 2ª demanda        │"
echo "  │         ↓                           │"
echo "  │ Modal: 'Você já tem 1 em andamento' │"
echo "  │ Dev escreve justificativa           │"
echo "  │         ↓                           │"
echo "  │ Admin recebe notificação + push     │"
echo "  │ Admin abre modal de revisão         │"
echo "  │         ↓                           │"
echo "  │ ✅ Aprovada → demanda inicia auto   │"
echo "  │ ❌ Rejeitada → dev notificado       │"
echo "  └─────────────────────────────────────┘"
echo ""
echo "  Regras:"
echo "  • Admin sempre pode ter múltiplas"
echo "  • Dev: máximo 1, precisa autorização"
echo "  • Autorização é por demanda específica"
echo ""
echo "  Subir: api.php + assets/js/app.js"
echo "  Ctrl+F5 para testar!"
echo "============================================"
