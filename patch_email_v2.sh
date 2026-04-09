#!/bin/bash
# =============================================
# PATCH: Email granular + relatório + teste
# =============================================

API="/var/www/html/layane/gestaodev/api.php"
JS="/var/www/html/layane/gestaodev/assets/js/app.js"
EMAIL="/var/www/html/layane/gestaodev/email-notify.php"

echo "=== 1. Atualizando email-notify.php ==="

cat > "$EMAIL" << 'PHPEOF'
<?php
function sendEmailNotification($db, $userId, $subject, $body, $type='general'){
    try {
        $st=$db->prepare("SELECT email,name,email_notifications,email_prefs FROM usuarios WHERE id=? AND active=1");
        $st->execute([$userId]);
        $user=$st->fetch();
        if(!$user || !$user['email_notifications']) return false;
        $prefs = json_decode($user['email_prefs'] ?? '{}', true) ?: [];
        $defaults = ['demandas'=>1,'solicitacoes'=>1,'automacoes'=>1,'reunioes'=>1,'avisos'=>1,'comentarios'=>1,'aprovacoes'=>1,'relatorio'=>1];
        $prefs = array_merge($defaults, $prefs);
        $typeMap = [
            'demand'=>'demandas','demand_assigned'=>'demandas','demand_status'=>'demandas',
            'demand_completed'=>'demandas','demand_review'=>'demandas',
            'solicitation'=>'solicitacoes','solicitation_approved'=>'solicitacoes',
            'auto'=>'automacoes','auto_approve'=>'automacoes','auto_complete'=>'automacoes',
            'meeting'=>'reunioes','notice'=>'avisos',
            'comment'=>'comentarios','mention'=>'comentarios',
            'presidency'=>'aprovacoes','approval'=>'aprovacoes',
            'report'=>'relatorio','test'=>'general','general'=>'general'
        ];
        $prefKey = $typeMap[$type] ?? 'general';
        if($prefKey !== 'general' && empty($prefs[$prefKey])) return false;
        $to=$user['email'];
        $name=$user['name'];
        $siteUrl='https://gestaodev.assego.com.br/gestaodev/';
        $headers="From: GestãoDev ASSEGO <noreply@assego.org.br>\r\n";
        $headers.="Reply-To: noreply@assego.org.br\r\n";
        $headers.="MIME-Version: 1.0\r\n";
        $headers.="Content-Type: text/html; charset=UTF-8\r\n";
        $htmlBody="<div style='font-family:Arial,sans-serif;max-width:600px;margin:0 auto;background:#0a0e17;color:#e8ecf4;border-radius:12px;overflow:hidden;border:1px solid #2a3654'>";
        $htmlBody.="<div style='background:linear-gradient(135deg,#3b82f6,#8b5cf6);padding:20px 24px'>";
        $htmlBody.="<h1 style='margin:0;font-size:18px;color:#fff'>GestãoDev ASSEGO</h1></div>";
        $htmlBody.="<div style='padding:24px'>";
        $htmlBody.="<p style='margin:0 0 8px;color:#8899b8'>Olá, <strong style='color:#e8ecf4'>{$name}</strong></p>";
        $htmlBody.="<div style='background:#111827;border:1px solid #2a3654;border-radius:8px;padding:16px;margin:12px 0'>";
        $htmlBody.=$body;
        $htmlBody.="</div>";
        $htmlBody.="<p style='margin:16px 0 0;font-size:12px;color:#5a6d8f'>Notificação automática do GestãoDev ASSEGO.</p>";
        $htmlBody.="<a href='{$siteUrl}' style='display:inline-block;margin-top:12px;padding:8px 16px;background:#3b82f6;color:#fff;text-decoration:none;border-radius:6px;font-size:13px'>Acessar Sistema</a>";
        $htmlBody.="</div></div>";
        return @mail($to, '=?UTF-8?B?'.base64_encode($subject).'?=', $htmlBody, $headers);
    } catch(Exception $e) { return false; }
}

