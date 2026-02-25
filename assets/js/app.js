// Inject permissions panel styles
(function(){var s=document.createElement('style');s.textContent='.perm-wrap{margin-top:12px;animation:fadeIn .2s ease}.perm-panel{background:var(--bg2);border:1px solid var(--brd);border-radius:14px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,.25)}.perm-ph{display:flex;justify-content:space-between;align-items:flex-start;padding:16px 18px;background:var(--bg3);border-bottom:1px solid var(--brd)}.perm-ph-title{font-weight:700;font-size:14px;color:var(--t1)}.perm-rb{font-size:10px;font-weight:700;padding:3px 10px;border-radius:20px}.prb-admin{background:#ef444422;color:#ef4444;border:1px solid #ef444440}.prb-dev{background:#3b82f622;color:#3b82f6;border:1px solid #3b82f640}.prb-presidencia{background:#f59e0b22;color:#f59e0b;border:1px solid #f59e0b40}.prb-diretor{background:#8b5cf622;color:#8b5cf6;border:1px solid #8b5cf640}.perm-grp{padding:12px 18px;border-bottom:1px solid var(--brd)}.perm-grp-t{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--t3);margin-bottom:8px}.perm-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:6px}.perm-it{display:flex;align-items:center;gap:10px;padding:8px 10px;border-radius:8px;cursor:pointer;border:1px solid var(--brd);transition:all .15s;user-select:none}.perm-it:hover{border-color:var(--acc)}.perm-it.on{background:rgba(99,102,241,.08);border-color:rgba(99,102,241,.35)}.perm-it.off{opacity:.55}.perm-tog{width:28px;height:16px;border-radius:8px;flex-shrink:0;position:relative;transition:background .2s}.perm-it.on .perm-tog{background:var(--acc)}.perm-it.off .perm-tog{background:var(--bg4)}.perm-tog::after{content:"";position:absolute;top:2px;left:2px;width:12px;height:12px;border-radius:50%;background:#fff;transition:transform .2s;box-shadow:0 1px 3px rgba(0,0,0,.3)}.perm-it.on .perm-tog::after{transform:translateX(12px)}.perm-lbl{font-size:12px;font-weight:600;color:var(--t1)}.perm-desc{font-size:10px;color:var(--t3);margin-top:1px}.perm-foot{display:flex;gap:8px;padding:12px 18px;background:var(--bg3);border-top:1px solid var(--brd)}.perm-open{border-color:var(--acc)!important}@media(max-width:600px){.perm-grid{grid-template-columns:1fr 1fr}}';document.head.appendChild(s)})();

// Inject report checkbox styles
(function(){var s=document.createElement('style');s.textContent='/* ===== REPORT CHECKBOX FILTERS ===== */.rep-filter-section{margin-bottom:6px}.rep-filter-section>label{font-size:10px;font-weight:600;color:var(--t2);display:block;margin-bottom:4px}.rep-cb-group{display:flex;flex-wrap:wrap;gap:4px}.rep-cb-item{display:flex;align-items:center;gap:4px;padding:3px 8px;border-radius:6px;background:var(--bg2);border:1px solid var(--brd);cursor:pointer;font-size:11px;color:var(--t2);transition:all .2s;user-select:none}.rep-cb-item:hover{border-color:var(--acc);background:var(--accg)}.rep-cb-item input[type="checkbox"]{width:14px;height:14px;accent-color:var(--acc);cursor:pointer;margin:0}.rep-cb-item.active{background:var(--accg);border-color:var(--acc);color:var(--acc);font-weight:600}.rep-filter-panel{background:var(--bg1);border:1px solid var(--brd);border-radius:10px;padding:14px;margin-bottom:16px}.rep-filter-panel .rep-filter-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}@media(max-width:768px){.rep-filter-panel .rep-filter-grid{grid-template-columns:1fr}}.rep-filter-panel .filter-dates{display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;margin-bottom:12px;padding-bottom:12px;border-bottom:1px solid var(--brd)}.rep-filter-panel .filter-actions{display:flex;gap:6px;justify-content:flex-end;margin-top:12px;padding-top:12px;border-top:1px solid var(--brd)}.sla-info-box{background:var(--bg2);border:1px solid var(--brd);border-radius:8px;padding:10px 14px;margin-top:10px;font-size:11px;color:var(--t3);line-height:1.5}.sla-info-box strong{color:var(--t2);font-weight:600}.chart-toggle-panel{background:var(--bg1);border:1px solid var(--brd);border-radius:10px;padding:12px 14px;margin-bottom:16px}.chart-toggle-panel>label{font-size:11px;font-weight:600;color:var(--t2);display:block;margin-bottom:8px}.chart-toggle-grid{display:flex;flex-wrap:wrap;gap:6px}.chart-toggle-item{display:flex;align-items:center;gap:5px;padding:4px 10px;border-radius:6px;background:var(--bg2);border:1px solid var(--brd);cursor:pointer;font-size:11px;color:var(--t2);transition:all .2s;user-select:none}.chart-toggle-item:hover{border-color:var(--acc)}.chart-toggle-item input[type="checkbox"]{width:14px;height:14px;accent-color:var(--acc);cursor:pointer;margin:0}.chart-toggle-item.active{background:var(--accg);border-color:var(--acc);color:var(--acc)}.rep-select-all{font-size:10px;color:var(--acc);cursor:pointer;margin-left:8px;text-decoration:underline}.rep-select-all:hover{color:var(--t1)}';document.head.appendChild(s)})();

const MY_ROLES=(ME.role||'').split(',').map(r=>r.trim());
function meHas(r){if(Array.isArray(r))return r.some(x=>MY_ROLES.includes(x));return MY_ROLES.includes(r)}
const IS_ADMIN=meHas('admin'),IS_PRES=meHas('presidencia'),IS_DEV=meHas('dev'),IS_USER=ME.role==='usuario',IS_DIR=meHas('diretor');
let MY_PERMS=null;
function hasPerm(key){
  if(IS_ADMIN&&!MY_PERMS)return true;
  if(!MY_PERMS)return(DP[MY_ROLES.find(r=>DP[r])||'dev']||DP.dev)[key]?true:false;
  return!!MY_PERMS[key];
}
async function loadMyPerms(){
  try{
    const r=await fetch('api.php?action=my_permissions').then(r=>r.json()).catch(()=>({}));
    const saved=r.permissions||{};
    const firstRole=(ME.role||'dev').split(',')[0].trim();
    const defs=DP[firstRole]||DP.dev;
    if(Object.keys(saved).length===0){MY_PERMS={...defs};return;}
    MY_PERMS={};
    PERM_GROUPS.flatMap(g=>g.perms).forEach(p=>{MY_PERMS[p.key]=(p.key in saved)?+saved[p.key]:(defs[p.key]?1:0);});
  }catch(e){MY_PERMS=null;}
}
function applyPerms(){
  const map={
    dashboard:'view_dashboard',kanban:'view_kanban',calendario:'view_calendario',
    sprints:'view_sprints',demandas:'view_demandas',avisos:'view_avisos',
    reunioes:'view_reunioes',sistemas:'view_sistemas',devs:'view_devs',
    relatorios:'view_relatorios',auditoria:'view_auditoria',docs:'view_docs',
    pesquisas:'view_pesquisas',aprovacoes:'view_aprovacoes',
    solicitacoes:'view_solicitacoes',perfil:null,notificacoes:null,usuarios:null
  };
  document.querySelectorAll('.sb-i[data-page]').forEach(el=>{
    const pg=el.dataset.page;
    const pkey=map[pg];
    if(pkey===null||pkey===undefined)return;
    if(!hasPerm(pkey)){el.style.display='none';}else{el.style.display='';}
  });
}

let allDevs=[],allSystems=[],allUsers=[],allSprints=[],pendingFiles=[];
let allDepartments=[];
const STATUS_COLORS={'Aberta':'#6366f1','Aguardando Aceite':'#d4a017','Em Andamento':'#3b82f6','Em Revisão':'#f59e0b','Concluída':'#10b981','Cancelada':'#ef4444'};
const STATUS_LIST=Object.keys(STATUS_COLORS);
const IC={
search:'<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>',
bulb:'<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 18h6"/><path d="M10 22h4"/><path d="M12 2a7 7 0 0 0-4 12.7V17h8v-2.3A7 7 0 0 0 12 2z"/></svg>',
cam:'<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>',
pin:'<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="12" y1="17" x2="12" y2="22"/><path d="M5 17h14v-1.76a2 2 0 0 0-1.11-1.79l-1.78-.9A2 2 0 0 1 15 10.76V6h1V2H8v4h1v4.76a2 2 0 0 1-1.11 1.79l-1.78.9A2 2 0 0 0 5 15.24V17z"/></svg>',
laptop:'<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="2" y1="20" x2="22" y2="20"/></svg>',
link:'<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>',
git:'<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="18" r="3"/><circle cx="6" cy="6" r="3"/><circle cx="18" cy="6" r="3"/><path d="M18 9v1a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V9"/><line x1="12" y1="12" x2="12" y2="15"/></svg>',
folder:'<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>',
archive:'<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="21 8 21 21 3 21 3 8"/><rect x="1" y="3" width="22" height="5"/><line x1="10" y1="12" x2="14" y2="12"/></svg>',
megaphone:'<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 11l18-5v12L3 13v-2z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/></svg>',
cal:'<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
clock:'<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
user:'<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
mappin:'<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>',
edit:'<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>',
trash:'<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>',
send:'<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>',
refresh:'<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>',
check:'<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>',
block:'<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>',
zap:'<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>',
hand:'<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 11V6a2 2 0 0 0-4 0"/><path d="M14 10V4a2 2 0 0 0-4 0v6"/><path d="M10 10.5V6a2 2 0 0 0-4 0v8"/><path d="M18 8a2 2 0 0 1 4 0v6a8 8 0 0 1-8 8h-2c-2.8 0-4.5-.86-5.99-2.34l-3.6-3.6a2 2 0 0 1 2.83-2.82L7 15"/></svg>',
upload:'<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="16 16 12 12 8 16"/><line x1="12" y1="12" x2="12" y2="21"/><path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"/></svg>',
plus:'<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>',
comment:'<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>',
clipboard:'<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/></svg>',
hourglass:'<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 22h14"/><path d="M5 2h14"/><path d="M17 22v-4.17a2 2 0 0 0-.59-1.41L12 12l-4.41 4.41A2 2 0 0 0 7 17.83V22"/><path d="M7 2v4.17a2 2 0 0 0 .59 1.41L12 12l4.41-4.41A2 2 0 0 0 17 6.17V2"/></svg>',
play:'<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polygon points="5 3 19 12 5 21 5 3"/></svg>',
alert:'<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
x:'<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
lock_sm:'<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>',
lock_lg:'<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>',
file:'<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>'};
const IC_CROWN='<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle"><path d="M2 20h20"/><path d="M4 20V10l4 4 4-8 4 8 4-4v10"/></svg>';
const TECH_LIST=['PHP','MySQL','JavaScript','jQuery','Bootstrap','CSS','HTML','React','Node.js','Vue.js','Angular','TypeScript','Python','Laravel','Docker','API REST','JSON','Git','Apache','Nginx','Redis','MongoDB','PostgreSQL','Tailwind','Sass','WordPress','Low Code','Photoprisma','QR Code','GPS','Cron','Vercel','Firebase','AWS','Linux','Flutter','Kotlin','Swift','C#','.NET','Java','Go','Rust'];
const ROLE_LABELS={admin:'Administrador',dev:'Desenvolvedor',presidencia:'Presidência',diretor:'Diretor',usuario:'Usuário'};
let calMonth=new Date().toISOString().slice(0,7);
let chartInstances={};

const DOCS_ACTIONS=new Set(['docs','doc','doc_upload','doc_file_delete','doc_file_download']);
async function api(a,o={}){const base=DOCS_ACTIONS.has(a)?'docs_api.php':'api.php';const u=base+'?action='+a+(o.params?'&'+new URLSearchParams(o.params):'');const f={method:o.method||'GET',headers:{}};if(o.body){f.headers['Content-Type']='application/json';f.body=JSON.stringify(o.body)}if(o.formData){f.body=o.formData;f.method='POST'}const r=await fetch(u,f);if(r.status===401){window.location.href='login.php';return null}return r.json()}
async function doLogout(){await api('logout');window.location.href='login.php'}
function toggleTheme(){document.body.classList.toggle('light');localStorage.setItem('theme',document.body.classList.contains('light')?'light':'dark')}
(function(){if(localStorage.getItem('theme')==='light')document.body.classList.add('light')})()

function esc(s){const d=document.createElement('div');d.textContent=s;return d.innerHTML}
function fmtDate(d){if(!d)return'—';const p=d.split('-');return`${p[2]}/${p[1]}/${p[0]}`}
function fmtDT(dt){if(!dt)return'—';const d=new Date(dt);if(isNaN(d))return'—';return d.toLocaleDateString('pt-BR')+' '+d.toLocaleTimeString('pt-BR',{hour:'2-digit',minute:'2-digit'})}
function timeAgo(dt){if(!dt)return'';const now=Date.now();const t=new Date(dt).getTime();if(isNaN(t))return'';const s=Math.floor((now-t)/1000);if(s<60)return'agora';const m=Math.floor(s/60);if(m<60)return m+'min';const h=Math.floor(m/60);if(h<24)return h+'h';const d=Math.floor(h/24);if(d<30)return d+'d';return Math.floor(d/30)+'mês'}
function updateClock(){const now=new Date();const el=document.getElementById('live-clock');if(el)el.textContent=now.toLocaleDateString('pt-BR',{weekday:'short',day:'2-digit',month:'short'})+' '+now.toLocaleTimeString('pt-BR',{hour:'2-digit',minute:'2-digit',second:'2-digit'})}
function av(n,c,sz,af,role){sz=sz||22;const img=af?`<img src="api.php?action=arquivo&f=${af}" style="width:100%;height:100%;border-radius:50%;object-fit:cover">`:`${(n||'?')[0]}`;const roleStr=role||'';const dataAttrs=`data-uname="${esc(n||'')}" data-urole="${esc(roleStr)}" data-ucolor="${c||'#666'}" data-uavatar="${af||''}"`;return`<div class="av" style="background:${c||'#666'};width:${sz}px;height:${sz}px;font-size:${Math.round(sz*.45)}px" ${dataAttrs} onmouseenter="showMiniProfile(event,this)" onmouseleave="hideMiniProfile()">${img}</div>`}
function sClass(s){return's-'+{'Aberta':'aberta','Aguardando Aceite':'aguardando','Em Andamento':'andamento','Em Revisão':'revisao','Concluída':'concluida','Cancelada':'cancelada'}[s]}
function pClass(p){return'p-'+{'Urgente':'urgente','Alta':'alta','Média':'media','Baixa':'baixa'}[p]}
function presClass(s){return'pres-'+{'Pendente':'pendente','Aprovada':'aprovada','Rejeitada':'rejeitada'}[s]}
function devsHtml(devs){if(!devs||!devs.length)return'—';return`<div class="devs-row">${devs.map(d=>`<div class="dev-tag">${av(d.name,d.avatar_color,22,d.avatar_file,d.role)}</div>`).join('')}</div>`}
function acceptBadge(devs){if(!devs||!devs.length)return'—';const p=devs.filter(d=>d.acceptance==='Pendente').length;const a=devs.filter(d=>d.acceptance==='Aceita').length;const r=devs.filter(d=>d.acceptance==='Recusada').length;if(r>0)return`<span class="badge accept-recusada">${r} recusou</span>`;if(p>0)return`<span class="badge accept-pendente">${p} pendente</span>`;return`<span class="badge accept-aceita">${IC.check} Aceito</span>`}
let miniPTimer=null;
function showMiniProfile(e,el){clearTimeout(miniPTimer);const mp=document.getElementById('mini-profile');if(!mp)return;const n=el.dataset.uname||'';const r=el.dataset.urole||'';const c=el.dataset.ucolor||'#666';const af=el.dataset.uavatar||'';const roleLabels={admin:'Administrador',dev:'Desenvolvedor',diretor:'Diretor',presidencia:'Presidência',usuario:'Usuário'};const roles=r.split(',').map(x=>x.trim()).filter(Boolean);const badges=roles.map(x=>`<span style="font-size:9px;padding:1px 6px;border-radius:8px;background:${{admin:'#ef444433',dev:'#3b82f633',diretor:'#8b5cf633',presidencia:'#f59e0b33',usuario:'#6b728033'}[x]||'#66666633'};color:${{admin:'#ef4444',dev:'#3b82f6',diretor:'#8b5cf6',presidencia:'#f59e0b',usuario:'#6b7280'}[x]||'#999'}">${roleLabels[x]||x}</span>`).join(' ');const avHtml=af?`<img src="api.php?action=arquivo&f=${af}" style="width:48px;height:48px;border-radius:50%;object-fit:cover">`:`<div style="width:48px;height:48px;border-radius:50%;background:${c};display:flex;align-items:center;justify-content:center;font-weight:700;color:#fff;font-size:20px">${(n||'?')[0]}</div>`;mp.innerHTML=`<div style="display:flex;align-items:center;gap:10px"><div style="flex-shrink:0;border-radius:50%;box-shadow:0 0 0 3px ${c}44">${avHtml}</div><div><div style="font-weight:700;font-size:13px;color:var(--t1)">${esc(n)}</div><div style="display:flex;gap:3px;flex-wrap:wrap;margin-top:3px">${badges}</div></div></div>`;const rect=el.getBoundingClientRect();let top=rect.bottom+6;let left=rect.left;if(top+100>window.innerHeight)top=rect.top-110;if(left+200>window.innerWidth)left=window.innerWidth-210;mp.style.top=top+'px';mp.style.left=left+'px';mp.classList.add('show')}
function hideMiniProfile(){miniPTimer=setTimeout(()=>{const mp=document.getElementById('mini-profile');if(mp)mp.classList.remove('show')},200)}

function toggleDesc(btn){const w=btn.previousElementSibling;const p=w.querySelector('#desc-full');const f=w.querySelector('#desc-fade');if(p.style.maxHeight==='none'){p.style.maxHeight='100px';p.style.overflow='hidden';f.style.display='';btn.textContent='\u25BC Ver mais'}else{p.style.maxHeight='none';p.style.overflow='visible';f.style.display='none';btn.textContent='\u25B2 Ver menos'}}
function openM(id){document.getElementById(id).classList.add('show')}
function closeM(id){document.getElementById(id).classList.remove('show');if(id==='m-detail')_openDemandId=null}
document.querySelectorAll('.modal-o').forEach(o=>o.addEventListener('click',e=>{if(e.target===o)o.classList.remove('show')}));

const pageInfo={dashboard:['Dashboard','Visão geral'],kanban:['Kanban','Gestão visual'],demandas:['Demandas','Lista completa'],calendario:['Calendário','Entregas e prazos'],sprints:['Sprints','Ciclos de desenvolvimento'],avisos:['Quadro de Avisos','Comunicados'],reunioes:['Reuniões','Agenda'],notificacoes:['Notificações','Todas as notificações'],sistemas:['Sistemas','Catálogo ASSEGO'],devs:['Desenvolvedores','Equipe'],perfil:['Meu Perfil','Dados e senha'],usuarios:['Usuários','Gerenciamento de contas'],solicitacoes:['Solicitações','Melhorias sugeridas'],relatorios:['Relatórios','Análise e diagnósticos'],docs:['Documentações','Gestão de documentos'],aprovacoes:['Aprovações','Presidência'],auditoria:['Auditoria','Registro de ações']};
document.querySelectorAll('.sb-i[data-page]').forEach(el=>el.addEventListener('click',()=>showPage(el.dataset.page)));
function showPage(pg){
  const permMap={dashboard:'view_dashboard',kanban:'view_kanban',calendario:'view_calendario',sprints:'view_sprints',demandas:'view_demandas',avisos:'view_avisos',reunioes:'view_reunioes',sistemas:'view_sistemas',devs:'view_devs',relatorios:'view_relatorios',auditoria:'view_auditoria',docs:'view_docs',aprovacoes:'view_aprovacoes',solicitacoes:'view_solicitacoes'};
  const reqPerm=permMap[pg];
  if(reqPerm&&!hasPerm(reqPerm)){showToast?showToast('⛔ Sem permissão para acessar esta página'):alert('Sem permissão');return;}
  document.querySelectorAll('.page').forEach(p=>p.classList.remove('active'));document.querySelectorAll('.sb-i').forEach(n=>n.classList.remove('active'));document.getElementById('page-'+pg)?.classList.add('active');document.querySelector(`.sb-i[data-page="${pg}"]`)?.classList.add('active');const[t,s]=pageInfo[pg]||['',''];document.getElementById('pg-title').textContent=t;document.getElementById('pg-sub').textContent=s;loadPage(pg);if(window.innerWidth<=900){document.getElementById('sidebar').classList.remove('open');const ov=document.getElementById('sb-overlay');if(ov)ov.classList.remove('show')}}
function loadPage(pg){const L={dashboard:loadDashboard,kanban:loadKanban,demandas:loadDemands,calendario:loadCalendario,sprints:loadSprints,avisos:loadNotices,reunioes:loadMeetings,notificacoes:loadNotificacoes,sistemas:loadSystems,devs:loadDevs,perfil:loadProfile,usuarios:loadUsers,solicitacoes:loadSolicitations,relatorios:loadReports,docs:loadDocs,aprovacoes:loadApprovals,auditoria:loadAuditoria};if(L[pg])L[pg]()}
function getCurrentPage(){const a=document.querySelector('.page.active');return a?a.id.replace('page-',''):'dashboard'}
function devCheckboxes(cid,sel=[]){const c=document.getElementById(cid);if(!c)return;c.innerHTML=allDevs.map(d=>`<label><input type="checkbox" value="${d.id}" ${sel.includes(String(d.id))||sel.includes(d.id)?'checked':''}><span>${d.name}</span></label>`).join('')}
function allUserCheckboxes(cid,sel=[]){const c=document.getElementById(cid);if(!c)return;c.innerHTML=allUsers.map(d=>`<label><input type="checkbox" value="${d.id}" ${sel.includes(String(d.id))||sel.includes(d.id)?'checked':''}><span>${d.name}</span></label>`).join('')}
function getCheckedIds(cid){return[...document.querySelectorAll(`#${cid} input:checked`)].map(i=>parseInt(i.value))}
// ===== NOTIFICATIONS =====
async function loadNotifCount(){const r=await api('notifications_unread');const c=r?.c||0;const el=document.getElementById('notif-count');const bn=document.getElementById('b-notif');if(c>0){el.textContent=c;el.style.display='';if(bn){bn.textContent=c;bn.style.display=''}}else{el.style.display='none';if(bn)bn.style.display='none'}}
async function toggleNotifs(){const p=document.getElementById('notif-panel');if(p.classList.contains('show')){p.classList.remove('show');return}const r=await api('notifications')||[];const items=r.slice(0,8);document.getElementById('notif-list').innerHTML=items.length?items.map(n=>`<div class="notif-item ${n.is_read==0?'unread':''}" onclick="readNotif(${n.id},'${esc(n.link||'')}')"><div class="nt">${esc(n.title)}</div>${n.message?`<div class="nm">${esc(n.message)}</div>`:''}<div class="nd">${timeAgo(n.created_at)} atrás · ${fmtDT(n.created_at)}</div></div>`).join(''):'<div style="padding:20px;text-align:center;color:var(--t3);font-size:12px">Sem notificações</div>';document.getElementById('notif-more').style.display=r.length>8?'block':'none';p.classList.add('show')}
async function readNotif(id,link){await api('notification_read',{method:'POST',params:{id}});loadNotifCount();document.getElementById('notif-panel').classList.remove('show');if(link){const[type,eid]=link.split(':');if(type==='demand')openDetail(parseInt(eid));else if(type==='meeting')showPage('reunioes');else if(type==='solicitation')showPage('solicitacoes');else if(type==='notice')openNoticeView(parseInt(eid))}}
async function readAllNotifs(){await api('notifications_read_all',{method:'POST'});loadNotifCount();document.getElementById('notif-panel').classList.remove('show')}
document.addEventListener('click',e=>{if(!e.target.closest('.notif-btn')&&!e.target.closest('.notif-panel'))document.getElementById('notif-panel').classList.remove('show')});

// ===== BASE DATA =====
async function loadBaseData(){const[d,s,u,sp]=await Promise.all([api('dev_list'),api('systems'),api('all_users_list'),api('sprints')]);allDevs=d||[];allSystems=s||[];allUsers=u||[];allSprints=sp||[];
['f-system','k-system'].forEach(id=>{const el=document.getElementById(id);if(el&&el.options.length<=1)allSystems.forEach(s=>{el.appendChild(new Option(s.name,s.id))})});
['f-sprint','k-sprint'].forEach(id=>{const el=document.getElementById(id);if(el&&el.options.length<=1){allSprints.filter(s=>s.status!=='Cancelada').forEach(s=>{el.appendChild(new Option(`${s.name}${s.status==='Ativa'?' ●':''}`,s.id))})}})}

// ===== DASHBOARD =====
async function loadDashboard(){const proms=[api('stats'),api('demands'),api('activities')];if(IS_DEV)proms.push(api('demands',{params:{dev_id:ME.id}}));const[stats,demands,acts,myDemands]=await Promise.all(proms);
document.getElementById('dash-stats').innerHTML=`<div class="sc blue"><div class="sc-l">Total</div><div class="sc-v">${stats.total||0}</div></div><div class="sc purple"><div class="sc-l">Abertas</div><div class="sc-v">${stats.abertas||0}</div></div><div class="sc gold"><div class="sc-l">Aguardando Aceite</div><div class="sc-v">${stats.aguardando||0}</div></div><div class="sc yellow"><div class="sc-l">Em Andamento</div><div class="sc-v">${(+stats.andamento||0)+(+stats.revisao||0)}</div></div><div class="sc green"><div class="sc-l">Concluídas</div><div class="sc-v">${stats.concluidas||0}</div></div><div class="sc red"><div class="sc-l">Urgentes</div><div class="sc-v">${stats.urgentes||0}</div></div>`;
// My demands section for devs
const myEl=document.getElementById('dash-mydemands');
if(IS_DEV&&myDemands){
    const active=(myDemands||[]).filter(d=>!['Concluída','Cancelada'].includes(d.status));
    const pending=(myDemands||[]).filter(d=>d.devs?.some(dv=>dv.user_id==ME.id&&dv.acceptance==='Pendente'));
    myEl.innerHTML=`<div class="tbl-c" style="margin-bottom:14px;border:1px solid var(--acc);border-radius:var(--r)"><div class="tbl-bar" style="background:var(--accg)"><h3 style="display:flex;align-items:center;gap:8px"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg> Minhas Demandas${pending.length?` <span class="badge" style="background:var(--err);color:#fff;font-size:10px">${pending.length} pendente${pending.length>1?'s':''}</span>`:''}</h3><span style="font-size:11px;color:var(--t3)">${active.length} ativa${active.length!==1?'s':''}</span></div>${active.length?`<div style="overflow-x:auto"><table><thead><tr><th>Demanda</th><th>Prioridade</th><th>Status</th><th>Aceite</th><th>Prazo</th></tr></thead><tbody>${active.map(d=>{const myAcc=(d.devs||[]).find(x=>x.user_id==ME.id);const acc=myAcc?.acceptance||'—';const isPend=acc==='Pendente';return`<tr onclick="openDetail(${d.id})" style="${isPend?'background:var(--accg);':''}"><td style="font-weight:600">${isPend?IC.zap+' ':''}${esc(d.title)}</td><td><span class="badge ${pClass(d.priority)}">${d.priority}</span></td><td><span class="badge ${sClass(d.status)}">${d.status}</span></td><td><span class="badge accept-${acc.toLowerCase()}">${acc}</span></td><td style="font-size:10px;font-family:'JetBrains Mono',monospace;color:var(--t3)">${fmtDate(d.deadline)}</td></tr>`}).join('')}</tbody></table></div>`:`<div class="empty" style="padding:20px"><p>Nenhuma demanda ativa atribuída a você</p></div>`}</div>`;
} else { myEl.innerHTML=''; }
const active=(demands||[]).filter(d=>!['Concluída','Cancelada'].includes(d.status)).length;document.getElementById('b-kan').textContent=active;const bAp=document.getElementById('b-ap');if(bAp)bAp.textContent=stats.pend_pres||0;
// Recent demands: devs see only their own + open unassigned; admins see all
const recentList=IS_DEV?(demands||[]).filter(d=>(d.devs||[]).some(dv=>dv.user_id==ME.id)||((d.devs||[]).length===0&&!['Concluída','Cancelada'].includes(d.status))):(demands||[]);
document.getElementById('dash-recent').innerHTML=recentList.slice(0,8).map(d=>`<tr onclick="openDetail(${d.id})"><td style="font-weight:600">${esc(d.title)}</td><td><span class="badge ${sClass(d.status)}">${d.status}</span></td><td>${devsHtml(d.devs)}</td></tr>`).join('')||'<tr><td colspan="3"><div class="empty"><p>Nenhuma demanda</p></div></td></tr>';
document.getElementById('dash-activity').innerHTML=(acts||[]).slice(0,15).map(a=>`<div style="padding:7px 0;border-bottom:1px solid var(--bdr);font-size:12px"><span style="color:var(--acc);font-weight:600">${esc(a.user_name||'Sistema')}</span> <span style="color:var(--t2)">${esc(a.action)}</span> <span style="color:var(--t3);font-size:10px;font-family:'JetBrains Mono',monospace;margin-left:6px">${timeAgo(a.created_at)}</span></div>`).join('')||'<div class="empty"><p>Sem atividade</p></div>'}

// ===== KANBAN =====
async function loadKanban(){const p={};const mine=document.getElementById('k-mine')?.checked;if(mine)p.dev_id=ME.id;const kp=document.getElementById('k-priority')?.value;if(kp)p.priority=kp;const ks=document.getElementById('k-system')?.value;if(ks)p.system_id=ks;const ksp=document.getElementById('k-sprint')?.value;if(ksp)p.sprint_id=ksp;const demands=await api('demands',{params:p})||[];document.getElementById('kanban-board').innerHTML=STATUS_LIST.map(st=>{const items=demands.filter(d=>d.status===st);return`<div class="k-col"><div class="k-head"><span class="k-title"><span class="k-dot" style="background:${STATUS_COLORS[st]}"></span>${st}</span><span class="k-cnt">${items.length}</span></div><div class="k-body">${items.map(d=>`<div class="k-card" onclick="openDetail(${d.id})" ${d.needs_presidency_approval==1&&d.presidency_status==='Rejeitada'?'style="border-left:3px solid var(--err);opacity:.75"':''}><div class="k-card-t">${d.needs_presidency_approval==1&&d.presidency_status==='Rejeitada'?IC.block+' ':''}${esc(d.title)}</div><div class="k-card-m"><span class="tag">${esc(d.system_name||'—')}</span><span class="badge ${pClass(d.priority)}">${d.priority}</span></div><div class="k-card-m" style="margin-top:5px">${devsHtml(d.devs)}${d.needs_presidency_approval==1?`<span class="badge ${presClass(d.presidency_status)}">${IC_CROWN}</span>`:''}</div></div>`).join('')||'<div class="empty" style="padding:12px"><p style="font-size:10px">Vazio</p></div>'}</div></div>`}).join('')}

// ===== DEMANDS =====
async function loadDemands(){const p={};const s=document.getElementById('d-search')?.value;if(s)p.search=s;const fs=document.getElementById('f-status')?.value;if(fs)p.status=fs;const fp=document.getElementById('f-priority')?.value;if(fp)p.priority=fp;const fsy=document.getElementById('f-system')?.value;if(fsy)p.system_id=fsy;const fsp=document.getElementById('f-sprint')?.value;if(fsp)p.sprint_id=fsp;const mine=document.getElementById('f-mine')?.checked;if(mine)p.dev_id=ME.id;
const demands=await api('demands',{params:p})||[];document.getElementById('dem-body').innerHTML=demands.length?demands.map(d=>`<tr onclick="openDetail(${d.id})"><td style="font-family:'JetBrains Mono',monospace;color:var(--t3);font-size:11px">#${d.id}</td><td style="font-weight:600">${esc(d.title)}</td><td><span class="tag">${esc(d.system_name||'—')}</span></td><td><span class="badge ${pClass(d.priority)}">${d.priority}</span></td><td>${devsHtml(d.devs)}</td><td><span class="badge ${sClass(d.status)}">${d.status}</span></td><td>${acceptBadge(d.devs)}</td><td>${d.needs_presidency_approval==1?`<span class="badge ${presClass(d.presidency_status)}">${IC_CROWN} ${d.presidency_status}</span>`:'—'}</td><td style="font-size:10px;font-family:'JetBrains Mono',monospace;color:var(--t3)">${fmtDate(d.deadline)}</td></tr>`).join(''):'<tr><td colspan="9"><div class="empty"><div class="ei">—</div><p>Nenhuma demanda</p></div></td></tr>'}

