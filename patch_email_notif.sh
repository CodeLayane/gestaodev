#!/bin/bash
API="/var/www/html/layane/gestaodev/api.php"
FILE="/var/www/html/layane/gestaodev/assets/js/app.js"

# ══════════════════════════════════════════
# 1. API: add email column + email function + profile toggle endpoint
# ══════════════════════════════════════════
python3 << 'PYEOF'
API = "/var/www/html/layane/gestaodev/api.php"
with open(API, 'r') as f:
    c = f.read()

fixes = 0

# 1a. Add email_notifications column to migration
old_mig = "['usuarios','work_hours',\"TINYINT DEFAULT 6\"],"
new_mig = "['usuarios','work_hours',\"TINYINT DEFAULT 6\"],\n        ['usuarios','email_notifications',\"TINYINT(1) DEFAULT 0\"],"
if old_mig in c and 'email_notifications' not in c:
    c = c.replace(old_mig, new_mig, 1)
    fixes += 1
    print("  1. Coluna email_notifications na migration")

# 1b. Add sendEmailNotification function after notify function or at top
# Find a good place - after the try { $db=getDB();
email_func = """
// ===== EMAIL NOTIFICATION =====
function sendEmailNotification($db, $userId, $subject, $body){
    try {
        $st=$db->prepare("SELECT email,name,email_notifications FROM usuarios WHERE id=? AND active=1");
        $st->execute([$userId]);
        $user=$st->fetch();
        if(!$user || !$user['email_notifications']) return false;
        $to=$user['email'];
        $name=$user['name'];
        $headers="From: GestãoDev ASSEGO <noreply@assego.org.br>\\r\\n";
        $headers.="Reply-To: noreply@assego.org.br\\r\\n";
        $headers.="MIME-Version: 1.0\\r\\n";
        $headers.="Content-Type: text/html; charset=UTF-8\\r\\n";
        $htmlBody="<div style='font-family:Arial,sans-serif;max-width:600px;margin:0 auto;background:#0a0e17;color:#e8ecf4;border-radius:12px;overflow:hidden;border:1px solid #2a3654'>";
        $htmlBody.="<div style='background:linear-gradient(135deg,#3b82f6,#8b5cf6);padding:20px 24px'>";
        $htmlBody.="<h1 style='margin:0;font-size:18px;color:#fff'>GestãoDev ASSEGO</h1></div>";
        $htmlBody.="<div style='padding:24px'>";
        $htmlBody.="<p style='margin:0 0 8px;color:#8899b8'>Olá, <strong style='color:#e8ecf4'>{$name}</strong></p>";
        $htmlBody.="<div style='background:#111827;border:1px solid #2a3654;border-radius:8px;padding:16px;margin:12px 0'>";
        $htmlBody.=$body;
        $htmlBody.="</div>";
        $htmlBody.="<p style='margin:16px 0 0;font-size:12px;color:#5a6d8f'>Esta notificação foi enviada automaticamente pelo GestãoDev ASSEGO.</p>";
        $htmlBody.="<a href='http://172.16.253.44/layane/gestaodev/' style='display:inline-block;margin-top:12px;padding:8px 16px;background:#3b82f6;color:#fff;text-decoration:none;border-radius:6px;font-size:13px'>Acessar Sistema</a>";
        $htmlBody.="</div></div>";
        return @mail($to, '=?UTF-8?B?'.base64_encode($subject).'?=', $htmlBody, $headers);
    } catch(Exception $e) { return false; }
}

"""

# Insert after the try { $db=getDB();
old_db = "try {\n\n$db=getDB();"
new_db = "try {\n\n$db=getDB();\n" + email_func
if 'sendEmailNotification' not in c:
    # Alternative insertion point
    old_db2 = "\$db=getDB();\n\n\n// Verificar limite"
    if old_db2 in c:
        c = c.replace(old_db2, "$db=getDB();\n" + email_func + "\n// Verificar limite", 1)
        fixes += 1
        print("  2. Funcao sendEmailNotification adicionada")
    else:
        print("  2. AVISO: precisa inserir manualmente")

# 1c. Add email toggle endpoint in profile section
old_profile_pw = "if($act==='profile_password'"
new_email_toggle = """if($act==='profile_email_toggle'&&$method==='POST'){
    $d=json_decode(file_get_contents('php://input'),true);
    $enabled=(int)($d['email_notifications']??0);
    $db->prepare("UPDATE usuarios SET email_notifications=? WHERE id=?")->execute([$enabled,$UID]);
    logActivity($UID,$enabled?'Email notifications ON':'Email notifications OFF','user',$UID);
    jsonR(['success'=>true,'email_notifications'=>$enabled]);
}
""" + old_profile_pw

if 'profile_email_toggle' not in c:
    c = c.replace(old_profile_pw, new_email_toggle, 1)
    fixes += 1
    print("  3. Endpoint profile_email_toggle adicionado")

