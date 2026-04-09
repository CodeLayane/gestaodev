#!/bin/bash
API="/var/www/html/layane/gestaodev/api.php"
FILE="/var/www/html/layane/gestaodev/assets/js/app.js"

# ══════════════════════════════════════════
# 1. API: tabela config + endpoint auto_process + endpoint config
# ══════════════════════════════════════════
python3 << 'PYEOF'
API = "/var/www/html/layane/gestaodev/api.php"
with open(API, 'r') as f:
    c = f.read()

# Add config table creation in migration block
old_migrated = "$_SESSION[\"_migrated\"]=1;"
new_migrated = """$_SESSION["_migrated"]=1;
    // Tabela de configurações do sistema
    try{$db->exec("CREATE TABLE IF NOT EXISTS system_config (
        config_key VARCHAR(100) PRIMARY KEY,
        config_value TEXT,
        updated_by INT DEFAULT NULL,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    // Defaults
    $db->exec("INSERT IGNORE INTO system_config (config_key,config_value) VALUES ('auto_approve_solicitations','1'),('auto_complete_reviews','1'),('auto_timeout_hours','6')");
    }catch(Exception $e){}"""

if "system_config" not in c:
    c = c.replace(old_migrated, new_migrated, 1)
    print("  ✅ 1. Tabela system_config criada")