function sendWeeklyReport($db, $userId){
    try {
        $user=$db->prepare("SELECT email,name,email_notifications,email_prefs FROM usuarios WHERE id=? AND active=1");
        $user->execute([$userId]);
        $user=$user->fetch();
        if(!$user||!$user['email_notifications']) return false;
        $prefs=json_decode($user['email_prefs']??'{}',true)?:[];
        if(isset($prefs['relatorio'])&&!$prefs['relatorio']) return false;

        $role=$user['role']??'dev';
        $isAdmin=strpos($role,'admin')!==false||strpos($role,'diretor')!==false;
        $uid=$userId;

        // Stats gerais
        $total=$db->query("SELECT COUNT(*) FROM demandas")->fetchColumn();
        $concluidas=$db->query("SELECT COUNT(*) FROM demandas WHERE status='Concluída'")->fetchColumn();
        $andamento=$db->query("SELECT COUNT(*) FROM demandas WHERE status='Em Andamento'")->fetchColumn();
        $revisao=$db->query("SELECT COUNT(*) FROM demandas WHERE status='Em Revisão'")->fetchColumn();
        $aguardando=$db->query("SELECT COUNT(*) FROM demandas WHERE status='Aguardando Aceite'")->fetchColumn();
        $atrasadas=$db->query("SELECT COUNT(*) FROM demandas WHERE deadline<CURDATE() AND status NOT IN('Concluída','Cancelada')")->fetchColumn();

        // Minhas demandas (se dev)
        $minhas=$db->prepare("SELECT COUNT(*) FROM demanda_devs WHERE user_id=? AND demand_id IN(SELECT id FROM demandas WHERE status NOT IN('Concluída','Cancelada'))");
        $minhas->execute([$uid]);
        $minhasCount=$minhas->fetchColumn();

        // Concluídas esta semana
        $semana=$db->query("SELECT COUNT(*) FROM demandas WHERE status='Concluída' AND completed_at>=DATE_SUB(NOW(),INTERVAL 7 DAY)")->fetchColumn();

        // Solicitações pendentes
        $solPend=$db->query("SELECT COUNT(*) FROM solicitacoes WHERE status='Pendente'")->fetchColumn();

        $name=$user['name'];
        $to=$user['email'];
        $subject="Relatório Semanal - GestãoDev ASSEGO";

        $cardStyle="style='display:inline-block;width:30%;min-width:120px;background:#111827;border:1px solid #2a3654;border-radius:8px;padding:12px;text-align:center;margin:4px;vertical-align:top'";
        $numStyle="style='font-size:24px;font-weight:800;margin:0'";
        $lblStyle="style='font-size:10px;color:#5a6d8f;margin:4px 0 0;text-transform:uppercase;letter-spacing:.5px'";

        $body="<h3 style='color:#818cf8;margin:0 0 16px;font-size:16px'>Relatório Semanal</h3>";
        $body.="<div style='text-align:center;margin-bottom:16px'>";
        $body.="<div {$cardStyle}><p {$numStyle} style='color:#6366f1'>{$total}</p><p {$lblStyle}>Total</p></div>";
        $body.="<div {$cardStyle}><p {$numStyle} style='color:#10b981'>{$concluidas}</p><p {$lblStyle}>Concluídas</p></div>";
        $body.="<div {$cardStyle}><p {$numStyle} style='color:#d4a017'>{$aguardando}</p><p {$lblStyle}>Aguardando</p></div>";
        $body.="<div {$cardStyle}><p {$numStyle} style='color:#3b82f6'>{$andamento}</p><p {$lblStyle}>Andamento</p></div>";
        $body.="<div {$cardStyle}><p {$numStyle} style='color:#f59e0b'>{$revisao}</p><p {$lblStyle}>Revisão</p></div>";
        $body.="<div {$cardStyle}><p {$numStyle} style='color:#ef4444'>{$atrasadas}</p><p {$lblStyle}>Atrasadas</p></div>";
        $body.="</div>";

        $body.="<div style='background:#0d1321;border:1px solid #2a3654;border-radius:8px;padding:14px;margin:12px 0'>";
        $body.="<table style='width:100%;border-collapse:collapse;font-size:13px'>";
        $body.="<tr><td style='padding:6px 0;color:#8899b8'>Concluídas esta semana</td><td style='text-align:right;font-weight:700;color:#10b981'>{$semana}</td></tr>";
        $body.="<tr><td style='padding:6px 0;color:#8899b8'>Suas demandas ativas</td><td style='text-align:right;font-weight:700;color:#3b82f6'>{$minhasCount}</td></tr>";
        if($isAdmin) $body.="<tr><td style='padding:6px 0;color:#8899b8'>Solicitações pendentes</td><td style='text-align:right;font-weight:700;color:#f59e0b'>{$solPend}</td></tr>";
        $body.="</table></div>";

        $body.="<p style='color:#5a6d8f;font-size:11px;margin:12px 0 0'>Gerado em ".date('d/m/Y H:i')."</p>";

        return sendEmailNotification($db, $userId, $subject, $body, 'report');
    } catch(Exception $e) { return false; }
}
PHPEOF