# 1d. Add email_notifications to profile GET response
old_profile_get = "SELECT id,name,email,role,avatar_color,avatar_file,last_login,created_at,work_hours FROM usuarios WHERE id=?"
new_profile_get = "SELECT id,name,email,role,avatar_color,avatar_file,last_login,created_at,work_hours,COALESCE(email_notifications,0) as email_notifications FROM usuarios WHERE id=?"
if 'email_notifications' not in c.split("if($act==='profile')")[1][:500] if "if($act==='profile')" in c else True:
    c = c.replace(old_profile_get, new_profile_get, 1)
    fixes += 1
    print("  4. email_notifications no profile GET")

# 1e. Hook sendEmailNotification into the existing notify function calls for auto-process
old_auto_notify1 = 'notify($a[\'id\'],\'solicitation\',"[AUTO] Solicitação #{$sol[\'id\']} aprovada automaticamente"'
new_auto_notify1 = 'sendEmailNotification($db,$a[\'id\'],"[AUTO] Solicitação aprovada","<h3 style=\'color:#10b981;margin:0 0 8px\'>Solicitação #{$sol[\'id\']} Auto-Aprovada</h3><p style=\'color:#8899b8;margin:0\'>Sem análise em {$timeoutH}h úteis.<br>Título: <strong style=\'color:#e8ecf4\'>{$sol[\'title\']}</strong><br>Convertida em demanda #{$did}</p>");\n                notify($a[\'id\'],\'solicitation\',"[AUTO] Solicitação #{$sol[\'id\']} aprovada automaticamente"'
if old_auto_notify1 in c:
    c = c.replace(old_auto_notify1, new_auto_notify1, 1)
    fixes += 1
    print("  5. Email na auto-aprovacao")

old_auto_notify2 = 'notify($a[\'id\'],\'demand_completed\',"[AUTO] Demanda #{$rev[\'id\']} concluída automaticamente"'
new_auto_notify2 = 'sendEmailNotification($db,$a[\'id\'],"[AUTO] Demanda concluída","<h3 style=\'color:#10b981;margin:0 0 8px\'>Demanda #{$rev[\'id\']} Auto-Concluída</h3><p style=\'color:#8899b8;margin:0\'>Revisão sem análise em {$timeoutH}h úteis.<br>Título: <strong style=\'color:#e8ecf4\'>{$rev[\'title\']}</strong></p>");\n                notify($a[\'id\'],\'demand_completed\',"[AUTO] Demanda #{$rev[\'id\']} concluída automaticamente"'
if old_auto_notify2 in c:
    c = c.replace(old_auto_notify2, new_auto_notify2, 1)
    fixes += 1
    print("  6. Email na auto-conclusao")

with open(API, 'w') as f:
    f.write(c)
print(f"\n  api.php: {fixes}")
PYEOF

# ══════════════════════════════════════════
# 2. JS: add email toggle in profile page
# ══════════════════════════════════════════
cat >> /var/www/html/layane/gestaodev/assets/js/app.js << 'JSEOF'

// ===== EMAIL NOTIFICATION TOGGLE =====
async function toggleEmailNotif(){
  var cb = document.getElementById('pf-email-notif');
  if(!cb) return;
  var r = await api('profile_email_toggle',{method:'POST',body:{email_notifications:cb.checked?1:0}});
  if(r?.success) showToast(cb.checked ? '📧 Notificações por email ativadas' : '📧 Notificações por email desativadas');
}
JSEOF

echo "  ✅ JS toggle adicionado"

# 3. Add toggle to profile rendering in app.js
python3 << 'PYEOF2'
FILE = "/var/www/html/layane/gestaodev/assets/js/app.js"
with open(FILE, 'r') as f:
    c = f.read()

# Find where profile info is rendered and add email toggle
# Look for password change section
old_pw = 'Alterar Senha</h3>'
if old_pw in c and 'pf-email-notif' not in c:
    new_pw = '''Notificações</h3></div><div style="padding:16px"><label style="display:flex;align-items:center;gap:10px;padding:10px 12px;background:var(--bg4);border-radius:8px;cursor:pointer;border:1px solid var(--bdr)"><input type="checkbox" id="pf-email-notif" onchange="toggleEmailNotif()" style="width:18px;height:18px;accent-color:var(--acc)" ${u.email_notifications==1?'checked':''}><div style="flex:1"><div style="font-weight:600;font-size:13px;color:var(--t1)">Receber notificações por email</div><div style="font-size:11px;color:var(--t3)">Demandas, solicitações e automações serão enviadas para ${esc(u.email)}</div></div></label></div></div><div class="tbl-c"><div class="tbl-bar"><h3>Alterar Senha</h3>'''
    c = c.replace(old_pw, new_pw, 1)
    print("  ✅ Toggle email no perfil")

with open(FILE, 'w') as f:
    f.write(c)
PYEOF2

php -l "$API" 2>&1 | head -3
echo "============================================"
echo "  Subir: api.php + assets/js/app.js"
echo "  Ctrl+F5!"
echo "============================================"