# Add auto_process endpoint before the 404 fallback
old_fallback = "// ===== 404 FALLBACK ====="
new_auto = """// ===== AUTO-PROCESS (automações) =====
if($act==='auto_process'){
    // Buscar configs
    $cfgs=[];
    try{$rows=$db->query("SELECT config_key,config_value FROM system_config")->fetchAll();foreach($rows as $r)$cfgs[$r['config_key']]=$r['config_value'];}catch(Exception $e){}
    $autoApproveSol=(int)($cfgs['auto_approve_solicitations']??0);
    $autoCompleteRev=(int)($cfgs['auto_complete_reviews']??0);
    $timeoutH=(int)($cfgs['auto_timeout_hours']??6);
    $processed=['solicitations'=>0,'reviews'=>0];

    // Calcular datetime limite (6h úteis = aprox 6h em horário comercial)
    // Simplificação: usar 6h reais por enquanto, considerando que a maioria cai em horário útil
    $limitDate=date('Y-m-d H:i:s',strtotime("-{$timeoutH} hours"));

    // 1. Auto-aprovar solicitações pendentes há mais de X horas
    if($autoApproveSol){
        $pending=$db->prepare("SELECT s.*,sy.name as system_name FROM solicitacoes s LEFT JOIN sistemas sy ON s.system_id=sy.id WHERE s.status='Pendente' AND s.created_at<=?");
        $pending->execute([$limitDate]);
        $sols=$pending->fetchAll();
        foreach($sols as $sol){
            // Auto-aprovar e converter em demanda
            $db->prepare("UPDATE solicitacoes SET status='Aprovada',reviewed_by=NULL,reviewed_at=NOW(),review_notes='Auto-aprovada após {$timeoutH}h sem análise' WHERE id=?")->execute([$sol['id']]);
            
            // Criar demanda
            $solCreator=$db->prepare("SELECT name FROM usuarios WHERE id=?");$solCreator->execute([$sol['created_by']??0]);$creatorName=$solCreator->fetchColumn()?:('Solicitação #'.$sol['id']);
            $db->prepare("INSERT INTO demandas (title,description,system_id,priority,status,requester,needs_presidency_approval,from_solicitation_id,created_by) VALUES (?,?,?,?,'Aguardando Aceite',?,0,?,?)")
                ->execute([$sol['title'],$sol['description']??'',$sol['system_id'],$sol['priority']??'Média',$creatorName,$sol['id'],$sol['created_by']??$UID]);
            $did=$db->lastInsertId();
            $db->prepare("UPDATE solicitacoes SET status='Convertida',converted_demand_id=? WHERE id=?")->execute([$did,$sol['id']]);
            
            // Atribuir devs do sistema automaticamente
            if($sol['system_id']){
                $sysDevs=$db->prepare("SELECT user_id FROM devs_sistemas WHERE system_id=?");$sysDevs->execute([$sol['system_id']]);
                $devIds=$sysDevs->fetchAll(PDO::FETCH_COLUMN);
                $ins=$db->prepare("INSERT IGNORE INTO devs_demandas (demand_id,user_id,assigned_by) VALUES (?,?,0)");
                foreach($devIds as $dvId){
                    $ins->execute([$did,$dvId]);
                    notify($dvId,'demand_assigned',"Nova demanda (auto-aprovada): {$sol['title']}",'',"demand:{$did}",'demand',$did);
                    sendPushToUser($db,(int)$dvId,['title'=>'📋 Nova Demanda (Auto)','message'=>"Solicitação auto-aprovada: {$sol['title']}",'url'=>'/index.php#demandas']);
                }
            }
            
            // Histórico
            $db->prepare("INSERT INTO historico_demandas (demand_id,user_id,action,new_value,details) VALUES (?,0,'Auto-aprovada','Aguardando Aceite',?)")->execute([$did,"Solicitação #{$sol['id']} auto-aprovada após {$timeoutH}h sem análise"]);
            
            // Notificar admins
            $admins=$db->query("SELECT id FROM usuarios WHERE (role LIKE '%admin%' OR role LIKE '%diretor%') AND active=1")->fetchAll();
            foreach($admins as $a){
                notify($a['id'],'solicitation',"Solicitação #{$sol['id']} auto-aprovada (sem análise em {$timeoutH}h)","Título: {$sol['title']}","demand:{$did}",'demand',$did);
            }
            // Notificar solicitante
            if($sol['created_by']){
                notify($sol['created_by'],'solicitation_approved',"Sua solicitação #{$sol['id']} foi aprovada automaticamente e virou demanda #{$did}",'',"demand:{$did}",'demand',$did);
                sendPushToUser($db,(int)$sol['created_by'],['title'=>'✅ Solicitação Aprovada','message'=>"Auto-aprovada: {$sol['title']}",'url'=>'/index.php#demandas']);
            }
            
            $processed['solicitations']++;
        }
    }

    // 2. Auto-concluir demandas em revisão há mais de X horas
    if($autoCompleteRev){
        $inReview=$db->prepare("SELECT d.id,d.title,d.review_at FROM demandas d WHERE d.status='Em Revisão' AND d.review_at IS NOT NULL AND d.review_at<=?");
        $inReview->execute([$limitDate]);
        $reviews=$inReview->fetchAll();
        foreach($reviews as $rev){
            $db->prepare("UPDATE demandas SET status='Concluída',completed_at=NOW() WHERE id=?")->execute([$rev['id']]);
            $db->prepare("INSERT INTO historico_demandas (demand_id,user_id,action,old_value,new_value,details) VALUES (?,0,'Auto-concluída','Em Revisão','Concluída',?)")->execute([$rev['id'],"Auto-concluída após {$timeoutH}h em revisão sem análise"]);
            
            // Notificar devs
            $devs=$db->prepare("SELECT user_id FROM devs_demandas WHERE demand_id=?");$devs->execute([$rev['id']]);
            foreach($devs->fetchAll() as $dv){
                notify($dv['user_id'],'demand_completed',"🎉 {$rev['title']}: Auto-concluída!",'',"demand:{$rev['id']}",'demand',$rev['id']);
                sendPushToUser($db,(int)$dv['user_id'],['title'=>'🎉 Concluída!','message'=>"Auto-concluída: {$rev['title']}",'url'=>'/index.php#demandas']);
            }
            // Notificar admins
            $admins=$db->query("SELECT id FROM usuarios WHERE (role LIKE '%admin%' OR role LIKE '%diretor%') AND active=1")->fetchAll();
            foreach($admins as $a){
                notify($a['id'],'demand_completed',"Demanda #{$rev['id']} auto-concluída (revisão sem análise em {$timeoutH}h)","Título: {$rev['title']}","demand:{$rev['id']}",'demand',$rev['id']);
            }
            
            $processed['reviews']++;
        }
    }

    jsonR(['success'=>true,'processed'=>$processed,'config'=>['auto_approve'=>$autoApproveSol,'auto_complete'=>$autoCompleteRev,'timeout_hours'=>$timeoutH]]);
}

// ===== CONFIG (admin) =====
if($act==='system_config'){
    if($method==='GET'){
        $rows=$db->query("SELECT config_key,config_value FROM system_config")->fetchAll();
        $cfgs=[];foreach($rows as $r)$cfgs[$r['config_key']]=$r['config_value'];
        jsonR($cfgs);
    }
    if($method==='POST'){
        if(!$IS_ADMIN) jsonR(['error'=>'Sem permissão'],403);
        $d=json_decode(file_get_contents('php://input'),true);
        $allowed=['auto_approve_solicitations','auto_complete_reviews','auto_timeout_hours'];
        $st=$db->prepare("INSERT INTO system_config (config_key,config_value,updated_by) VALUES (?,?,?) ON DUPLICATE KEY UPDATE config_value=VALUES(config_value),updated_by=VALUES(updated_by)");
        foreach($d as $k=>$v){
            if(in_array($k,$allowed)) $st->execute([$k,$v,$UID]);
        }
        logActivity($UID,'Config atualizada','system',0,json_encode($d));
        jsonR(['success'=>true]);
    }
}

// ===== 404 FALLBACK ====="""