echo "  OK email-notify.php"

echo ""
echo "=== 2. API: coluna email_prefs + endpoints ==="

python3 << 'PYEOF'
f='/var/www/html/layane/gestaodev/api.php'
c=open(f).read()
fixes=0

# 2a. Coluna email_prefs na migration
if 'email_prefs' not in c:
    old="['usuarios','email_notifications',\"TINYINT(1) DEFAULT 0\"],"
    if old in c:
        new=old+"\n        ['usuarios','email_prefs',\"TEXT DEFAULT NULL\"],"
        c=c.replace(old,new,1)
        fixes+=1
        print("  OK coluna email_prefs")

# 2b. email_prefs no profile GET
old_pget="COALESCE(email_notifications,0) as email_notifications FROM usuarios WHERE id=?"
new_pget="COALESCE(email_notifications,0) as email_notifications,email_prefs FROM usuarios WHERE id=?"
if old_pget in c and 'email_prefs FROM' not in c:
    c=c.replace(old_pget,new_pget,1)
    fixes+=1
    print("  OK email_prefs no profile GET")

# 2c. Endpoints email_prefs + email_test + email_report
old_toggle="if($act==='profile_email_toggle'"
if 'email_test' not in c:
    new_endpoints="""if($act==='email_prefs'&&$method==='POST'){
    $d=json_decode(file_get_contents('php://input'),true);
    $prefs=json_encode($d['prefs']??[]);
    $db->prepare("UPDATE usuarios SET email_prefs=? WHERE id=?")->execute([$prefs,$UID]);
    jsonR(['success'=>true]);
}

if($act==='email_test'&&$method==='POST'){
    require_once __DIR__.'/email-notify.php';
    $u2=$db->prepare("SELECT email,name FROM usuarios WHERE id=?");$u2->execute([$UID]);$u2=$u2->fetch();
    $to=$u2['email'];$nm=$u2['name'];
    $subject='Teste de Email - GestãoDev ASSEGO';
    $body='<h3 style="color:#10b981;margin:0 0 8px">Email de teste recebido</h3>';
    $body.='<p style="color:#8899b8;margin:0">As notificacoes por email estao funcionando corretamente.</p>';
    $body.='<p style="color:#5a6d8f;margin:8px 0 0;font-size:12px">Enviado em: '.date('d/m/Y H:i:s').'</p>';
    $headers="From: GestãoDev ASSEGO <noreply@assego.org.br>\\r\\n";
    $headers.="MIME-Version: 1.0\\r\\n";
    $headers.="Content-Type: text/html; charset=UTF-8\\r\\n";
    $hb="<div style='font-family:Arial,sans-serif;max-width:600px;margin:0 auto;background:#0a0e17;color:#e8ecf4;border-radius:12px;overflow:hidden;border:1px solid #2a3654'>";
    $hb.="<div style='background:linear-gradient(135deg,#3b82f6,#8b5cf6);padding:20px 24px'><h1 style='margin:0;font-size:18px;color:#fff'>GestãoDev ASSEGO</h1></div>";
    $hb.="<div style='padding:24px'><p style='margin:0 0 8px;color:#8899b8'>Ola, <strong style='color:#e8ecf4'>{$nm}</strong></p>";
    $hb.="<div style='background:#111827;border:1px solid #2a3654;border-radius:8px;padding:16px;margin:12px 0'>".$body."</div>";
    $hb.="<p style='margin:16px 0 0;font-size:12px;color:#5a6d8f'>Notificacao automatica.</p>";
    $hb.="<a href='https://gestaodev.assego.com.br/gestaodev/' style='display:inline-block;margin-top:12px;padding:8px 16px;background:#3b82f6;color:#fff;text-decoration:none;border-radius:6px;font-size:13px'>Acessar</a></div></div>";
    $sent=@mail($to,'=?UTF-8?B?'.base64_encode($subject).'?=',$hb,$headers);
    logActivity($UID,'Email teste -> '.$to,'user',$UID);
    jsonR(['success'=>true,'sent'=>$sent,'email'=>$to]);
}

if($act==='email_report'&&$method==='POST'){
    require_once __DIR__.'/email-notify.php';
    $sent=sendWeeklyReport($db,$UID);
    jsonR(['success'=>true,'sent'=>$sent]);
}

"""+old_toggle
    c=c.replace(old_toggle,new_endpoints,1)
    fixes+=1
    print("  OK endpoints email_prefs + email_test + email_report")