function openNewDemand(){document.getElementById('m-demand-t').textContent='Nova Demanda';document.getElementById('d-edit-id').value='';document.getElementById('d-title').value='';document.getElementById('d-desc').value='';document.getElementById('d-priority').value='Média';document.getElementById('d-status').value='Aberta';document.getElementById('d-start').value=new Date().toISOString().split('T')[0];document.getElementById('d-deadline').value='';document.getElementById('d-requester').value='';document.getElementById('d-presidency').checked=false;pendingFiles=[];document.getElementById('upload-preview').innerHTML='';
document.getElementById('d-system').innerHTML='<option value="">Selecione...</option>'+allSystems.map(s=>`<option value="${s.id}">${esc(s.name)}</option>`).join('');document.getElementById('d-sprint').innerHTML='<option value="">Sem sprint</option>'+allSprints.filter(s=>s.status!=='Cancelada'&&s.status!=='Concluída').map(s=>`<option value="${s.id}">${esc(s.name)}${s.status==='Ativa'?' ●':''}</option>`).join('');devCheckboxes('d-devs');openM('m-demand')}
async function openEdit(id){const d=await api('demand',{params:{id}});if(!d||d.error)return alert(d?.error||'Erro');document.getElementById('m-demand-t').textContent='Editar #'+id;document.getElementById('d-edit-id').value=id;document.getElementById('d-title').value=d.title;document.getElementById('d-desc').value=d.description||'';document.getElementById('d-priority').value=d.priority;document.getElementById('d-status').value=d.status;document.getElementById('d-start').value=d.start_date||'';document.getElementById('d-deadline').value=d.deadline||'';document.getElementById('d-requester').value=d.requester||'';document.getElementById('d-presidency').checked=d.needs_presidency_approval==1;pendingFiles=[];document.getElementById('upload-preview').innerHTML='';
document.getElementById('d-system').innerHTML='<option value="">Selecione...</option>'+allSystems.map(s=>`<option value="${s.id}" ${s.id==d.system_id?'selected':''}>${esc(s.name)}</option>`).join('');document.getElementById('d-sprint').innerHTML='<option value="">Sem sprint</option>'+allSprints.filter(s=>s.status!=='Cancelada'&&s.status!=='Concluída').map(s=>`<option value="${s.id}" ${s.id==d.sprint_id?'selected':''}>${esc(s.name)}${s.status==='Ativa'?' ●':''}</option>`).join('');const selDevs=(d.devs||[]).map(x=>x.user_id);devCheckboxes('d-devs',selDevs);openM('m-demand')}
let _saving=false;
async function saveDemand(){if(_saving)return;_saving=true;try{const editId=document.getElementById('d-edit-id').value;const body={title:document.getElementById('d-title').value.trim(),description:document.getElementById('d-desc').value.trim(),system_id:document.getElementById('d-system').value||null,priority:document.getElementById('d-priority').value,status:document.getElementById('d-status').value,start_date:document.getElementById('d-start').value||null,deadline:document.getElementById('d-deadline').value||null,requester:document.getElementById('d-requester').value.trim(),needs_presidency_approval:document.getElementById('d-presidency').checked,sprint_id:document.getElementById('d-sprint').value||null,dev_ids:getCheckedIds('d-devs')};
if(!body.title){_saving=false;return alert('Título obrigatório')}let r;if(editId)r=await api('demand',{method:'PUT',params:{id:editId},body});else r=await api('demands',{method:'POST',body});if(!r?.success){_saving=false;return alert(r?.error||'Erro')}const did=editId||r.id;
// If creating from a solicitation, mark it as approved
const solId=document.getElementById('d-edit-id').dataset.solicitationId;
if(solId&&!editId){const solNotes=document.getElementById('d-edit-id').dataset.solNotes||'';await api('solicitation_review',{method:'POST',params:{id:solId},body:{status:'Aprovada',review_notes:solNotes,demand_id:did}});delete document.getElementById('d-edit-id').dataset.solicitationId;delete document.getElementById('d-edit-id').dataset.solNotes}
// If editing a rejected demand with presidency approval, resubmit
if(editId&&body.needs_presidency_approval){await api('demand_resubmit',{method:'POST',params:{id:editId}})}
for(const f of pendingFiles){const fd=new FormData();fd.append('image',f);await api('demand_upload',{params:{id:did},formData:fd})}pendingFiles=[];closeM('m-demand');loadPage(getCurrentPage())}finally{_saving=false}}
async function deleteDemand(id){if(!confirm('Excluir #'+id+'?'))return;await api('demand',{method:'DELETE',params:{id}});loadPage(getCurrentPage())}

// ===== DETAIL =====
const WF_STEPS=[{key:'Aberta',label:'Aberta',desc:'Sem dev'},{key:'Aguardando Aceite',label:'Aceite',desc:'Devs designados'},{key:'Em Andamento',label:'Desenvolvimento',desc:'Em progresso'},{key:'Em Revisão',label:'Revisão',desc:'Admin valida'},{key:'Concluída',label:'Concluída',desc:'Entregue'}];

let _openDemandId=null;
async function openDetail(id){_openDemandId=id;const d=await api('demand',{params:{id}});if(!d||d.error)return alert(d?.error||'Erro');
document.getElementById('det-title').textContent=d.title;

const footer=document.getElementById('det-footer');
let footerHtml='<button class="btn btn-g" onclick="closeM(\'m-detail\')">Fechar</button>';
// Admin/Dir always get edit/delete
if(IS_ADMIN||IS_DIR){
    footerHtml+=` <button class="btn btn-p" onclick="closeM('m-detail');openEdit(${d.id})">${IC.edit} Editar</button>`;
    if(IS_ADMIN) footerHtml+=` <button class="btn btn-d btn-sm" onclick="if(confirm('Excluir?')){closeM('m-detail');deleteDemand(${d.id})}">${IC.trash}</button>`;
}
// Dev (including admin+dev) gets assumir if applicable
const presPendingFooter=d.needs_presidency_approval==1&&d.presidency_status==='Pendente';
const blockedFooter=d.needs_presidency_approval==1&&d.presidency_status==='Rejeitada';
if(meHas('dev')&&!presPendingFooter&&!blockedFooter){
    const myAssign=(d.devs||[]).find(x=>x.user_id==ME.id);
    if(myAssign&&myAssign.acceptance==='Pendente'){
        footerHtml+=` <button class="btn btn-ok" onclick="claimDemand(${d.id})">${IC.check} Assumir Demanda</button><button class="btn btn-d" onclick="acceptDemand(${d.id},false)">Recusar</button>`;
    } else if(!myAssign&&(d.devs||[]).length===0&&!['Concluída','Cancelada'].includes(d.status)){
        footerHtml+=` <button class="btn btn-p" onclick="claimDemand(${d.id})">${IC.hand} Assumir Demanda</button>`;
    }
}
footer.innerHTML=footerHtml;
const imgBase=i=>'api.php?action=arquivo&f=';
const imgs=(d.images||[]).map(i=>`<img src="${imgBase(i)}${i.filename}" onclick="event.stopPropagation();document.getElementById('img-full-src').src='${imgBase(i)}${i.filename}';document.getElementById('img-full').classList.add('show')">`).join('');
const cmts=(d.comments||[]).map(c=>`<div class="comment${(c.user_id==ME.id||c.user_name===ME.name)?' mine':''}"><div class="ch"><span class="ca">${esc(c.user_name)}</span><span class="ct" title="${fmtDT(c.created_at)}">${timeAgo(c.created_at)}</span></div><div class="cx">${fmtMention(esc(c.text))}</div></div>`).join('');
const hist=(d.history||[]).slice(0,15).map(h=>`<div style="padding:5px 0;border-bottom:1px solid var(--bdr);font-size:11px;display:flex;align-items:center;gap:6px;flex-wrap:wrap"><span style="color:var(--acc);font-weight:600">${esc(h.user_name||'Sistema')}</span><span style="color:var(--t2)">${esc(h.action)}</span>${h.old_value?` <span class="badge" style="font-size:9px;padding:1px 6px;background:var(--errb);color:var(--err)">${esc(h.old_value)}</span> <span style="color:var(--t3)">→</span>`:''} ${h.new_value?`<span class="badge" style="font-size:9px;padding:1px 6px;background:var(--okb);color:var(--ok)">${esc(h.new_value)}</span>`:''} ${h.details?`<span style="font-size:10px;color:var(--t3);font-style:italic">"${esc(h.details)}"</span>`:''}<span style="color:var(--t3);font-family:'JetBrains Mono',monospace;margin-left:auto;font-size:9px;flex-shrink:0" title="${fmtDT(h.created_at)}">${timeAgo(h.created_at)}</span></div>`).join('');
const devsDetail=(d.devs||[]).map(dv=>`<div style="display:flex;align-items:center;gap:8px;padding:6px 0"><div class="dev-tag">${av(dv.name,dv.avatar_color)} ${esc(dv.name)}</div><span class="badge accept-${dv.acceptance.toLowerCase()}">${dv.acceptance}</span>${dv.acceptance==='Recusada'&&dv.rejection_reason?`<span style="font-size:10px;color:var(--t3)">(${esc(dv.rejection_reason)})</span>`:''}<button onclick="removeDevFromDemand(${d.id},${dv.user_id},'${esc(dv.name)}')" title="Remover dev" style="margin-left:auto;background:none;border:1px solid var(--err);color:var(--err);width:22px;height:22px;border-radius:50%;cursor:pointer;font-size:12px;display:flex;align-items:center;justify-content:center;opacity:.6;transition:opacity .2s" onmouseover="this.style.opacity=1;this.style.background='var(--err)';this.style.color='#fff'" onmouseout="this.style.opacity=.6;this.style.background='none';this.style.color='var(--err)'">✕</button></div>`).join('');

// Workflow steps
const stIdx=WF_STEPS.findIndex(s=>s.key===d.status);
const cancelled=d.status==='Cancelada';
const wfDates={};
wfDates['Aberta']=d.created_at;
(d.history||[]).slice().reverse().forEach(h=>{if(h.new_value&&!wfDates[h.new_value])wfDates[h.new_value]=h.created_at});
if(d.completed_at)wfDates['Concluída']=d.completed_at;
let wfHtml='<div class="workflow-steps">';
WF_STEPS.forEach((s,i)=>{
    const cls=cancelled?'':(i<stIdx?'done':(i===stIdx?'current':''));
    const dt=wfDates[s.key]?fmtDT(wfDates[s.key]):'—';const stepDesc=wfDates[s.key]?fmtDT(wfDates[s.key]):s.desc;
    wfHtml+=`<div class="wf-step ${cls}" title="${s.label}: ${dt}"><div class="wf-step-n">${i<stIdx?'✓':(i+1)}</div><span class="wf-step-t">${s.label}</span><span class="wf-step-d">${stepDesc}</span></div>`;
    if(i<WF_STEPS.length-1) wfHtml+='<span class="wf-arrow">→</span>';
});
wfHtml+='</div>';
if(cancelled) wfHtml='<div style="background:var(--errb);border:1px solid var(--err);border-radius:8px;padding:10px;text-align:center;font-weight:700;color:var(--err);font-size:12px;margin:8px 0">Demanda Cancelada</div>';

// Priority SLA tooltip
const priTip=`<span class="pri-tip"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg><div class="pri-tip-box"><div style="font-weight:700;font-size:10px;text-transform:uppercase;color:var(--t3);margin-bottom:6px;letter-spacing:.5px">SLA por Prioridade</div>${[['#10b981','Baixa','15 dias úteis'],['#3b82f6','Média','7 dias úteis'],['#f59e0b','Alta','3 dias úteis'],['#ef4444','Urgente','ASAP']].map(([c,n,t])=>`<div class="pri-tip-row${d.priority===n?' active':''}"><span class="pri-tip-dot" style="background:${c}"></span>${n}<span style="margin-left:auto;font-family:'JetBrains Mono',monospace">${t}</span></div>`).join('')}</div></span>`;

// Deadline urgency
let deadlineHtml=fmtDate(d.deadline);
if(d.deadline&&!['Concluída','Cancelada'].includes(d.status)){
    const diff=Math.ceil((new Date(d.deadline+'T12:00:00')-Date.now())/86400000);
    if(diff<0) deadlineHtml+=` <span style="color:var(--err);font-size:10px;font-weight:700">(${Math.abs(diff)}d atrasado!)</span>`;
    else if(diff<=3) deadlineHtml+=` <span style="color:var(--err);font-size:10px">(${diff}d restantes)</span>`;
    else if(diff<=7) deadlineHtml+=` <span style="color:var(--warn);font-size:10px">(${diff}d restantes)</span>`;
}

// Presidency rejection banner
let presRejBanner='';
if(d.needs_presidency_approval==1&&d.presidency_status==='Rejeitada'){
    presRejBanner=`<div style="background:var(--errb);border:1px solid var(--err);border-radius:var(--r);padding:14px;margin-bottom:16px"><div style="font-weight:700;color:var(--err);margin-bottom:4px">${IC.block} DEMANDA BLOQUEADA — Rejeitada pela Presidência</div><div style="font-size:12px;color:var(--t2)">${d.presidency_notes?'Motivo: '+esc(d.presidency_notes):''}</div><div style="font-size:11px;color:var(--t3);margin-top:4px">Aprovador: ${esc(d.approver_name||'—')} · ${fmtDT(d.presidency_approved_at)}</div>${IS_ADMIN?`<div style="margin-top:10px"><button class="btn btn-w btn-sm" onclick="resubmitDemand(${d.id})">${IC.refresh} Editar e Reenviar</button></div>`:''}</div>`;
}

// Status action buttons (context-aware)
let statusHtml='';
const blocked=d.needs_presidency_approval==1&&d.presidency_status==='Rejeitada';
const presPending=d.needs_presidency_approval==1&&d.presidency_status==='Pendente';
if(blocked){
    statusHtml=`<div style="font-size:11px;color:var(--err);padding:6px 0">${IC.block} Bloqueada — Rejeitada pela Presidência</div>`;
} else if(presPending){
    if(IS_PRES||IS_ADMIN){
        statusHtml=`<div style="display:flex;flex-direction:column;gap:8px"><div style="display:flex;align-items:center;gap:6px;padding:8px 12px;background:var(--warnb,rgba(245,158,11,.1));border:1px solid var(--warn);border-radius:var(--r);font-size:11px;color:var(--warn)">${IC_CROWN} Aguardando aprovação da Presidência</div></div>`;
    } else {
        statusHtml=`<div style="display:flex;align-items:center;gap:6px;padding:8px 12px;background:var(--warnb,rgba(245,158,11,.1));border:1px solid var(--warn);border-radius:var(--r);font-size:11px;color:var(--warn)">${IC_CROWN} Aguardando aprovação da Presidência — ações bloqueadas até aprovação</div>`;
    }
} else if(d.status==='Em Revisão'){
    if(IS_ADMIN||IS_DIR){
        statusHtml=`<div style="display:flex;flex-direction:column;gap:8px"><p style="font-size:11px;color:var(--t3);margin:0">Esta demanda aguarda sua análise.</p><div style="display:flex;gap:8px;flex-wrap:wrap"><button class="btn btn-ok btn-sm" onclick="changeStatus(${d.id},'Concluída')">${IC.check} Aprovar e Concluir</button><button class="btn btn-d btn-sm" onclick="returnToDevPrompt(${d.id})">↩ Devolver para Ajustes</button><button class="btn btn-g btn-sm" onclick="changeStatus(${d.id},'Cancelada')">Cancelar</button></div></div>`;
    } else {
        statusHtml='';
    }
} else if(['Concluída','Cancelada'].includes(d.status)){
    if(IS_ADMIN) statusHtml=`<div style="display:flex;gap:6px"><button class="btn btn-g btn-sm" onclick="changeStatus(${d.id},'Em Andamento')">↩ Reabrir</button></div>`;
} else {
    let btns=[];
    const isMyDev=(d.devs||[]).find(x=>x.user_id==ME.id&&x.acceptance==='Aceita');
    const isAssigned=(d.devs||[]).find(x=>x.user_id==ME.id);
    // Admin/Dir actions
    if(IS_ADMIN||IS_DIR){
        const flow={Aberta:['Em Andamento','Cancelada'],['Aguardando Aceite']:['Em Andamento','Cancelada'],['Em Andamento']:['Em Revisão','Cancelada']};
        const nextSteps=flow[d.status]||[];
        const labels={'Em Andamento':IC.play+' Iniciar Desenvolvimento','Em Revisão':IC.upload+' Enviar para Revisão','Cancelada':'Cancelar'};
        nextSteps.forEach(s=>btns.push(`<button class="btn btn-sm ${s==='Cancelada'?'btn-d':'btn-p'}" onclick="changeStatus(${d.id},'${s}')">${labels[s]||s}</button>`));
    }
    // Dev actions (for admin+dev or pure dev)
    if(meHas('dev')){
        if(isMyDev){
            if(d.status==='Em Andamento'&&!btns.some(b=>b.includes('Revisão'))){
                btns.push(`<button class="btn btn-sm btn-p" onclick="openReviewModal(${d.id})">${IC.upload} Enviar para Revisão</button>`);
            }
            if(!IS_ADMIN) btns.push(`<button class="btn btn-sm btn-g" onclick="requestCancel(${d.id})">${IC.block} Solicitar Cancelamento</button>`);
        } else if(!isAssigned&&(d.devs||[]).length===0&&!['Concluída','Cancelada'].includes(d.status)){
            btns.push(`<button class="btn btn-sm btn-p" onclick="claimDemand(${d.id})">${IC.hand} Assumir Demanda</button>`);
        } else if(isAssigned&&isAssigned.acceptance==='Pendente'){
            btns.push(`<button class="btn btn-sm btn-ok" onclick="claimDemand(${d.id})">${IC.check} Assumir Demanda</button>`);
        }
    }
    if(btns.length) statusHtml=`<div style="display:flex;gap:6px;flex-wrap:wrap">${btns.join('')}</div>`;
}

// Delegate dev selector
let delegateHtml='';
if(IS_DEV&&!['Concluída','Cancelada'].includes(d.status)){
    const myAssign=(d.devs||[]).find(x=>x.user_id==ME.id);
    if(myAssign){
        const otherDevs=allDevs.filter(dv=>dv.id!=ME.id&&!(d.devs||[]).some(x=>x.user_id==dv.id));
        if(otherDevs.length) delegateHtml=`<div style="margin-top:8px;padding-top:8px;border-top:1px solid var(--bdr);display:flex;align-items:center;gap:8px;flex-wrap:wrap"><span style="font-size:11px;color:var(--t3)">Passar para colega:</span><select id="delegate-sel-${d.id}" class="fsel" style="font-size:11px"><option value="">Selecione...</option>${otherDevs.map(dv=>`<option value="${dv.id}">${esc(dv.name)}</option>`).join('')}</select><button class="btn btn-g btn-sm" onclick="delegateDemand(${d.id},false)">${IC.plus} Adicionar</button><button class="btn btn-w btn-sm" onclick="delegateDemand(${d.id},true)">${IC.refresh} Transferir</button></div>`;
    }
}

document.getElementById('det-body').innerHTML=`${presRejBanner}
${wfHtml}
<div class="det-sec"><div class="det-sec-t"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg> Informações</div>
<div class="det-info-grid">
<div class="det-info-item"><span class="det-info-label">Status</span><span class="badge ${sClass(d.status)}">${d.status}</span></div>
<div class="det-info-item"><span class="det-info-label">Prioridade ${priTip}</span><span class="badge ${pClass(d.priority)}">${d.priority}</span></div>
<div class="det-info-item"><span class="det-info-label">Sistema</span><span class="det-info-val">${d.system_id?`<a href="#" onclick="event.preventDefault();event.stopPropagation();closeM('m-detail');setTimeout(()=>openSystemDetail(${d.system_id}),200)" style="color:var(--acc);font-weight:600;text-decoration:none;cursor:pointer" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">${esc(d.system_name||'—')}</a>`:esc(d.system_name||'—')}${d.system_url?` <a href="https://${d.system_url}" target="_blank" style="color:var(--acc)" onclick="event.stopPropagation()">↗</a>`:''}${d.github_url?` <a href="https://${d.github_url}" target="_blank" style="color:var(--t2)" onclick="event.stopPropagation()">⊙</a>`:''}</span></div>
<div class="det-info-item"><span class="det-info-label">Solicitante</span><span class="det-info-val">${esc(d.requester||'—')}</span></div>
<div class="det-info-item"><span class="det-info-label">Criado por</span><span class="det-info-val">${esc(d.creator_name||'—')}</span></div>
<div class="det-info-item"><span class="det-info-label">Início</span><span class="det-info-val" style="font-family:'JetBrains Mono',monospace;font-size:11px">${fmtDate(d.start_date)}</span></div>
<div class="det-info-item"><span class="det-info-label">Prazo</span><span class="det-info-val" style="font-family:'JetBrains Mono',monospace;font-size:11px">${deadlineHtml}</span></div>
${d.needs_presidency_approval==1?`<div class="det-info-item"><span class="det-info-label">Presidência</span><span class="badge ${presClass(d.presidency_status)}">${d.presidency_status}</span></div>`:''}
${d.approver_name?`<div class="det-info-item"><span class="det-info-label">Aprovado por</span><span class="det-info-val" style="font-size:11px">${esc(d.approver_name)} · ${timeAgo(d.presidency_approved_at)}</span></div>`:''}
${d.from_solicitation_id?`<div class="det-info-item"><span class="det-info-label">Origem</span><span class="det-info-val">Solicitação #${d.from_solicitation_id}</span></div>`:''}
${d.sprint_name?`<div class="det-info-item"><span class="det-info-label">Sprint</span><span class="det-info-val"><span class="sprint-bar" style="display:inline-flex;margin:0"><span class="sp-dot"></span>${esc(d.sprint_name)}</span></span></div>`:''}
</div></div>
<div class="det-sec"><div class="det-sec-t"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg> Desenvolvedores</div>${devsDetail||'<span style="font-size:11px;color:var(--t3)">Nenhum dev atribuído</span>'}${delegateHtml}</div>
${d.description?`<div class="det-sec"><div class="det-sec-t"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg> Descrição</div>${d.description.length>300?`<div id="desc-wrap" style="position:relative"><p id="desc-full" style="font-size:12px;color:var(--t2);line-height:1.6;white-space:pre-wrap;max-height:100px;overflow:hidden">${esc(d.description)}</p><div id="desc-fade" style="position:absolute;bottom:0;left:0;right:0;height:40px;background:linear-gradient(transparent,var(--bg3))"></div></div><button class="btn btn-g btn-sm" style="margin-top:6px;width:100%" onclick="toggleDesc(this)">\u25BC Ver mais</button>`:`<p style="font-size:12px;color:var(--t2);line-height:1.6;white-space:pre-wrap">${esc(d.description)}</p>`}</div>`:''}
<div class="det-sec"><div class="det-sec-t" style="cursor:pointer;user-select:none" onclick="const b=this.nextElementSibling;b.style.display=b.style.display==='none'?'block':'none';this.querySelector('.img-toggle').textContent=b.style.display==='none'?'\u25B6':'\u25BC'"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg> Imagens (${(d.images||[]).length}) <span class="img-toggle" style="font-size:9px;color:var(--t3);margin-left:4px">\u25BC</span></div><div class="det-img-body"><div class="img-gallery">${imgs||'<span style="font-size:11px;color:var(--t3)">Nenhuma</span>'}</div>${(IS_ADMIN||IS_DIR||(d.devs||[]).some(x=>x.user_id==ME.id))?`<div style="margin-top:8px"><input type="file" id="det-f-${d.id}" accept="image/*" style="display:none" onchange="uploadImg(${d.id},this.files[0])"><button class="btn btn-g btn-sm" onclick="document.getElementById('det-f-${d.id}').click()">${IC.cam} Adicionar</button></div>`:''}</div>
<div class="det-sec"><div class="det-sec-t" style="cursor:pointer;user-select:none" onclick="const b=this.nextElementSibling;b.style.display=b.style.display==='none'?'block':'none';this.querySelector('.cmt-toggle').textContent=b.style.display==='none'?'▶':'▼'"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg> Comentários (${(d.comments||[]).length}) <span class="cmt-toggle" style="font-size:9px;color:var(--t3);margin-left:4px">▼</span></div><div class="det-cmt-body" style="cursor:pointer;user-select:none"><div class="comment-list">${cmts||'<span style="font-size:11px;color:var(--t3)">Nenhum</span>'}</div><div class="cbox"><div class="mention-dd" id="mention-dd-${d.id}"></div><input id="cmt-${d.id}" placeholder="Escreva um comentário..." oninput="handleMention(this,${d.id})" onkeydown="handleMentionKey(event,this,${d.id})"><button class="btn btn-p btn-sm" onclick="addCmt(${d.id})">Enviar</button></div></div></div>
<div class="det-sec"><div class="det-sec-t"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg> Ações</div>${statusHtml}</div>
${hist?`<div class="det-sec"><div class="det-sec-t" style="cursor:pointer;user-select:none" onclick="const b=this.nextElementSibling;b.style.display=b.style.display==='none'?'block':'none';this.querySelector('.hist-toggle').textContent=b.style.display==='none'?'▶':'▼'"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> Histórico <span class="hist-toggle" style="font-size:9px;color:var(--t3);margin-left:4px">▶</span></div><div class="det-hist-body" style="display:none">${hist}</div></div>`:''}`;openM('m-detail')}

async function changeStatus(id,st,justification=''){const r=await api('demand_status',{method:'POST',params:{id},body:{status:st,justification}});if(r?.error)return showToast(r.error);if(!r?.unchanged){if(st==='Concluída'){showToast('✅ Demanda concluída com sucesso!')}else{showToast('Status → '+st)}}openDetail(id);loadPage(getCurrentPage())}

function launchConfetti(){
const canvas=document.createElement('canvas');canvas.id='confetti-canvas';canvas.style.cssText='position:fixed;inset:0;width:100%;height:100%;pointer-events:none;z-index:99999';document.body.appendChild(canvas);
const ctx=canvas.getContext('2d');canvas.width=window.innerWidth;canvas.height=window.innerHeight;
const colors=['#ef4444','#f59e0b','#10b981','#3b82f6','#8b5cf6','#ec4899','#06b6d4','#f97316'];
const particles=[];
for(let i=0;i<150;i++){
particles.push({x:canvas.width/2+(Math.random()-.5)*200,y:canvas.height*.4,vx:(Math.random()-.5)*15,vy:-Math.random()*18-5,color:colors[Math.floor(Math.random()*colors.length)],size:Math.random()*8+3,rotation:Math.random()*360,rotSpeed:(Math.random()-.5)*10,gravity:.3,drag:.99,opacity:1,shape:Math.random()>.5?'rect':'circle'})
}
let frame=0;const maxFrames=180;
function animate(){
if(frame>=maxFrames){canvas.remove();return}
ctx.clearRect(0,0,canvas.width,canvas.height);
particles.forEach(p=>{
p.vx*=p.drag;p.vy+=p.gravity;p.vy*=p.drag;p.x+=p.vx;p.y+=p.vy;p.rotation+=p.rotSpeed;
p.opacity=Math.max(0,1-(frame/maxFrames));
ctx.save();ctx.translate(p.x,p.y);ctx.rotate(p.rotation*Math.PI/180);ctx.globalAlpha=p.opacity;ctx.fillStyle=p.color;
if(p.shape==='rect'){ctx.fillRect(-p.size/2,-p.size/2,p.size,p.size*0.6)}
else{ctx.beginPath();ctx.arc(0,0,p.size/2,0,Math.PI*2);ctx.fill()}
ctx.restore()
});
frame++;requestAnimationFrame(animate)
}
animate()
}
async function addCmt(id){const i=document.getElementById('cmt-'+id);const t=i?.value?.trim();if(!t)return;closeMentionDD(id);const mentions=[];const re=/@(\w[\w\s]*?\w|\w)/g;let m;while((m=re.exec(t))!==null){const name=m[1];const user=allUsers.find(u=>u.name.toLowerCase()===name.toLowerCase());if(user)mentions.push(user.id)}await api('demand_comment',{method:'POST',params:{id},body:{text:t,mentions}});openDetail(id)}
let mentionIdx=-1;
function fmtMention(txt){return txt.replace(/@([\w\s]+?)(?=\s@|\s|$|[.,;!?])/g,(m,name)=>{const u=allUsers.find(x=>x.name.toLowerCase()===name.toLowerCase());return u?`<span class="cmt-mention">@${esc(u.name)}</span>`:m})}
function handleMention(input,did){const v=input.value,pos=input.selectionStart,before=v.substring(0,pos);const atIdx=before.lastIndexOf('@');if(atIdx===-1||before.substring(atIdx).includes('\n')){closeMentionDD(did);return}const query=before.substring(atIdx+1).toLowerCase();if(query.length===0||query.length>30){if(query.length>30)closeMentionDD(did);showMentionDD(did,query);return}showMentionDD(did,query)}
function showMentionDD(did,query){const dd=document.getElementById('mention-dd-'+did);if(!dd)return;const filtered=allUsers.filter(u=>u.id!==ME.id&&u.name.toLowerCase().includes(query));if(!filtered.length){dd.classList.remove('show');return}mentionIdx=-1;dd.innerHTML=filtered.slice(0,8).map((u,i)=>`<div class="mention-item" data-name="${esc(u.name)}" data-idx="${i}" onmousedown="event.preventDefault();selectMention(${did},'${esc(u.name)}')"><div class="m-av" style="background:${u.avatar_color||'#666'}">${(u.name||'?')[0]}</div><span class="m-name">${esc(u.name)}</span><span class="m-role">${u.role}</span></div>`).join('');dd.classList.add('show')}
function closeMentionDD(did){const dd=document.getElementById('mention-dd-'+did);if(dd)dd.classList.remove('show');mentionIdx=-1}
function selectMention(did,name){const input=document.getElementById('cmt-'+did);if(!input)return;const v=input.value,pos=input.selectionStart,before=v.substring(0,pos),after=v.substring(pos);const atIdx=before.lastIndexOf('@');const newVal=before.substring(0,atIdx)+'@'+name+' '+after;input.value=newVal;const newPos=atIdx+name.length+2;input.setSelectionRange(newPos,newPos);input.focus();closeMentionDD(did)}
function handleMentionKey(e,input,did){const dd=document.getElementById('mention-dd-'+did);if(!dd||!dd.classList.contains('show')){if(e.key==='Enter')addCmt(did);return}const items=dd.querySelectorAll('.mention-item');if(e.key==='ArrowDown'){e.preventDefault();mentionIdx=Math.min(mentionIdx+1,items.length-1);items.forEach((it,i)=>it.classList.toggle('active',i===mentionIdx))}else if(e.key==='ArrowUp'){e.preventDefault();mentionIdx=Math.max(mentionIdx-1,0);items.forEach((it,i)=>it.classList.toggle('active',i===mentionIdx))}else if(e.key==='Enter'||e.key==='Tab'){e.preventDefault();if(mentionIdx>=0&&items[mentionIdx])selectMention(did,items[mentionIdx].dataset.name);else if(items.length===1)selectMention(did,items[0].dataset.name);else addCmt(did)}else if(e.key==='Escape'){closeMentionDD(did)}}
async function uploadImg(id,f){if(!f)return;const fd=new FormData();fd.append('image',f);await api('demand_upload',{params:{id},formData:fd});openDetail(id)}
async function acceptDemand(id,accept){let reason='';if(!accept){reason=prompt('Motivo da recusa:');if(reason===null)return}await api('demand_accept',{method:'POST',params:{id},body:{acceptance:accept?'Aceita':'Recusada',reason}});closeM('m-detail');showToast(accept?IC.check+' Demanda assumida! Desenvolvimento iniciado.':IC.block+' Demanda recusada');loadPage(getCurrentPage());loadNotifCount()}
async function removeDevFromDemand(did,uid,name){if(!confirm("Remover "+name+" desta demanda?"))return;const r=await api("demand_remove_dev",{method:"POST",params:{id:did},body:{user_id:uid}});if(r?.error)return showToast("⚠️ "+r.error);showToast("✅ "+name+" removido");openDetail(did);loadPage(getCurrentPage())}
async function claimDemand(id,force=false){const r=await api('demand_claim',{method:'POST',params:{id},body:{force}});if(r?.conflict){if(confirm(r.message+'\n\nDeseja assumir mesmo assim?')){return claimDemand(id,true)}return}if(r?.error&&!r?.success){return showToast(r.error)}closeM('m-detail');if(r?.started)showToast(IC.check+' Demanda assumida! Desenvolvimento iniciado.');else if(r?.already)showToast(IC.check+' Você já está nesta demanda.');else showToast(IC.check+' Demanda atribuída a você!');loadPage(getCurrentPage());loadNotifCount()}
async function delegateDemand(id,removeSelf){const sel=document.getElementById('delegate-sel-'+id);const tid=sel?.value;if(!tid)return showToast('Selecione um dev');const r=await api('demand_delegate',{method:'POST',params:{id},body:{target_user_id:+tid,remove_self:removeSelf}});if(r?.error)return showToast(r.error);showToast(removeSelf?IC.refresh+' Demanda transferida!':IC.plus+' Dev adicionado!');openDetail(id)}
function returnToDevPrompt(id){const just=prompt('Motivo da devolução (o que precisa ser ajustado):');if(!just||!just.trim())return;changeStatus(id,'Em Andamento',just.trim())}

async function requestCancel(id){const reason=prompt('Motivo do cancelamento:');if(!reason||!reason.trim())return;const d=await api('demand',{params:{id}});const body={title:'Cancelamento: '+(d?.title||'Demanda #'+id),description:'Solicitação de cancelamento da demanda #'+id+'.\n\nMotivo: '+reason.trim(),system_id:d?.system_id||null,type:'Correção',priority:'Média'};await api('solicitations',{method:'POST',body});closeM('m-detail');showToast('Solicitação de cancelamento enviada para aprovação')}
function showToast(msg,dur=3500){const t=document.createElement('div');t.className='toast-notif';t.innerHTML=msg;document.body.appendChild(t);requestAnimationFrame(()=>t.classList.add('show'));setTimeout(()=>{t.classList.remove('show');setTimeout(()=>t.remove(),400)},dur)}
async function openNoticeView(id){const n=await api('notice',{params:{id}});if(!n||n.error)return;document.getElementById('nv-title').textContent=n.title;document.getElementById('nv-content').textContent=n.content;document.getElementById('nv-meta').innerHTML=`${esc(n.author_name||'Sistema')} · ${fmtDT(n.created_at)}${n.event_date?' · Data: '+fmtDate(n.event_date):''}`;const imgEl=document.getElementById('nv-image');if(imgEl){imgEl.innerHTML=n.image_file?`<img src="api.php?action=arquivo&f=${n.image_file}" style="max-width:100%;max-height:400px;border-radius:8px;margin-bottom:12px;cursor:pointer" onclick="document.getElementById('img-full-src').src=this.src;document.getElementById('img-full').classList.add('show')">`:''}openM('m-notice-view')}
async function openMeetingView(id){const ms=await api('meetings')||[];const m=ms.find(x=>x.id==id);if(!m)return showPage('reunioes');const isToday=m.meeting_date===new Date().toISOString().split('T')[0];document.getElementById('mv-body').innerHTML=`<div style="text-align:center;margin-bottom:16px"><div style="font-size:11px;font-family:'JetBrains Mono',monospace;color:${isToday?'var(--ok)':'var(--inf)'};font-weight:600">${isToday?'● HOJE ':''}${fmtDate(m.meeting_date)} às ${(m.meeting_time||'').substring(0,5)}</div><h3 style="margin-top:6px">${esc(m.title)}</h3></div>${m.description?`<p style="font-size:13px;color:var(--t2);line-height:1.6;margin-bottom:12px">${esc(m.description)}</p>`:''}<div style="display:flex;flex-wrap:wrap;gap:12px;margin-bottom:12px;font-size:12px"><span>${IC.clock} ${m.duration_minutes||60} min</span><span>${m.is_online==1?IC.laptop+' Online':IC.mappin+' '+esc(m.location||'Sala TI')}</span><span>${IC.user} ${esc(m.creator_name||'—')}</span></div>${m.is_online==1&&m.online_link?`<a href="${esc(m.online_link)}" target="_blank" class="btn btn-p" style="margin-bottom:12px;display:inline-flex">${IC.link} Entrar na reunião</a>`:''}<div style="margin-top:8px"><div style="font-size:10px;color:var(--t3);text-transform:uppercase;font-weight:700;margin-bottom:6px">Participantes</div><div style="display:flex;flex-wrap:wrap;gap:4px">${(m.participant_names||'').split(', ').filter(Boolean).map(n=>`<span class="tag">${esc(n)}</span>`).join('')}</div></div>`;openM('m-meet-view')}
function openReviewModal(id){document.getElementById('rev-demand-id').value=id;document.getElementById('rev-obs').value='';document.getElementById('rev-file').value='';openM('m-review-obs')}
async function submitReview(){const id=document.getElementById('rev-demand-id').value;const obs=document.getElementById('rev-obs').value.trim();const file=document.getElementById('rev-file').files[0];if(obs){await api('demand_comment',{method:'POST',params:{id},body:{text:'📋 Observações de entrega:\n'+obs}})}if(file){const fd=new FormData();fd.append('image',file);await api('demand_upload',{params:{id},formData:fd})}closeM('m-review-obs');await changeStatus(id,'Em Revisão')}
async function resubmitDemand(id){closeM('m-detail');openEdit(id);setTimeout(()=>{const note=document.createElement('div');note.style.cssText='background:var(--warnb);border:1px solid var(--warn);border-radius:8px;padding:10px;margin-bottom:12px;font-size:12px;color:var(--warn)';note.innerHTML='Esta demanda foi <strong>rejeitada</strong> pela presidência. Edite e salve para reenviar para aprovação.';document.querySelector('#m-demand .modal-b')?.prepend(note)},100)}

// Full notifications page
async function loadNotificacoes(){const r=await api('notifications')||[];
const types={'demand_assigned':IC.clipboard,'demand_status':IC.refresh,'demand_accept':IC.check,'comment':IC.comment,'presidency':IC_CROWN,'presidency_rejected':IC.block,'meeting':IC.cal,'meeting_updated':IC.cal,'solicitation':IC.bulb,'solicitation_approved':IC.check,'solicitation_rejected':IC.block,'notice':IC.megaphone};
let html=`<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px"><span style="font-size:13px;color:var(--t3)">${r.length} notificações</span><button class="btn btn-g btn-sm" onclick="readAllNotifs();loadNotificacoes()">Marcar todas como lidas</button></div>`;
html+=`<div class="tbl-c"><div style="overflow-x:auto"><table><thead><tr><th style="width:40px"></th><th>Notificação</th><th>Mensagem</th><th>Data</th><th>Status</th></tr></thead><tbody>`;
if(r.length){r.forEach(n=>{const icon=types[n.type]||IC.clock;const unread=n.is_read==0;html+=`<tr style="cursor:pointer;${unread?'background:var(--accg);':''}" onclick="readNotif(${n.id},'${esc(n.link||'')}')"><td><div class="notif-icon-box">${icon}</div></td><td style="font-weight:${unread?'700':'500'}">${esc(n.title)}</td><td style="color:var(--t2);font-size:11px">${esc(n.message||'—')}</td><td style="font-size:10px;font-family:'JetBrains Mono',monospace;color:var(--t3);white-space:nowrap">${fmtDT(n.created_at)}</td><td>${unread?'<span class="badge s-andamento">Nova</span>':'<span style="font-size:10px;color:var(--t3)">Lida</span>'}</td></tr>`})}
else html+='<tr><td colspan="5"><div class="empty"><div class="ei">—</div><p>Sem notificações</p></div></td></tr>';
html+=`</tbody></table></div></div>`;
document.getElementById('notificacoes-content').innerHTML=html}
function previewFiles(files){for(const f of files){pendingFiles.push(f);const r=new FileReader();r.onload=e=>{const p=document.getElementById('upload-preview');const item=document.createElement('div');item.className='up-item';item.innerHTML=`<img src="${e.target.result}"><button class="up-rm" onclick="this.parentElement.remove()">×</button>`;p.appendChild(item)};r.readAsDataURL(f)}}
const ua=document.getElementById('upload-area');if(ua){ua.addEventListener('dragover',e=>{e.preventDefault();ua.classList.add('dragover')});ua.addEventListener('dragleave',()=>ua.classList.remove('dragover'));ua.addEventListener('drop',e=>{e.preventDefault();ua.classList.remove('dragover');previewFiles(e.dataTransfer.files)})}