if "auto_process" not in c:
    c = c.replace(old_fallback, new_auto, 1)
    print("  ✅ 2. Endpoints auto_process + system_config adicionados")

with open(API, 'w') as f:
    f.write(c)
PYEOF

# ══════════════════════════════════════════
# 2. JS: chamar auto_process + painel config no dashboard
# ══════════════════════════════════════════
python3 << 'PYEOF2'
FILE = "/var/www/html/layane/gestaodev/assets/js/app.js"
with open(FILE, 'r') as f:
    c = f.read()

fixes = 0

# 1. Chamar auto_process no ciclo de 30s (junto com check_pending_accept)
old_poll = "loadNotifCount();pollNewNotifs();checkDeadlines();api(\"check_pending_accept\")},30000)"
new_poll = "loadNotifCount();pollNewNotifs();checkDeadlines();api(\"check_pending_accept\");if(IS_ADMIN)api(\"auto_process\")},30000)"
if old_poll in c:
    c = c.replace(old_poll, new_poll, 1)
    fixes += 1
    print("  ✅ 3. auto_process chamado a cada 30s (admin)")

# 2. Add config panel function for dashboard
config_panel = """
// ===== PAINEL AUTOMAÇÕES (Admin) =====
async function loadAutoConfig(){
  if(!IS_ADMIN) return;
  const cfg = await api('system_config') || {};
  const autoApprove = cfg.auto_approve_solicitations == '1';
  const autoComplete = cfg.auto_complete_reviews == '1';
  const timeout = cfg.auto_timeout_hours || '6';
  
  const el = document.getElementById('auto-config-panel');
  if(!el) return;
  el.innerHTML = `
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--acc)" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
      <span style="font-weight:700;font-size:14px">Automações</span>
    </div>
    <div style="display:flex;flex-direction:column;gap:10px">
      <label style="display:flex;align-items:center;gap:10px;padding:10px 12px;background:var(--bg4);border-radius:8px;cursor:pointer;border:1px solid ${autoApprove?'var(--ok)':'var(--bdr)'}">
        <input type="checkbox" id="cfg-auto-approve" ${autoApprove?'checked':''} onchange="saveAutoConfig()" style="width:18px;height:18px;accent-color:var(--ok)">
        <div style="flex:1">
          <div style="font-weight:600;font-size:13px;color:var(--t1)">Auto-aprovar solicitações</div>
          <div style="font-size:11px;color:var(--t3)">Solicitações pendentes há mais de ${timeout}h serão aprovadas e convertidas em demanda automaticamente</div>
        </div>
        <span style="font-size:10px;padding:3px 8px;border-radius:12px;background:${autoApprove?'var(--okb)':'var(--bg3)'};color:${autoApprove?'var(--ok)':'var(--t3)'};font-weight:700">${autoApprove?'ATIVO':'OFF'}</span>
      </label>
      <label style="display:flex;align-items:center;gap:10px;padding:10px 12px;background:var(--bg4);border-radius:8px;cursor:pointer;border:1px solid ${autoComplete?'var(--ok)':'var(--bdr)'}">
        <input type="checkbox" id="cfg-auto-complete" ${autoComplete?'checked':''} onchange="saveAutoConfig()" style="width:18px;height:18px;accent-color:var(--ok)">
        <div style="flex:1">
          <div style="font-weight:600;font-size:13px;color:var(--t1)">Auto-concluir revisões</div>
          <div style="font-size:11px;color:var(--t3)">Demandas em revisão há mais de ${timeout}h serão concluídas automaticamente</div>
        </div>
        <span style="font-size:10px;padding:3px 8px;border-radius:12px;background:${autoComplete?'var(--okb)':'var(--bg3)'};color:${autoComplete?'var(--ok)':'var(--t3)'};font-weight:700">${autoComplete?'ATIVO':'OFF'}</span>
      </label>
      <div style="display:flex;align-items:center;gap:8px;padding:8px 12px;background:var(--bg4);border-radius:8px">
        <span style="font-size:12px;color:var(--t2);font-weight:600">Tempo limite:</span>
        <select id="cfg-auto-timeout" onchange="saveAutoConfig()" style="background:var(--bg3);border:1px solid var(--bdr);color:var(--t1);padding:4px 8px;border-radius:6px;font-size:12px">
          <option value="3" ${timeout=='3'?'selected':''}>3 horas</option>
          <option value="6" ${timeout=='6'?'selected':''}>6 horas</option>
          <option value="12" ${timeout=='12'?'selected':''}>12 horas</option>
          <option value="24" ${timeout=='24'?'selected':''}>24 horas</option>
          <option value="48" ${timeout=='48'?'selected':''}>48 horas</option>
        </select>
      </div>
    </div>`;
}

async function saveAutoConfig(){
  const r = await api('system_config',{method:'POST',body:{
    auto_approve_solicitations: document.getElementById('cfg-auto-approve')?.checked ? '1' : '0',
    auto_complete_reviews: document.getElementById('cfg-auto-complete')?.checked ? '1' : '0',
    auto_timeout_hours: document.getElementById('cfg-auto-timeout')?.value || '6'
  }});
  if(r?.success){ showToast(IC.check+' Configuração salva'); loadAutoConfig(); }
}
"""

