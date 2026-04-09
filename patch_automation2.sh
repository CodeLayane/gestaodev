#!/bin/bash
FILE="/var/www/html/layane/gestaodev/assets/js/app.js"
IDX="/var/www/html/layane/gestaodev/index.php"

# 1. Add auto_process call in polling
python3 << 'PYEOF'
FILE = "/var/www/html/layane/gestaodev/assets/js/app.js"
with open(FILE, 'r') as f:
    c = f.read()

fixes = 0

# Add auto_process to polling
old = 'loadNotifCount();pollNewNotifs();checkDeadlines();api("check_pending_accept")},30000)'
new = 'loadNotifCount();pollNewNotifs();checkDeadlines();api("check_pending_accept");if(IS_ADMIN)api("auto_process")},30000)'
if old in c and 'auto_process' not in c:
    c = c.replace(old, new, 1)
    fixes += 1
    print("  1. auto_process no polling")

with open(FILE, 'w') as f:
    f.write(c)
print(f"  Total: {fixes}")
PYEOF

# 2. Create the JS functions in a temp file and append
cat >> /var/www/html/layane/gestaodev/assets/js/app.js << 'JSEOF'

// ===== PAINEL AUTOMAÇÕES (Admin) =====
async function loadAutoConfig(){
  if(!IS_ADMIN) return;
  var cfg = await api('system_config') || {};
  var autoApprove = cfg.auto_approve_solicitations == '1';
  var autoComplete = cfg.auto_complete_reviews == '1';
  var timeout = cfg.auto_timeout_hours || '6';
  var el = document.getElementById('auto-config-panel');
  if(!el) return;
  var html = '<div style="display:flex;align-items:center;gap:10px;margin-bottom:10px">';
  html += '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--acc)" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>';
  html += '<span style="font-weight:700;font-size:14px">Automações</span></div>';
  html += '<div style="display:flex;flex-direction:column;gap:10px">';
  // Toggle 1
  html += '<label style="display:flex;align-items:center;gap:10px;padding:10px 12px;background:var(--bg4);border-radius:8px;cursor:pointer;border:1px solid '+(autoApprove?'var(--ok)':'var(--bdr)')+'">';
  html += '<input type="checkbox" id="cfg-auto-approve" '+(autoApprove?'checked':'')+' onchange="saveAutoConfig()" style="width:18px;height:18px;accent-color:var(--ok)">';
  html += '<div style="flex:1"><div style="font-weight:600;font-size:13px;color:var(--t1)">Auto-aprovar solicitações</div>';
  html += '<div style="font-size:11px;color:var(--t3)">Solicitações pendentes há mais de '+timeout+'h serão aprovadas automaticamente</div></div>';
  html += '<span style="font-size:10px;padding:3px 8px;border-radius:12px;background:'+(autoApprove?'var(--okb)':'var(--bg3)')+';color:'+(autoApprove?'var(--ok)':'var(--t3)')+';font-weight:700">'+(autoApprove?'ATIVO':'OFF')+'</span></label>';
  // Toggle 2
  html += '<label style="display:flex;align-items:center;gap:10px;padding:10px 12px;background:var(--bg4);border-radius:8px;cursor:pointer;border:1px solid '+(autoComplete?'var(--ok)':'var(--bdr)')+'">';
  html += '<input type="checkbox" id="cfg-auto-complete" '+(autoComplete?'checked':'')+' onchange="saveAutoConfig()" style="width:18px;height:18px;accent-color:var(--ok)">';
  html += '<div style="flex:1"><div style="font-weight:600;font-size:13px;color:var(--t1)">Auto-concluir revisões</div>';
  html += '<div style="font-size:11px;color:var(--t3)">Demandas em revisão há mais de '+timeout+'h serão concluídas automaticamente</div></div>';
  html += '<span style="font-size:10px;padding:3px 8px;border-radius:12px;background:'+(autoComplete?'var(--okb)':'var(--bg3)')+';color:'+(autoComplete?'var(--ok)':'var(--t3)')+';font-weight:700">'+(autoComplete?'ATIVO':'OFF')+'</span></label>';
  // Timeout selector
  html += '<div style="display:flex;align-items:center;gap:8px;padding:8px 12px;background:var(--bg4);border-radius:8px">';
  html += '<span style="font-size:12px;color:var(--t2);font-weight:600">Tempo limite:</span>';
  html += '<select id="cfg-auto-timeout" onchange="saveAutoConfig()" style="background:var(--bg3);border:1px solid var(--bdr);color:var(--t1);padding:4px 8px;border-radius:6px;font-size:12px">';
  ['3','6','12','24','48'].forEach(function(v){ html += '<option value="'+v+'"'+(timeout==v?' selected':'')+'>'+v+' horas</option>'; });
  html += '</select></div></div>';
  el.innerHTML = html;
}

async function saveAutoConfig(){
  var r = await api('system_config',{method:'POST',body:{
    auto_approve_solicitations: document.getElementById('cfg-auto-approve')?.checked ? '1' : '0',
    auto_complete_reviews: document.getElementById('cfg-auto-complete')?.checked ? '1' : '0',
    auto_timeout_hours: document.getElementById('cfg-auto-timeout')?.value || '6'
  }});
  if(r?.success){ showToast(IC.check+' Configuração salva'); loadAutoConfig(); }
}
JSEOF

echo "  ✅ 2. Funções JS adicionadas"

# 3. Add loadAutoConfig call in loadDashboard
python3 << 'PYEOF2'
FILE = "/var/www/html/layane/gestaodev/assets/js/app.js"
with open(FILE, 'r') as f:
    c = f.read()

old = "loadNotifCount()}"
if old in c and "loadAutoConfig" not in c:
    c = c.replace(old, "loadAutoConfig();loadNotifCount()}", 1)
    print("  ✅ 3. loadAutoConfig no loadDashboard")

with open(FILE, 'w') as f:
    f.write(c)
PYEOF2

# 4. Add div to index.php
python3 << 'PYEOF3'
IDX = "/var/www/html/layane/gestaodev/index.php"
with open(IDX, 'r') as f:
    c = f.read()

old = '<div id="dash-activity"></div>'
new = '<div id="dash-activity"></div><div id="auto-config-panel" style="margin-top:16px"></div>'
if old in c and "auto-config-panel" not in c:
    c = c.replace(old, new, 1)
    print("  ✅ 4. Div auto-config-panel no dashboard")

with open(IDX, 'w') as f:
    f.write(c)
PYEOF3

# 5. Verify PHP syntax
php -l /var/www/html/layane/gestaodev/api.php 2>&1 | head -3

echo "============================================"
echo "  Subir: api.php + assets/js/app.js + index.php"
echo "  Ctrl+F5!"
echo "============================================"