// ===== NOTICES =====
async function loadNotices(){const ns=await api('notices')||[];document.getElementById('avisos-grid').innerHTML=ns.length?ns.map(n=>`<div class="card notice-card ${n.priority}">${n.pinned==1?'<div style="font-size:10px;color:var(--gold);font-weight:700;margin-bottom:4px">FIXADO</div>':''}<h4>${esc(n.title)}</h4><p>${esc(n.content)}</p>${n.image_file?`<div style="margin-top:8px"><img src="api.php?action=arquivo&f=${n.image_file}" class="notice-img-thumb" onclick="event.stopPropagation();document.getElementById('img-full-src').src=this.src;document.getElementById('img-full').classList.add('show')"></div>`:''}<div style="display:flex;gap:4px;margin-top:6px;flex-wrap:wrap">${n.show_board==1?'<span class="tag" style="font-size:9px">Quadro</span>':''}${n.show_calendar==1?'<span class="tag" style="font-size:9px">Calendário'+( n.event_date?' · '+fmtDate(n.event_date):'')+'</span>':''}</div><div class="meta">${esc(n.author_name||'Sistema')} · ${fmtDT(n.created_at)}${IS_ADMIN?` <button class="btn btn-g btn-sm" style="margin-left:8px" onclick="editNotice(${n.id})">${IC.edit}</button> <button class="btn btn-g btn-sm" style="color:var(--err)" onclick="deleteNotice(${n.id})">${IC.trash}</button>`:''}</div></div>`).join(''):'<div class="empty"><div class="ei">—</div><p>Nenhum aviso</p></div>'}
function openNoticeModal(d){document.getElementById('m-notice-t').textContent=d?'Editar':'Novo Aviso';document.getElementById('n-edit-id').value=d?.id||'';document.getElementById('n-title').value=d?.title||'';document.getElementById('n-content').value=d?.content||'';document.getElementById('n-priority').value=d?.priority||'normal';document.getElementById('n-target').value=d?.target_role||'todos';document.getElementById('n-expires').value=d?.expires_at||'';document.getElementById('n-pinned').checked=d?.pinned==1;document.getElementById('n-show-board').checked=d?d.show_board==1:true;document.getElementById('n-show-calendar').checked=d?.show_calendar==1;document.getElementById('n-event-date').value=d?.event_date||'';document.getElementById('n-event-date-fg').style.display=d?.show_calendar==1?'flex':'none';openM('m-notice')}
async function editNotice(id){const ns=await api('notices')||[];const n=ns.find(x=>x.id==id);if(n)openNoticeModal(n)}
async function deleteNotice(id){if(!confirm('Excluir este aviso?'))return;await api('notice',{method:'DELETE',params:{id}});loadNotices()}
async function saveNotice(){const eid=document.getElementById('n-edit-id').value;const fd=new FormData();fd.append('title',document.getElementById('n-title').value.trim());fd.append('content',document.getElementById('n-content').value.trim());fd.append('priority',document.getElementById('n-priority').value);fd.append('target_role',document.getElementById('n-target').value);fd.append('expires_at',document.getElementById('n-expires').value||'');fd.append('pinned',document.getElementById('n-pinned').checked?1:0);fd.append('show_board',document.getElementById('n-show-board').checked?1:0);fd.append('show_calendar',document.getElementById('n-show-calendar').checked?1:0);fd.append('event_date',document.getElementById('n-event-date').value||'');const imgFile=document.getElementById('n-image');if(imgFile&&imgFile.files[0])fd.append('image',imgFile.files[0]);if(!fd.get('title')||!fd.get('content'))return alert('Preencha título e conteúdo');const url=eid?'api.php?action=notice_form&id='+eid:'api.php?action=notices_form';const r=await fetch(url,{method:'POST',body:fd,credentials:'same-origin'});const d=await r.json();if(d.error)return alert(d.error);closeM('m-notice');loadNotices()}

// ===== MEETINGS =====
async function loadMeetings(){const ms=await api('meetings')||[];const today=new Date().toISOString().split('T')[0];document.getElementById('reunioes-grid').innerHTML=ms.length?ms.map(m=>{const isToday=m.meeting_date===today;return`<div class="card meet-card ${isToday?'today':''}"><div style="font-size:10px;font-family:'JetBrains Mono',monospace;color:${isToday?'var(--ok)':'var(--inf)'};font-weight:600;margin-bottom:6px">${isToday?'● HOJE ':''}${fmtDate(m.meeting_date)} às ${(m.meeting_time||'').substring(0,5)}</div><h4>${esc(m.title)}</h4>${m.description?`<p style="font-size:12px;color:var(--t2);margin-top:4px">${esc(m.description)}</p>`:''}<div class="meet-info"><span>${IC.clock} ${m.duration_minutes||60}min</span><span>${m.is_online==1?IC.laptop+' Online':IC.mappin+' '+esc(m.location||'Sala TI')}</span><span>${IC.user} ${esc(m.creator_name||'—')}</span></div>${m.is_online==1&&m.online_link?`<div style="margin-top:8px"><a href="${esc(m.online_link)}" target="_blank" class="btn btn-sm btn-p">${IC.link} Entrar na reunião</a></div>`:''}<div style="margin-top:8px;display:flex;flex-wrap:wrap;gap:3px">${(m.participant_names||'').split(', ').filter(Boolean).map(n=>`<span class="tag">${esc(n)}</span>`).join('')}</div>${(IS_ADMIN||IS_DEV||IS_DIR)?`<div style="margin-top:10px"><button class="btn btn-g btn-sm" onclick="editMeeting(${m.id})">${IC.edit}</button> <button class="btn btn-g btn-sm" style="color:var(--err)" onclick="deleteMeeting(${m.id})">${IC.trash}</button></div>`:''}</div>`}).join(''):'<div class="empty"><div class="ei">—</div><p>Nenhuma reunião</p></div>'}
async function deleteMeeting(id){if(!confirm('Excluir esta reunião?'))return;await api('meeting',{method:'DELETE',params:{id}});loadMeetings()}
function openMeetingModal(d){document.getElementById('m-meeting-t').textContent=d?'Editar':'Agendar Reunião';document.getElementById('mt-edit-id').value=d?.id||'';document.getElementById('mt-title').value=d?.title||'';document.getElementById('mt-desc').value=d?.description||'';document.getElementById('mt-date').value=d?.meeting_date||new Date().toISOString().split('T')[0];document.getElementById('mt-time').value=d?.meeting_time?d.meeting_time.substring(0,5):'10:00';document.getElementById('mt-duration').value=d?.duration_minutes||60;document.getElementById('mt-location').value=d?.location||'Sala TI';document.getElementById('mt-online').checked=d?.is_online==1;document.getElementById('mt-link-fg').style.display=d?.is_online==1?'flex':'none';document.getElementById('mt-link').value=d?.online_link||'';allUserCheckboxes('mt-participants',d?.participant_ids?String(d.participant_ids).split(','):[]);openM('m-meeting')}
async function editMeeting(id){const ms=await api('meetings')||[];const m=ms.find(x=>x.id==id);if(m)openMeetingModal(m)}
async function saveMeeting(){const eid=document.getElementById('mt-edit-id').value;const body={title:document.getElementById('mt-title').value.trim(),description:document.getElementById('mt-desc').value.trim(),meeting_date:document.getElementById('mt-date').value,meeting_time:document.getElementById('mt-time').value,duration_minutes:parseInt(document.getElementById('mt-duration').value)||60,location:document.getElementById('mt-location').value.trim(),is_online:document.getElementById('mt-online').checked,online_link:document.getElementById('mt-link').value.trim(),participant_ids:getCheckedIds('mt-participants')};if(!body.title||!body.meeting_date)return alert('Título e data obrigatórios');if(eid)await api('meeting',{method:'PUT',params:{id:eid},body});else await api('meetings',{method:'POST',body});closeM('m-meeting');loadMeetings()}

// ===== SYSTEMS =====
async function loadSystems(){const sys=await api('systems')||[];
// Populate dev filter
const devSel=document.getElementById('sys-dev-filter');
if(devSel&&devSel.options.length<=1) allDevs.forEach(d=>{devSel.appendChild(new Option(d.name,d.id))});
const mineEl=document.getElementById('sys-mine');
const mine=mineEl?.checked;
const devFilter=devSel?.value;
let filtered=sys;
if(mine) filtered=filtered.filter(s=>(s.dev_ids||'').split(',').includes(String(ME.id)));
if(devFilter) filtered=filtered.filter(s=>(s.dev_ids||'').split(',').includes(devFilter));
document.getElementById('sistemas-grid').innerHTML=filtered.length?filtered.map(s=>{const dn=(s.dev_names||'').split(', ').filter(Boolean);const dc=(s.dev_colors||'').split(',');const da=(s.dev_avatars||'').split(',');const dr=(s.dev_roles||'').split('|');const techs=(s.technology||'').split(',').filter(Boolean);return`<div class="card" onclick="openSystemDetail(${s.id})" style="cursor:pointer;transition:border-color .18s,box-shadow .18s,transform .15s" onmouseover="this.style.borderColor='var(--acc)';this.style.boxShadow='0 6px 24px rgba(0,0,0,.18)';this.style.transform='translateY(-2px)'" onmouseout="this.style.borderColor='';this.style.boxShadow='';this.style.transform=''"><div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px"><span style="font-size:14px;font-weight:700">${esc(s.name)}</span><span class="badge ${{
'Em uso':'s-concluida','Testes':'s-revisao','Pausado':'s-cancelada','Em desenvolvimento':'s-andamento','Não utilizado':'s-aberta'}[s.status]||''}">${s.status}</span></div><p style="font-size:11px;color:var(--t2);line-height:1.5;margin-bottom:10px">${esc(s.description||'')}</p><div class="tech-tags">${techs.map(t=>`<span class="tech-tag">${esc(t.trim())}</span>`).join('')}</div><div style="margin-top:8px;display:flex;align-items:center;gap:8px;flex-wrap:wrap">${s.url?`<a href="https://${s.url}" target="_blank" class="tag" style="color:var(--ok)">${IC.link} ${esc(s.url)}</a>`:''}${s.github_url?`<a href="https://${s.github_url}" target="_blank" class="tag" style="color:var(--t1)">${IC.git} GitHub</a>`:''}</div><div style="margin-top:8px;display:flex;gap:3px;flex-wrap:wrap">${dn.map((n,i)=>av(n,dc[i],22,da[i]||'',dr[i]||'')).join('')}</div>${s.department?`<div style="margin-top:6px;font-size:10px;color:var(--t3)">${IC.folder} ${esc(s.department)}</div>`:''}${IS_ADMIN?`<div style="margin-top:10px"><button class="btn btn-g btn-sm" onclick="editSystem(${s.id})">${IC.edit}</button> <button class="btn btn-g btn-sm" style="color:var(--err)" onclick="if(confirm('Excluir?'))api('system',{method:'DELETE',params:{id:${s.id}}}).then(loadSystems)">${IC.trash}</button></div>`:''}</div>`}).join(''):'<div class="empty" style="padding:40px;text-align:center"><p>Nenhum sistema encontrado</p></div>'}
function openSystemModal(d){document.getElementById('m-system-t').textContent=d?'Editar':'Novo Sistema';document.getElementById('s-edit-id').value=d?.id||'';document.getElementById('s-name').value=d?.name||'';document.getElementById('s-desc').value=d?.description||'';document.getElementById('s-status').value=d?.status||'Em desenvolvimento';document.getElementById('s-url').value=d?.url||'';document.getElementById('s-github').value=d?.github_url||'';document.getElementById('s-dept').value=d?.department||'';devCheckboxes('s-devs',d?.dev_ids?String(d.dev_ids).split(','):[]);
// Tech dropdown
const selTechs=(d?.technology||'').split(',').map(t=>t.trim()).filter(Boolean);
const tl=document.getElementById('s-tech-list');tl.innerHTML=TECH_LIST.map(t=>`<label><input type="checkbox" value="${t}" ${selTechs.includes(t)?'checked':''} onchange="updateTechDisplay()"> ${t}</label>`).join('');
updateTechDisplay();openM('m-system')}
async function editSystem(id){const sys=await api('systems')||[];const s=sys.find(x=>x.id==id);if(s)openSystemModal(s)}
async function saveSystem(){const eid=document.getElementById('s-edit-id').value;const selTechs=[...document.querySelectorAll('#s-tech-list input:checked')].map(c=>c.value);const body={name:document.getElementById('s-name').value.trim(),description:document.getElementById('s-desc').value.trim(),technology:selTechs.join(','),status:document.getElementById('s-status').value,url:document.getElementById('s-url').value.trim(),github_url:document.getElementById('s-github').value.trim(),department:document.getElementById('s-dept').value.trim(),dev_ids:getCheckedIds('s-devs')};if(!body.name)return alert('Nome obrigatório');if(eid)await api('system',{method:'PUT',params:{id:eid},body});else await api('systems',{method:'POST',body});closeM('m-system');await loadBaseData();loadSystems()}

// ===== DEVS (admin only) =====
async function loadDevs(){const users=await api('users')||[];const devUsers=users.filter(u=>(u.role||'').split(',').some(r=>['dev','diretor'].includes(r.trim())));const cards=[];for(const u of devUsers){const st=await api('user_stats',{params:{id:u.id}});cards.push(renderDevCard(u,st))}document.getElementById('devs-grid').innerHTML=cards.join('')}
function renderDevCard(u,st){const sys=(st?.sistemas||[]).map(s=>`<span class="tag">${esc(s.name)}</span>`).join('');const roleLabels={admin:'Administrador',dev:'Desenvolvedor',diretor:'Diretor',presidencia:'Presidência',usuario:'Usuário'};const roleColors={admin:'#ef4444',dev:'#3b82f6',diretor:'#8b5cf6',presidencia:'#f59e0b',usuario:'#6b7280'};const roles=(u.role||'').split(',').map(r=>r.trim()).filter(Boolean);const badges=roles.map(r=>`<span style="display:inline-flex;align-items:center;gap:4px;font-size:10px;font-weight:600;padding:2px 8px;border-radius:10px;background:${roleColors[r]||'#666'}22;color:${roleColors[r]||'#666'}">${roleLabels[r]||r}</span>`).join(' ');const avImg=u.avatar_file?`<img src="api.php?action=arquivo&f=${u.avatar_file}" style="width:100%;height:100%;border-radius:50%;object-fit:cover">`:(u.name||'?')[0];return`<div class="card dev-card" onclick="openDevDetail(${u.id})" style="cursor:pointer;transition:border-color .18s,box-shadow .18s,transform .15s" onmouseover="this.style.borderColor='var(--acc)';this.style.boxShadow='0 6px 24px rgba(0,0,0,.18)';this.style.transform='translateY(-2px)'" onmouseout="this.style.borderColor='';this.style.boxShadow='';this.style.transform=''"><div class="big-av" style="background:${u.avatar_color||'#666'}">${avImg}</div><h4>${esc(u.name)}</h4><div style="margin:4px 0 12px;display:flex;flex-wrap:wrap;gap:4px;justify-content:center">${badges}</div><div class="dev-stats"><div class="ds"><div class="ds-v" style="color:var(--warn)">${st?.ativas||0}</div><div class="ds-l">Ativas</div></div><div class="ds"><div class="ds-v" style="color:var(--ok)">${st?.concluidas||0}</div><div class="ds-l">Concluídas</div></div><div class="ds"><div class="ds-v" style="color:var(--acc)">${(st?.sistemas||[]).length}</div><div class="ds-l">Sistemas</div></div></div><div style="margin-top:10px;text-align:left"><div style="font-size:9px;color:var(--t3);text-transform:uppercase;letter-spacing:1px;margin-bottom:4px;font-weight:700">Sistemas</div><div style="display:flex;flex-wrap:wrap;gap:3px">${sys||'—'}</div></div>${IS_ADMIN?`<div style="margin-top:12px"><button class="btn btn-g btn-sm" onclick="editUser(${u.id})">${IC.edit}</button></div>`:''}</div>`}
function openUserModal(d){document.getElementById('m-user-t').textContent=d?'Editar':'Novo Usuário';document.getElementById('u-edit-id').value=d?.id||'';document.getElementById('u-name').value=d?.name||'';document.getElementById('u-email').value=d?.email||'';document.getElementById('u-pass').value='';document.getElementById('u-color').value=d?.avatar_color||'#3b82f6';const checks=document.querySelectorAll('#u-roles input[type=checkbox]');const roles=(d?.role||'dev').split(',').map(r=>r.trim());checks.forEach(cb=>{cb.checked=roles.includes(cb.value)});openM('m-user')}
async function editUser(id){const users=await api('users')||[];const u=users.find(x=>x.id==id);if(u)openUserModal(u)}
async function saveUser(){const eid=document.getElementById('u-edit-id').value;const selRoles=Array.from(document.querySelectorAll('#u-roles input:checked')).map(cb=>cb.value);if(!selRoles.length)return alert('Selecione pelo menos um cargo');const body={name:document.getElementById('u-name').value.trim(),email:document.getElementById('u-email').value.trim(),role:selRoles.join(','),avatar_color:document.getElementById('u-color').value};const pass=document.getElementById('u-pass').value;if(pass)body.password=pass;if(!body.name||!body.email)return alert('Nome e email obrigatórios');if(!eid&&!pass)return alert('Senha obrigatória');if(eid)await api('admin_user',{method:'PUT',params:{id:eid},body});else{body.password=pass;await api('admin_users',{method:'POST',body})}closeM('m-user');if(document.getElementById('page-usuarios'))loadUsers();loadDevs()}

// ===== MY PROFILE =====
// ===== TECH DROPDOWN HELPER =====
function updateTechDisplay(){const sel=[...document.querySelectorAll('#s-tech-list input:checked')].map(c=>c.value);const el=document.getElementById('s-tech-display');el.innerHTML=sel.length?sel.map(t=>`<span class="tech-tag">${t}</span>`).join(''):'<span style="color:var(--t3);font-size:11px">Selecione...</span>'}
document.addEventListener('click',e=>{if(!e.target.closest('.tech-dropdown')){document.querySelectorAll('.tech-dd-list.open').forEach(l=>l.classList.remove('open'))}})

// ===== CALENDAR =====
async function loadCalendario(){
window._dayEvts={};
const r=await api('calendar',{params:{month:calMonth}});
const demands=r?.demandas||[];const meetings=r?.reunioes||[];const sprints=r?.sprints||[];const calNotices=r?.avisos||[];const calNotes=r?.notes||[];
const [year,month]=[+calMonth.split('-')[0],+calMonth.split('-')[1]-1];
const mName=['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'][month];
const firstDay=new Date(year,month,1).getDay();const daysInMonth=new Date(year,month+1,0).getDate();
const prevDays=new Date(year,month,0).getDate();const today=new Date();const todayStr=today.getFullYear()+'-'+String(today.getMonth()+1).padStart(2,'0')+'-'+String(today.getDate()).padStart(2,'0');

// Build events map
const evMap={};
demands.forEach(d=>{
  // Track which days this demand appears on to avoid duplicates
  const addedDays=new Set();
  // Priority: done > deadline > start (only show one event per demand per day)
  if(d.completed_at){const k=d.completed_at.slice(0,10);if(!evMap[k])evMap[k]=[];evMap[k].push({type:'done',title:d.title,id:d.id});addedDays.add(k)}
  if(d.deadline){const k=d.deadline.slice(0,10);if(!addedDays.has(k)){if(!evMap[k])evMap[k]=[];evMap[k].push({type:'deadline',title:d.title,id:d.id,status:d.status,priority:d.priority,sys:d.system_name,devs:d.dev_names});addedDays.add(k)}}
  if(d.start_date&&d.status!=='Concluída'){const k=d.start_date.slice(0,10);if(!addedDays.has(k)){if(!evMap[k])evMap[k]=[];evMap[k].push({type:'start',title:d.title,id:d.id,status:d.status})}}
});
meetings.forEach(m=>{const k=m.meeting_date;if(!evMap[k])evMap[k]=[];evMap[k].push({type:'meeting',title:m.title,id:m.id,time:m.meeting_time?.slice(0,5),online:m.is_online})});
calNotices.forEach(n=>{if(n.event_date){const k=n.event_date;if(!evMap[k])evMap[k]=[];evMap[k].push({type:'notice',title:n.title,id:n.id,priority:n.priority})}});

// Notes map
const noteMap={};calNotes.forEach(n=>{noteMap[n.note_date]=n});
window._dayNotes=noteMap;

// Build sprint map
const sprintMap={};
sprints.forEach(sp=>{
  const s=new Date(sp.start_date+'T12:00:00'),e=new Date(sp.end_date+'T12:00:00');
  for(let d=new Date(s);d<=e;d.setDate(d.getDate()+1)){
    const k=d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0');
    if(!sprintMap[k])sprintMap[k]=[];sprintMap[k].push(sp);
  }
});

const prevM=new Date(year,month-1,1);const nextM=new Date(year,month+1,1);
const prevMStr=prevM.getFullYear()+'-'+String(prevM.getMonth()+1).padStart(2,'0');
const nextMStr=nextM.getFullYear()+'-'+String(nextM.getMonth()+1).padStart(2,'0');

let html=`<div class="cal-nav"><button class="btn btn-g btn-sm" onclick="calMonth='${prevMStr}';loadCalendario()">◀</button><h3>${mName} ${year}</h3><button class="btn btn-g btn-sm" onclick="calMonth='${nextMStr}';loadCalendario()">▶</button><button class="btn btn-g btn-sm" onclick="calMonth=new Date().toISOString().slice(0,7);loadCalendario()">Hoje</button></div>`;
// Sprint overview bars
if(sprints.length){
  html+=`<div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:12px">${sprints.map(sp=>{
    const pct=sp.demand_count>0?Math.round((sp.done_count/sp.demand_count)*100):0;
    const stCls={'Planejada':'s-aberta','Ativa':'s-andamento','Concluída':'s-concluida','Cancelada':'s-cancelada'}[sp.status]||'';
    return`<div class="sprint-bar" style="padding:6px 12px;border-radius:6px;gap:8px;font-size:11px;cursor:pointer" onclick="showPage('sprints')" title="${esc(sp.goal||'')}"><span class="sp-dot"></span>${esc(sp.name)} <span class="badge ${stCls}" style="font-size:9px;padding:1px 6px">${sp.status}</span><span style="font-size:10px;color:var(--t3);margin-left:4px">${fmtDate(sp.start_date)} — ${fmtDate(sp.end_date)}</span><span style="font-size:10px;margin-left:4px">${sp.done_count}/${sp.demand_count} (${pct}%)</span></div>`}).join('')}</div>`;
}
html+=`<div class="cal-legend"><span class="lg-dl">Prazo</span><span class="lg-st">Início</span><span class="lg-dn">Concluída</span><span class="lg-mt">Reunião</span><span class="lg-nt">Aviso</span><span class="lg-sp">Sprint</span><span class="lg-note">Nota</span></div>`;
html+=`<div class="cal-grid">`;
['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'].forEach(d=>{html+=`<div class="cal-head">${d}</div>`});
// Previous month days
for(let i=firstDay-1;i>=0;i--){html+=`<div class="cal-day other"><div class="cal-dn">${prevDays-i}</div></div>`}
// Current month days
for(let d=1;d<=daysInMonth;d++){
  const key=`${year}-${String(month+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
  const isToday=key===todayStr;const evts=evMap[key]||[];const daySprints=sprintMap[key]||[];
  const inSprint=daySprints.length>0;const hasNote=noteMap[key];
  html+=`<div class="cal-day${isToday?' today':''}${hasNote?' has-note':''}" ${inSprint?'style="border-top:2px solid var(--gold)"':''}><div class="cal-dn">${d}${hasNote?`<span class="cal-note-icon" title="${esc(hasNote.content)}">${IC.edit}</span>`:''}</div>`;
  // Show sprint name on start day or first day of month
  daySprints.forEach(sp=>{
    if(sp.start_date===key||d===1) html+=`<div class="sprint-bar" onclick="showPage('sprints')" title="${esc(sp.name)}: ${fmtDate(sp.start_date)} — ${fmtDate(sp.end_date)}"><span class="sp-dot"></span>${esc(sp.name.length>14?sp.name.slice(0,12)+'…':sp.name)}</div>`;
  });
  evts.slice(0,4).forEach(e=>{
    const cls={deadline:'ev-deadline',start:'ev-start',done:'ev-done',meeting:'ev-meeting',notice:'ev-notice'}[e.type];
    let click='';
    if(e.type==='meeting') click=`onclick="openMeetingView(${e.id})"`;
    else if(e.type==='notice') click=`onclick="openNoticeView(${e.id})"`;
    else click=`onclick="openDetail(${e.id})"`;
    const tip=e.type==='deadline'?IC.clock+' ':e.type==='start'?IC.play+' ':e.type==='done'?IC.check+' ':e.type==='notice'?IC.megaphone+' ':IC.cal+' ';
    html+=`<div class="cal-ev ${cls}" ${click} title="${esc(e.title)}${e.devs?' — '+esc(e.devs):''}">${tip}${esc(e.title.length>18?e.title.slice(0,16)+'…':e.title)}</div>`;
  });
  if(evts.length>4){window._dayEvts=window._dayEvts||{};window._dayEvts[key]=evts;html+=`<div class="cal-more" onclick="openDayModal('${key}',window._dayEvts['${key}'])">+${evts.length-4} ver tudo</div>`;}
  html+=`</div>`;
}
// Fill remaining
const totalCells=firstDay+daysInMonth;const remaining=totalCells%7?7-totalCells%7:0;
for(let i=1;i<=remaining;i++){html+=`<div class="cal-day other"><div class="cal-dn">${i}</div></div>`}
html+=`</div>`;

// Personal notes section
const allNotes=await api('calendar_notes',{params:{archived:0}})||[];
html+=`<div class="tbl-c" style="margin-top:20px"><div class="tbl-bar"><h3>${IC.edit} Minhas Anotações</h3><span style="font-size:10px;color:var(--t3);display:flex;align-items:center;gap:4px"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg> Pessoal — só você pode ver</span></div><div style="padding:14px">`;
// Search
html+=`<div style="margin-bottom:12px"><input id="note-search" type="text" placeholder="Buscar anotações..." oninput="filterNotes()" style="width:100%;max-width:320px;font-size:12px"></div>`;
// New note form
html+=`<div style="display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap;padding:12px;background:var(--bg3);border-radius:8px;margin-bottom:12px"><div class="fg" style="flex:0 0 130px;margin:0"><label style="font-size:10px">Data</label><input type="date" id="cal-note-date" value="${todayStr}"></div><div class="fg" style="flex:1;min-width:180px;margin:0"><label style="font-size:10px">Anotação</label><input id="cal-note-text" placeholder="Ex: Entregar relatório..." style="font-size:12px"></div><button class="btn btn-p btn-sm" onclick="saveCalNote()">Salvar</button></div>`;
// Notes list
html+=`<div id="notes-list">`;
if(allNotes.length){
  allNotes.forEach(n=>{
    html+=`<div class="note-item" data-content="${esc(n.content.toLowerCase())}" style="display:flex;align-items:center;gap:8px;padding:8px 10px;background:var(--bg2);border-radius:6px;font-size:12px;margin-bottom:4px;border-left:3px solid var(--acc)"><span style="font-family:'JetBrains Mono',monospace;font-size:10px;color:var(--acc);min-width:65px">${fmtDate(n.note_date)}</span><span style="flex:1;color:var(--t1)">${esc(n.content)}</span><button class="btn btn-g btn-sm" style="color:var(--err);padding:2px 5px" onclick="deleteCalNote(${n.id})" title="Excluir">${IC.trash}</button></div>`;
  });
} else {
  html+=`<div style="text-align:center;padding:20px;color:var(--t3);font-size:12px">Nenhuma anotação</div>`;
}
html+=`</div></div></div>`;

// Upcoming deadlines list
const upcoming=demands.filter(d=>d.deadline&&d.deadline>=todayStr&&d.status!=='Concluída'&&d.status!=='Cancelada').sort((a,b)=>a.deadline.localeCompare(b.deadline));
if(upcoming.length){
  html+=`<div class="tbl-c" style="margin-top:20px"><div class="tbl-bar"><h3>${IC.clock} Próximos Prazos</h3><span class="badge s-andamento">${upcoming.length}</span></div><div style="overflow-x:auto"><table><thead><tr><th>Prazo</th><th>Demanda</th><th>Sistema</th><th>Prioridade</th><th>Status</th><th>Devs</th></tr></thead><tbody>`;
  upcoming.slice(0,15).forEach(d=>{
    const diffDays=Math.ceil((new Date(d.deadline+'T12:00:00')-today)/(86400000));
    const urgStyle=diffDays<=2?'color:var(--err);font-weight:700':diffDays<=5?'color:var(--warn)':'color:var(--t2)';
    html+=`<tr style="cursor:pointer" onclick="openDetail(${d.id})"><td style="font-family:'JetBrains Mono',monospace;font-size:11px;${urgStyle}">${fmtDate(d.deadline)} <span style="font-size:9px">(${diffDays}d)</span></td><td style="font-weight:600">${esc(d.title)}</td><td><span class="tag">${esc(d.system_name||'—')}</span></td><td><span class="badge ${pClass(d.priority)}">${d.priority}</span></td><td><span class="badge ${sClass(d.status)}">${d.status}</span></td><td style="font-size:10px">${esc(d.dev_names||'—')}</td></tr>`;
  });
  html+=`</tbody></table></div></div>`;
}
document.getElementById('calendario-content').innerHTML=html}
async function saveCalNote(){const date=document.getElementById('cal-note-date').value;const text=document.getElementById('cal-note-text').value.trim();if(!date||!text)return alert('Preencha data e anotação');await api('calendar_notes',{method:'POST',body:{note_date:date,content:text}});document.getElementById('cal-note-text').value='';loadCalendario()}
async function deleteCalNote(id){if(!confirm('Excluir anotação?'))return;await api('calendar_note',{method:'DELETE',params:{id}});loadCalendario()}
async function archiveNote(id){await api('calendar_note',{method:'PUT',params:{id},body:{archived:1}});showToast('Anotação arquivada');loadCalendario()}
async function unarchiveNote(id){await api('calendar_note',{method:'PUT',params:{id},body:{archived:0}});showToast('Anotação restaurada');loadCalendario()}
function filterNotes(){const q=(document.getElementById('note-search')?.value||'').toLowerCase();document.querySelectorAll('.note-item').forEach(el=>{el.style.display=!q||el.dataset.content.includes(q)?'flex':'none'})}
let showingArchived=false;
async function toggleArchived(){showingArchived=!showingArchived;const btn=document.getElementById('btn-archived');if(showingArchived){btn.classList.add('btn-p');btn.classList.remove('btn-g');const notes=await api('calendar_notes',{params:{archived:1}})||[];const list=document.getElementById('notes-list');if(!notes.length){list.innerHTML='<div style="text-align:center;padding:20px;color:var(--t3);font-size:12px">Nenhuma anotação arquivada</div>';return}list.innerHTML=notes.map(n=>`<div class="note-item" style="display:flex;align-items:center;gap:8px;padding:6px 10px;background:var(--bg2);border-radius:6px;font-size:12px;margin-bottom:4px;opacity:.7"><span style="font-family:'JetBrains Mono',monospace;font-size:10px;color:var(--t3);min-width:65px">${fmtDate(n.note_date)}</span><span style="flex:1;color:var(--t2)">${esc(n.content)}</span>${n.folder?`<span class="tag" style="font-size:9px">${esc(n.folder)}</span>`:''}<button class="btn btn-g btn-sm" style="padding:2px 5px;font-size:10px" onclick="unarchiveNote(${n.id})" title="Restaurar">↩</button><button class="btn btn-g btn-sm" style="color:var(--err);padding:2px 5px" onclick="deleteCalNote(${n.id})">${IC.trash}</button></div>`).join('')}else{btn.classList.remove('btn-p');btn.classList.add('btn-g');loadCalendario()}}
function openFolderModal(){const modal=document.getElementById('m-folders');if(!modal)return;loadFoldersList();openM('m-folders')}
async function loadFoldersList(){const folders=await api('note_folders')||[];document.getElementById('folders-list').innerHTML=folders.length?folders.map(f=>`<div style="display:flex;align-items:center;gap:8px;padding:8px 10px;background:var(--bg3);border-radius:6px;margin-bottom:4px"><span style="width:12px;height:12px;border-radius:3px;background:${f.color}"></span><span style="flex:1;font-size:13px">${esc(f.name)}</span><button class="btn btn-g btn-sm" style="color:var(--err);padding:2px 6px" onclick="deleteFolder(${f.id})">${IC.trash}</button></div>`).join(''):'<div style="color:var(--t3);font-size:12px;padding:10px">Nenhuma pasta criada</div>'}
async function createFolder(){const name=document.getElementById('new-folder-name').value.trim();const color=document.getElementById('new-folder-color').value;if(!name)return;await api('note_folders',{method:'POST',body:{name,color}});document.getElementById('new-folder-name').value='';loadFoldersList();loadCalendario()}
async function deleteFolder(id){if(!confirm('Excluir pasta? As anotações serão mantidas sem pasta.'))return;await api('note_folders',{method:'DELETE',params:{id}});loadFoldersList();loadCalendario()}
function openDayModal(dateKey,evts){const d=new Date(dateKey+'T12:00:00');const dayName=['Domingo','Segunda','Terça','Quarta','Quinta','Sexta','Sábado'][d.getDay()];document.getElementById('day-modal-title').textContent=`${dayName}, ${fmtDate(dateKey)}`;const typeLabels={deadline:'Prazo',start:'Início',done:'Concluída',meeting:'Reunião',notice:'Aviso'};const typeIcons={deadline:IC.clock,start:IC.play,done:IC.check,meeting:IC.cal,notice:IC.megaphone};let body=evts.map(e=>{const cls={deadline:'ev-deadline',start:'ev-start',done:'ev-done',meeting:'ev-meeting',notice:'ev-notice'}[e.type];let click='';if(e.type==='meeting')click=`onclick="closeM('m-day');openMeetingView(${e.id})"`;else if(e.type==='notice')click=`onclick="closeM('m-day');openNoticeView(${e.id})"`;else click=`onclick="closeM('m-day');openDetail(${e.id})"`;return`<div class="cal-ev-full ${cls}" ${click} style="display:flex;align-items:center;gap:10px;padding:10px 12px;background:var(--bg3);border-radius:8px;cursor:pointer;margin-bottom:6px"><span style="flex-shrink:0">${typeIcons[e.type]||''}</span><div style="flex:1;min-width:0"><div style="font-weight:600;font-size:13px">${esc(e.title)}</div><div style="font-size:10px;color:var(--t3)">${typeLabels[e.type]||e.type}${e.time?' · '+e.time:''}${e.devs?' · '+esc(e.devs):''}</div></div></div>`}).join('');
// Show personal note for this day if exists
const dayNote=window._dayNotes&&window._dayNotes[dateKey];
if(dayNote) body+=`<div style="margin-top:8px;padding:10px 12px;background:var(--accg,rgba(99,102,241,.1));border-radius:8px;border-left:3px solid var(--acc)"><div style="font-size:9px;font-weight:700;text-transform:uppercase;color:var(--acc);margin-bottom:4px">${IC.edit} Anotação pessoal</div><div style="font-size:12px;color:var(--t1)">${esc(dayNote.content)}</div></div>`;
document.getElementById('day-modal-body').innerHTML=body;openM('m-day')}

// ===== SPRINTS =====
async function loadSprints(){
const sprints=await api('sprints')||[];const demands=await api('demands')||[];
const active=sprints.find(s=>s.status==='Ativa');
let html='';
if(!IS_DEV){html+=`<div style="margin-bottom:14px"><button class="btn btn-p" onclick="openSprintModal()"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> Nova Sprint</button></div>`}
if(!sprints.length){html+='<div class="empty"><p>Nenhuma sprint criada</p></div>';document.getElementById('sprints-content').innerHTML=html;return}
html+=`<div style="display:grid;gap:14px">`;
sprints.forEach(sp=>{
  const pct=sp.demand_count>0?Math.round((sp.done_count/sp.demand_count)*100):0;
  const stCls={'Planejada':'s-aberta','Ativa':'s-andamento','Concluída':'s-concluida','Cancelada':'s-cancelada'}[sp.status]||'';
  const spDemands=demands.filter(d=>d.sprint_id==sp.id);
  const daysTotal=Math.ceil((new Date(sp.end_date+'T12:00:00')-new Date(sp.start_date+'T12:00:00'))/86400000);
  const daysLeft=Math.ceil((new Date(sp.end_date+'T12:00:00')-Date.now())/86400000);
  html+=`<div class="sprint-card ${sp.status==='Ativa'?'ativa':''}"><div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap"><h4 style="margin:0;font-size:15px">${esc(sp.name)}</h4><span class="badge ${stCls}">${sp.status}</span>${!IS_DEV?`<div style="margin-left:auto;display:flex;gap:4px"><button class="btn btn-g btn-sm" onclick="openSprintModal(${sp.id})">${IC.edit}</button>${IS_ADMIN?`<button class="btn btn-g btn-sm" style="color:var(--err)" onclick="deleteSprint(${sp.id})">${IC.trash}</button>`:''}</div>`:''}</div>`;
  if(sp.goal) html+=`<p style="font-size:12px;color:var(--t2);margin:6px 0 0">${esc(sp.goal)}</p>`;
  html+=`<div style="display:flex;gap:16px;margin-top:10px;font-size:11px;color:var(--t3);flex-wrap:wrap"><span style="font-family:'JetBrains Mono',monospace">${fmtDate(sp.start_date)} — ${fmtDate(sp.end_date)}</span><span>${daysTotal} dias${daysLeft>0&&sp.status==='Ativa'?` (${daysLeft}d restantes)`:''}</span><span>${sp.demand_count} demandas</span><span style="color:var(--ok)">${sp.done_count} concluídas</span><span style="font-weight:700;color:var(--acc)">${pct}%</span></div>`;
  html+=`<div class="sprint-progress"><div class="sprint-progress-bar" style="width:${pct}%"></div></div>`;
  // Demands in this sprint
  if(spDemands.length){
    html+=`<div style="margin-top:10px"><table style="width:100%"><tbody>${spDemands.map(d=>`<tr onclick="openDetail(${d.id})" style="cursor:pointer"><td style="font-weight:600;font-size:12px">${esc(d.title)}</td><td><span class="badge ${pClass(d.priority)}" style="font-size:9px">${d.priority}</span></td><td><span class="badge ${sClass(d.status)}" style="font-size:9px">${d.status}</span></td><td style="font-size:10px">${devsHtml(d.devs)}</td></tr>`).join('')}</tbody></table></div>`;
  }
  html+=`</div>`;
});
html+=`</div>`;
document.getElementById('sprints-content').innerHTML=html}