open(f,'w').write(c)
print(f"  api.php: {fixes} fixes")
PYEOF

echo ""
echo "=== 3. JS: seção Notificações granular com ícones SVG ==="

python3 << 'PYEOF2'
import re
f='/var/www/html/layane/gestaodev/assets/js/app.js'
c=open(f).read()
fixes=0

# ---- 3a. Substituir seção de notificações no perfil ----
# Procurar bloco entre "// Email Notification" e "// Change Password"
start_markers = ['// Email Notifications - Granular', '// Email Notifications', '// Email Notification']
end_marker = '// Change Password'

start_idx = -1
for m in start_markers:
    idx = c.find(m)
    if idx > -1:
        start_idx = idx
        break

if start_idx > -1:
    end_idx = c.find(end_marker, start_idx)
    if end_idx > -1:
        old_block = c[start_idx:end_idx]
        
        new_block = r"""// Email Notifications - Granular v2
const _emailPrefs=(() => { try { return JSON.parse(p.email_prefs||'{}') } catch(e) { return {} } })();
const _epDefs={demandas:1,solicitacoes:1,automacoes:1,reunioes:1,avisos:1,comentarios:1,aprovacoes:1,relatorio:1};
const _ep={..._epDefs,..._emailPrefs};
const _epSvg={
  demandas:'<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>',
  solicitacoes:'<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 18h6"/><path d="M10 22h4"/><path d="M12 2a7 7 0 0 0-4 12.7V17h8v-2.3A7 7 0 0 0 12 2z"/></svg>',
  automacoes:'<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>',
  reunioes:'<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
  avisos:'<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 11l18-5v12L3 13v-2z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/></svg>',
  comentarios:'<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>',
  aprovacoes:'<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2z"/></svg>',
  relatorio:'<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/><line x1="3" y1="21" x2="21" y2="21"/></svg>'
};
const _epItems=[
  {key:'demandas',label:'Demandas',desc:'Atribuicao, status, conclusao'},
  {key:'solicitacoes',label:'Solicitacoes',desc:'Aprovacao e rejeicao'},
  {key:'automacoes',label:'Automacoes',desc:'Auto-aprovacao e conclusao'},
  {key:'reunioes',label:'Reunioes',desc:'Novas reunioes e lembretes'},
  {key:'avisos',label:'Avisos',desc:'Comunicados do sistema'},
  {key:'comentarios',label:'Comentarios',desc:'Comentarios e mencoes'},
  {key:'aprovacoes',label:'Aprovacoes',desc:'Presidencia e revisoes'},
  {key:'relatorio',label:'Relatorio Semanal',desc:'Resumo com cards do dashboard'}
];
html+=`<div class="tbl-c" style="padding:20px">`;
html+=`<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px"><div style="display:flex;align-items:center;gap:8px"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--acc)" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg><h3 style="font-size:14px;margin:0">Notificacoes por Email</h3></div><div style="display:flex;gap:6px"><button class="btn btn-g btn-sm" onclick="sendTestEmail()" id="btn-test-email" style="font-size:11px"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg> Teste</button><button class="btn btn-g btn-sm" onclick="sendReportEmail()" id="btn-report-email" style="font-size:11px"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg> Relatorio</button></div></div>`;
html+=`<label style="display:flex;align-items:center;gap:10px;padding:12px;background:var(--bg4);border-radius:10px;cursor:pointer;border:1px solid ${p.email_notifications==1?'var(--ok)':'var(--bdr)'};margin-bottom:14px"><input type="checkbox" id="pf-email-notif" onchange="toggleEmailNotif()" style="width:20px;height:20px;accent-color:var(--ok)" ${p.email_notifications==1?'checked':""}><div style="flex:1"><div style="font-weight:700;font-size:13px;color:var(--t1)">Ativar notificacoes por email</div><div style="font-size:11px;color:var(--t3)">Enviar para: ${esc(p.email)}</div></div><span style="font-size:10px;padding:3px 8px;border-radius:12px;background:${p.email_notifications==1?'var(--okb)':'var(--bg3)'};color:${p.email_notifications==1?'var(--ok)':'var(--t3)'};font-weight:700">${p.email_notifications==1?'ATIVO':'OFF'}</span></label>`;
html+=`<div id="email-prefs-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:6px;opacity:${p.email_notifications==1?'1':'0.4'};pointer-events:${p.email_notifications==1?'auto':'none'};transition:opacity .3s">`;
_epItems.forEach(item=>{
  const on=_ep[item.key]?true:false;
  html+=`<label style="display:flex;align-items:center;gap:8px;padding:8px 10px;background:${on?'rgba(99,102,241,.06)':'var(--bg3)'};border:1px solid ${on?'rgba(99,102,241,.25)':'var(--bdr)'};border-radius:8px;cursor:pointer;transition:all .15s" onmouseover="this.style.borderColor='var(--acc)'" onmouseout="this.style.borderColor='${on?'rgba(99,102,241,.25)':'var(--bdr)'}'" ><input type="checkbox" class="ep-cb" data-key="${item.key}" ${on?'checked':''} onchange="saveEmailPrefs()" style="width:15px;height:15px;accent-color:var(--acc);flex-shrink:0"><div style="display:flex;align-items:center;gap:6px;flex:1"><span style="color:${on?'var(--acc)':'var(--t3)};flex-shrink:0;display:flex">${_epSvg[item.key]||''}</span><div><div style="font-size:11px;font-weight:600;color:var(--t1)">${item.label}</div><div style="font-size:9px;color:var(--t3)">${item.desc}</div></div></div></label>`;
});
html+=`</div></div>`;
"""
        c = c[:start_idx] + new_block + "\n" + c[end_idx:]
        fixes += 1
        print("  OK secao notificacoes substituida")
    else:
        print("  ERRO: end_marker nao encontrado")