# Insert before loadDashboard
marker = "async function loadDashboard()"
if marker in c and "loadAutoConfig" not in c:
    c = c.replace(marker, config_panel + "\n" + marker)
    fixes += 1
    print("  ✅ 4. Painel automações adicionado")

# 3. Add config panel div to dashboard and call loadAutoConfig
# Find where dashboard stats are rendered and add panel after
old_dash_end = "document.getElementById('dash-activity')"
if old_dash_end in c and "auto-config-panel" not in c:
    # Add call to loadAutoConfig after loadDashboard completes
    old_load_dash_end = "loadNotifCount()}"
    # Alternative: just add a call at the end of loadDashboard
    pass

with open(FILE, 'w') as f:
    f.write(c)
print(f"  app.js: {fixes}")
PYEOF

# ══════════════════════════════════════════
# 3. Index.php: add config panel div to dashboard page
# ══════════════════════════════════════════
python3 << 'PYEOF3'
IDX = "/var/www/html/layane/gestaodev/index.php"
with open(IDX, 'r') as f:
    c = f.read()

# Add auto-config-panel div to dashboard
old_dash = '<div id="dash-activity"></div>'
new_dash = '<div id="dash-activity"></div><div id="auto-config-panel" style="margin-top:16px"></div>'
if old_dash in c and "auto-config-panel" not in c:
    c = c.replace(old_dash, new_dash, 1)
    print("  ✅ 5. Div auto-config-panel no dashboard")

with open(IDX, 'w') as f:
    f.write(c)
PYEOF3

# ══════════════════════════════════════════
# 4. Add loadAutoConfig call in loadDashboard
# ══════════════════════════════════════════
python3 << 'PYEOF4'
FILE = "/var/www/html/layane/gestaodev/assets/js/app.js"
with open(FILE, 'r') as f:
    c = f.read()

# Find where loadDashboard ends and add loadAutoConfig call
# Look for the end of activity rendering
old_act = "document.getElementById('dash-activity').innerHTML="
if old_act in c:
    # Find the line and add loadAutoConfig after
    idx = c.find(old_act)
    # Find the end of this statement (next semicolon + closing brace)
    end_idx = c.find("loadNotifCount()", idx)
    if end_idx > 0 and "loadAutoConfig" not in c[idx:end_idx+100]:
        c = c[:end_idx] + "loadAutoConfig();" + c[end_idx:]
        print("  ✅ 6. loadAutoConfig() chamado no loadDashboard")

with open(FILE, 'w') as f:
    f.write(c)
PYEOF4

echo "============================================"
echo "  Verificar sintaxe..."
php -l "$API" 2>&1 | head -3
echo "  Subir: api.php + assets/js/app.js + index.php"
echo "  Ctrl+F5!"
echo "============================================"