function openSprintModal(id){
  document.getElementById('m-sprint-t').textContent=id?'Editar Sprint':'Nova Sprint';
  document.getElementById('sp-edit-id').value=id||'';
  if(id){
    const sp=allSprints.find(s=>s.id==id);
    if(sp){document.getElementById('sp-name').value=sp.name;document.getElementById('sp-goal').value=sp.goal||'';document.getElementById('sp-start').value=sp.start_date;document.getElementById('sp-end').value=sp.end_date;document.getElementById('sp-status').value=sp.status}
  } else {
    document.getElementById('sp-name').value='';document.getElementById('sp-goal').value='';document.getElementById('sp-start').value='';document.getElementById('sp-end').value='';document.getElementById('sp-status').value='Planejada';
  }
  openM('m-sprint');
}
async function saveSprint(){
  const eid=document.getElementById('sp-edit-id').value;
  const body={name:document.getElementById('sp-name').value.trim(),goal:document.getElementById('sp-goal').value.trim(),start_date:document.getElementById('sp-start').value,end_date:document.getElementById('sp-end').value,status:document.getElementById('sp-status').value};
  if(!body.name||!body.start_date||!body.end_date)return alert('Preencha nome e datas');
  if(eid)await api('sprint',{method:'PUT',params:{id:eid},body});else await api('sprints',{method:'POST',body});
  closeM('m-sprint');await loadBaseData();loadSprints();
}
async function deleteSprint(id){if(!confirm('Excluir esta sprint? As demandas vinculadas perderão a associação.'))return;await api('sprint',{method:'DELETE',params:{id}});await loadBaseData();loadSprints()}