else:
    print("  AVISO: nenhum marcador de inicio encontrado, inserindo antes de Change Password")
    idx2 = c.find('// Change Password')
    if idx2 > -1 and 'email-prefs-grid' not in c:
        # Insert the new block before Change Password
        new_block = r"""// Email Notifications - Granular v2
const _emailPrefs=(() => { try { return JSON.parse(p.email_prefs||'{}') } catch(e) { return {} } })();
const _epDefs={demandas:1,solicitacoes:1,automacoes:1,reunioes:1,avisos:1,comentarios:1,aprovacoes:1,relatorio:1};
const _ep={..._epDefs,..._emailPrefs};
const _epSvg={
  demandas:'<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>',
  solicitacoes:'<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 18h6"/><path d="M10 22h4"/><path d="M12 2a7 7 0 0 0-4 12.7V17h8v-2.3A7 7 0 0 0 12 2z"/></svg>',
  automacoes:'<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>',
  reunioes:'<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
  avisos:'<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 11l18-5v12L3 13v-2z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/></svg>',
  comentarios:'<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>',
  aprovacoes:'<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2z"/></svg>',
  relatorio:'<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/><line x1="3" y1="21" x2="21" y2="21"/></svg>'
};
const _epItems=[
  {key:'demandas',label:'Demandas',desc:'Atribuicao, status, conclusao'},
  {key:'solicitacoes',label:'Solicitacoes',desc:'Aprovacao e rejeicao'},
  {key:'automacoes',label:'Automacoes',desc:'Auto-aprovacao e conclusao'},
  {key:'reunioes',label:'Reunioes',desc:'Novas reunioes e lembretes'},
  {key:'avisos',label:'Avisos',desc:'Comunicados do sistema'},
  {key:'comentarios',label:'Comentarios',desc:'Comentarios e mencoes'},
  {key:'aprovacoes',label:'Aprovacoes',desc:'Presidencia e revisoes'},
  {key:'relatorio',label:'Relatorio Semanal',desc:'Resumo com cards do dashboard'}
];
html+=`<div class="tbl-c" style="padding:20px">`;
html+=`<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px"><div style="display:flex;align-items:center;gap:8px"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--acc)" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg><h3 style="font-size:14px;margin:0">Notificacoes por Email</h3></div><div style="display:flex;gap:6px"><button class="btn btn-g btn-sm" onclick="sendTestEmail()" id="btn-test-email" style="font-size:11px"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg> Teste</button><button class="btn btn-g btn-sm" onclick="sendReportEmail()" id="btn-report-email" style="font-size:11px"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg> Relatorio</button></div></div>`;
html+=`<label style="display:flex;align-items:center;gap:10px;padding:12px;background:var(--bg4);border-radius:10px;cursor:pointer;border:1px solid ${p.email_notifications==1?'var(--ok)':'var(--bdr)'};margin-bottom:14px"><input type="checkbox" id="pf-email-notif" onchange="toggleEmailNotif()" style="width:20px;height:20px;accent-color:var(--ok)" ${p.email_notifications==1?'checked':""}><div style="flex:1"><div style="font-weight:700;font-size:13px;color:var(--t1)">Ativar notificacoes por email</div><div style="font-size:11px;color:var(--t3)">Enviar para: ${esc(p.email)}</div></div><span style="font-size:10px;padding:3px 8px;border-radius:12px;background:${p.email_notifications==1?'var(--okb)':'var(--bg3)'};color:${p.email_notifications==1?'var(--ok)':'var(--t3)'};font-weight:700">${p.email_notifications==1?'ATIVO':'OFF'}</span></label>`;
html+=`<div id="email-prefs-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:6px;opacity:${p.email_notifications==1?'1':'0.4'};pointer-events:${p.email_notifications==1?'auto':'none'};transition:opacity .3s">`;
_epItems.forEach(item=>{
  const on=_ep[item.key]?true:false;
  html+=`<label style="display:flex;align-items:center;gap:8px;padding:8px 10px;background:${on?'rgba(99,102,241,.06)':'var(--bg3)'};border:1px solid ${on?'rgba(99,102,241,.25)':'var(--bdr)'};border-radius:8px;cursor:pointer;transition:all .15s" onmouseover="this.style.borderColor='var(--acc)'" onmouseout="this.style.borderColor='${on?'rgba(99,102,241,.25)':'var(--bdr)'}'" ><input type="checkbox" class="ep-cb" data-key="${item.key}" ${on?'checked':''} onchange="saveEmailPrefs()" style="width:15px;height:15px;accent-color:var(--acc);flex-shrink:0"><div style="display:flex;align-items:center;gap:6px;flex:1"><span style="color:${on?'var(--acc)':'var(--t3)};flex-shrink:0;display:flex">${_epSvg[item.key]||''}</span><div><div style="font-size:11px;font-weight:600;color:var(--t1)">${item.label}</div><div style="font-size:9px;color:var(--t3)">${item.desc}</div></div></div></label>`;
});
html+=`</div></div>`;
"""
        c = c[:idx2] + new_block + "\n" + c[idx2:]
        fixes += 1
        print("  OK secao inserida antes de Change Password")