// ===== AUDIT =====
async function loadAuditoria(){
const p={limit:200};
const uf=document.getElementById('aud-user')?.value;if(uf)p.user_id=uf;
const ef=document.getElementById('aud-entity')?.value;if(ef)p.entity_type=ef;
const df=document.getElementById('aud-from')?.value;if(df)p.date_from=df;
const dt2=document.getElementById('aud-to')?.value;if(dt2)p.date_to=dt2;
const sf=document.getElementById('aud-search')?.value;if(sf)p.search=sf;
const r=await api('audit',{params:p});
const logs=r?.logs||[];const total=r?.total||0;const byUser=r?.by_user||[];const byType=r?.by_type||[];

let html=`<div class="audit-stat"><div class="as"><div class="as-v">${total}</div><div class="as-l">Total de ações</div></div><div class="as"><div class="as-v">${byUser.length}</div><div class="as-l">Usuários ativos</div></div><div class="as"><div class="as-v">${byType.length}</div><div class="as-l">Tipos de entidade</div></div><div class="as"><div class="as-v">${logs.length}</div><div class="as-l">Exibindo</div></div></div>`;

// Filters
html+=`<div class="tbl-c" style="margin-bottom:16px"><div style="display:flex;flex-wrap:wrap;gap:8px;padding:12px"><select class="fsel" id="aud-user" onchange="loadAuditoria()"><option value="">Todos os usuários</option>${allUsers.map(u=>`<option value="${u.id}" ${uf==u.id?'selected':''}>${esc(u.name)} (${u.role})</option>`).join('')}</select><select class="fsel" id="aud-entity" onchange="loadAuditoria()"><option value="">Todas as entidades</option>${byType.map(t=>`<option value="${t.entity_type}" ${ef===t.entity_type?'selected':''}>${t.entity_type} (${t.c})</option>`).join('')}</select><input type="date" class="fsel" id="aud-from" value="${df||''}" onchange="loadAuditoria()"><input type="date" class="fsel" id="aud-to" value="${dt2||''}" onchange="loadAuditoria()"><input class="fsel" id="aud-search" placeholder="Buscar ação..." value="${sf||''}" oninput="clearTimeout(this._t);this._t=setTimeout(loadAuditoria,400)" style="min-width:180px"></div></div>`;

// Top users
html+=`<div style="display:flex;gap:16px;margin-bottom:16px;flex-wrap:wrap">`;
byUser.slice(0,6).forEach(u=>{html+=`<div class="card" style="flex:1;min-width:120px;padding:12px"><div style="font-weight:700;font-size:13px">${esc(u.name)}</div><div style="font-size:9px;color:var(--t3);margin-bottom:4px">${ROLE_LABELS[u.role]||u.role}</div><div style="font-size:18px;font-weight:800;color:var(--acc);font-family:'JetBrains Mono',monospace">${u.actions}</div><div style="font-size:9px;color:var(--t3)">ações</div></div>`});
html+=`</div>`;

// Logs table
html+=`<div class="tbl-c"><div class="tbl-bar"><h3>${IC.lock} Registro de Auditoria</h3></div><div style="overflow-x:auto"><table><thead><tr><th style="width:140px">Data/Hora</th><th>Usuário</th><th>Nível</th><th>Ação</th><th>Entidade</th><th>ID</th></tr></thead><tbody>`;
logs.forEach(l=>{
  html+=`<tr><td style="font-family:'JetBrains Mono',monospace;font-size:10px;color:var(--t3);white-space:nowrap">${fmtDT(l.created_at)}</td><td style="font-weight:600;font-size:12px">${esc(l.user_name||'Sistema')}</td><td><span class="role-badge role-${l.user_role||'usuario'}">${ROLE_LABELS[l.user_role]||l.user_role||'—'}</span></td><td style="font-size:11px">${esc(l.action||'')}</td><td style="font-size:10px"><span class="tag">${esc(l.entity_type||'—')}</span></td><td style="font-family:'JetBrains Mono',monospace;font-size:10px;color:var(--t3)">${l.entity_id||'—'}</td></tr>`;
});
if(!logs.length) html+='<tr><td colspan="6"><div class="empty"><p>Nenhum registro encontrado</p></div></td></tr>';
html+=`</tbody></table></div></div>`;
document.getElementById('auditoria-content').innerHTML=html}
async function loadSolicitations(){const sols=await api('solicitations')||[];const CAN_REVIEW=IS_ADMIN||IS_DIR;
const sf=document.getElementById('sol-f-status')?.value||'';
const tf=document.getElementById('sol-f-type')?.value||'';
const syf=document.getElementById('sol-f-system')?.value||'';
const filtered=sols.filter(s=>{if(sf&&s.status!==sf)return false;if(tf&&(s.type||'Melhoria')!==tf)return false;if(syf&&s.system_id!=syf)return false;return true});
let html=`<div style="margin-bottom:16px;display:flex;gap:8px;flex-wrap:wrap;align-items:center"><button class="btn btn-p" onclick="openSolModal()">${IC.bulb} Nova Solicitação</button><select class="fsel" id="sol-f-status" onchange="loadSolicitations()"><option value="">Todos os status</option><option value="Pendente" ${sf==='Pendente'?'selected':''}>Pendente</option><option value="Aprovada" ${sf==='Aprovada'?'selected':''}>Aprovada</option><option value="Rejeitada" ${sf==='Rejeitada'?'selected':''}>Rejeitada</option><option value="Convertida" ${sf==='Convertida'?'selected':''}>Convertida</option></select><select class="fsel" id="sol-f-type" onchange="loadSolicitations()"><option value="">Todos os tipos</option><option value="Melhoria" ${tf==='Melhoria'?'selected':''}>Melhoria</option><option value="Correção" ${tf==='Correção'?'selected':''}>Correção</option><option value="Nova Funcionalidade" ${tf==='Nova Funcionalidade'?'selected':''}>Nova Funcionalidade</option><option value="Sugestão de Usuário" ${tf==='Sugestão de Usuário'?'selected':''}>Sugestão de Usuário</option></select><select class="fsel" id="sol-f-system" onchange="loadSolicitations()"><option value="">Todos os sistemas</option>${allSystems.map(s=>`<option value="${s.id}" ${syf==s.id?'selected':''}>${esc(s.name)}</option>`).join('')}</select></div>`;
html+=`<div class="tbl-c"><div class="tbl-bar"><h3>Solicitações</h3><span class="badge" style="background:var(--bg4)">${filtered.filter(s=>s.status==='Pendente').length} pendentes</span><span style="font-size:11px;color:var(--t3);margin-left:8px">${filtered.length} de ${sols.length}</span></div><div style="overflow-x:auto"><table><thead><tr><th>#</th><th>Título</th><th>Tipo</th><th>Sistema</th><th>Prioridade</th><th>Status</th><th>Solicitante</th><th>Analisado por</th><th>Data</th>${CAN_REVIEW?'<th>Ação</th>':''}</tr></thead><tbody>${filtered.length?filtered.map(s=>{const tpCls={'Melhoria':'s-andamento','Correção':'s-cancelada','Nova Funcionalidade':'s-aberta','Sugestão de Usuário':'s-aguardando'}[s.type||'Melhoria']||'';return`<tr><td style="font-family:'JetBrains Mono',monospace;color:var(--t3)">#${s.id}</td><td style="font-weight:600">${esc(s.title)}</td><td><span class="badge ${tpCls}" style="font-size:9px">${esc(s.type||'Melhoria')}</span></td><td><span class="tag">${esc(s.system_name||'—')}</span></td><td><span class="badge ${pClass(s.priority)}">${s.priority}</span></td><td><span class="badge ${{Pendente:'accept-pendente',Aprovada:'accept-aceita',Rejeitada:'accept-recusada',Convertida:'s-concluida'}[s.status]}">${s.status}</span></td><td>${esc(s.creator_name||'—')}</td><td>${s.reviewer_name?esc(s.reviewer_name)+' <span style="font-size:9px;color:var(--t3)">'+fmtDT(s.reviewed_at)+'</span>':'—'}</td><td style="font-size:10px;font-family:'JetBrains Mono',monospace">${fmtDT(s.created_at)}</td>${CAN_REVIEW?`<td onclick="event.stopPropagation()">${s.status==='Pendente'?`<button class="btn btn-ok btn-sm" onclick="openSolReview(${s.id},'${esc(s.title)}','${esc(s.description||'')}','${s.system_id||''}','${s.priority||'Média'}','${esc(s.creator_name||'')}')">Analisar</button>`:s.converted_demand_id?`<button class="btn btn-g btn-sm" onclick="openDetail(${s.converted_demand_id})">Ver demanda</button>`:''}</td>`:''}</tr>`}).join(''):'<tr><td colspan="10"><div class="empty"><p>Nenhuma solicitação encontrada</p></div></td></tr>'}</tbody></table></div></div>`;
document.getElementById('sol-content').innerHTML=html}
function openSolModal(){document.getElementById('sol-title').value='';document.getElementById('sol-desc').value='';document.getElementById('sol-system').innerHTML='<option value="">Selecione...</option>'+allSystems.map(s=>`<option value="${s.id}">${esc(s.name)}</option>`).join('');document.getElementById('sol-priority').value='Média';document.getElementById('sol-type').value=IS_USER?'Sugestão de Usuário':'Melhoria';openM('m-solicitation')}
async function saveSolicitation(){const body={title:document.getElementById('sol-title').value.trim(),description:document.getElementById('sol-desc').value.trim(),system_id:document.getElementById('sol-system').value||null,type:document.getElementById('sol-type').value,priority:document.getElementById('sol-priority').value};if(!body.title||!body.description)return alert('Preencha título e descrição');await api('solicitations',{method:'POST',body});closeM('m-solicitation');showToast('Solicitação enviada!');loadSolicitations()}
function openSolReview(id,title,desc,sysId,pri,creatorName){document.getElementById('sr-id').value=id;document.getElementById('sr-sys-id').value=sysId||'';document.getElementById('sr-pri').value=pri||'Média';document.getElementById('sr-title').textContent='#'+id+' — '+title;document.getElementById('sr-desc').textContent=desc;document.getElementById('sr-notes').value='';const crEl=document.getElementById('sr-creator');if(crEl)crEl.textContent=creatorName||'';openM('m-sol-review')}
async function approveSolAsDemand(){const solId=document.getElementById('sr-id').value;const title=document.getElementById('sr-title').textContent.replace(/^#\d+\s*—\s*/,'');const desc=document.getElementById('sr-desc').textContent;const sysId=document.getElementById('sr-sys-id').value;const pri=document.getElementById('sr-pri').value;const notes=document.getElementById('sr-notes').value.trim();const solicitante=document.getElementById('sr-creator')?.textContent||'';closeM('m-sol-review');openNewDemand();document.getElementById('d-title').value=title;document.getElementById('d-desc').value=desc;if(sysId){document.getElementById('d-system').value=sysId}document.getElementById('d-priority').value=pri||'Média';document.getElementById('d-requester').value=solicitante||('Solicitação #'+solId);document.getElementById('d-edit-id').value='';document.getElementById('d-edit-id').dataset.solicitationId=solId;document.getElementById('d-edit-id').dataset.solNotes=notes;document.getElementById('m-demand-t').textContent='Criar Demanda (Solicitação #'+solId+')'}
async function reviewSol(status){const id=document.getElementById('sr-id').value;const notes=document.getElementById('sr-notes').value.trim();await api('solicitation_review',{method:'POST',params:{id},body:{status,review_notes:notes}});closeM('m-sol-review');loadSolicitations()}

 
// ===== COMPREHENSIVE REPORTS =====
function loadScriptOnce(url){return new Promise((ok,fail)=>{if(document.querySelector('script[src="'+url+'"]'))return ok();const s=document.createElement('script');s.src=url;s.onload=ok;s.onerror=fail;document.head.appendChild(s)})}

// ===== REPORT FILTER STATE =====
var _repState={systems:[],devs:[],priorities:[],sprints:[],charts:['status','timeline','prod','avgdays','priority','system','sla','workload','flow','cancel','bydev','bysys','proddetail']};
var _repNeedsReload=false;
function repGetState(){
  var el;
  el=document.querySelectorAll('input[name="rep-system"]:checked');if(el.length)_repState.systems=Array.from(el).map(function(e){return e.value});
  el=document.querySelectorAll('input[name="rep-dev"]:checked');if(el.length)_repState.devs=Array.from(el).map(function(e){return e.value});
  el=document.querySelectorAll('input[name="rep-priority"]:checked');if(el.length)_repState.priorities=Array.from(el).map(function(e){return e.value});
  el=document.querySelectorAll('input[name="rep-sprint"]:checked');if(el.length)_repState.sprints=Array.from(el).map(function(e){return e.value});
  el=document.querySelectorAll('input[name="rep-charts"]:checked');
  _repState.charts=el.length?Array.from(el).map(function(e){return e.value}):['status','timeline','prod','avgdays','priority','system','sla','workload','flow','cancel','syshealth','bydev','bysys','proddetail'];
}
function repToggle(cb){
  repGetState();
  var nm=cb.name;
  var val=cb.value;
  if(nm==='rep-system'){if(cb.checked){if(_repState.systems.indexOf(val)<0)_repState.systems.push(val)}else{_repState.systems=_repState.systems.filter(function(v){return v!==val})}}
  if(nm==='rep-dev'){if(cb.checked){if(_repState.devs.indexOf(val)<0)_repState.devs.push(val)}else{_repState.devs=_repState.devs.filter(function(v){return v!==val})}}
  if(nm==='rep-priority'){if(cb.checked){if(_repState.priorities.indexOf(val)<0)_repState.priorities.push(val)}else{_repState.priorities=_repState.priorities.filter(function(v){return v!==val})}}
  if(nm==='rep-sprint'){if(cb.checked){if(_repState.sprints.indexOf(val)<0)_repState.sprints.push(val)}else{_repState.sprints=_repState.sprints.filter(function(v){return v!==val})}}
  if(nm==='rep-charts'){
    if(cb.checked){if(_repState.charts.indexOf(val)<0)_repState.charts.push(val)}else{_repState.charts=_repState.charts.filter(function(v){return v!==val})}
    // Update visual state
    var item=cb.closest('.rep-cb-item');
    if(item){
      if(cb.checked){item.classList.add('active')}else{item.classList.remove('active')}
    }
    applyChartVisibility();
  }else{
    loadReports();
  }
}
function repSelectAll(name){
  var boxes=document.querySelectorAll('input[name="'+name+'"]');
  var allChecked=Array.from(boxes).every(function(b){return b.checked});
  boxes.forEach(function(b){b.checked=!allChecked;var item=b.closest('.rep-cb-item');if(item)item.classList.toggle('active',!allChecked)});
  repGetState();
  if(name==='rep-system')_repState.systems=!allChecked?Array.from(boxes).map(function(b){return b.value}):[];
  if(name==='rep-dev')_repState.devs=!allChecked?Array.from(boxes).map(function(b){return b.value}):[];
  if(name==='rep-priority')_repState.priorities=!allChecked?Array.from(boxes).map(function(b){return b.value}):[];
  if(name==='rep-sprint')_repState.sprints=!allChecked?Array.from(boxes).map(function(b){return b.value}):[];
  loadReports();
}
function chartSelectAll(){
  var boxes=document.querySelectorAll('input[name="rep-charts"]');
  var allChecked=Array.from(boxes).every(function(b){return b.checked});
  boxes.forEach(function(b){
    b.checked=!allChecked;
    var item=b.closest('.rep-cb-item');
    if(item)item.classList.toggle('active',!allChecked)
  });
  _repState.charts=!allChecked?Array.from(boxes).map(function(b){return b.value}):[];
  applyChartVisibility();
}
function applyChartVisibility(){
  var checked=_repState.charts;
  
  // Find all elements with data-chart attribute
  var allChartElements=document.querySelectorAll('[data-chart]');
  
  allChartElements.forEach(function(element){
    var key=element.getAttribute('data-chart');
    if(key){
      // If no charts selected, show all. Otherwise, show only selected ones
      var shouldShow=checked.length===0||checked.indexOf(key)>=0;
      element.style.display=shouldShow?'':'none';
    }
  });
}

function toggleRepAccordion(header){
  var accordion=header.parentElement;
  var content=accordion.querySelector('.rep-acc-content');
  var icon=header.querySelector('.acc-icon');
  var isOpen=accordion.classList.contains('open');
  
  if(isOpen){
    accordion.classList.remove('open');
    content.style.maxHeight='0px';
    icon.style.transform='rotate(0deg)';
  }else{
    accordion.classList.add('open');
    content.style.maxHeight=content.scrollHeight+'px';
    icon.style.transform='rotate(90deg)';
  }
}

function repSyncSelect(select,stateKey){
  var selected=Array.from(select.selectedOptions).filter(o=>o.value).map(o=>o.value);
  _repState[stateKey]=selected;
  loadReports();
}

function toggleRepDropdown(btn){
  var dropdown=btn.parentElement;
  var panel=dropdown.querySelector('.rep-dd-panel');
  var allDropdowns=document.querySelectorAll('.rep-custom-dropdown');
  var wasOpen=dropdown.classList.contains('open');
  
  // Close all dropdowns
  allDropdowns.forEach(function(dd){
    dd.classList.remove('open');
    var p=dd.querySelector('.rep-dd-panel');
    if(p)p.style.display='none';
  });
  
  // Toggle this one
  if(!wasOpen){
    dropdown.classList.add('open');
    panel.style.display='block';
  }
}

function repDDChange(checkbox){
  var type=checkbox.dataset.type;
  var value=checkbox.dataset.value;
  var label=checkbox.closest('.rep-dd-opt');
  
  if(checkbox.checked){
    label.classList.add('active');
    if(_repState[type].indexOf(value)<0){
      _repState[type].push(value);
    }
  }else{
    label.classList.remove('active');
    _repState[type]=_repState[type].filter(function(v){return v!==value});
  }
  
  // Update button counter without reloading
  updateDropdownCounter(checkbox.closest('.rep-custom-dropdown'),type);
  _repNeedsReload=true;
}

function updateDropdownCounter(dropdown,type){
  var btn=dropdown.querySelector('.rep-dd-btn');
  var count=_repState[type].length;
  var label=btn.textContent.split('(')[0].trim();
  btn.innerHTML=label+(count?' ('+count+')':'')+' <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>';
}

function repDDToggleAll(checkbox,type){
  var dropdown=checkbox.closest('.rep-custom-dropdown');
  var opts=dropdown.querySelectorAll('input[data-type="'+type+'"]');
  
  opts.forEach(function(cb){
    cb.checked=checkbox.checked;
    var label=cb.closest('.rep-dd-opt');
    if(checkbox.checked){
      label.classList.add('active');
    }else{
      label.classList.remove('active');
    }
  });
  
  if(checkbox.checked){
    _repState[type]=Array.from(opts).map(function(cb){return cb.dataset.value});
  }else{
    _repState[type]=[];
  }
  
  // Update button counter without reloading
  updateDropdownCounter(dropdown,type);
  _repNeedsReload=true;
}

function filterRepDD(input){
  var term=input.value.toLowerCase();
  var opts=input.closest('.rep-dd-panel').querySelectorAll('.rep-dd-opt');
  
  opts.forEach(function(opt,i){
    if(i===0)return; // Skip "Todos"
    var text=opt.textContent.toLowerCase();
    opt.style.display=text.indexOf(term)>=0?'':'none';
  });
}

function toggleChartPanel(){
  var panel=document.getElementById('chart-panel');
  var btn=document.getElementById('chart-toggle-btn');
  if(!panel||!btn)return;
  
  var isHidden=panel.style.display==='none'||!panel.style.display||panel.style.display==='';
  
  if(isHidden){
    panel.style.display='block';
    btn.classList.add('active');
  }else{
    panel.style.display='none';
    btn.classList.remove('active');
  }
}

// Close dropdowns when clicking outside
var _repNeedsReload=false;
document.addEventListener('click',function(e){
  if(!e.target.closest('.rep-custom-dropdown')){
    var hadOpenDropdown=false;
    document.querySelectorAll('.rep-custom-dropdown').forEach(function(dd){
      if(dd.classList.contains('open')){
        hadOpenDropdown=true;
      }
      dd.classList.remove('open');
      var p=dd.querySelector('.rep-dd-panel');
      if(p)p.style.display='none';
    });
    
    // Reload reports if dropdown was open and changes were made
    if(hadOpenDropdown&&_repNeedsReload){
      _repNeedsReload=false;
      loadReports();
    }
  }
});

async function loadReports(){
const dateFrom=document.getElementById('rep-from')?.value||new Date(Date.now()-90*86400000).toISOString().split('T')[0];
const dateTo=document.getElementById('rep-to')?.value||new Date().toISOString().split('T')[0];
var fSys=_repState.systems.join(',');
var fDev=_repState.devs.join(',');
var fPri=_repState.priorities.join(',');
var fSprint=_repState.sprints.join(',');
const p={date_from:dateFrom,date_to:dateTo};
if(fSys)p.system_id=fSys;if(fDev)p.dev_id=fDev;if(fPri)p.priority=fPri;if(fSprint)p.sprint_id=fSprint;

const[genStats,byDev,bySys,timeline,productivity,sysHealth]=await Promise.all([
  api('reports',{params:{...p,type:'general_stats'}}),
  api('reports',{params:{...p,type:'by_dev'}}),
  api('reports',{params:{...p,type:'by_system'}}),
  api('reports',{params:{...p,type:'timeline'}}),
  api('reports',{params:{...p,type:'productivity'}}),
  api('reports',{params:{...p,type:'system_health'}})
]);

const ov=genStats?.overview||{};const sla=genStats?.sla||{};
const slaRate=sla.total?Math.round((sla.on_time/sla.total)*100):0;
const completionRate=ov.total?Math.round((ov.concluidas/ov.total)*100):0;
const avgDays=ov.avg_days?parseFloat(ov.avg_days).toFixed(1):'—';
const statusDist=genStats?.status_dist||[];const priDist=genStats?.priority_dist||[];
const cancelRate=ov.total?Math.round(((ov.canceladas||0)/ov.total)*100):0;
const avgPerMonth=timeline&&timeline.length?Math.round(timeline.reduce((a,t)=>a+(t.criadas||0),0)/timeline.length):0;

let html='';
// FILTERS

// Calculate active filters count
var sysActive=_repState.systems.length;
var devActive=_repState.devs.length;
var priActive=_repState.priorities.length;
var sprActive=_repState.sprints.length;

// ULTRA-COMPACT FILTERS - Single row with custom dropdowns
html+='<div class="rep-filters-ultra-compact">';
html+='<div class="rep-ultra-row">';

// Dates
html+='<div class="rep-date-compact"><label>De</label><input type="date" class="fsel-compact" id="rep-from" value="'+dateFrom+'" onchange="loadReports()"></div>';
html+='<div class="rep-date-compact"><label>Até</label><input type="date" class="fsel-compact" id="rep-to" value="'+dateTo+'" onchange="loadReports()"></div>';

// Sistema custom dropdown
html+='<div class="rep-custom-dropdown"><button class="rep-dd-btn" onclick="toggleRepDropdown(this)">Sistema'+(sysActive?' ('+sysActive+')':'')+'<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg></button><div class="rep-dd-panel"><div class="rep-dd-search"><input type="text" placeholder="Buscar..." onkeyup="filterRepDD(this)"></div><div class="rep-dd-opts">';
html+='<label class="rep-dd-opt"><input type="checkbox" onchange="repDDToggleAll(this,\'systems\')" '+(sysActive===allSystems.length?'checked':'')+'><span>Todos</span></label>';
(allSystems||[]).forEach(function(s){var chk=_repState.systems.indexOf(String(s.id))>=0;html+='<label class="rep-dd-opt'+(chk?' active':'')+'"><input type="checkbox" data-type="systems" data-value="'+s.id+'"'+(chk?' checked':'')+' onchange="repDDChange(this)"><span>'+esc(s.name)+'</span></label>'});
html+='</div></div></div>';

// Desenvolvedor custom dropdown
html+='<div class="rep-custom-dropdown"><button class="rep-dd-btn" onclick="toggleRepDropdown(this)">Desenvolvedor'+(devActive?' ('+devActive+')':'')+'<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg></button><div class="rep-dd-panel"><div class="rep-dd-search"><input type="text" placeholder="Buscar..." onkeyup="filterRepDD(this)"></div><div class="rep-dd-opts">';
html+='<label class="rep-dd-opt"><input type="checkbox" onchange="repDDToggleAll(this,\'devs\')" '+(devActive===allDevs.length?'checked':'')+'><span>Todos</span></label>';
(allDevs||[]).forEach(function(d){var chk=_repState.devs.indexOf(String(d.id))>=0;html+='<label class="rep-dd-opt'+(chk?' active':'')+'"><input type="checkbox" data-type="devs" data-value="'+d.id+'"'+(chk?' checked':'')+' onchange="repDDChange(this)"><span>'+esc(d.name)+'</span></label>'});
html+='</div></div></div>';

// Prioridade custom dropdown
html+='<div class="rep-custom-dropdown"><button class="rep-dd-btn" onclick="toggleRepDropdown(this)">Prioridade'+(priActive?' ('+priActive+')':'')+'<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg></button><div class="rep-dd-panel"><div class="rep-dd-opts">';
html+='<label class="rep-dd-opt"><input type="checkbox" onchange="repDDToggleAll(this,\'priorities\')" '+(priActive===4?'checked':'')+'><span>Todos</span></label>';
['Urgente','Alta','Média','Baixa'].forEach(function(p){var chk=_repState.priorities.indexOf(p)>=0;html+='<label class="rep-dd-opt'+(chk?' active':'')+'"><input type="checkbox" data-type="priorities" data-value="'+p+'"'+(chk?' checked':'')+' onchange="repDDChange(this)"><span>'+p+'</span></label>'});
html+='</div></div></div>';

// Sprint custom dropdown
html+='<div class="rep-custom-dropdown"><button class="rep-dd-btn" onclick="toggleRepDropdown(this)">Sprint'+(sprActive?' ('+sprActive+')':'')+'<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg></button><div class="rep-dd-panel"><div class="rep-dd-search"><input type="text" placeholder="Buscar..." onkeyup="filterRepDD(this)"></div><div class="rep-dd-opts">';
html+='<label class="rep-dd-opt"><input type="checkbox" onchange="repDDToggleAll(this,\'sprints\')" '+(sprActive===allSprints.length?'checked':'')+'><span>Todos</span></label>';
(allSprints||[]).forEach(function(s){var chk=_repState.sprints.indexOf(String(s.id))>=0;html+='<label class="rep-dd-opt'+(chk?' active':'')+'"><input type="checkbox" data-type="sprints" data-value="'+s.id+'"'+(chk?' checked':'')+' onchange="repDDChange(this)"><span>'+esc(s.name)+'</span></label>'});
html+='</div></div></div>';

// Gráficos toggle button
var _chartDefs=[
  {key:'status',label:'Distribuição por Status'},
  {key:'timeline',label:'Evolução Mensal'},
  {key:'prod',label:'Produtividade da Equipe'},
  {key:'avgdays',label:'Tempo Médio de Conclusão'},
  {key:'priority',label:'Distribuição por Prioridade'},
  {key:'system',label:'Demandas por Sistema (Gráfico)'},
  {key:'sla',label:'Conformidade SLA'},
  {key:'workload',label:'Carga de Trabalho'},
  {key:'flow',label:'Fluxo Criação vs Conclusão'},
  {key:'cancel',label:'Taxa de Cancelamento'},
  {key:'bydev',label:'Demandas por Desenvolvedor (Tabela)'},
  {key:'bysys',label:'Demandas por Sistema (Tabela)'},
  {key:'proddetail',label:'Produtividade Detalhada (Tabela)'}
];
var chartActive=_repState.charts.length;
html+='<button class="btn-filter-toggle" onclick="toggleChartPanel()" id="chart-toggle-btn"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg> Gráficos'+(chartActive<10?' ('+chartActive+')':'')+'</button>';

// Export buttons
html+='<button class="btn btn-ok btn-sm" onclick="exportReportExcel()" style="margin-left:auto">'+IC.clipboard+' Excel</button>';
html+='<button class="btn btn-sm" style="background:var(--err);color:#fff" onclick="exportReportPDF()">'+IC.clipboard+' PDF</button>';

html+='</div>'; // close rep-ultra-row

// Chart selection panel (collapsible)
html+='<div class="rep-chart-panel" id="chart-panel" style="display:none">';
html+='<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;padding:0 4px">';
html+='<span style="font-size:10px;font-weight:600;color:var(--t2);text-transform:uppercase">Selecione os gráficos</span>';
html+='<a class="rep-select-all" onclick="chartSelectAll()">Todos/Nenhum</a>';
html+='</div>';
html+='<div class="rep-cb-group">';
_chartDefs.forEach(function(c){var isOn=_repState.charts.indexOf(c.key)>=0;html+='<label class="rep-cb-item'+(isOn?' active':'')+'"><input type="checkbox" name="rep-charts" value="'+c.key+'"'+(isOn?' checked':'')+' onchange="repToggle(this)"><span class="cb-dot"></span>'+c.label+'</label>'});
html+='</div></div>';

html+='</div>'; // close rep-filters-ultra-compact

html+='<div id="report-area">';

// KPI CARDS
html+='<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:10px;margin-bottom:20px">';
html+='<div class="sc blue"><div class="sc-l">Total Demandas</div><div class="sc-v">'+(ov.total||0)+'</div></div>';
html+='<div class="sc green"><div class="sc-l">Taxa Conclusão</div><div class="sc-v">'+completionRate+'%</div></div>';
html+='<div class="sc purple"><div class="sc-l">Média Dias</div><div class="sc-v">'+avgDays+'</div></div>';
html+='<div class="sc" style="border-left:3px solid #f59e0b"><div class="sc-l">SLA (no prazo)</div><div class="sc-v">'+slaRate+'%</div></div>';
html+='<div class="sc blue"><div class="sc-l">Ativas</div><div class="sc-v">'+(ov.ativas||0)+'</div></div>';
html+='<div class="sc red"><div class="sc-l">Urgentes</div><div class="sc-v">'+(ov.urgentes||0)+'</div></div>';
html+='<div class="sc" style="border-left:3px solid #d4a017"><div class="sc-l">Canceladas</div><div class="sc-v">'+(ov.canceladas||0)+' ('+cancelRate+'%)</div></div>';
html+='<div class="sc purple"><div class="sc-l">Média/Mês</div><div class="sc-v">'+avgPerMonth+'</div></div>';
html+='</div>';

// CHARTS ROW 1
html+='<div class="chart-row">';
html+='<div class="chart-card" data-chart="status"><h4>Distribuição por Status</h4><div style="max-width:280px;margin:0 auto"><canvas id="ch-pie" height="280"></canvas></div></div>';
html+='<div class="chart-card" data-chart="timeline"><h4>Evolução Mensal</h4><canvas id="ch-timeline" height="250"></canvas></div>';
html+='</div>';

// CHARTS ROW 2
html+='<div class="chart-row" style="margin-top:14px">';
html+='<div class="chart-card" data-chart="prod"><h4>Produtividade da Equipe</h4><canvas id="ch-prod" height="250"></canvas></div>';
html+='<div class="chart-card" data-chart="avgdays"><h4>Tempo Médio de Conclusão (dias)</h4><canvas id="ch-avgdays" height="250"></canvas></div>';
html+='</div>';

// CHARTS ROW 3
html+='<div class="chart-row" style="margin-top:14px">';
html+='<div class="chart-card" data-chart="priority"><h4>Distribuição por Prioridade</h4><div style="max-width:280px;margin:0 auto"><canvas id="ch-priority" height="280"></canvas></div></div>';
html+='<div class="chart-card" data-chart="system"><h4>Demandas por Sistema</h4><canvas id="ch-system" height="250"></canvas></div>';
html+='</div>';

// CHARTS ROW 4
html+='<div class="chart-row" style="margin-top:14px">';
html+='<div class="chart-card sla-card-combined" data-chart="sla">';
html+='<h4>Conformidade SLA</h4>';
html+='<div style="display:flex;gap:20px;align-items:flex-start">';
html+='<div style="flex:0 0 auto;max-width:220px"><canvas id="ch-sla" height="220"></canvas><div style="text-align:center;margin-top:8px"><span style="font-size:28px;font-weight:800;color:'+(slaRate>=80?'var(--ok)':slaRate>=50?'var(--warn)':'var(--err)')+'">'+slaRate+'%</span><div style="font-size:10px;color:var(--t3)">No prazo: '+(sla.on_time||0)+' / Total: '+(sla.total||0)+'</div></div></div>';
html+='<div style="flex:1;font-size:11px;color:var(--t2);line-height:1.6;padding:8px 12px;background:var(--bg3);border-radius:8px;border:1px solid var(--brd)"><strong style="color:var(--t1);font-size:12px">O que é SLA?</strong><br><br>SLA (Service Level Agreement) mede a porcentagem de demandas entregues dentro do prazo estabelecido.<br><br>'+
'<strong style="color:var(--ok)">Verde (≥80%)</strong>: Excelente — a equipe está cumprindo os prazos de forma consistente.<br><br>'+
'<strong style="color:var(--warn)">Amarelo (50-79%)</strong>: Atenção — há atrasos que precisam ser monitorados.<br><br>'+
'<strong style="color:var(--err)">Vermelho (&lt;50%)</strong>: Crítico — a maioria das demandas está atrasando, requer ação imediata.<br><br>'+
'O cálculo considera apenas demandas que possuem prazo definido e que já foram concluídas ou estão em andamento.</div>';
html+='</div></div>';

html+='<div class="chart-card" data-chart="workload"><h4>Carga de Trabalho Atual</h4><canvas id="ch-workload" height="250"></canvas></div>';
html+='</div>';

// CHARTS ROW 5
html+='<div class="chart-row" style="margin-top:14px">';
html+='<div class="chart-card" data-chart="flow"><h4>Fluxo Criação vs Conclusão</h4><canvas id="ch-flow" height="250"></canvas></div>';
html+='<div class="chart-card" data-chart="cancel"><h4>Taxa de Cancelamento Mensal</h4><canvas id="ch-cancel" height="250"></canvas></div>';
html+='</div>';


applyChartVisibility();
// TABLE: By Dev
html+='<div class="tbl-c" data-chart="bydev" style="margin-top:14px"><div class="tbl-bar"><h3>Demandas por Desenvolvedor</h3></div><div style="overflow-x:auto"><table><thead><tr><th>Desenvolvedor</th><th>Total</th><th>Concluídas</th><th>Abertas</th><th>Andamento</th><th>Revisão</th><th>Média (dias)</th><th>% Conclusão</th></tr></thead><tbody>';
(byDev||[]).forEach(d=>{
  const pct=d.total?Math.round((d.concluidas/d.total)*100):0;
  html+='<tr><td><div class="dev-tag">'+av(d.name,d.avatar_color,22,d.avatar_file,d.role)+' '+esc(d.name)+'</div></td><td style="font-weight:700">'+(d.total||0)+'</td><td style="color:var(--ok)">'+(d.concluidas||0)+'</td><td>'+(d.abertas||0)+'</td><td style="color:var(--acc)">'+(d.andamento||0)+'</td><td style="color:var(--warn)">'+(d.revisao||0)+'</td><td>'+(d.avg_days?parseFloat(d.avg_days).toFixed(1):'—')+'</td><td><div style="display:flex;align-items:center;gap:6px"><div style="flex:1;height:6px;background:var(--bg4);border-radius:3px;overflow:hidden"><div style="height:100%;width:'+pct+'%;background:'+(pct>=70?'var(--ok)':pct>=40?'var(--warn)':'var(--err)')+';border-radius:3px"></div></div><span style="font-size:10px;font-weight:700">'+pct+'%</span></div></td></tr>';
});
if(!(byDev||[]).length)html+='<tr><td colspan="8" style="text-align:center;padding:20px;color:var(--t3)">Sem dados</td></tr>';
html+='</tbody></table></div></div>';

// TABLE: By System
html+='<div class="tbl-c" data-chart="bysys" style="margin-top:14px"><div class="tbl-bar"><h3>Demandas por Sistema</h3></div><div style="overflow-x:auto"><table><thead><tr><th>Sistema</th><th>Total</th><th>Abertas</th><th>Andamento</th><th>Concluídas</th><th>Canceladas</th><th>Média (dias)</th></tr></thead><tbody>';
(bySys||[]).forEach(s=>{
  html+='<tr><td style="font-weight:600">'+esc(s.name||'Sem sistema')+'</td><td style="font-weight:700">'+(s.total||0)+'</td><td>'+(s.abertas||0)+'</td><td style="color:var(--acc)">'+(s.andamento||0)+'</td><td style="color:var(--ok)">'+(s.concluidas||0)+'</td><td style="color:var(--err)">'+(s.canceladas||0)+'</td><td>'+(s.avg_days?parseFloat(s.avg_days).toFixed(1):'—')+'</td></tr>';
});
if(!(bySys||[]).length)html+='<tr><td colspan="7" style="text-align:center;padding:20px;color:var(--t3)">Sem dados</td></tr>';
html+='</tbody></table></div></div>';

// TABLE: Productivity
html+='<div class="tbl-c" data-chart="proddetail" style="margin-top:14px"><div class="tbl-bar"><h3>Produtividade Detalhada</h3></div><div style="overflow-x:auto"><table><thead><tr><th>Dev</th><th>Concluídas</th><th>Em Aberto</th><th>Média (dias)</th><th>Horas</th><th>Reports</th><th>Eficiência</th></tr></thead><tbody>';
(productivity||[]).forEach(p=>{
  const eff=p.concluidas>0&&p.total_hours>0?(p.concluidas/(p.total_hours/8)).toFixed(1):'—';
  html+='<tr><td><div class="dev-tag">'+av(p.name,p.avatar_color,22,p.avatar_file,p.role)+' '+esc(p.name)+'</div></td><td style="color:var(--ok);font-weight:700">'+(p.concluidas||0)+'</td><td>'+(p.em_aberto||0)+'</td><td>'+(p.avg_days?parseFloat(p.avg_days).toFixed(1):'—')+'</td><td>'+(p.total_hours||0)+'h</td><td>'+(p.reports_count||0)+'</td><td style="font-weight:700;color:var(--acc)">'+eff+'</td></tr>';
});
if(!(productivity||[]).length)html+='<tr><td colspan="7" style="text-align:center;padding:20px;color:var(--t3)">Sem dados</td></tr>';
html+='</tbody></table></div></div>';

html+='</div>'; // close report-area
document.getElementById('rep-content').innerHTML=html;
window._reportData={genStats,byDev,bySys,timeline,productivity,sysHealth,dateFrom,dateTo,ov,sla};

// RENDER CHARTS
setTimeout(()=>{
const gc=getComputedStyle(document.body).getPropertyValue('--bdr').trim()||'#2a3654';
const tc=getComputedStyle(document.body).getPropertyValue('--t3').trim()||'#5a6d8f';
const lc=getComputedStyle(document.body).getPropertyValue('--t2').trim()||'#8899b8';
const co={responsive:true,devicePixelRatio:3,plugins:{legend:{labels:{color:lc,font:{size:10}}}}};
Object.keys(chartInstances).forEach(k=>{if(chartInstances[k]){chartInstances[k].destroy();delete chartInstances[k]}});

const pieC={'Aberta':'#6366f1','Aguardando Aceite':'#d4a017','Em Andamento':'#3b82f6','Em Revisão':'#f59e0b','Concluída':'#10b981','Cancelada':'#ef4444'};
if(statusDist.length){chartInstances.pie=new Chart(document.getElementById('ch-pie'),{type:'doughnut',data:{labels:statusDist.map(s=>s.status),datasets:[{data:statusDist.map(s=>s.c),backgroundColor:statusDist.map(s=>pieC[s.status]||'#64748b'),borderWidth:0}]},options:{responsive:true,devicePixelRatio:3,cutout:'55%',plugins:{legend:{position:'bottom',labels:{color:lc,font:{size:10},padding:8,usePointStyle:true}}}}})}

if(timeline&&timeline.length){chartInstances.timeline=new Chart(document.getElementById('ch-timeline'),{type:'line',data:{labels:timeline.map(t=>t.month),datasets:[{label:'Criadas',data:timeline.map(t=>t.criadas),borderColor:'#3b82f6',backgroundColor:'rgba(59,130,246,.1)',fill:true,tension:.4},{label:'Concluídas',data:timeline.map(t=>t.concluidas),borderColor:'#10b981',backgroundColor:'rgba(16,185,129,.1)',fill:true,tension:.4},{label:'Canceladas',data:timeline.map(t=>t.canceladas||0),borderColor:'#ef4444',backgroundColor:'rgba(239,68,68,.05)',fill:true,tension:.4,borderDash:[5,5]}]},options:{...co,scales:{x:{grid:{color:gc},ticks:{color:tc,font:{size:10}}},y:{grid:{color:gc},ticks:{color:tc}}}}})}

// Apply developer filter to productivity data
const filteredProd=_repState.devs.length>0?(productivity||[]).filter(p=>{const devId=String(p.id||p.user_id||p.developer_id||'');return _repState.devs.indexOf(devId)>=0}):(productivity||[]);
if(filteredProd.length){chartInstances.prod=new Chart(document.getElementById('ch-prod'),{type:'bar',data:{labels:filteredProd.map(p=>p.name),datasets:[{label:'Concluídas',data:filteredProd.map(p=>p.concluidas||0),backgroundColor:'#10b981',borderRadius:4},{label:'Em Aberto',data:filteredProd.map(p=>p.em_aberto||0),backgroundColor:'#3b82f6',borderRadius:4}]},options:{...co,scales:{x:{stacked:true,grid:{display:false},ticks:{color:tc,font:{size:10}}},y:{stacked:true,grid:{color:gc},ticks:{color:tc}}}}})}

// Apply developer filter to byDev data
const filteredByDev=_repState.devs.length>0?(byDev||[]).filter(d=>{const devId=String(d.id||d.user_id||d.developer_id||'');return _repState.devs.indexOf(devId)>=0}):(byDev||[]);
const devDays=filteredByDev.filter(d=>d.avg_days);
if(devDays.length){chartInstances.avgdays=new Chart(document.getElementById('ch-avgdays'),{type:'bar',data:{labels:devDays.map(d=>d.name),datasets:[{label:'Dias',data:devDays.map(d=>parseFloat(d.avg_days).toFixed(1)),backgroundColor:devDays.map(d=>d.avatar_color||'#3b82f6'),borderRadius:4}]},options:{indexAxis:'y',responsive:true,plugins:{legend:{display:false}},scales:{x:{grid:{color:gc},ticks:{color:tc}},y:{grid:{display:false},ticks:{color:lc,font:{size:11}}}}}})}

const priC2={'Urgente':'#ef4444','Alta':'#f59e0b','Média':'#6366f1','Baixa':'#10b981'};
if(priDist.length){chartInstances.priority=new Chart(document.getElementById('ch-priority'),{type:'doughnut',data:{labels:priDist.map(p=>p.priority),datasets:[{data:priDist.map(p=>p.c),backgroundColor:priDist.map(p=>priC2[p.priority]||'#64748b'),borderWidth:0}]},options:{responsive:true,devicePixelRatio:3,cutout:'55%',plugins:{legend:{position:'bottom',labels:{color:lc,font:{size:10},padding:8,usePointStyle:true}}}}})}

// Apply system filter to bySys data
const filteredBySys=_repState.systems.length>0?(bySys||[]).filter(s=>{const sysId=String(s.system_id||s.id||'');return _repState.systems.indexOf(sysId)>=0}):(bySys||[]);
if(filteredBySys.length){chartInstances.system=new Chart(document.getElementById('ch-system'),{type:'bar',data:{labels:filteredBySys.map(s=>(s.name||'').length>15?s.name.slice(0,13)+'...':s.name||'—'),datasets:[{label:'Total',data:filteredBySys.map(s=>s.total||0),backgroundColor:'#6366f1',borderRadius:4},{label:'Concluídas',data:filteredBySys.map(s=>s.concluidas||0),backgroundColor:'#10b981',borderRadius:4}]},options:{...co,scales:{x:{grid:{display:false},ticks:{color:tc,font:{size:9}}},y:{grid:{color:gc},ticks:{color:tc}}}}})}

chartInstances.sla=new Chart(document.getElementById('ch-sla'),{type:'doughnut',data:{labels:['No Prazo','Atrasadas'],datasets:[{data:[sla.on_time||0,Math.max(0,(sla.total||0)-(sla.on_time||0))],backgroundColor:[slaRate>=80?'#10b981':slaRate>=50?'#f59e0b':'#ef4444','#2a365440'],borderWidth:0}]},options:{responsive:true,devicePixelRatio:3,cutout:'70%',rotation:-90,circumference:180,plugins:{legend:{display:false}}}});

// Apply developer filter to workload (uses filtered productivity)
const wl=filteredProd.filter(p=>(p.em_aberto||0)>0);
if(wl.length){chartInstances.workload=new Chart(document.getElementById('ch-workload'),{type:'bar',data:{labels:wl.map(p=>p.name),datasets:[{label:'Ativas',data:wl.map(p=>p.em_aberto||0),backgroundColor:wl.map(p=>p.avatar_color||'#3b82f6'),borderRadius:4}]},options:{indexAxis:'y',responsive:true,plugins:{legend:{display:false}},scales:{x:{grid:{color:gc},ticks:{color:tc,stepSize:1}},y:{grid:{display:false},ticks:{color:lc,font:{size:11}}}}}})}

if(timeline&&timeline.length){let a1=0,a2=0;const cc=[],cd=[];timeline.forEach(t=>{a1+=t.criadas||0;a2+=t.concluidas||0;cc.push(a1);cd.push(a2)});
chartInstances.flow=new Chart(document.getElementById('ch-flow'),{type:'line',data:{labels:timeline.map(t=>t.month),datasets:[{label:'Criadas (acum.)',data:cc,borderColor:'#6366f1',backgroundColor:'rgba(99,102,241,.08)',fill:true,tension:.3},{label:'Concluídas (acum.)',data:cd,borderColor:'#10b981',backgroundColor:'rgba(16,185,129,.08)',fill:true,tension:.3}]},options:{...co,scales:{x:{grid:{color:gc},ticks:{color:tc,font:{size:10}}},y:{grid:{color:gc},ticks:{color:tc}}}}})}

if(timeline&&timeline.length){chartInstances.cancel=new Chart(document.getElementById('ch-cancel'),{type:'bar',data:{labels:timeline.map(t=>t.month),datasets:[{label:'Canceladas',data:timeline.map(t=>t.canceladas||0),backgroundColor:'#ef444466',borderColor:'#ef4444',borderWidth:1,borderRadius:4}]},options:{...co,scales:{x:{grid:{display:false},ticks:{color:tc,font:{size:10}}},y:{grid:{color:gc},ticks:{color:tc,stepSize:1}}}}})}

// Apply chart visibility after all charts are rendered
applyChartVisibility();
},200);
}

// ===== EXPORT EXCEL =====
async function exportReportExcel(){
showToast(IC.clipboard+' Gerando Excel...');
await loadScriptOnce('https://cdn.sheetjs.com/xlsx-0.20.0/package/dist/xlsx.full.min.js');
const rd=window._reportData;if(!rd)return showToast('Carregue os relatórios primeiro');
const selectedCharts=_repState.charts;
const wb=XLSX.utils.book_new();

// Visão Geral - always included
const ov1=[['Métrica','Valor'],['Total',rd.ov.total||0],['Concluídas',rd.ov.concluidas||0],['Ativas',rd.ov.ativas||0],['Canceladas',rd.ov.canceladas||0],['Urgentes',rd.ov.urgentes||0],['Taxa Conclusão %',rd.ov.total?Math.round((rd.ov.concluidas/rd.ov.total)*100):0],['Média Dias',rd.ov.avg_days||''],['SLA No Prazo',rd.sla.on_time||0],['SLA Total',rd.sla.total||0],['',''],['Período',rd.dateFrom+' a '+rd.dateTo]];
XLSX.utils.book_append_sheet(wb,XLSX.utils.aoa_to_sheet(ov1),'Visão Geral');

// Por Desenvolvedor - only if selected
if(selectedCharts.indexOf('bydev')>=0){
  const dr=[['Nome','Total','Concluídas','Abertas','Andamento','Revisão','Média Dias','% Conclusão']];
  const filteredDevs=_repState.devs.length>0
    ?(rd.byDev||[]).filter(d=>{
      const devId=String(d.id||d.user_id||d.developer_id||'');
      return _repState.devs.indexOf(devId)>=0;
    })
    :(rd.byDev||[]);
  filteredDevs.forEach(d=>dr.push([d.name,d.total||0,d.concluidas||0,d.abertas||0,d.andamento||0,d.revisao||0,d.avg_days||'',d.total?Math.round((d.concluidas/d.total)*100):0]));
  XLSX.utils.book_append_sheet(wb,XLSX.utils.aoa_to_sheet(dr),'Por Desenvolvedor');
}

// Por Sistema - only if selected
if(selectedCharts.indexOf('bysys')>=0){
  const sr=[['Sistema','Total','Abertas','Andamento','Concluídas','Canceladas','Média Dias']];
  const filteredSys=_repState.systems.length>0
    ?(rd.bySys||[]).filter(s=>{
      const sysId=String(s.system_id||s.id||'');
      return _repState.systems.indexOf(sysId)>=0;
    })
    :(rd.bySys||[]);
  filteredSys.forEach(s=>sr.push([s.name||'—',s.total||0,s.abertas||0,s.andamento||0,s.concluidas||0,s.canceladas||0,s.avg_days||'']));
  XLSX.utils.book_append_sheet(wb,XLSX.utils.aoa_to_sheet(sr),'Por Sistema');
}

// Evolução Mensal - only if selected
if(selectedCharts.indexOf('timeline')>=0){
  const tr=[['Mês','Criadas','Concluídas','Canceladas']];
  (rd.timeline||[]).forEach(t=>tr.push([t.month,t.criadas||0,t.concluidas||0,t.canceladas||0]));
  XLSX.utils.book_append_sheet(wb,XLSX.utils.aoa_to_sheet(tr),'Evolução Mensal');
}

// Produtividade - only if selected
if(selectedCharts.indexOf('proddetail')>=0||selectedCharts.indexOf('prod')>=0){
  const pr=[['Nome','Concluídas','Em Aberto','Média Dias','Horas','Reports']];
  const filteredProd=_repState.devs.length>0
    ?(rd.productivity||[]).filter(p=>{
      const devId=String(p.id||p.user_id||p.developer_id||'');
      return _repState.devs.indexOf(devId)>=0;
    })
    :(rd.productivity||[]);
  filteredProd.forEach(p=>pr.push([p.name,p.concluidas||0,p.em_aberto||0,p.avg_days||'',p.total_hours||0,p.reports_count||0]));
  XLSX.utils.book_append_sheet(wb,XLSX.utils.aoa_to_sheet(pr),'Produtividade');
}

XLSX.writeFile(wb,'Relatorio_ASSEGO_TI_'+rd.dateFrom+'_'+rd.dateTo+'.xlsx');
showToast(IC.check+' Excel exportado!');
}

// ===== EXPORT PDF (LANDSCAPE) — FIXED =====
async function exportReportPDF(){
showToast(IC.clipboard+' Gerando PDF...');
await loadScriptOnce('https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js');
await loadScriptOnce('https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js');
const rd=window._reportData;if(!rd)return showToast('Carregue os relatórios primeiro');
const{jsPDF}=window.jspdf;
const doc=new jsPDF({orientation:'landscape',unit:'mm',format:'a4'});
const pw=doc.internal.pageSize.getWidth();const ph=doc.internal.pageSize.getHeight();const mg=12;let y=mg;let pg=1;

// Detect theme for chart background
const isLight=document.body.classList.contains('light');
const chartBg=isLight?'#f8fafc':'#1a2236';

// Load logos — FIXED PATHS
let logoAssego=null,logoSergipe=null,logoGoias=null;
try{
  const loadImg=src=>new Promise((ok)=>{const img=new Image();img.crossOrigin="anonymous";img.onload=()=>ok(img);img.onerror=()=>ok(null);img.src=src;setTimeout(()=>ok(null),3000)});
  const imgs=await Promise.all([loadImg("assets/img/logoassego.png"),loadImg("assets/img/logopre.png"),loadImg("assets/img/logo.png")]);
  logoAssego=imgs[0];logoSergipe=imgs[1];logoGoias=imgs[2];
}catch(e){console.log('Logo load error',e)}

function hdr(){
  // Blue gradient header
  var gSteps=20;var hdrH=20;
  for(var gi=0;gi<gSteps;gi++){
    var ratio=gi/gSteps;
    var r1=10,g1=50,b1=140;
    var r2=30,g2=100,b2=200;
    var rr=Math.round(r1+(r2-r1)*ratio);
    var gg=Math.round(g1+(g2-g1)*ratio);
    var bb=Math.round(b1+(b2-b1)*ratio);
    doc.setFillColor(rr,gg,bb);
    doc.rect(0,(hdrH/gSteps)*gi,pw,hdrH/gSteps+0.5,'F');
  }
  // Gold accent bar
  doc.setFillColor(245,190,30);doc.rect(0,hdrH-0.6,pw,0.6,'F');

  // --- Logos (right side) ---
  // logoassego.png = full size (14mm height) - mantém posição
  // logopre.png & logo.png = reduced (9mm height) - descer para alinhar melhor
  var logoAssH=14;
  var logoSmH=9;
  var logoGap=3;
  var logoTopY=3; // logoAssego mantém posição
  var logoSmTopY=5.5; // logopre.png e logo.png descem 2.5mm
  var logoX=pw-mg;
  try{
    if(logoGoias){
      var lw=logoSmH*(logoGoias.width/logoGoias.height);
      logoX-=lw;
      doc.addImage(logoGoias,'PNG',logoX,logoSmTopY,lw,logoSmH);
      logoX-=logoGap;
    }
    if(logoSergipe){
      var lw2=logoSmH*(logoSergipe.width/logoSergipe.height);
      logoX-=lw2;
      doc.addImage(logoSergipe,'PNG',logoX,logoSmTopY,lw2,logoSmH);
      logoX-=logoGap;
    }
    if(logoAssego){
      var lw3=logoAssH*(logoAssego.width/logoAssego.height);
      logoX-=lw3;
      doc.addImage(logoAssego,'PNG',logoX,logoTopY,lw3,logoAssH);
    }
  }catch(e){console.log('Logo render error',e)}

  // --- Title text aligned with top of logos ---
  var txtY=logoTopY+3.5;
  doc.setTextColor(255,255,255);doc.setFontSize(12);doc.setFont('helvetica','bold');
  doc.text('Relatório ASSEGO TI - Desenvolvimento de Sistemas',mg,txtY);
  doc.setFontSize(7);doc.setFont('helvetica','normal');
  doc.text('Relatório Gerencial  |  Presidência / Associação dos Subtenentes e Sargentos do Estado de Goiás',mg,txtY+5);
  doc.setFontSize(6.5);
  doc.text('Período: '+rd.dateFrom+' a '+rd.dateTo+'  |  Gerado: '+new Date().toLocaleDateString('pt-BR')+' '+new Date().toLocaleTimeString('pt-BR',{hour:'2-digit',minute:'2-digit'}),mg,txtY+9);
  return hdrH+4;
}

function ftr(){
  doc.setFillColor(10,50,140);doc.rect(0,ph-8,pw,8,'F');
  doc.setTextColor(140,150,170);doc.setFontSize(6.5);
  doc.text('ASSEGO - Associação dos Subtenentes e Sargentos do Estado de Goiás  |  Sistema Gestão Dev',pw/2,ph-4.5,{align:'center'});
  doc.text('Pág. '+pg,pw-mg,ph-4.5,{align:'right'});
}

function newPg(){doc.addPage();pg++;y=hdr();ftr();}
function safeY(needed){if(y+needed>ph-16){newPg();y+=2;}}

y=hdr();ftr();y+=2;

// ============================================================
// KPI BOXES — improved: shadow, larger value font, better spacing
// ============================================================
const ov=rd.ov;const sla=rd.sla;const slaR=sla.total?Math.round((sla.on_time/sla.total)*100):0;
const kpis=[
  {l:'Total',v:ov.total||0,c:[99,102,241]},
  {l:'Concluídas',v:ov.concluidas||0,c:[16,185,129]},
  {l:'Ativas',v:ov.ativas||0,c:[59,130,246]},
  {l:'Urgentes',v:ov.urgentes||0,c:[239,68,68]},
  {l:'Canceladas',v:ov.canceladas||0,c:[212,160,23]},
  {l:'Taxa Conclusão',v:(ov.total?Math.round((ov.concluidas/ov.total)*100):0)+'%',c:[16,185,129]},
  {l:'Média Dias',v:ov.avg_days?parseFloat(ov.avg_days).toFixed(1):'—',c:[139,92,246]},
  {l:'SLA',v:slaR+'%',c:[245,158,11]}
];
const kpiGap=2.5;
const bw=(pw-mg*2-(kpis.length-1)*kpiGap)/kpis.length;
const kpiH=15;
kpis.forEach((k,i)=>{
  const bx=mg+i*(bw+kpiGap);
  // Subtle shadow
  doc.setFillColor(Math.max(0,k.c[0]-40),Math.max(0,k.c[1]-40),Math.max(0,k.c[2]-40));
  doc.roundedRect(bx+0.3,y+0.5,bw,kpiH,2,2,'F');
  // Main box
  doc.setFillColor(k.c[0],k.c[1],k.c[2]);
  doc.roundedRect(bx,y,bw,kpiH,2,2,'F');
  // Label
  doc.setTextColor(255,255,255);
  doc.setFontSize(6.5);doc.setFont('helvetica','normal');
  doc.text(k.l,bx+bw/2,y+5,{align:'center'});
  // Value
  doc.setFontSize(13);doc.setFont('helvetica','bold');
  doc.text(String(k.v),bx+bw/2,y+12.5,{align:'center'});
});
y+=kpiH+6;

// ============================================================
// CHARTS — ULTRA HIGH QUALITY: scale 5, PNG lossless, border frame
// ONLY CAPTURE CHART.JS CANVASES (not tables/HTML content)
// ============================================================
const area=document.getElementById('report-area');
if(area){
  // Get only .chart-card elements that contain a <canvas> (Chart.js graphs)
  const allChartCards=Array.from(area.querySelectorAll('.chart-card')).filter(card=>card.querySelector('canvas'));
  const visibleCards=allChartCards.filter(card=>card.style.display!=='none');
  const gap=6;
  const colW=(pw-mg*2-gap)/2;
  let rowStartY=y;
  
  for(let i=0;i<visibleCards.length;i++){
    try{
      const card=visibleCards[i];
      
      // Use html2canvas - charts already at 3x devicePixelRatio
      const cvs=await html2canvas(card,{
        backgroundColor:chartBg,
        scale:2,
        useCORS:true,
        allowTaint:false,
        logging:false
      });
      
      const imgData=cvs.toDataURL('image/png',1.0);
      let ih=(cvs.height/cvs.width)*colW;
      if(ih>75)ih=75;

      const colIdx=i%2;
      if(colIdx===0){
        safeY(ih+10);
        rowStartY=y;
      }

      const xPos=mg+colIdx*(colW+gap);
      
      // Draw chart with border
      doc.setDrawColor(226,232,240);
      doc.setLineWidth(0.3);
      doc.roundedRect(xPos-0.5,rowStartY-0.5,colW+1,ih+1,1.5,1.5,'S');
      doc.addImage(imgData,'PNG',xPos,rowStartY,colW,ih);

      if(colIdx===1||i===visibleCards.length-1){
        y=rowStartY+ih+5;
      }
    }catch(e){console.log('PDF chart err',e)}
  }
  
  // Ensure y is properly set after all charts
  if(visibleCards.length>0){
    y+=5; // Extra spacing after charts
  }else{
    y+=10; // Spacing when no charts (going directly to tables)
  }
}

// ============================================================
// TABLE: Developer Productivity — ONLY IF SELECTED
// ============================================================
const selectedCharts=_repState.charts;
if(selectedCharts.indexOf('bydev')>=0){
  // Ensure minimum spacing from charts
  const minSpacing=20;
  y+=minSpacing;
  
  // Only create new page if current page doesn't have enough space
  const neededSpace=50;
  if(y+neededSpace>ph-16){
    newPg();
  }
  
  const tblW=pw-mg*2;
  doc.setTextColor(59,130,246);doc.setFontSize(12);doc.setFont('helvetica','bold');
  doc.text('PRODUTIVIDADE POR DESENVOLVEDOR',mg,y);y+=7;
  const dh=['Desenvolvedor','Concluídas','Em Aberto','Média Dias','% Conclusão'];
  const dw=[72,28,28,28,42];
  // Header row
  doc.setFillColor(30,45,80);doc.roundedRect(mg,y,tblW,6.5,1,1,'F');
  doc.setTextColor(220,225,240);doc.setFontSize(7.5);doc.setFont('helvetica','bold');
  dh.forEach((h,i)=>{let cx=mg;for(let j=0;j<i;j++)cx+=dw[j];doc.text(h,cx+3,y+4.5)});
  y+=7.5;
  // Data rows with alternating background - APPLY DEVELOPER FILTER
  doc.setFont('helvetica','normal');
  const filteredDevs=_repState.devs.length>0
    ?(rd.byDev||[]).filter(d=>{
      const devId=String(d.id||d.user_id||d.developer_id||'');
      return _repState.devs.indexOf(devId)>=0;
    })
    :(rd.byDev||[]);
  
  filteredDevs.forEach((d,ri)=>{
    safeY(7);
    if(ri%2===0){doc.setFillColor(240,242,248);doc.rect(mg,y,tblW,6,'F')}
    doc.setTextColor(40,50,70);doc.setFontSize(7.5);
    const pct=d.total?Math.round((d.concluidas/d.total)*100):0;
    const vals=[d.name,String(d.concluidas||0),String(d.abertas||0),d.avg_days?parseFloat(d.avg_days).toFixed(1):'—',pct+'%'];
    vals.forEach((v,i)=>{let cx=mg;for(let j=0;j<i;j++)cx+=dw[j];doc.text(String(v),cx+3,y+4.2)});
    doc.setDrawColor(210,215,225);doc.setLineWidth(0.15);doc.line(mg,y+6,pw-mg,y+6);
    y+=6;
  });
}

// ============================================================
// TABLE: By System — ONLY IF SELECTED
// ============================================================
if(selectedCharts.indexOf('bysys')>=0){
  // Ensure minimum spacing
  const minSpacing=15;
  y+=minSpacing;
  
  // Check if need new page
  const neededSpace=50;
  if(y+neededSpace>ph-16){
    newPg();
  }
  
  const tblW=pw-mg*2;
  doc.setTextColor(59,130,246);doc.setFontSize(12);doc.setFont('helvetica','bold');
  doc.text('DEMANDAS POR SISTEMA',mg,y);y+=7;
  const bsh=['Sistema','Total','Abertas','Andamento','Concluídas','Canceladas','Média Dias'];
  const bsw=[62,20,20,24,24,24,24];
  // Header row
  doc.setFillColor(30,45,80);doc.roundedRect(mg,y,tblW,6.5,1,1,'F');
  doc.setTextColor(220,225,240);doc.setFontSize(7.5);doc.setFont('helvetica','bold');
  bsh.forEach((h,i)=>{let cx=mg;for(let j=0;j<i;j++)cx+=bsw[j];doc.text(h,cx+3,y+4.5)});
  y+=7.5;
  // Data rows - APPLY SYSTEM FILTER
  doc.setFont('helvetica','normal');
  const filteredBySys=_repState.systems.length>0
    ?(rd.bySys||[]).filter(s=>{
      const sysId=String(s.system_id||s.id||'');
      return _repState.systems.indexOf(sysId)>=0;
    })
    :(rd.bySys||[]);
  
  filteredBySys.forEach((s,ri)=>{
    safeY(7);
    if(ri%2===0){doc.setFillColor(240,242,248);doc.rect(mg,y,tblW,6,'F')}
    const vals=[s.name||'Sem sistema',String(s.total||0),String(s.abertas||0),String(s.andamento||0),String(s.concluidas||0),String(s.canceladas||0),s.avg_days?parseFloat(s.avg_days).toFixed(1):'—'];
    doc.setTextColor(40,50,70);doc.setFontSize(7.5);
    vals.forEach((v,i)=>{let cx=mg;for(let j=0;j<i;j++)cx+=bsw[j];doc.text(String(v),cx+3,y+4.2)});
    doc.setDrawColor(210,215,225);doc.setLineWidth(0.15);doc.line(mg,y+6,pw-mg,y+6);
    y+=6;
  });
}

// ============================================================
// TABLE: Detailed Productivity — ONLY IF SELECTED
// ============================================================
if(selectedCharts.indexOf('proddetail')>=0){
  // Ensure minimum spacing
  const minSpacing=15;
  y+=minSpacing;
  
  // Check if need new page
  const neededSpace=50;
  if(y+neededSpace>ph-16){
    newPg();
  }
  
  const tblW=pw-mg*2;
  doc.setTextColor(59,130,246);doc.setFontSize(12);doc.setFont('helvetica','bold');
  doc.text('PRODUTIVIDADE DETALHADA',mg,y);y+=7;
  const pdh=['Desenvolvedor','Concluídas','Em Aberto','Média Dias','Horas','Reports','Eficiência'];
  const pdw=[52,22,22,22,18,18,24];
  // Header row
  doc.setFillColor(30,45,80);doc.roundedRect(mg,y,tblW,6.5,1,1,'F');
  doc.setTextColor(220,225,240);doc.setFontSize(7.5);doc.setFont('helvetica','bold');
  pdh.forEach((h,i)=>{let cx=mg;for(let j=0;j<i;j++)cx+=pdw[j];doc.text(h,cx+3,y+4.5)});
  y+=7.5;
  // Data rows - APPLY DEVELOPER FILTER
  doc.setFont('helvetica','normal');
  const filteredProd=_repState.devs.length>0
    ?(rd.productivity||[]).filter(p=>{
      const devId=String(p.id||p.user_id||p.developer_id||'');
      return _repState.devs.indexOf(devId)>=0;
    })
    :(rd.productivity||[]);
  
  filteredProd.forEach((p,ri)=>{
    safeY(7);
    if(ri%2===0){doc.setFillColor(240,242,248);doc.rect(mg,y,tblW,6,'F')}
    const eff=p.concluidas>0&&p.total_hours>0?(p.concluidas/(p.total_hours/8)).toFixed(1):'—';
    const vals=[p.name,String(p.concluidas||0),String(p.em_aberto||0),p.avg_days?parseFloat(p.avg_days).toFixed(1):'—',String((p.total_hours||0)+'h'),String(p.reports_count||0),eff];
    doc.setTextColor(40,50,70);doc.setFontSize(7.5);
    vals.forEach((v,i)=>{let cx=mg;for(let j=0;j<i;j++)cx+=pdw[j];doc.text(String(v),cx+3,y+4.2)});
    doc.setDrawColor(210,215,225);doc.setLineWidth(0.15);doc.line(mg,y+6,pw-mg,y+6);
    y+=6;
  });
}

doc.save('Relatorio_ASSEGO_TI_'+rd.dateFrom+'_'+rd.dateTo+'.pdf');
showToast(IC.check+' PDF exportado!');
}

// ===== APPROVALS =====
async function loadApprovals(){const[pending,rejected]=await Promise.all([api('demands',{params:{presidency_status:'Pendente'}})||[],api('demands',{params:{presidency_status:'Rejeitada'}})||[]]);
let html=`<div class="tbl-c"><div class="tbl-bar"><h3>${IC_CROWN} Aguardando Aprovação da Presidência</h3><span class="badge s-aguardando">${(pending||[]).length} pendentes</span></div><div style="overflow-x:auto"><table><thead><tr><th>#</th><th>Demanda</th><th>Sistema</th><th>Prioridade</th><th>Solicitante</th><th>Devs</th><th>Ações</th></tr></thead><tbody>`;
if(pending&&pending.length){pending.forEach(d=>{html+=`<tr><td style="font-family:'JetBrains Mono',monospace;color:var(--t3)">#${d.id}</td><td style="font-weight:600;cursor:pointer" onclick="openDetail(${d.id})">${esc(d.title)}</td><td><span class="tag">${esc(d.system_name||'—')}</span></td><td><span class="badge ${pClass(d.priority)}">${d.priority}</span></td><td>${esc(d.requester||'—')}</td><td>${devsHtml(d.devs)}</td><td onclick="event.stopPropagation()"><button class="btn btn-gold btn-sm" onclick="openApproval(${d.id},'${esc(d.title)}')">${IC_CROWN} Analisar</button></td></tr>`})}
else html+='<tr><td colspan="7"><div class="empty" style="padding:16px"><p>Nenhuma pendente</p></div></td></tr>';
html+=`</tbody></table></div></div>`;

// Rejected section
html+=`<div class="tbl-c" style="margin-top:16px"><div class="tbl-bar"><h3>${IC.block} Rejeitadas pela Presidência</h3><span class="badge s-cancelada">${(rejected||[]).length}</span></div><div style="overflow-x:auto"><table><thead><tr><th>#</th><th>Demanda</th><th>Sistema</th><th>Motivo</th><th>Rejeitado por</th><th>Data</th><th>Ações</th></tr></thead><tbody>`;
if(rejected&&rejected.length){rejected.forEach(d=>{html+=`<tr><td style="font-family:'JetBrains Mono',monospace;color:var(--t3)">#${d.id}</td><td style="font-weight:600;cursor:pointer" onclick="openDetail(${d.id})">${esc(d.title)}</td><td><span class="tag">${esc(d.system_name||'—')}</span></td><td style="font-size:11px;color:var(--err);max-width:200px;overflow:hidden;text-overflow:ellipsis">${esc(d.presidency_notes||'—')}</td><td>${esc(d.approver_name||'—')}</td><td style="font-size:10px;font-family:'JetBrains Mono',monospace">${fmtDT(d.presidency_approved_at)}</td><td onclick="event.stopPropagation()"><button class="btn btn-w btn-sm" onclick="resubmitDemand(${d.id})">${IC.edit} Editar e Reenviar</button></td></tr>`})}
else html+='<tr><td colspan="7"><div class="empty" style="padding:16px"><p>Nenhuma rejeitada</p></div></td></tr>';
html+=`</tbody></table></div></div>`;
document.getElementById('page-aprovacoes').innerHTML=html}
function openApproval(id,title){document.getElementById('ap-id').value=id;document.getElementById('ap-title').textContent='#'+id+' — '+title;document.getElementById('ap-notes').value='';openM('m-approve')}
async function doApproval(st){const id=document.getElementById('ap-id').value;const notes=document.getElementById('ap-notes').value.trim();await api('demand_approve',{method:'POST',params:{id},body:{presidency_status:st,presidency_notes:notes}});closeM('m-approve');loadApprovals();loadDashboard()}

// ===== AVATAR HELPER =====
function avatarHtml(user,size=28){if(!user)return'';if(user.avatar_file)return`<div class="av" style="width:${size}px;height:${size}px;background:${user.avatar_color||'#3b82f6'}"><img src="api.php?action=arquivo&f=${user.avatar_file}" style="width:100%;height:100%;border-radius:50%;object-fit:cover"></div>`;return`<div class="av" style="width:${size}px;height:${size}px;background:${user.avatar_color||'#3b82f6'};font-size:${Math.round(size*0.4)}px">${(user.name||'?')[0]}</div>`}

// ===== PROFILE (enhanced) =====
async function loadProfile(){const[p,hist]=await Promise.all([api('profile'),api('my_history')]);
const s=p.stats||{};const pct=s.total?Math.round(s.completed/s.total*100):0;
const priColors={'Urgente':'#ef4444','Alta':'#f59e0b','Média':'#3b82f6','Baixa':'#10b981'};
const stColors={'Em Andamento':'#3b82f6','Em Revisão':'#f59e0b','Concluída':'#10b981','Cancelada':'#ef4444'};
const roles=(p.role||'').split(',').map(r=>r.trim());
const RL={admin:'Administrador',dev:'Desenvolvedor',diretor:'Diretor',presidencia:'Presidência',usuario:'Usuário'};
const RC={admin:'#ef4444',dev:'#3b82f6',diretor:'#8b5cf6',presidencia:'#f59e0b',usuario:'#10b981'};
let roleBadges=roles.map(r=>`<span style="display:inline-flex;align-items:center;gap:3px;padding:2px 10px;border-radius:20px;font-size:10px;font-weight:600;background:${RC[r]||'#666'}18;color:${RC[r]||'#666'};border:1px solid ${RC[r]||'#666'}30">${RL[r]||r}</span>`).join(' ');

let html=`<div class="profile-grid">`;
// === LEFT COLUMN ===
html+=`<div style="display:flex;flex-direction:column;gap:16px">`;
// Avatar card
html+=`<div class="tbl-c" style="padding:28px 20px;text-align:center">`;
html+=`<div style="width:100px;height:100px;border-radius:50%;margin:0 auto 14px;background:${p.avatar_color||'#3b82f6'};display:flex;align-items:center;justify-content:center;font-size:38px;color:#fff;font-weight:700;position:relative;overflow:hidden;cursor:pointer;box-shadow:0 4px 20px ${p.avatar_color||'#3b82f6'}40" onclick="document.getElementById('avatar-upload').click()" title="Alterar foto">`;
if(p.avatar_file)html+=`<img src="api.php?action=arquivo&f=${p.avatar_file}" style="width:100%;height:100%;object-fit:cover">`;
else html+=`${(p.name||'?')[0]}`;
html+=`<div style="position:absolute;bottom:0;left:0;right:0;background:rgba(0,0,0,.55);color:#fff;font-size:9px;padding:4px 0;display:flex;align-items:center;justify-content:center;gap:3px"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg> Alterar</div>`;
html+=`</div><input type="file" id="avatar-upload" accept="image/*" style="display:none" onchange="uploadAvatar(this.files[0])">`;
html+=`<h3 style="margin:0 0 6px;font-size:18px">${esc(p.name)}</h3>`;
html+=`<div style="margin-bottom:10px">${roleBadges}</div>`;
html+=`<div style="font-size:11px;color:var(--t3);display:flex;flex-direction:column;gap:3px;align-items:center">`;
html+=`<span style="display:flex;align-items:center;gap:4px"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="2" y="4" width="20" height="16" rx="3"/><polyline points="22,4 12,13 2,4"/></svg> ${esc(p.email)}</span>`;
html+=`<span style="display:flex;align-items:center;gap:4px"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> ${p.last_login?'Último acesso: '+fmtDT(p.last_login):'Nunca acessou'}</span>`;
html+=`<span style="display:flex;align-items:center;gap:4px">${IC.cal} Membro desde: ${fmtDT(p.created_at)}</span>`;
html+=`</div></div>`;

// Performance ring
html+=`<div class="tbl-c" style="padding:20px;text-align:center">`;
html+=`<div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--t3);margin-bottom:12px">Taxa de Conclusão</div>`;
html+=`<div style="width:90px;height:90px;margin:0 auto 8px;position:relative"><svg width="90" height="90" viewBox="0 0 90 90"><circle cx="45" cy="45" r="38" fill="none" stroke="var(--bdr)" stroke-width="6"/><circle cx="45" cy="45" r="38" fill="none" stroke="${pct>=70?'#10b981':pct>=40?'#f59e0b':'#ef4444'}" stroke-width="6" stroke-linecap="round" stroke-dasharray="${pct*2.39} 239" transform="rotate(-90 45 45)" style="transition:stroke-dasharray .8s ease"/></svg><div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;flex-direction:column"><span style="font-size:22px;font-weight:800">${pct}%</span></div></div>`;
html+=`<div style="font-size:10px;color:var(--t3)">${s.completed||0} de ${s.total||0} demandas</div>`;
html+=`</div>`;

// Quick stats grid
html+=`<div class="tbl-c" style="padding:16px"><div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">`;
html+=`<div style="background:var(--bg);border-radius:8px;padding:10px;text-align:center"><div style="font-size:20px;font-weight:800;color:#3b82f6">${s.in_progress||0}</div><div style="font-size:9px;color:var(--t3);margin-top:2px">Em Andamento</div></div>`;
html+=`<div style="background:var(--bg);border-radius:8px;padding:10px;text-align:center"><div style="font-size:20px;font-weight:800;color:#f59e0b">${s.in_review||0}</div><div style="font-size:9px;color:var(--t3);margin-top:2px">Em Revisão</div></div>`;
html+=`<div style="background:var(--bg);border-radius:8px;padding:10px;text-align:center"><div style="font-size:20px;font-weight:800;color:#10b981">${s.completed||0}</div><div style="font-size:9px;color:var(--t3);margin-top:2px">Concluídas</div></div>`;
html+=`<div style="background:var(--bg);border-radius:8px;padding:10px;text-align:center"><div style="font-size:20px;font-weight:800;color:var(--t2)">${s.comments||0}</div><div style="font-size:9px;color:var(--t3);margin-top:2px">Comentários</div></div>`;
html+=`</div></div>`;
html+=`</div>`;

// === RIGHT COLUMN ===
html+=`<div style="display:flex;flex-direction:column;gap:16px">`;

// Edit Profile
html+=`<div class="tbl-c" style="padding:20px">`;
html+=`<div style="display:flex;align-items:center;gap:8px;margin-bottom:16px"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--acc)" stroke-width="2" stroke-linecap="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg><h3 style="font-size:14px;margin:0">Editar Perfil</h3></div>`;
html+=`<div class="fg2"><div class="fg"><label>Nome</label><input id="pf-name" value="${esc(p.name)}"></div>`;
const emailNote=IS_ADMIN?'':'<span style="font-size:9px;color:var(--t3)">(somente admin altera)</span>';
const emailAttr=IS_ADMIN?'':'readonly style="opacity:.6;cursor:not-allowed"';
html+=`<div class="fg"><label>Email ${emailNote}</label><input id="pf-email" value="${esc(p.email)}" ${emailAttr}></div>`;
html+=`<div class="fg"><label>Cor do Avatar</label><input type="color" id="pf-color" value="${p.avatar_color||'#3b82f6'}" style="height:36px;width:100%"></div>`;
html+=`<div class="fg" style="display:flex;align-items:flex-end"><button class="btn btn-p btn-sm" onclick="saveProfile()" style="padding:8px 20px">${IC.check} Salvar</button></div></div></div>`;

// Change Password
html+=`<div class="tbl-c" style="padding:20px">`;
html+=`<div style="display:flex;align-items:center;gap:8px;margin-bottom:16px"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--acc)" stroke-width="2" stroke-linecap="round"><rect x="3" y="11" width="18" height="11" rx="3"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg><h3 style="font-size:14px;margin:0">Alterar Senha</h3></div>`;
html+=`<div class="fg2"><div class="fg"><label>Senha Atual</label><input type="password" id="pf-curpw" placeholder="••••••••"></div>`;
html+=`<div class="fg"><label>Nova Senha</label><input type="password" id="pf-newpw" placeholder="Mínimo 6 caracteres" oninput="checkPwStrength(this.value)"></div>`;
html+=`<div class="fg"><div id="pw-meter"></div></div>`;
html+=`<div class="fg" style="display:flex;align-items:flex-end"><button class="btn btn-w btn-sm" onclick="changePassword()" style="padding:8px 20px"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="11" width="18" height="11" rx="3"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg> Alterar</button></div></div></div>`;

// Avg completion time
if(s.avg_days!==null&&s.avg_days!==undefined){
html+=`<div class="tbl-c" style="padding:20px">`;
html+=`<div style="display:flex;align-items:center;gap:8px;margin-bottom:12px"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--acc)" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg><h3 style="font-size:14px;margin:0">Tempo Médio de Entrega</h3></div>`;
html+=`<div style="text-align:center;padding:8px 0"><span style="font-size:32px;font-weight:800;color:var(--acc)">${s.avg_days}</span><span style="font-size:13px;color:var(--t3);margin-left:4px">dias</span></div>`;
html+=`</div>`}

// By Priority
if(s.by_priority&&Object.keys(s.by_priority).length){
html+=`<div class="tbl-c" style="padding:20px">`;
html+=`<div style="display:flex;align-items:center;gap:8px;margin-bottom:14px"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--acc)" stroke-width="2" stroke-linecap="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg><h3 style="font-size:14px;margin:0">Demandas por Prioridade</h3></div>`;
const priOrder=['Urgente','Alta','Média','Baixa'];
priOrder.forEach(pri=>{const c=s.by_priority[pri]||0;if(c){const w=s.total?Math.round(c/s.total*100):0;
html+=`<div style="display:flex;align-items:center;gap:8px;margin-bottom:6px"><span style="width:60px;font-size:11px;color:var(--t2)">${pri}</span><div style="flex:1;height:6px;background:var(--bg);border-radius:3px;overflow:hidden"><div style="height:100%;width:${w}%;background:${priColors[pri]};border-radius:3px;transition:width .5s"></div></div><span style="font-size:11px;font-weight:600;width:24px;text-align:right">${c}</span></div>`}});
html+=`</div>`}

// By System
if(s.by_system&&s.by_system.length){
html+=`<div class="tbl-c" style="padding:20px">`;
html+=`<div style="display:flex;align-items:center;gap:8px;margin-bottom:14px"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--acc)" stroke-width="2" stroke-linecap="round"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="2" y1="20" x2="22" y2="20"/></svg><h3 style="font-size:14px;margin:0">Top Sistemas</h3></div>`;
s.by_system.forEach(sys=>{const w=s.total?Math.round(sys.c/s.total*100):0;
html+=`<div style="display:flex;align-items:center;gap:8px;margin-bottom:6px"><span style="flex:1;font-size:11px;color:var(--t2);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${esc(sys.name||'Sem sistema')}</span><div style="width:100px;height:6px;background:var(--bg);border-radius:3px;overflow:hidden"><div style="height:100%;width:${w}%;background:var(--acc);border-radius:3px"></div></div><span style="font-size:11px;font-weight:600;width:24px;text-align:right">${sys.c}</span></div>`});
html+=`</div>`}

// Monthly
if(s.monthly&&s.monthly.length){
html+=`<div class="tbl-c" style="padding:20px">`;
html+=`<div style="display:flex;align-items:center;gap:8px;margin-bottom:14px"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--acc)" stroke-width="2" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg><h3 style="font-size:14px;margin:0">Entregas Mensais</h3></div>`;
const maxM=Math.max(...s.monthly.map(m=>m.c),1);
html+=`<div style="display:flex;align-items:flex-end;gap:6px;height:80px;padding-top:8px">`;
s.monthly.forEach(m=>{const h=Math.max((m.c/maxM)*64,4);const lbl=m.mes.split('-')[1];
html+=`<div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:4px"><span style="font-size:10px;font-weight:700;color:var(--t1)">${m.c}</span><div style="width:100%;height:${h}px;background:linear-gradient(to top,var(--acc),var(--acc)80);border-radius:4px 4px 0 0;transition:height .5s"></div><span style="font-size:9px;color:var(--t3)">${lbl}</span></div>`});
html+=`</div></div>`}

// Recent Demands
if(s.recent_demands&&s.recent_demands.length){
html+=`<div class="tbl-c" style="padding:20px">`;
html+=`<div style="display:flex;align-items:center;gap:8px;margin-bottom:14px"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--acc)" stroke-width="2" stroke-linecap="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg><h3 style="font-size:14px;margin:0">Demandas Recentes</h3></div>`;
s.recent_demands.forEach(d=>{
html+=`<div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--bdr);cursor:pointer" onclick="openDetail(${d.id})"><div style="width:8px;height:8px;border-radius:50%;background:${stColors[d.status]||'#666'};flex-shrink:0"></div><div style="flex:1;min-width:0"><div style="font-size:12px;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${esc(d.title)}</div><div style="font-size:10px;color:var(--t3)">${d.status} · ${fmtDT(d.created_at)}</div></div><span style="font-size:9px;padding:2px 6px;border-radius:4px;background:${priColors[d.priority]||'#666'}18;color:${priColors[d.priority]||'#666'};font-weight:600">${d.priority}</span></div>`});
html+=`</div>`}

// Activity History
html+=`<div class="tbl-c" style="padding:20px">`;
html+=`<div style="display:flex;align-items:center;gap:8px;margin-bottom:14px"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--acc)" stroke-width="2" stroke-linecap="round"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg><h3 style="font-size:14px;margin:0">Histórico Recente</h3></div>`;
if(hist&&hist.length){hist.slice(0,15).forEach(h=>{
html+=`<div style="display:flex;gap:10px;padding:6px 0;border-bottom:1px solid var(--bdr);font-size:11px"><span style="color:var(--t3);font-family:'JetBrains Mono',monospace;white-space:nowrap;font-size:10px">${fmtDT(h.created_at)}</span><span style="color:var(--t2)">${esc(h.action)}</span></div>`})}
else html+=`<p style="color:var(--t3);font-size:12px">Nenhuma atividade recente</p>`;
html+=`</div>`;

html+=`</div></div>`;
document.getElementById('perfil-content').innerHTML=html}

async function saveProfile(){const body={name:document.getElementById('pf-name').value.trim(),avatar_color:document.getElementById('pf-color').value};const emailEl=document.getElementById('pf-email');if(!emailEl.readOnly)body.email=emailEl.value.trim();else body.email=emailEl.value;if(!body.name||!body.email)return alert('Nome obrigatório');const r=await api('profile',{method:'PUT',body});if(r&&!r.error){showToast(IC.check+' Perfil atualizado!');ME.name=body.name;ME.avatar_color=body.avatar_color;if(!emailEl.readOnly)ME.email=body.email;loadProfile()}}

async function uploadAvatar(file){if(!file)return;const fd=new FormData();fd.append('avatar',file);const r=await fetch('api.php?action=profile_avatar',{method:'POST',body:fd});const d=await r.json();if(d.success){ME.avatar_file=d.filename;showToast(IC.check+' Foto atualizada!');loadProfile()}else alert(d.error||'Erro no upload')}

async function changePassword(){const cur=document.getElementById('pf-curpw').value;const nw=document.getElementById('pf-newpw').value;if(!cur||!nw)return alert('Preencha ambos os campos');if(nw.length<6)return alert('Mínimo 6 caracteres');const r=await fetch('api.php?action=profile_password',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({current:cur,new:nw})});const d=await r.json();if(d.success){showToast(IC.check+' Senha alterada!');document.getElementById('pf-curpw').value='';document.getElementById('pf-newpw').value='';document.getElementById('pw-meter').innerHTML=''}else alert(d.error||'Erro')}

function checkPwStrength(pw){const el=document.getElementById('pw-meter');if(!pw){el.innerHTML='';return}let score=0;if(pw.length>=6)score++;if(pw.length>=10)score++;if(/[A-Z]/.test(pw))score++;if(/[0-9]/.test(pw))score++;if(/[^A-Za-z0-9]/.test(pw))score++;const levels=['','Fraca','Média','Boa','Forte','Excelente'];const colors=['','#ef4444','#f59e0b','#3b82f6','#10b981','#10b981'];el.innerHTML=`<div style="height:4px;border-radius:2px;background:var(--bdr);margin-top:4px"><div style="height:100%;width:${score*20}%;background:${colors[score]};border-radius:2px;transition:all .3s"></div></div><span style="font-size:10px;color:${colors[score]}">${levels[score]}</span>`}

// ===== ADMIN USER MANAGEMENT =====
// ─── Definição de Permissões ───────────────────────────
const PERM_GROUPS=[
  {label:'📊 Visão Geral',perms:[
    {key:'view_dashboard',label:'Dashboard',desc:'Ver painel principal'},
    {key:'view_kanban',label:'Kanban',desc:'Ver quadro visual'},
    {key:'view_calendario',label:'Calendário',desc:'Ver datas e entregas'},
    {key:'view_sprints',label:'Sprints',desc:'Ver ciclos de desenvolvimento'},
  ]},
  {label:'📋 Demandas',perms:[
    {key:'view_demandas',label:'Ver Demandas',desc:'Acessar lista de demandas'},
    {key:'view_all_demandas',label:'Ver Todas',desc:'Demandas de outros devs'},
    {key:'create_demandas',label:'Criar',desc:'Criar novas demandas'},
    {key:'edit_demandas',label:'Editar',desc:'Editar demandas existentes'},
    {key:'delete_demandas',label:'Excluir',desc:'Excluir demandas'},
    {key:'manage_sprints',label:'Sprints',desc:'Gerenciar sprints'},
  ]},
  {label:'📢 Comunicação',perms:[
    {key:'view_avisos',label:'Ver Avisos',desc:'Ver quadro de avisos'},
    {key:'create_avisos',label:'Criar Avisos',desc:'Publicar comunicados'},
    {key:'view_reunioes',label:'Ver Reuniões',desc:'Ver agenda de reuniões'},
    {key:'create_reunioes',label:'Criar Reuniões',desc:'Agendar reuniões'},
  ]},
  {label:'⚙️ Sistemas & Dados',perms:[
    {key:'view_sistemas',label:'Ver Sistemas',desc:'Catálogo de sistemas'},
    {key:'manage_sistemas',label:'Gerenciar Sistemas',desc:'Criar e editar sistemas'},
    {key:'view_devs',label:'Ver Devs',desc:'Lista de desenvolvedores'},
    {key:'view_relatorios',label:'Relatórios',desc:'Acessar analytics'},
    {key:'view_auditoria',label:'Auditoria',desc:'Registro de ações'},
  ]},
  {label:'📁 Docs & Pesquisas',perms:[
    {key:'view_docs',label:'Ver Docs',desc:'Acessar documentações'},
    {key:'manage_docs',label:'Gerenciar Docs',desc:'Criar e editar documentos'},
  ]},
  {label:'✅ Aprovações & Solicitações',perms:[
    {key:'view_aprovacoes',label:'Ver Aprovações',desc:'Painel de aprovações'},
    {key:'manage_aprovacoes',label:'Aprovar/Rejeitar',desc:'Gerenciar aprovações'},
    {key:'view_solicitacoes',label:'Ver Solicitações',desc:'Acessar solicitações'},
    {key:'manage_solicitacoes',label:'Tratar',desc:'Responder solicitações'},
  ]},
];
const DP={
  admin:Object.fromEntries(PERM_GROUPS.flatMap(g=>g.perms).map(p=>[p.key,1])),
  dev:{view_dashboard:1,view_kanban:1,view_calendario:1,view_sprints:1,view_demandas:1,view_all_demandas:0,create_demandas:1,edit_demandas:1,delete_demandas:0,manage_sprints:0,view_avisos:1,create_avisos:0,view_reunioes:1,create_reunioes:0,view_sistemas:1,manage_sistemas:0,view_devs:1,view_relatorios:0,view_auditoria:0,view_docs:1,manage_docs:0,view_aprovacoes:0,manage_aprovacoes:0,view_solicitacoes:1,manage_solicitacoes:0},
  presidencia:{view_dashboard:1,view_kanban:1,view_calendario:1,view_sprints:1,view_demandas:1,view_all_demandas:1,create_demandas:0,edit_demandas:0,delete_demandas:0,manage_sprints:0,view_avisos:1,create_avisos:1,view_reunioes:1,create_reunioes:1,view_sistemas:1,manage_sistemas:0,view_devs:1,view_relatorios:1,view_auditoria:1,view_docs:1,manage_docs:0,view_aprovacoes:1,manage_aprovacoes:1,view_solicitacoes:1,manage_solicitacoes:1},
  diretor:{view_dashboard:1,view_kanban:1,view_calendario:1,view_sprints:1,view_demandas:1,view_all_demandas:1,create_demandas:0,edit_demandas:0,delete_demandas:0,manage_sprints:0,view_avisos:1,create_avisos:1,view_reunioes:1,create_reunioes:1,view_sistemas:1,manage_sistemas:0,view_devs:1,view_relatorios:1,view_auditoria:0,view_docs:1,manage_docs:0,view_aprovacoes:1,manage_aprovacoes:0,view_solicitacoes:1,manage_solicitacoes:0},
};
let _pState={};
let _pOpen=null;

async function openPerms(uid,uname,urole){
  const firstRole=(urole||'dev').split(',')[0].trim();
  // Toggle — se já aberto, fecha
  if(_pOpen===uid){closePermsModal();return;}
  _pOpen=uid;

  // Remove modal anterior se existir
  let mo=document.getElementById('m-perms');
  if(!mo){
    mo=document.createElement('div');
    mo.id='m-perms';mo.className='modal-o';
    mo.innerHTML='<div class="modal" style="max-width:540px"><div class="modal-h"><h3 id="perms-modal-title">Permissões</h3><button class="modal-x" onclick="closePermsModal()">×</button></div><div class="modal-b" id="perms-modal-body" style="max-height:70vh;overflow-y:auto"></div><div class="modal-f" id="perms-modal-foot"></div></div>';
    document.body.appendChild(mo);
  }
  mo.classList.add('show');
  document.getElementById('perms-modal-body').innerHTML='<div style="padding:20px;text-align:center;color:var(--t3);font-size:12px">⏳ Carregando permissões...</div>';
  document.getElementById('perms-modal-foot').innerHTML='';

  const data=await fetch('api.php?action=user_permissions&user_id='+uid).then(r=>r.json()).catch(()=>({}));
  const saved=data.permissions||{};
  const defs=DP[firstRole]||DP.dev;
  const merged={};
  PERM_GROUPS.flatMap(g=>g.perms).forEach(p=>{merged[p.key]=(p.key in saved)?+saved[p.key]:(defs[p.key]?1:0)});
  _pState[uid]=merged;

  const rn={admin:'Admin',dev:'Dev',presidencia:'Presidência',diretor:'Diretor'};
  const rc={admin:'prb-admin',dev:'prb-dev',presidencia:'prb-presidencia',diretor:'prb-diretor'};

  document.getElementById('perms-modal-title').innerHTML='<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" style="vertical-align:-2px;margin-right:4px"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg> Permissões — '+esc(uname)+' <span class="perm-rb '+(rc[firstRole]||'prb-dev')+'" style="margin-left:8px;font-size:10px;vertical-align:middle">'+( rn[firstRole]||firstRole)+'</span>';

  let h='';
  PERM_GROUPS.forEach(g=>{
    h+='<div class="perm-grp"><div class="perm-grp-t">'+g.label+'</div><div class="perm-grid">';
    g.perms.forEach(p=>{
      const on=merged[p.key];
      h+='<div class="perm-it '+(on?'on':'off')+'" id="pi-'+uid+'-'+p.key+'" onclick="tgPerm('+uid+',\''+p.key+'\')"><div class="perm-tog"></div><div><div class="perm-lbl">'+p.label+'</div><div class="perm-desc">'+p.desc+'</div></div></div>';
    });
    h+='</div></div>';
  });
  document.getElementById('perms-modal-body').innerHTML=h;
  document.getElementById('perms-modal-foot').innerHTML='<button class="btn btn-w btn-sm" onclick="resetPerm('+uid+',\''+firstRole+'\')">↺ Padrões</button><button class="btn btn-p btn-sm" onclick="savePerm('+uid+')" >✓ Salvar</button>';
}

function closePermsModal(){
  const mo=document.getElementById('m-perms');
  if(mo)mo.classList.remove('show');
  _pOpen=null;
}

function tgPerm(uid,key){
  if(!_pState[uid])return;
  _pState[uid][key]=_pState[uid][key]?0:1;
  const el=document.getElementById('pi-'+uid+'-'+key);
  if(el){el.classList.toggle('on',!!_pState[uid][key]);el.classList.toggle('off',!_pState[uid][key]);}
}

async function savePerm(uid){
  const s=_pState[uid];if(!s)return;
  const perms=Object.entries(s).map(([k,v])=>({permission:k,granted:v}));
  const r=await fetch('api.php?action=user_permissions&user_id='+uid,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({permissions:perms})}).then(r=>r.json()).catch(()=>({}));
  if(r.success){showToast?showToast('✓ Permissões salvas!'):alert('Permissões salvas!');closePermsModal();if(uid===ME.id){loadMyPerms().then(()=>applyPerms());}}
  else alert(r.error||'Erro ao salvar');
}

async function resetPerm(uid,role){
  if(!confirm('Restaurar permissões padrão do role "'+role+'"?'))return;
  const r=await fetch('api.php?action=user_permissions_reset',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({user_id:uid})}).then(r=>r.json()).catch(()=>({}));
  if(r.success){
    _pState[uid]={...(DP[role]||DP.dev)};
    PERM_GROUPS.flatMap(g=>g.perms).forEach(p=>{
      const el=document.getElementById('pi-'+uid+'-'+p.key);
      if(el){el.classList.toggle('on',!!_pState[uid][p.key]);el.classList.toggle('off',!_pState[uid][p.key]);}
    });
    showToast?showToast('✓ Permissões restauradas!'):alert('Restaurado!');
  }else alert(r.error||'Erro');
}

async function loadUsers(){const users=await api('admin_users');let html='';if(!users||!users.length){html='<div class="empty"><p>Nenhum usuário cadastrado</p></div>';document.getElementById('usuarios-grid').innerHTML=html;return}
const RL={admin:'Administrador',dev:'Desenvolvedor',diretor:'Diretor',presidencia:'Presidência',usuario:'Usuário'};
const RC={admin:'#ef4444',dev:'#3b82f6',diretor:'#8b5cf6',presidencia:'#f59e0b',usuario:'#10b981'};
const RI={admin:'<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',dev:'<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>',diretor:'<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M2 20h20"/><path d="M4 20V10l4 4 4-8 4 8 4-4v10"/></svg>',presidencia:'<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',usuario:'<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>'};
users.forEach(u=>{const blocked=!u.active;
const roles=(u.role||'dev').split(',').map(r=>r.trim());
let badges=roles.map(r=>`<span style="display:inline-flex;align-items:center;gap:3px;padding:2px 8px;border-radius:20px;font-size:10px;font-weight:600;background:${RC[r]||'#666'}15;color:${RC[r]||'#666'};border:1px solid ${RC[r]||'#666'}30">${RI[r]||''}${RL[r]||r}</span>`).join(' ');
const lastLogin=u.last_login?`<span style="color:var(--suc)">${IC.clock} ${fmtDT(u.last_login)}</span>`:`<span style="color:var(--t3)">Nunca acessou</span>`;
html+=`<div class="card${blocked?' opacity-50':''}" style="position:relative;overflow:hidden" data-uid="${u.id}">
${blocked?'<div style="position:absolute;top:8px;right:8px;padding:2px 8px;border-radius:4px;background:var(--err);color:#fff;font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.5px">Bloqueado</div>':''}
<div class="card-h" style="border:none;padding-bottom:4px"><div style="display:flex;align-items:center;gap:12px">${avatarHtml(u,42)}<div><div style="font-weight:700;font-size:14px">${esc(u.name)}</div><div style="font-size:11px;color:var(--t3);display:flex;align-items:center;gap:4px"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="2" y="4" width="20" height="16" rx="3"/><polyline points="22,4 12,13 2,4"/></svg> ${esc(u.email)}</div></div></div></div>
<div style="padding:0 16px 8px;display:flex;flex-wrap:wrap;gap:4px">${badges}</div>
<div class="card-b" style="font-size:11px;color:var(--t3);display:flex;flex-direction:column;gap:4px;padding-top:8px;border-top:1px solid var(--bdr)">
<div style="display:flex;align-items:center;gap:4px"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg> ${lastLogin}</div>
<div style="display:flex;align-items:center;gap:4px">${IC.cal} Criado: ${fmtDT(u.created_at)}</div></div>
<div class="card-f" style="display:flex;gap:6px;flex-wrap:wrap"><button class="btn btn-w btn-sm" onclick='openUserModal(${JSON.stringify(u)})'>${IC.edit} Editar</button><button class="btn btn-w btn-sm" onclick="resetUserPw(${u.id},'${esc(u.name)}')" title="Redefinir senha"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="11" width="18" height="11" rx="3"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg> Senha</button>${u.id!==ME.id?`<button class="btn btn-sm ${blocked?'btn-ok':'btn-d'}" onclick="toggleUserBlock(${u.id},${blocked?1:0})" title="${blocked?'Desbloquear':'Bloquear'}">${blocked?IC.check:IC.block} ${blocked?'Ativar':'Bloquear'}</button>`:''}<button class="btn btn-sm" style="background:rgba(99,102,241,.12);color:var(--primary);border:1px solid rgba(99,102,241,.25)" onclick="openPerms(${u.id},'${esc(u.name).replace(/\\/g,'\\\\').replace(/'/g,"\\'")}','${u.role}')"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg> Permissões</button></div></div>`});
document.getElementById('usuarios-grid').innerHTML=html}