# ---- 3b. Substituir toggleEmailNotif + adicionar saveEmailPrefs, sendTestEmail, sendReportEmail ----
old_func_start = "async function toggleEmailNotif(){"
if old_func_start in c:
    idx = c.find(old_func_start)
    # Find the closing brace of this function
    brace_count = 0
    i = idx
    found_start = False
    while i < len(c):
        if c[i] == '{':
            brace_count += 1
            found_start = True
        elif c[i] == '}':
            brace_count -= 1
            if found_start and brace_count == 0:
                break
        i += 1
    old_func = c[idx:i+1]
    
    new_funcs = """async function toggleEmailNotif(){
  var cb=document.getElementById('pf-email-notif');if(!cb)return;
  var en=cb.checked?1:0;
  var r=await api('profile_email_toggle',{method:'POST',body:{email_notifications:en}});
  if(r?.success){
    showToast(en?'Notificacoes por email ativadas':'Notificacoes desativadas');
    var grid=document.getElementById('email-prefs-grid');
    if(grid){grid.style.opacity=en?'1':'0.4';grid.style.pointerEvents=en?'auto':'none';}
    var label=cb.closest('label');
    if(label){label.style.borderColor=en?'var(--ok)':'var(--bdr)';var badge=label.querySelector('span:last-child');if(badge){badge.textContent=en?'ATIVO':'OFF';badge.style.background=en?'var(--okb)':'var(--bg3)';badge.style.color=en?'var(--ok)':'var(--t3)';}}
  }
}
async function saveEmailPrefs(){
  var prefs={};document.querySelectorAll('.ep-cb').forEach(function(cb){prefs[cb.dataset.key]=cb.checked?1:0;});
  var r=await api('email_prefs',{method:'POST',body:{prefs:prefs}});
  if(r?.success)showToast('Preferencias de email salvas');
}
async function sendTestEmail(){
  var btn=document.getElementById('btn-test-email');if(btn){btn.disabled=true;btn.innerHTML='Enviando...';}
  var r=await api('email_test',{method:'POST'});
  if(btn){btn.disabled=false;btn.innerHTML='<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg> Teste';}
  if(r?.sent)showToast('Email de teste enviado para '+(r.email||''));
  else showToast('Servidor nao conseguiu enviar. Funciona apenas na HostGator.');
}
async function sendReportEmail(){
  var btn=document.getElementById('btn-report-email');if(btn){btn.disabled=true;btn.innerHTML='Enviando...';}
  var r=await api('email_report',{method:'POST'});
  if(btn){btn.disabled=false;btn.innerHTML='<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg> Relatorio';}
  if(r?.sent)showToast('Relatorio enviado por email');
  else showToast('Nao foi possivel enviar. Funciona apenas na HostGator.');
}"""
    
    # Also check if saveEmailPrefs already exists
    if 'saveEmailPrefs' not in c:
        c = c.replace(old_func, new_funcs, 1)
        fixes += 1
        print("  OK funcoes substituidas")
    else:
        # Just replace toggleEmailNotif
        c = c.replace(old_func, new_funcs.split('\nasync function saveEmailPrefs')[0], 1)
        fixes += 1
        print("  OK toggleEmailNotif substituido (saveEmailPrefs ja existe)")

open(f,'w').write(c)
print(f"  app.js: {fixes} fixes")
PYEOF2

echo ""
echo "=== 4. Verificando ==="
php -l "$API" 2>&1 | head -3
echo "api.php endpoints:"
grep -c "email_test\|email_prefs\|email_report" "$API"
echo "app.js funcoes:"
grep -c "saveEmailPrefs\|sendTestEmail\|sendReportEmail\|email-prefs-grid" "$JS"

echo ""
echo "=== 5. Banco de dados ==="
mysql -u gestaodev -pgestaodev gestaodev -e "ALTER TABLE usuarios ADD COLUMN email_prefs TEXT DEFAULT NULL" 2>/dev/null
echo "  Se falhou no servidor teste, nao tem problema."
echo "  Na HostGator phpMyAdmin: ALTER TABLE usuarios ADD COLUMN email_prefs TEXT DEFAULT NULL;"

echo ""
echo "============================================"
echo "  SUBIR: api.php + email-notify.php + assets/js/app.js"
echo "  HostGator SQL: ALTER TABLE usuarios ADD COLUMN email_prefs TEXT DEFAULT NULL;"
echo "  Ctrl+F5!"
echo "============================================"