async function resetUserPw(uid,name){const pw=prompt('Nova senha para '+name+' (min 6 caracteres):');if(!pw)return;if(pw.length<6)return alert('Mínimo 6 caracteres');const r=await fetch('api.php?action=admin_reset_pw',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({user_id:uid,password:pw})});const d=await r.json();if(d.success)alert('Senha redefinida!');else alert(d.error||'Erro')}

async function toggleUserBlock(uid,active){if(!confirm(active?'Desbloquear este usuário?':'Bloquear este usuário?'))return;const r=await fetch('api.php?action=admin_user_toggle',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({user_id:uid,active:active})});const d=await r.json();if(d.success)loadUsers();else alert(d.error||'Erro')}


// ===== LIGHTBOX =====
function openLightbox(src){const lb=document.getElementById('lightbox');const img=document.getElementById('lightbox-img');if(lb&&img){img.src=src;lb.classList.add('show')}}

// ===== TOAST NOTIFICATIONS =====
let lastNotifIds=new Set();
let toastReady=false;
function showToastNotif(title,msg,type){
const tc=document.getElementById('toast-container');if(!tc)return;
const colors={info:'#3b82f6',success:'#10b981',warning:'#f59e0b',error:'#ef4444',demand:'#6366f1',notice:'#f59e0b',meeting:'#8b5cf6',urgent:'#ef4444'};
const icons={info:'<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>',success:'<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',demand:'<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>',notice:'<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 11l18-5v12L3 13v-2z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/></svg>',meeting:'<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',urgent:'<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>'};
const c=colors[type||'info']||colors.info;const ic=icons[type||'info']||icons.info;
const t=document.createElement('div');
t.style.cssText='pointer-events:auto;display:flex;align-items:flex-start;gap:10px;padding:14px 16px;border-radius:12px;background:var(--bg2,#1a1a2e);border:1px solid '+c+'40;box-shadow:0 8px 32px rgba(0,0,0,.4),0 0 0 1px '+c+'20;max-width:360px;min-width:280px;animation:toastIn .4s ease-out;cursor:pointer;position:relative;overflow:hidden;backdrop-filter:blur(12px)';
t.innerHTML='<div style="flex-shrink:0;color:'+c+';margin-top:1px">'+ic+'</div><div style="flex:1;min-width:0"><div style="font-size:12px;font-weight:600;color:var(--t1,#fff);margin-bottom:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">'+esc(title)+'</div>'+(msg?'<div style="font-size:11px;color:var(--t2,#999);line-height:1.4;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden">'+esc(msg)+'</div>':'')+'</div><button onclick="event.stopPropagation();this.parentElement.remove()" style="flex-shrink:0;background:none;border:none;color:var(--t3,#666);cursor:pointer;padding:2px;font-size:16px;line-height:1">&times;</button><div style="position:absolute;bottom:0;left:0;height:3px;background:'+c+';animation:toastTimer 6s linear forwards;width:100%"></div>';
t.onclick=function(e){if(e.target.tagName!=='BUTTON'){t.remove();showPage('notificacoes')}};
tc.prepend(t);
while(tc.children.length>4)tc.lastElementChild.remove();
setTimeout(function(){if(t.parentNode){t.style.animation='toastOut .3s ease-in forwards';setTimeout(function(){if(t.parentNode)t.remove()},300)}},6000)}

async function pollNewNotifs(){
try{
const r=await api('notifications_recent');
if(!r||!Array.isArray(r))return;
const currentIds=r.map(n=>n.id);
if(toastReady){
const brandNew=r.filter(n=>!lastNotifIds.has(n.id));
brandNew.forEach(n=>{
const typeMap={demand_assigned:'demand',demand_status:'demand',demand_comment:'demand',demand_new:'demand',demand_review:'demand',demand_accept:'demand',demand_completed:'success',mention:'demand',notice:'notice',meeting:'meeting',solicitation:'info',solicitation_approved:'info',solicitation_rejected:'info',deadline_warning:'urgent'};
showToastNotif(n.title,n.message||'',typeMap[n.type]||'info');
if(n.type==='demand_completed') launchConfetti()
});
// Auto-refresh current page on new notifications
if(brandNew.length>0){if(_openDemandId&&document.getElementById('m-detail')?.classList.contains('show'))openDetail(_openDemandId);const pg=getCurrentPage();if(pg==='dashboard')loadDashboard();else if(pg==='kanban')loadKanban();else if(pg==='demandas')loadDemands();else if(pg==='notificacoes')loadNotificacoes();else if(pg==='solicitacoes')loadSolicitations();else if(pg==='aprovacoes')loadApprovals()}
}
lastNotifIds=new Set(currentIds);
toastReady=true;
}catch(e){console.log('poll error',e)}}
let lastDeadlineCheck=0;
async function checkDeadlines(){
const now=Date.now();
if(now-lastDeadlineCheck<300000)return; // every 5 min
lastDeadlineCheck=now;
try{await api('check_deadlines')}catch(e){}}

// ===== INIT =====
(async()=>{await loadBaseData();
// Devs: default Minhas ON, others: OFF
if(!IS_DEV){const fm=document.getElementById('f-mine');if(fm)fm.checked=false;const km=document.getElementById('k-mine');if(km)km.checked=false}
loadMyPerms().then(()=>{applyPerms();loadDashboard()});loadNotifCount();pollNewNotifs();updateClock();checkDeadlines();
setInterval(updateClock,1000);
setInterval(()=>{loadNotifCount();pollNewNotifs();checkDeadlines()},2000);
setInterval(()=>{const pg=getCurrentPage();if(pg==='dashboard')loadDashboard();else if(pg==='kanban')loadKanban();else if(pg==='demandas')loadDemands();else if(pg==='notificacoes')loadNotificacoes()},15000)})();

// ===== DOCUMENTATIONS PAGE =====
async function loadDocs(){
const dc=document.getElementById('docs-content');if(!dc)return;
const sysF=document.getElementById('doc-sys-f')?.value||'';
const catF=document.getElementById('doc-cat-f')?.value||'';
const p={};if(sysF)p.system_id=sysF;if(catF)p.category=catF;
let docs=[];
try{const res=await api('docs',{params:p});if(Array.isArray(res))docs=res;else if(res&&Array.isArray(res.data))docs=res.data;else{console.warn('loadDocs: resposta inesperada',res);docs=[];}}catch(e){console.error('loadDocs erro:',e);docs=[];}
const cats=[...new Set(docs.map(d=>d.category).filter(Boolean))];
let html='<div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:20px;align-items:center">';
html+='<button class="btn btn-p btn-sm" onclick="openDocModal()">'+IC.plus+' Nova Documentação</button>';
html+='<select class="fsel" id="doc-sys-f" onchange="loadDocs()" style="min-width:160px"><option value="">Todos Sistemas</option>'+(allSystems||[]).map(s=>'<option value="'+s.id+'"'+(sysF==s.id?' selected':'')+'>'+esc(s.name)+'</option>').join('')+'</select>';
html+='<select class="fsel" id="doc-cat-f" onchange="loadDocs()" style="min-width:140px"><option value="">Todas Categorias</option>'+cats.map(c=>'<option value="'+esc(c)+'"'+(catF===c?' selected':'')+'>'+esc(c)+'</option>').join('')+'</select>';
html+='<span style="margin-left:auto;font-size:11px;color:var(--t3)">'+docs.length+' documento'+(docs.length!==1?'s':'')+'</span></div>';
html+='<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:14px">';
docs.forEach(d=>{
  const fc=d.files?.length||0;
  html+=`<div onclick="openDoc(${d.id})" style="cursor:pointer;padding:16px;background:var(--bg2);border:1px solid var(--bdr);border-radius:14px;transition:border-color .18s,box-shadow .18s,transform .15s;display:flex;flex-direction:column;gap:8px" onmouseover="this.style.borderColor='var(--acc)';this.style.boxShadow='0 6px 24px rgba(0,0,0,.18)';this.style.transform='translateY(-2px)'" onmouseout="this.style.borderColor='var(--bdr)';this.style.boxShadow='none';this.style.transform='translateY(0)'">`;
  // Title row
  html+=`<div style="display:flex;align-items:center;gap:6px"><span style="font-weight:600;font-size:13px;color:var(--t1);flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${esc(d.title)}</span>${d.has_password?'<span style="color:var(--t3);flex-shrink:0" title="Protegido por senha">'+IC.lock_sm+'</span>':''}</div>`;
  // Description
  if(d.description)html+=`<p style="font-size:11px;color:var(--t2);line-height:1.5;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;margin:0">${esc(d.description)}</p>`;
  // Tags row
  html+=`<div style="display:flex;gap:5px;flex-wrap:wrap;align-items:center">`;
  html+=`<span style="font-size:9px;background:var(--bg3);color:var(--t2);padding:2px 8px;border-radius:6px;font-weight:500">${esc(d.category||'Geral')}</span>`;
  if(d.system_name)html+=`<a href="#" onclick="event.preventDefault();event.stopPropagation();openSystemDetail(${d.system_id})" style="font-size:9px;background:rgba(99,102,241,.1);color:#818cf8;padding:2px 8px;border-radius:6px;text-decoration:none;cursor:pointer" onmouseover="this.style.background='rgba(99,102,241,.2)'" onmouseout="this.style.background='rgba(99,102,241,.1)'">${esc(d.system_name)}</a>`;
  html+=`</div>`;
  // Footer
  html+=`<div style="display:flex;align-items:center;justify-content:space-between;padding-top:8px;border-top:1px solid var(--bdr)">`;
  html+=`<span style="font-size:10px;color:var(--t3)">${esc(d.author_name||'—')} · ${timeAgo(d.updated_at||d.created_at)}</span>`;
  html+=`<div style="display:flex;gap:6px;align-items:center">`;
  if(fc)html+=`<span style="font-size:10px;color:var(--t3);">${IC.file} ${fc}</span>`;
  if(d.has_password)html+=`<span style="font-size:10px;color:var(--t3)">${IC.lock_sm}</span>`;
  html+=`</div></div>`;
  html+=`</div>`;
});
if(!docs.length)html+='<div class="empty" style="grid-column:1/-1"><p>Nenhuma documentação encontrada</p></div>';
html+='</div>';
dc.innerHTML=html;
}

async function openDoc(id,pw){
const dc=document.getElementById('docs-content');if(!dc)return;
const params={id};if(pw)params.password=pw;
const d=await api('doc',{params});
if(!d||d.error)return showToast(d?.error||'Erro ao carregar');
if(d.locked){
  const mid='doc-pw-modal';document.getElementById(mid)?.remove();
  document.body.insertAdjacentHTML('beforeend',`<div id="${mid}" style="position:fixed;inset:0;background:rgba(0,0,0,.6);backdrop-filter:blur(8px);display:flex;align-items:center;justify-content:center;z-index:9999" onclick="if(event.target===this){this.remove();loadDocs()}">
    <div style="background:var(--bg2);border:1px solid var(--bdr);border-radius:20px;padding:32px 28px 28px;width:360px;box-shadow:0 32px 80px rgba(0,0,0,.4)" onclick="event.stopPropagation()">
      <div style="text-align:center;margin-bottom:24px">
        <div style="display:inline-flex;align-items:center;justify-content:center;width:52px;height:52px;border-radius:16px;background:var(--bg3);margin-bottom:12px">${IC.lock_lg}</div>
        <div style="font-weight:600;font-size:15px;color:var(--t1)">Documentação protegida</div>
        <div style="font-size:12px;color:var(--t3);margin-top:4px">Digite a senha para acessar</div>
      </div>
      <div id="doc-pw-error" style="display:none;font-size:11px;color:var(--err);background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.2);border-radius:8px;padding:8px 12px;margin-bottom:12px;text-align:center">Senha incorreta. Tente novamente.</div>
      <input type="password" id="doc-pw-input" class="fsel" placeholder="Senha de acesso" style="width:100%;box-sizing:border-box;margin-bottom:16px" autofocus>
      <div style="display:flex;gap:8px">
        <button class="btn btn-g" style="flex:1" onclick="document.getElementById('${mid}').remove();loadDocs()">Cancelar</button>
        <button class="btn btn-p" style="flex:2" id="doc-pw-ok">Acessar</button>
      </div>
    </div></div>`);
  const input=document.getElementById('doc-pw-input');
  const ok=document.getElementById('doc-pw-ok');
  const doAccess=async()=>{
    const v=input.value.trim();if(!v){input.focus();return;}
    ok.disabled=true;ok.textContent='...';
    const result=await api('doc',{params:{id,password:v}});
    if(result&&!result.locked&&!result.error){document.getElementById(mid)?.remove();openDoc(id,v);}
    else{
      const errEl=document.getElementById('doc-pw-error');if(errEl)errEl.style.display='block';
      input.value='';input.style.borderColor='rgba(239,68,68,.6)';
      setTimeout(()=>{if(input)input.style.borderColor='';},2000);
      ok.disabled=false;ok.textContent='Acessar';input.focus();
    }
  };
  ok.onclick=doAccess;
  input.onkeydown=e=>{if(e.key==='Enter')doAccess();if(e.key==='Escape'){document.getElementById(mid)?.remove();loadDocs();}};
  setTimeout(()=>input.focus(),50);
  return;
}
const fIcons={pdf:'📄',doc:'📝',docx:'📝',xls:'📊',xlsx:'📊',ppt:'📊',pptx:'📊',png:'🖼',jpg:'🖼',jpeg:'🖼',gif:'🖼',zip:'🗜',rar:'🗜',txt:'📋',md:'📋',csv:'📊'};
let html='<div>';
// Header bar
html+='<div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:16px;gap:12px">';
html+='<div style="flex:1">';
html+='<div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:6px">';
html+='<span style="font-size:9px;background:var(--bg3);color:var(--t2);padding:2px 8px;border-radius:6px;font-weight:500">'+esc(d.category||'Geral')+'</span>';
if(d.system_name)html+='<a href="#" onclick="event.preventDefault();openSystemDetail('+d.system_id+')" style="font-size:9px;background:rgba(99,102,241,.1);color:#818cf8;padding:2px 8px;border-radius:6px;text-decoration:none;cursor:pointer" onmouseover="this.style.background=\'rgba(99,102,241,.2)\'" onmouseout="this.style.background=\'rgba(99,102,241,.1)\'">'+esc(d.system_name)+'</a>';
if(d.has_password)html+='<span style="font-size:10px;color:var(--t3);display:inline-flex;align-items:center;gap:3px">'+IC.lock_sm+' Protegido</span>';
html+='<span style="font-size:10px;color:var(--t3)">'+esc(d.author_name||'—')+' · '+fmtDT(d.updated_at||d.created_at)+'</span>';
html+='</div>';
html+='<h2 style="font-size:17px;font-weight:700;color:var(--t1);margin:0">'+esc(d.title)+'</h2>';
html+='</div>';
html+='<div style="display:flex;gap:6px;flex-shrink:0">';
if(d.created_by==ME.id||IS_ADMIN)html+='<button class="btn btn-g btn-sm" onclick="openDocModal('+d.id+')">'+IC.edit+' Editar</button>';
if(d.created_by==ME.id||IS_ADMIN)html+='<button class="btn btn-g btn-sm" style="color:var(--err)" onclick="deleteDoc('+d.id+')">'+IC.trash+' Excluir</button>';
if(d.has_password&&d.password_visible)html+='<button class="btn btn-g btn-sm" style="color:var(--t3);font-family:monospace;font-size:11px" onclick="navigator.clipboard?.writeText(\''+esc(d.password_visible)+'\');showToast(\'Senha copiada!\')">'+IC.lock_sm+' '+esc(d.password_visible)+'</button>';
html+='<button class="btn btn-g btn-sm" onclick="loadDocs()">← Voltar</button>';
html+='</div></div>';
// Description
if(d.description)html+='<p style="font-size:12px;color:var(--t2);margin-bottom:14px;padding:10px 14px;background:var(--bg3);border-radius:10px;border-left:3px solid var(--acc)">'+esc(d.description)+'</p>';
// Content
if(d.content)html+='<div style="background:var(--bg2);border:1px solid var(--bdr);border-radius:12px;padding:20px;margin-bottom:16px;font-size:13px;line-height:1.8;white-space:pre-wrap;font-family:\'JetBrains Mono\',monospace;color:var(--t1)">'+esc(d.content)+'</div>';
// Files
if(d.files&&d.files.length){
  html+='<div style="margin-top:4px"><div style="font-size:10px;font-weight:600;color:var(--t3);margin-bottom:8px;text-transform:uppercase;letter-spacing:.06em">'+IC.file+' Arquivos ('+d.files.length+')</div>';
  html+='<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:8px">';
  d.files.forEach(f=>{const ext=f.original_name.split('.').pop().toLowerCase();const sz=f.file_size>1048576?(f.file_size/1048576).toFixed(1)+'MB':(f.file_size/1024).toFixed(0)+'KB';
    html+='<a href="docs_api.php?action=doc_file_download&id='+f.id+'" target="_blank" style="display:flex;align-items:center;gap:10px;padding:10px 12px;background:var(--bg3);border-radius:10px;text-decoration:none;border:1px solid var(--bdr);transition:.15s" onmouseover="this.style.borderColor=\'var(--acc)\'" onmouseout="this.style.borderColor=\'var(--bdr)\'"><span style="font-size:20px">'+(fIcons[ext]||'📎')+'</span><div style="flex:1;min-width:0"><div style="font-size:12px;color:var(--t1);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">'+esc(f.original_name)+'</div><div style="font-size:10px;color:var(--t3)">'+sz+'</div></div></a>';
  });
  
  html+='</div></div>';
}
html+='</div>';
dc.innerHTML=html;
}

let _pendingFiles=[];
function previewPendingFiles(){
const input=document.getElementById('doc-file-input-new');if(!input)return;
_pendingFiles=[..._pendingFiles,...input.files];
const list=document.getElementById('doc-files-pending-list');if(!list)return;
const fIcons={pdf:'📄',doc:'📝',docx:'📝',xls:'📊',xlsx:'📊',ppt:'📊',pptx:'📊',png:'🖼',jpg:'🖼',jpeg:'🖼',gif:'🖼',zip:'🗜',rar:'🗜',txt:'📋',md:'📋',csv:'📊'};
list.innerHTML=_pendingFiles.length?_pendingFiles.map((f,i)=>`<div style="display:flex;align-items:center;gap:8px;padding:6px 10px;background:var(--bg3);border-radius:8px;margin-bottom:4px"><span style="font-size:16px">${fIcons[f.name.split('.').pop().toLowerCase()]||'📎'}</span><span style="flex:1;font-size:12px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${esc(f.name)}</span><span style="font-size:10px;color:var(--t3)">${f.size>1048576?(f.size/1048576).toFixed(1)+'MB':(f.size/1024).toFixed(0)+'KB'}</span><button class="btn btn-g btn-sm" style="color:var(--err);padding:2px 6px" onclick="_pendingFiles.splice(${i},1);previewPendingFiles()">✕</button></div>`).join(''):'';
input.value='';
}

function renderDocFilesList(files,docId){
const fl=document.getElementById('doc-files-list');if(!fl)return;
const fIcons={pdf:'📄',doc:'📝',docx:'📝',xls:'📊',xlsx:'📊',ppt:'📊',pptx:'📊',png:'🖼',jpg:'🖼',jpeg:'🖼',gif:'🖼',zip:'🗜',rar:'🗜',txt:'📋',md:'📋',csv:'📊'};
if(!files.length){fl.innerHTML='<p style="font-size:11px;color:var(--t3);padding:6px 0">Nenhum arquivo ainda.</p>';return;}
fl.innerHTML=files.map(f=>{const ext=f.original_name.split('.').pop().toLowerCase();const sz=f.file_size>1048576?(f.file_size/1048576).toFixed(1)+'MB':(f.file_size/1024).toFixed(0)+'KB';
return`<div style="display:flex;align-items:center;gap:8px;padding:8px 10px;background:var(--bg3);border-radius:8px;margin-bottom:4px"><span style="font-size:18px">${fIcons[ext]||'📎'}</span><a href="docs_api.php?action=doc_file_download&id=${f.id}" target="_blank" style="flex:1;font-size:12px;color:var(--t1);text-decoration:none;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${esc(f.original_name)}</a><span style="font-size:10px;color:var(--t3);white-space:nowrap">${sz}</span><button class="btn btn-g btn-sm" style="color:var(--err);padding:2px 8px;flex-shrink:0" onclick="deleteDocFile(${f.id},${docId})">✕</button></div>`;
}).join('');
}

async function uploadDocFiles(docId){
const input=document.getElementById('doc-file-input'),status=document.getElementById('doc-upload-status');
if(!input||!input.files.length)return;
const files=[...input.files];if(status)status.textContent='Enviando...';
let ok=0,fail=0;
for(const file of files){const fd=new FormData();fd.append('file',file);try{const r=await api('doc_upload',{formData:fd,params:{id:docId}});if(r?.success)ok++;else{fail++;console.error('Upload error:',r?.error);}}catch(e){fail++;}}
input.value='';if(ok)showToast('✓ '+ok+' arquivo(s) enviado(s)!');if(fail)showToast('⚠ '+fail+' falha(s)');if(status)status.textContent='';
const dd=await api('doc',{params:{id:docId}});renderDocFilesList(dd?.files||[],docId);
}

async function deleteDocFile(fileId,docId){
if(!confirm('Remover este arquivo?'))return;
const r=await api('doc_file_delete',{method:'DELETE',params:{id:fileId}});
if(r?.success){showToast('Arquivo removido');const dd=await api('doc',{params:{id:docId}});renderDocFilesList(dd?.files||[],docId);}
else showToast('Erro: '+(r?.error||''));
}

function openDocModal(editId){
const dc=document.getElementById('docs-content');if(!dc)return;
_pendingFiles=[];
const isEdit=!!editId;
// ALL accepted file types
const accepted='.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.md,.csv,.png,.jpg,.jpeg,.gif,.webp,.svg,.zip,.rar,.7z,.mp4,.mp3,.json,.xml,.html,.css,.js,.php,.py,.sql,.log';
let html='<div style="max-width:820px">';
html+=`<h3 style="margin-bottom:20px;font-size:15px;color:var(--t1)">${isEdit?'✏️ Editar':'📄 Nova'} Documentação</h3>`;
html+='<div class="fg"><label>Título *</label><input class="fsel" id="doc-title" placeholder="Título do documento"></div>';
html+='<div class="fg"><label>Descrição</label><input class="fsel" id="doc-desc" placeholder="Breve resumo da documentação"></div>';
html+='<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">';
html+='<div class="fg"><label>Sistema</label><select class="fsel" id="doc-sys"><option value="">Nenhum</option>'+(allSystems||[]).map(s=>'<option value="'+s.id+'">'+esc(s.name)+'</option>').join('')+'</select></div>';
html+='<div class="fg"><label>Categoria</label><input class="fsel" id="doc-cat" value="Geral" placeholder="Manual, API, Deploy..."></div>';
html+='</div>';
html+='<div class="fg"><label>Conteúdo</label><textarea class="fsel" id="doc-content" rows="12" placeholder="Conteúdo..." style="font-family:\'JetBrains Mono\',monospace;font-size:12px;resize:vertical;line-height:1.7"></textarea></div>';
html+='<div class="fg"><label style="display:flex;align-items:center;gap:5px">'+IC.lock_sm+' Senha de proteção <span style="font-weight:400;color:var(--t3)">(opcional)</span></label>';
html+='<div style="display:flex;align-items:center;gap:8px"><input type="password" class="fsel" id="doc-pw" placeholder="Deixe vazio para acesso livre" style="flex:1">';
html+='<button type="button" onclick="const i=document.getElementById(\'doc-pw\');i.type=i.type===\'password\'?\'text\':\'password\'" style="height:42px;padding:0 14px;background:var(--bg3);border:1px solid var(--bdr);border-radius:10px;cursor:pointer;font-size:14px;flex-shrink:0;color:var(--t2)">👁</button></div>';
html+='<div id="doc-pw-hint" style="display:none;font-size:10px;color:var(--t3);margin-top:4px;background:var(--bg3);padding:4px 8px;border-radius:6px;font-family:monospace"></div></div>';
html+='<div class="fg"><label style="display:flex;align-items:center;gap:5px">'+IC.file+' Arquivos <span style="font-weight:400;color:var(--t3)">(qualquer tipo)</span></label>';
if(isEdit){
  html+='<div id="doc-files-list" style="margin-bottom:8px"></div>';
  html+='<label style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;background:var(--bg3);border:1px dashed var(--bdr);border-radius:10px;cursor:pointer;font-size:12px;color:var(--t2);transition:.2s" onmouseover="this.style.borderColor=\'var(--acc)\'" onmouseout="this.style.borderColor=\'var(--bdr)\'">'+IC.file+' Adicionar arquivo<input type="file" id="doc-file-input" multiple accept="'+accepted+'" style="display:none" onchange="uploadDocFiles('+editId+')"></label>';
  html+='<span id="doc-upload-status" style="font-size:11px;color:var(--t3);margin-left:8px"></span>';
}else{
  html+='<div id="doc-files-pending-list" style="margin-bottom:8px"></div>';
  html+='<label style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;background:var(--bg3);border:1px dashed var(--bdr);border-radius:10px;cursor:pointer;font-size:12px;color:var(--t2);transition:.2s" onmouseover="this.style.borderColor=\'var(--acc)\'" onmouseout="this.style.borderColor=\'var(--bdr)\'">'+IC.file+' Selecionar arquivos<input type="file" id="doc-file-input-new" multiple accept="'+accepted+'" style="display:none" onchange="previewPendingFiles()"></label>';
  html+='<p style="font-size:10px;color:var(--t3);margin-top:6px">Enviados automaticamente após salvar.</p>';
}
html+='</div>';
html+='<div style="display:flex;gap:8px;justify-content:flex-end;margin-top:20px;padding-top:16px;border-top:1px solid var(--bdr)">';
html+='<button class="btn btn-g" onclick="loadDocs()">Cancelar</button>';
html+=`<button class="btn btn-p" onclick="saveDoc(${editId||'null'})">✓ Salvar</button>`;
html+='</div></div>';
dc.innerHTML=html;
if(isEdit){api('doc',{params:{id:editId}}).then(d=>{if(!d||d.error)return;if(!d.locked){document.getElementById('doc-title').value=d.title||'';document.getElementById('doc-desc').value=d.description||'';const sysSel=document.getElementById('doc-sys');if(sysSel)sysSel.value=d.system_id||'';document.getElementById('doc-cat').value=d.category||'';document.getElementById('doc-content').value=d.content||'';if(d.password_visible){const pw=document.getElementById('doc-pw');if(pw)pw.value=d.password_visible;const hint=document.getElementById('doc-pw-hint');if(hint){hint.textContent='Senha atual: '+d.password_visible;hint.style.display='block';}}}renderDocFilesList(d.files||[],editId);});}
}

async function saveDoc(editId){
const body={title:document.getElementById('doc-title').value.trim(),description:document.getElementById('doc-desc').value.trim(),content:document.getElementById('doc-content').value,system_id:document.getElementById('doc-sys').value||null,category:document.getElementById('doc-cat').value.trim()||'Geral',password:document.getElementById('doc-pw').value};
if(!body.title)return showToast('Título obrigatório');
const r=editId?await api('doc',{method:'PUT',params:{id:editId},body}):await api('docs',{method:'POST',body});
if(r?.success){
  showToast('✓ Documentação salva!');
  if(!editId&&r.id&&typeof _pendingFiles!=='undefined'&&_pendingFiles.length){
    for(const file of _pendingFiles){const fd=new FormData();fd.append('file',file);await api('doc_upload',{formData:fd,params:{id:r.id}});}
    _pendingFiles=[];
  }
  loadDocs();
}else showToast(r?.error||'Erro ao salvar');
}

async function deleteDoc(id){if(!confirm('Excluir documentação?'))return;const r=await api('doc',{method:'DELETE',params:{id}});if(r?.success){showToast(IC.check+' Excluída!');loadDocs()}}

// ===== SURVEYS PAGE =====
async function loadSurveys(){
const surveys=await api('surveys')||[];
let html='<div style="margin-bottom:16px">';
if(IS_ADMIN||IS_DIR)html+='<button class="btn btn-p btn-sm" onclick="openSurveyModal()">'+IC.plus+' Nova Pesquisa</button>';
html+='</div>';

surveys.forEach(sv=>{
  const expired=sv.expires_at&&new Date(sv.expires_at)<new Date();
  const canVote=sv.active&&!expired;
  const totalVotes=sv.total_votes||0;
  const maxVotes=Math.max(...(sv.options||[]).map(o=>o.votes||0),1);
  
  html+='<div class="survey-card'+((!sv.active||expired)?' style="opacity:.6"':'')+'"><div class="sv-t">'+esc(sv.title);
  if(!sv.active)html+=' <span style="font-size:10px;color:var(--err)">(Encerrada)</span>';
  if(expired)html+=' <span style="font-size:10px;color:var(--warn)">(Expirada)</span>';
  html+='</div>';
  if(sv.description)html+='<div class="sv-d">'+esc(sv.description)+'</div>';
  
  (sv.options||[]).forEach(o=>{
    const pct=totalVotes>0?Math.round((o.votes/totalVotes)*100):0;
    const isMyVote=(sv.my_votes||[]).includes(o.id);
    const barColor=isMyVote?'var(--acc)':'var(--t3)';
    html+='<div class="sv-opt'+(isMyVote?' voted':'')+'"'+(canVote?' onclick="voteSurvey('+sv.id+','+o.id+',\''+sv.type+'\')"':'')+' data-sv="'+sv.id+'" data-opt="'+o.id+'">';
    html+='<span style="min-width:20px;text-align:center;font-weight:700;color:'+(isMyVote?'var(--acc)':'var(--t3)')+'">'+(isMyVote?'✓':'○')+'</span>';
    html+='<span style="flex:1">'+esc(o.label)+'</span>';
    html+='<div class="sv-bar" style="width:80px"><div class="sv-bar-fill" style="width:'+pct+'%;background:'+barColor+'"></div></div>';
    html+='<span style="font-size:10px;color:var(--t3);min-width:40px;text-align:right">'+o.votes+' ('+pct+'%)</span>';
    html+='</div>';
  });
  
  html+='<div class="sv-meta"><span>'+totalVotes+' voto(s)'+(sv.anonymous?' · Anônima':'')+'</span>';
  html+='<span>';
  if(IS_ADMIN||IS_DIR){
    html+='<button class="btn btn-g btn-sm" style="font-size:9px;padding:2px 8px" onclick="toggleSurvey('+sv.id+')">'+(sv.active?'Encerrar':'Reativar')+'</button> ';
    html+='<button class="btn btn-g btn-sm" style="font-size:9px;padding:2px 8px;color:var(--err)" onclick="deleteSurvey('+sv.id+')">Excluir</button>';
  }
  if(sv.expires_at)html+=' · Expira: '+fmtDT(sv.expires_at);
  html+='</span></div></div>';
});

if(!surveys.length)html+='<div class="empty"><p>Nenhuma pesquisa criada</p></div>';
document.getElementById('pesquisas-content').innerHTML=html;
}

async function voteSurvey(sid,oid,type){
let optIds=[oid];
if(type==='multiple'){
  // Toggle: if already voted, remove; otherwise add
  const card=document.querySelector('.sv-opt[data-sv="'+sid+'"][data-opt="'+oid+'"]');
  const allOpts=document.querySelectorAll('.sv-opt[data-sv="'+sid+'"]');
  optIds=[];
  allOpts.forEach(el=>{
    const isThis=el.dataset.opt==oid;
    const wasVoted=el.classList.contains('voted');
    if((isThis&&!wasVoted)||(!isThis&&wasVoted))optIds.push(parseInt(el.dataset.opt));
  });
}
await api('survey_vote',{method:'POST',params:{id:sid},body:{option_ids:optIds}});
loadSurveys();
}

function openSurveyModal(){
let html='<div style="padding:20px"><h3>Nova Pesquisa</h3>';
html+='<div class="fg"><label>Título *</label><input class="fsel" id="sv-title" placeholder="Ex: Qual framework preferem?"></div>';
html+='<div class="fg"><label>Descrição</label><input class="fsel" id="sv-desc" placeholder="Contexto da pesquisa"></div>';
html+='<div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">';
html+='<div class="fg"><label>Tipo</label><select class="fsel" id="sv-type"><option value="single">Escolha única</option><option value="multiple">Múltipla escolha</option></select></div>';
html+='<div class="fg"><label>Expira em</label><input type="datetime-local" class="fsel" id="sv-expires"></div>';
html+='</div>';
html+='<div class="fg"><label>Anônima?</label><select class="fsel" id="sv-anon"><option value="0">Não</option><option value="1">Sim</option></select></div>';
html+='<div id="sv-opts-list"><div class="fg"><label>Opções *</label></div></div>';
html+='<button class="btn btn-g btn-sm" style="margin-bottom:12px" onclick="addSurveyOpt()">+ Adicionar opção</button>';
html+='<div style="display:flex;gap:8px;justify-content:flex-end">';
html+='<button class="btn btn-g" onclick="loadSurveys()">Cancelar</button>';
html+='<button class="btn btn-p" onclick="saveSurvey()">Criar</button>';
html+='</div></div>';
document.getElementById('pesquisas-content').innerHTML=html;
// Add 2 initial options
addSurveyOpt();addSurveyOpt();
}

function addSurveyOpt(){
const list=document.getElementById('sv-opts-list');
const n=list.querySelectorAll('input').length;
const div=document.createElement('div');div.style.cssText='display:flex;gap:6px;margin-bottom:6px';
div.innerHTML='<input class="fsel sv-opt-input" placeholder="Opção '+(n+1)+'" style="flex:1"><button class="btn btn-g btn-sm" onclick="this.parentElement.remove()" style="color:var(--err)">✕</button>';
list.appendChild(div);
}

async function saveSurvey(){
const opts=[];document.querySelectorAll('.sv-opt-input').forEach(i=>{if(i.value.trim())opts.push(i.value.trim())});
if(opts.length<2)return showToast('Mínimo 2 opções');
const body={title:document.getElementById('sv-title').value.trim(),description:document.getElementById('sv-desc').value.trim(),type:document.getElementById('sv-type').value,anonymous:parseInt(document.getElementById('sv-anon').value),expires_at:document.getElementById('sv-expires').value||null,options:opts};
if(!body.title)return showToast('Título obrigatório');
const r=await api('surveys',{method:'POST',body});
if(r?.success){showToast(IC.check+' Pesquisa criada!');loadSurveys()}else showToast(r?.error||'Erro');
}

async function toggleSurvey(id){await api('survey_toggle',{method:'POST',params:{id}});loadSurveys()}
async function deleteSurvey(id){if(!confirm('Excluir pesquisa?'))return;await api('survey_delete',{method:'DELETE',params:{id}});loadSurveys()}

// ===== SYSTEM DETAIL VIEW =====
async function openSystemDetail(id){
const d=await api('system_detail',{params:{id}});
if(!d||d.error)return showToast(d?.error||'Erro');
let html='<div style="padding:20px"><div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">';
html+='<h3 style="font-size:16px">'+esc(d.name)+'</h3>';
html+='<button class="btn btn-g btn-sm" onclick="closeM(\'m-detail\')">'+IC.x+' Fechar</button></div>';
// Stats
const st=d.stats||{};
html+='<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:14px">';
html+='<div class="sc blue"><div class="sc-l">Total</div><div class="sc-v">'+(st.total||0)+'</div></div>';
html+='<div class="sc green"><div class="sc-l">Concluídas</div><div class="sc-v">'+(st.done||0)+'</div></div>';
html+='<div class="sc" style="border-left:3px solid var(--acc)"><div class="sc-l">Ativas</div><div class="sc-v">'+(st.active||0)+'</div></div>';
html+='</div>';
// Tabs
html+='<div class="sys-detail-tabs"><button class="active" onclick="sysTab(this,\'sys-demands\')">Demandas ('+(d.demands||[]).length+')</button><button onclick="sysTab(this,\'sys-docs\')">Documentações ('+(d.docs||[]).length+')</button></div>';
// Demands tab
html+='<div id="sys-demands"><div style="max-height:300px;overflow-y:auto">';
(d.demands||[]).forEach(dm=>{
  const stColor={'Aberta':'var(--pnd)','Em Andamento':'var(--acc)','Em Revisão':'var(--warn)','Concluída':'var(--ok)','Cancelada':'var(--err)'}[dm.status]||'var(--t3)';
  html+='<div style="display:flex;justify-content:space-between;align-items:center;padding:8px 10px;border-bottom:1px solid var(--bdr);font-size:12px;cursor:pointer" onclick="closeM(\'m-detail\');setTimeout(()=>openDetail('+dm.id+'),200)">';
  html+='<div><span style="font-weight:600">'+esc(dm.title)+'</span> <span style="font-size:10px;color:'+stColor+'">'+dm.status+'</span></div>';
  html+='<span style="font-size:10px;color:var(--t3)">'+fmtDT(dm.created_at)+'</span></div>';
});
if(!(d.demands||[]).length)html+='<div class="empty"><p>Nenhuma demanda</p></div>';
html+='</div></div>';
// Docs tab
html+='<div id="sys-docs" style="display:none"><div style="max-height:300px;overflow-y:auto">';
(d.docs||[]).forEach(dc=>{
  html+='<div class="doc-card" style="margin-bottom:8px" onclick="closeM(\'m-detail\');setTimeout(()=>{loadPage(\'docs\');setTimeout(()=>openDoc('+dc.id+'),300)},200)">';
  html+='<div class="dc-t">'+(dc.has_password?'🔒 ':'')+esc(dc.title)+'</div>';
  html+='<div class="dc-meta">'+esc(dc.category||'Geral')+' · '+fmtDT(dc.created_at)+'</div></div>';
});
if(!(d.docs||[]).length)html+='<div class="empty"><p>Nenhuma documentação</p></div>';
html+='</div></div>';
html+='</div>';
const _mb=document.getElementById('det-body');if(_mb)_mb.innerHTML=html;
openM('m-detail');
}

function sysTab(btn,tabId){
btn.parentElement.querySelectorAll('button').forEach(b=>b.classList.remove('active'));
btn.classList.add('active');
btn.parentElement.parentElement.querySelectorAll('[id^="sys-"]').forEach(el=>el.style.display='none');
document.getElementById(tabId).style.display='block';
}

// ===== DEV DETAIL VIEW (COMPREHENSIVE) =====
async function openDevDetail(userId){
const d=await api('dev_detail',{params:{id:userId}});
if(!d||d.error)return showToast(d?.error||'Erro ao carregar perfil');
const st=d.stats||{};
const demands=d.demands||[];
const activity=d.activity||[];
const sistemas=d.sistemas||[];
const monthly=d.monthly||[];
const acceptances=d.acceptances||{};

// Derived stats
const total=st.total||0;
const concluidas=st.concluidas||0;
const ativas=st.ativas||0;
const pctConc=total?Math.round(concluidas/total*100):0;
const avgDays=st.avg_completion_days?parseFloat(st.avg_completion_days).toFixed(1):'—';

// Role badges
const roleLabels={admin:'Administrador',dev:'Desenvolvedor',diretor:'Diretor',presidencia:'Presidência',usuario:'Usuário'};
const roleColors={admin:'#ef4444',dev:'#3b82f6',diretor:'#8b5cf6',presidencia:'#f59e0b',usuario:'#6b7280'};
const roles=(d.role||'').split(',').map(r=>r.trim()).filter(Boolean);
const roleBadges=roles.map(r=>`<span style="display:inline-flex;align-items:center;gap:3px;padding:2px 10px;border-radius:20px;font-size:10px;font-weight:600;background:${roleColors[r]||'#666'}18;color:${roleColors[r]||'#666'};border:1px solid ${roleColors[r]||'#666'}30">${roleLabels[r]||r}</span>`).join(' ');

// Status distribution
const stDist={'Aberta':0,'Aguardando Aceite':0,'Em Andamento':0,'Em Revisão':0,'Concluída':0,'Cancelada':0};
demands.forEach(dm=>{if(stDist[dm.status]!==undefined)stDist[dm.status]++});
const stColors={'Aberta':'#6366f1','Aguardando Aceite':'#d4a017','Em Andamento':'#3b82f6','Em Revisão':'#f59e0b','Concluída':'#10b981','Cancelada':'#ef4444'};

// Priority distribution
const priDist={Urgente:0,Alta:0,'Média':0,Baixa:0};
demands.forEach(dm=>{if(priDist[dm.priority]!==undefined)priDist[dm.priority]++});

// Avatar
const avImg=d.avatar_file?`<img src="api.php?action=arquivo&f=${d.avatar_file}" style="width:100%;height:100%;border-radius:50%;object-fit:cover">`:`<span style="font-size:28px;color:#fff;font-weight:700">${(d.name||'?')[0]}</span>`;

let html='<div style="padding:0">';

// ── HEADER ──
html+=`<div style="display:flex;align-items:center;gap:16px;margin-bottom:20px">
  <div style="width:64px;height:64px;border-radius:50%;background:${d.avatar_color||'#3b82f6'};display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden;box-shadow:0 4px 16px ${d.avatar_color||'#3b82f6'}40">${avImg}</div>
  <div style="flex:1;min-width:0">
    <h2 style="margin:0 0 4px;font-size:18px;font-weight:700">${esc(d.name)}</h2>
    <div style="display:flex;gap:4px;flex-wrap:wrap;margin-bottom:4px">${roleBadges}</div>
    <div style="font-size:11px;color:var(--t3);display:flex;gap:12px;flex-wrap:wrap">
      <span>${IC.mail||'✉'} ${esc(d.email)}</span>
      ${d.last_login?`<span>${IC.clock||'⏱'} Último acesso: ${fmtDT(d.last_login)}</span>`:''}
      <span>${IC.cal||'📅'} Membro desde: ${fmtDT(d.created_at)}</span>
    </div>
  </div>
  <div style="display:flex;gap:6px;flex-shrink:0">
    ${IS_ADMIN?`<button class="btn btn-g btn-sm" onclick="closeM('m-detail');editUser(${d.id})">${IC.edit} Editar</button>`:''}
    <button class="btn btn-g btn-sm" onclick="closeM('m-detail')">${IC.x}</button>
  </div>
</div>`;

// ── KPI STATS ──
html+=`<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(90px,1fr));gap:8px;margin-bottom:20px">
  <div class="sc blue"><div class="sc-l">Total</div><div class="sc-v">${total}</div></div>
  <div class="sc green"><div class="sc-l">Concluídas</div><div class="sc-v">${concluidas}</div></div>
  <div class="sc" style="border-left:3px solid var(--warn)"><div class="sc-l">Ativas</div><div class="sc-v">${ativas}</div></div>
  <div class="sc purple"><div class="sc-l">Conclusão</div><div class="sc-v">${pctConc}%</div></div>
  <div class="sc" style="border-left:3px solid #f59e0b"><div class="sc-l">Média Dias</div><div class="sc-v">${avgDays}</div></div>
  <div class="sc" style="border-left:3px solid #8b5cf6"><div class="sc-l">Sistemas</div><div class="sc-v">${sistemas.length}</div></div>
  
  <div class="sc" style="border-left:3px solid #10b981"><div class="sc-l">Aceites</div><div class="sc-v">${acceptances.Aceita||0}/${total}</div></div>
</div>`;

// ── COMPLETION RING + STATUS BAR ──
html+=`<div style="display:flex;gap:16px;margin-bottom:20px;flex-wrap:wrap">`;
// Ring
html+=`<div style="text-align:center;flex-shrink:0">
  <svg width="80" height="80" viewBox="0 0 80 80"><circle cx="40" cy="40" r="34" fill="none" stroke="var(--bdr)" stroke-width="5"/><circle cx="40" cy="40" r="34" fill="none" stroke="${pctConc>=70?'#10b981':pctConc>=40?'#f59e0b':'#ef4444'}" stroke-width="5" stroke-linecap="round" stroke-dasharray="${pctConc*2.136} 213.6" transform="rotate(-90 40 40)" style="transition:stroke-dasharray .8s ease"/></svg>
  <div style="margin-top:-55px;position:relative;font-size:18px;font-weight:800">${pctConc}%</div>
  <div style="margin-top:32px;font-size:9px;color:var(--t3)">Taxa Conclusão</div>
</div>`;
// Status bar
if(total>0){
  html+=`<div style="flex:1;min-width:200px">
    <div style="font-size:10px;font-weight:600;color:var(--t3);text-transform:uppercase;margin-bottom:6px">Distribuição por Status</div>
    <div style="display:flex;height:8px;border-radius:4px;overflow:hidden;background:var(--bg)">`;
  Object.entries(stDist).forEach(([s,c])=>{
    if(c>0){const w=(c/total*100);html+=`<div style="width:${w}%;background:${stColors[s]||'#666'}" title="${s}: ${c}"></div>`}
  });
  html+=`</div>
    <div style="display:flex;gap:8px;margin-top:6px;flex-wrap:wrap">`;
  Object.entries(stDist).forEach(([s,c])=>{
    if(c>0)html+=`<span style="font-size:10px;color:var(--t3);display:flex;align-items:center;gap:3px"><span style="width:8px;height:8px;border-radius:2px;background:${stColors[s]}"></span>${s}: ${c}</span>`;
  });
  html+=`</div>
    <div style="font-size:10px;font-weight:600;color:var(--t3);text-transform:uppercase;margin:12px 0 6px">Prioridade</div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">`;
  Object.entries(priDist).forEach(([p,c])=>{
    if(c>0)html+=`<span class="badge ${pClass(p)}" style="font-size:10px">${p}: ${c}</span>`;
  });
  html+=`</div></div>`;
}
html+=`</div>`;

// ── MONTHLY CHART ──
if(monthly.length>1){
  const maxM=Math.max(...monthly.map(m=>m.total||0),1);
  html+=`<div style="margin-bottom:20px">
    <div style="font-size:10px;font-weight:600;color:var(--t3);text-transform:uppercase;margin-bottom:8px">Entregas por Mês</div>
    <div style="display:flex;align-items:flex-end;gap:4px;height:60px">`;
  monthly.slice().reverse().forEach(m=>{
    const h=Math.max((m.total/maxM)*55,3);
    const label=m.month?m.month.substring(5):'';
    html+=`<div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:2px">
      <span style="font-size:9px;font-weight:700;color:var(--t2)">${m.total}</span>
      <div style="width:100%;height:${h}px;background:var(--acc);border-radius:3px 3px 0 0;min-width:16px"></div>
      <span style="font-size:8px;color:var(--t3)">${label}</span>
    </div>`;
  });
  html+=`</div></div>`;
}

// ── TABS ──
html+=`<div style="display:flex;gap:0;border-bottom:2px solid var(--bdr);margin-bottom:16px">
  <button class="sys-tab active" onclick="devTab(this,'dev-demands')" style="padding:8px 16px;font-size:12px;font-weight:600;border:none;background:none;color:var(--acc);border-bottom:2px solid var(--acc);margin-bottom:-2px;cursor:pointer">${IC.clipboard||'📋'} Demandas (${demands.length})</button>
  <button class="sys-tab" onclick="devTab(this,'dev-sistemas')" style="padding:8px 16px;font-size:12px;font-weight:600;border:none;background:none;color:var(--t3);border-bottom:2px solid transparent;margin-bottom:-2px;cursor:pointer">${IC.monitor||'💻'} Sistemas (${sistemas.length})</button>
  <button class="sys-tab" onclick="devTab(this,'dev-activity')" style="padding:8px 16px;font-size:12px;font-weight:600;border:none;background:none;color:var(--t3);border-bottom:2px solid transparent;margin-bottom:-2px;cursor:pointer">${IC.clock||'⏱'} Atividade</button>
</div>`;

// ── TAB: DEMANDAS ──
html+=`<div id="dev-demands">`;
if(demands.length){
  // Filters
  html+=`<div style="display:flex;gap:8px;margin-bottom:12px;flex-wrap:wrap">
    <select id="dev-dm-status" onchange="filterDevDemands()" style="font-size:11px;padding:4px 8px;border-radius:6px;border:1px solid var(--bdr);background:var(--bg2);color:var(--t1)">
      <option value="">Todos Status</option>
      ${Object.keys(stDist).map(s=>`<option value="${s}">${s}</option>`).join('')}
    </select>
    <select id="dev-dm-pri" onchange="filterDevDemands()" style="font-size:11px;padding:4px 8px;border-radius:6px;border:1px solid var(--bdr);background:var(--bg2);color:var(--t1)">
      <option value="">Todas Prioridades</option>
      <option>Urgente</option><option>Alta</option><option>Média</option><option>Baixa</option>
    </select>
    <input id="dev-dm-search" onkeyup="filterDevDemands()" placeholder="Buscar..." style="font-size:11px;padding:4px 8px;border-radius:6px;border:1px solid var(--bdr);background:var(--bg2);color:var(--t1);flex:1;min-width:120px">
  </div>`;
  html+=`<div id="dev-demands-list" style="max-height:350px;overflow-y:auto"><table><thead><tr><th style="width:30px">#</th><th>Demanda</th><th>Sistema</th><th>Prioridade</th><th>Status</th><th>Aceite</th><th>Prazo</th><th>Criada</th></tr></thead><tbody>`;
  demands.forEach(dm=>{
    let deadlineHtml=fmtDate(dm.deadline);
    if(dm.deadline&&!['Concluída','Cancelada'].includes(dm.status)){
      const diff=Math.ceil((new Date(dm.deadline+'T12:00:00')-Date.now())/86400000);
      if(diff<0) deadlineHtml+=` <span style="color:var(--err);font-size:9px;font-weight:700">(${Math.abs(diff)}d atraso)</span>`;
      else if(diff<=3) deadlineHtml+=` <span style="color:var(--err);font-size:9px">(${diff}d)</span>`;
      else if(diff<=7) deadlineHtml+=` <span style="color:var(--warn);font-size:9px">(${diff}d)</span>`;
    }
    const accBadge={'Aceita':'<span style="color:var(--ok);font-size:10px;font-weight:600">✓ Aceita</span>','Recusada':'<span style="color:var(--err);font-size:10px;font-weight:600">✗ Recusada</span>','Pendente':'<span style="color:var(--t3);font-size:10px">Pendente</span>'}[dm.acceptance]||'';
    html+=`<tr class="dev-dm-row" data-status="${dm.status}" data-pri="${dm.priority}" data-title="${esc(dm.title).toLowerCase()}" onclick="closeM('m-detail');setTimeout(()=>openDetail(${dm.id}),200)" style="cursor:pointer">
      <td style="font-family:'JetBrains Mono',monospace;color:var(--t3);font-size:10px">#${dm.id}</td>
      <td style="font-weight:600;font-size:12px">${esc(dm.title)}</td>
      <td style="font-size:10px;color:var(--t2)">${dm.system_name?esc(dm.system_name):'—'}</td>
      <td><span class="badge ${pClass(dm.priority)}">${dm.priority}</span></td>
      <td><span class="badge ${sClass(dm.status)}">${dm.status}</span></td>
      <td>${accBadge}</td>
      <td style="font-size:10px;font-family:'JetBrains Mono',monospace;color:var(--t3)">${deadlineHtml}</td>
      <td style="font-size:10px;font-family:'JetBrains Mono',monospace;color:var(--t3)">${fmtDT(dm.created_at)}</td>
    </tr>`;
  });
  html+=`</tbody></table></div>`;
}else{
  html+=`<div class="empty" style="padding:30px"><p>Nenhuma demanda atribuída</p></div>`;
}
html+=`</div>`;

// ── TAB: SISTEMAS ──
html+=`<div id="dev-sistemas" style="display:none">`;
if(sistemas.length){
  html+=`<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:10px">`;
  sistemas.forEach(s=>{
    const techs=(s.technology||'').split(',').filter(Boolean);
    html+=`<div onclick="closeM('m-detail');setTimeout(()=>openSystemDetail(${s.id}),200)" style="cursor:pointer;padding:14px;background:var(--bg3);border:1px solid var(--bdr);border-radius:12px;transition:.18s" onmouseover="this.style.borderColor='var(--acc)'" onmouseout="this.style.borderColor='var(--bdr)'">
      <div style="font-weight:600;font-size:13px;margin-bottom:6px">${esc(s.name)}</div>
      <span class="badge ${{
        'Em uso':'s-concluida','Testes':'s-revisao','Pausado':'s-cancelada','Em desenvolvimento':'s-andamento','Não utilizado':'s-aberta'}[s.status]||''}" style="font-size:9px">${s.status||'—'}</span>
      ${s.url?`<div style="margin-top:6px"><a href="https://${s.url}" target="_blank" onclick="event.stopPropagation()" style="font-size:10px;color:var(--acc);text-decoration:none">🔗 ${esc(s.url)}</a></div>`:''}
      ${techs.length?`<div style="display:flex;flex-wrap:wrap;gap:3px;margin-top:6px">${techs.map(t=>`<span class="tech-tag" style="font-size:9px">${esc(t.trim())}</span>`).join('')}</div>`:''}
      <div style="display:flex;gap:8px;margin-top:8px;font-size:10px;color:var(--t3)">
        <span>${s.demand_count||0} demandas</span>
        <span>${s.doc_count||0} docs</span>
      </div>
    </div>`;
  });
  html+=`</div>`;
}else{
  html+=`<div class="empty" style="padding:30px"><p>Nenhum sistema atribuído</p></div>`;
}
html+=`</div>`;

// ── TAB: ATIVIDADE ──
html+=`<div id="dev-activity" style="display:none">`;
if(activity.length){
  html+=`<div style="max-height:400px;overflow-y:auto">`;
  let lastDate='';
  activity.forEach(h=>{
    const dt=(h.created_at||'').substring(0,10);
    if(dt!==lastDate){
      lastDate=dt;
      html+=`<div style="font-size:10px;font-weight:700;color:var(--acc);padding:10px 0 4px;border-bottom:1px solid var(--bdr);margin-bottom:4px">${fmtDate(dt)}</div>`;
    }
    html+=`<div style="display:flex;align-items:flex-start;gap:10px;padding:6px 0">
      <div style="width:6px;height:6px;border-radius:50%;background:${stColors[h.new_value]||'var(--acc)'};margin-top:5px;flex-shrink:0"></div>
      <div style="flex:1;min-width:0">
        <div style="font-size:11px">
          <span style="color:var(--t2)">${esc(h.action||'')}</span>
          ${h.old_value?` <span class="badge" style="font-size:9px;padding:1px 6px;background:var(--errb);color:var(--err)">${esc(h.old_value)}</span> → `:''}
          ${h.new_value?`<span class="badge" style="font-size:9px;padding:1px 6px;background:var(--okb);color:var(--ok)">${esc(h.new_value)}</span>`:''}
          ${h.details?` <span style="font-size:10px;color:var(--t3);font-style:italic">"${esc(h.details.substring(0,80))}"</span>`:''}
        </div>
        ${h.demand_title?`<div style="font-size:10px;color:var(--t3);margin-top:2px;cursor:pointer" onclick="closeM('m-detail');setTimeout(()=>openDetail(${h.entity_id}),200)">→ ${esc(h.demand_title)}</div>`:''}
        <div style="font-size:9px;color:var(--t3);font-family:'JetBrains Mono',monospace;margin-top:1px">${fmtDT(h.created_at)}</div>
      </div>
    </div>`;
  });
  html+=`</div>`;
}else{
  html+=`<div class="empty" style="padding:30px"><p>Nenhuma atividade registrada</p></div>`;
}
html+=`</div>`;

html+=`</div>`;

const _mb=document.getElementById('det-body');if(_mb)_mb.innerHTML=html;
const footer=document.getElementById('det-footer');
if(footer)footer.innerHTML=`<button class="btn btn-g" onclick="closeM('m-detail')">Fechar</button>`;
const titleEl=document.getElementById('det-title');
if(titleEl)titleEl.textContent=d.name+' — Perfil do Desenvolvedor';
openM('m-detail');
}

function devTab(btn,tabId){
btn.parentElement.querySelectorAll('button').forEach(b=>{b.style.color='var(--t3)';b.style.borderBottomColor='transparent';b.classList.remove('active')});
btn.style.color='var(--acc)';btn.style.borderBottomColor='var(--acc)';btn.classList.add('active');
['dev-demands','dev-sistemas','dev-activity'].forEach(id=>{const el=document.getElementById(id);if(el)el.style.display='none'});
const target=document.getElementById(tabId);if(target)target.style.display='block';
}

function filterDevDemands(){
const st=document.getElementById('dev-dm-status')?.value||'';
const pr=document.getElementById('dev-dm-pri')?.value||'';
const q=(document.getElementById('dev-dm-search')?.value||'').toLowerCase();
document.querySelectorAll('.dev-dm-row').forEach(row=>{
  const match=(!st||row.dataset.status===st)&&(!pr||row.dataset.pri===pr)&&(!q||row.dataset.title.includes(q));
  row.style.display=match?'':'none';
});
}


// ═══ SIDEBAR TOGGLE v4 — FINAL ═══
(function initSidebarToggle(){
  var sidebar = document.getElementById('sidebar') || document.querySelector('.sidebar');
  if(!sidebar) return;

  // Remove old toggles
  sidebar.querySelectorAll('.sidebar-toggle').forEach(function(el){ el.remove(); });

  // ── Create toggle button ──
  var btn = document.createElement('div');
  btn.className = 'sidebar-toggle';
  btn.title = 'Recolher / Expandir';
  btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>';
  btn.addEventListener('click', function(e){
    e.preventDefault();
    e.stopPropagation();
    toggleSidebar();
  });
  sidebar.appendChild(btn);

  // ── Favicon mini logo ──
  var brand = sidebar.querySelector('.sb-brand');
  if(brand){
    var mini = document.createElement('img');
    mini.src = 'assets/img/favicon.png';
    mini.alt = 'Menu';
    mini.className = 'logo-mini';
    mini.style.cssText = 'display:none;width:32px;height:32px;object-fit:contain;border-radius:6px;margin:0 auto;';
    brand.appendChild(mini);
  }

  // ── Tooltips on nav items ──
  sidebar.querySelectorAll('[onclick], a').forEach(function(item){
    if(item.classList.contains('sidebar-toggle')) return;
    if(item.closest('.sidebar-toggle')) return;
    if(item.closest('.sb-brand')) return;

    var text = '';
    // Get text from spans
    var spans = item.querySelectorAll('span');
    for(var i = 0; i < spans.length; i++){
      var s = spans[i];
      if(s.className && s.className.match && s.className.match(/badge|icon/)) continue;
      var t = s.textContent.trim();
      if(t.length > 1 && t.length < 40){ text = t; break; }
    }
    // Fallback: direct text content
    if(!text){
      var nodes = item.childNodes;
      for(var j = 0; j < nodes.length; j++){
        if(nodes[j].nodeType === 3){
          var t2 = nodes[j].textContent.trim();
          if(t2.length > 1 && t2.length < 40){ text = t2; break; }
        }
      }
    }
    if(text) item.setAttribute('data-tip', text);
  });

  // ── Restore saved state ──
  if(localStorage.getItem('sidebar_collapsed') === '1'){
    document.body.classList.add('sidebar-collapsed');
  }
})();

function toggleSidebar(){
  var collapsed = document.body.classList.toggle('sidebar-collapsed');
  localStorage.setItem('sidebar_collapsed', collapsed ? '1' : '0');
  setTimeout(function(){ window.dispatchEvent(new Event('resize')); }, 300);
}

// Click em area vazia do sidebar colapsado → expandir
document.getElementById('sidebar').addEventListener('click', function(e){
    if(!document.body.classList.contains('sidebar-collapsed')) return;
    // Se clicou em nav item ou toggle, ignora
    var t = e.target.closest('.sb-i, .sidebar-toggle, .sb-user .av, a[onclick]');
    if(t) return;
    toggleSidebar();
});

// Click no avatar quando colapsado → Meu Perfil
var _sbAvBtn = document.querySelector('.sb-user .av');
if(_sbAvBtn) _sbAvBtn.addEventListener('click', function(){
    if(document.body.classList.contains('sidebar-collapsed')){
        showPage('perfil');
    }
});

// Click no avatar OU nome → Meu Perfil (aberto e colapsado)
var _sbUserEl = document.querySelector('.sb-user');
if(_sbUserEl) _sbUserEl.addEventListener('click', function(e){
    // Ignora se clicou no botão logout
    if(e.target.closest('button,[onclick*="logout"]')) return;
    showPage('perfil');
});

// Atualizar avatar do sidebar ao trocar foto no perfil
(function(){
    var origSave = window.saveAvatar;
    if(!origSave) return;
    window.saveAvatar = async function(){
        await origSave.apply(this, arguments);
        // Reload avatar no sidebar
        setTimeout(function(){
            var av = document.querySelector('.sb-user .av');
            if(!av) return;
            var img = av.querySelector('img');
            if(img){
                img.src = img.src.split('?')[0] + '?t=' + Date.now();
            }
        }, 500);
    };
})();

// Também atualizar sidebar quando perfil carrega nova foto
var _origLoadPerfil = window.loadPerfil;
if(_origLoadPerfil){
    window.loadPerfil = async function(){
        await _origLoadPerfil.apply(this, arguments);
        setTimeout(function(){
            var profileImg = document.querySelector('#perfil-content .profile-av img, #perfil-content .big-av img');
            var sidebarAv = document.querySelector('.sb-user .av');
            if(profileImg && sidebarAv){
                var existing = sidebarAv.querySelector('img');
                if(existing){
                    existing.src = profileImg.src.split('?')[0] + '?t=' + Date.now();
                } else {
                    sidebarAv.textContent = '';
                    var img = document.createElement('img');
                    img.src = profileImg.src;
                    img.style.cssText = 'width:100%;height:100%;border-radius:50%;object-fit:cover';
                    sidebarAv.appendChild(img);
                }
            }
        }, 600);
    };
}

// Forçar atualizar avatar do sidebar após upload
(function(){
    // Observer: detecta quando img do perfil muda
    var observer = new MutationObserver(function(){
        var perfImg = document.querySelector('#perfil-content img[src*="avatars/"]');
        var sbImg = document.querySelector('.sb-user .av img');
        var sbAv = document.querySelector('.sb-user .av');
        if(perfImg && sbAv){
            var newSrc = perfImg.src.split('?')[0] + '?t=' + Date.now();
            if(sbImg){
                sbImg.src = newSrc;
            } else {
                sbAv.innerHTML = '';
                sbAv.innerHTML = '<img src="'+newSrc+'" style="width:100%;height:100%;border-radius:50%;object-fit:cover">';
            }
        }
    });
    var content = document.getElementById('perfil-content');
    if(content) observer.observe(content, {childList:true, subtree:true, attributes:true, attributeFilter:['src']});
})();

// Override uploadAvatar para atualizar sidebar
var _origUploadAvatar = window.uploadAvatar;
window.uploadAvatar = async function(file){
    if(!file) return;
    const fd = new FormData();
    fd.append('avatar', file);
    const r = await fetch('api.php?action=profile_avatar', {method:'POST', body:fd});
    const d = await r.json();
    if(d.success){
        ME.avatar_file = d.filename;
        showToast(IC.check+' Foto atualizada!');
        // Atualizar sidebar
        var sbAv = document.querySelector('.sb-user .av');
        if(sbAv){
            var newSrc = 'api.php?action=arquivo&f=' + d.filename + '&t=' + Date.now();
            var img = sbAv.querySelector('img');
            if(img){
                img.src = newSrc;
            } else {
                sbAv.innerHTML = '<img src="'+newSrc+'" style="width:100%;height:100%;border-radius:50%;object-fit:cover">';
            }
        }
        loadProfile();
    } else {
        alert(d.error || 'Erro no upload');
    }
};
