<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    require_once __DIR__.'/config.php';
    initSession();
    if (isLoggedIn() && getCurrentUser()) {
        header('Location: index.php');
        exit;
    }
} catch (Exception $e) {
    // DB not ready yet
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Login - Gestão Dev ASSEGO</title>
<link rel="icon" type="image/png" href="assets/img/logo.png">
<link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
:root{
    --bg:#0a0e1a;--bg2:#111827;--bg3:#1a2236;--bg4:#232d44;
    --acc:#3b82f6;--acc2:#2563eb;--acc3:#60a5fa;
    --t1:#f1f5f9;--t2:#94a3b8;--t3:#64748b;
    --bdr:#1e293b;--suc:#10b981;--err:#ef4444;--gold:#f59e0b;
}
html,body{height:100%;font-family:'Plus Jakarta Sans',sans-serif;color:var(--t1)}

/* === ANIMATED BACKGROUND === */
body{
    background:var(--bg);
    overflow:hidden;
    display:flex;align-items:center;justify-content:center;
}
.bg-grid{
    position:fixed;inset:0;z-index:0;
    background-image:
        linear-gradient(rgba(59,130,246,.03) 1px, transparent 1px),
        linear-gradient(90deg, rgba(59,130,246,.03) 1px, transparent 1px);
    background-size:60px 60px;
    animation:gridMove 20s linear infinite;
}
@keyframes gridMove{to{background-position:60px 60px}}

.bg-orb{
    position:fixed;border-radius:50%;filter:blur(80px);opacity:.15;z-index:0;
    animation:orbFloat 12s ease-in-out infinite alternate;
}
.bg-orb.o1{width:500px;height:500px;background:var(--acc);top:-10%;left:-10%;animation-delay:0s}
.bg-orb.o2{width:400px;height:400px;background:#8b5cf6;bottom:-10%;right:-10%;animation-delay:-4s}
.bg-orb.o3{width:300px;height:300px;background:var(--suc);top:50%;left:60%;animation-delay:-8s}
@keyframes orbFloat{
    0%{transform:translate(0,0) scale(1)}
    50%{transform:translate(30px,-30px) scale(1.1)}
    100%{transform:translate(-20px,20px) scale(.95)}
}

/* === PARTICLES === */
.particles{position:fixed;inset:0;z-index:0;overflow:hidden}
.particles span{
    position:absolute;display:block;width:2px;height:2px;background:var(--acc3);
    border-radius:50%;opacity:0;
    animation:particleFloat 8s linear infinite;
}
.particles span:nth-child(1){left:10%;animation-delay:0s;animation-duration:6s}
.particles span:nth-child(2){left:25%;animation-delay:1.5s;animation-duration:7s}
.particles span:nth-child(3){left:40%;animation-delay:3s;animation-duration:8s}
.particles span:nth-child(4){left:55%;animation-delay:.5s;animation-duration:6.5s}
.particles span:nth-child(5){left:70%;animation-delay:2s;animation-duration:7.5s}
.particles span:nth-child(6){left:85%;animation-delay:4s;animation-duration:9s}
.particles span:nth-child(7){left:15%;animation-delay:1s;animation-duration:8.5s}
.particles span:nth-child(8){left:50%;animation-delay:3.5s;animation-duration:6s}
@keyframes particleFloat{
    0%{bottom:-10px;opacity:0;transform:translateX(0)}
    20%{opacity:.6}
    80%{opacity:.3}
    100%{bottom:110%;opacity:0;transform:translateX(40px)}
}

/* === LOGIN CONTAINER === */
.login-wrapper{
    position:relative;z-index:1;width:100%;max-width:440px;padding:20px;
    animation:fadeUp .6s ease-out;
}
@keyframes fadeUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}

.login-card{
    background:rgba(17,24,39,.85);
    backdrop-filter:blur(24px);
    border:1px solid rgba(59,130,246,.15);
    border-radius:20px;
    padding:40px 36px;
    box-shadow:0 25px 60px rgba(0,0,0,.4),0 0 40px rgba(59,130,246,.05);
}

/* === BRAND === */
.brand{text-align:center;margin-bottom:32px}
.brand-logo{
    width:160px;height:auto;
    margin:0 auto 16px;
    display:flex;align-items:center;justify-content:center;
    background:transparent;
    position:relative;overflow:visible;
}
.brand-logo img{width:160px;height:auto;position:relative;z-index:1}
.brand-logo .fallback{
    color:#fff;font-weight:800;font-size:20px;letter-spacing:1px;
    font-family:'JetBrains Mono',monospace;position:relative;z-index:1;
}
.brand h1{font-size:22px;font-weight:700;letter-spacing:-.3px;margin-bottom:4px}
.brand p{font-size:12px;color:var(--t3);font-family:'JetBrains Mono',monospace;letter-spacing:.5px}

/* === FORM === */
.form-group{margin-bottom:18px;position:relative}
.form-group label{
    display:block;font-size:11px;font-weight:600;color:var(--t3);
    text-transform:uppercase;letter-spacing:.8px;margin-bottom:8px;
}
.form-group .input-wrap{
    position:relative;display:flex;align-items:center;
    background:var(--bg3);border:1px solid var(--bdr);border-radius:12px;
    transition:all .3s ease;
}
.form-group .input-wrap:focus-within{
    border-color:var(--acc);
    box-shadow:0 0 0 3px rgba(59,130,246,.15);
}
.form-group .input-icon{
    padding:0 14px;display:flex;align-items:center;color:var(--t3);
    flex-shrink:0;
}
.form-group .input-wrap:focus-within .input-icon{color:var(--acc3)}
.form-group input{
    flex:1;background:none;border:none;outline:none;
    color:var(--t1);font-size:14px;padding:14px 14px 14px 0;
    font-family:inherit;width:100%;
}
.form-group input::placeholder{color:var(--t3)}
.form-group .toggle-pass{
    padding:0 14px;cursor:pointer;color:var(--t3);background:none;border:none;
    display:flex;align-items:center;transition:color .2s;
}
.form-group .toggle-pass:hover{color:var(--t1)}

/* === ERROR === */
.error-msg{
    display:none;padding:12px 16px;border-radius:10px;
    background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);
    color:var(--err);font-size:12px;margin-bottom:16px;
    animation:shake .4s ease-in-out;
    align-items:center;gap:8px;
}
.error-msg.show{display:flex}
@keyframes shake{
    0%,100%{transform:translateX(0)}
    25%{transform:translateX(-6px)}
    75%{transform:translateX(6px)}
}

/* === BUTTON === */
.btn-login{
    width:100%;padding:14px;border:none;border-radius:12px;
    background:linear-gradient(135deg,var(--acc),var(--acc2));
    color:#fff;font-size:15px;font-weight:600;cursor:pointer;
    font-family:inherit;position:relative;overflow:visible;
    transition:transform .2s,box-shadow .2s;
    box-shadow:0 4px 16px rgba(59,130,246,.3);
}
.btn-login:hover{transform:translateY(-1px);box-shadow:0 8px 24px rgba(59,130,246,.4)}
.btn-login:active{transform:translateY(0)}
.btn-login:disabled{opacity:.7;cursor:wait}
.btn-login .shine{
    position:absolute;top:0;left:-100%;width:100%;height:100%;
    background:linear-gradient(90deg,transparent,rgba(255,255,255,.15),transparent);
    transition:none;
}
.btn-login:hover .shine{left:100%;transition:left .6s ease}
.btn-loading{display:none;width:18px;height:18px;border:2px solid rgba(255,255,255,.3);
    border-top-color:#fff;border-radius:50%;animation:spin .8s linear infinite;
    margin:0 auto;
}
@keyframes spin{to{transform:rotate(360deg)}}

/* === FOOTER === */
.login-footer{
    text-align:center;margin-top:24px;padding-top:20px;
    border-top:1px solid var(--bdr);
}
.login-footer p{font-size:10px;color:var(--t3);font-family:'JetBrains Mono',monospace}
.login-footer .hint{
    display:inline-flex;align-items:center;gap:6px;
    padding:6px 12px;border-radius:8px;background:var(--bg3);
    margin-top:8px;font-size:10px;color:var(--t2);
}

/* === STATUS BAR === */
.status-bar{
    display:flex;align-items:center;justify-content:center;gap:8px;
    margin-top:20px;opacity:.6;
}
.status-dot{width:6px;height:6px;border-radius:50%;background:var(--suc);
    animation:pulse 2s ease-in-out infinite}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.3}}
.status-bar span{font-size:9px;color:var(--t3);font-family:'JetBrains Mono',monospace;
    text-transform:uppercase;letter-spacing:1px}

/* === RESPONSIVE === */
@media(max-width:480px){
    .login-card{padding:32px 24px;border-radius:16px}
    .brand-logo{width:120px;height:auto}
    .brand h1{font-size:18px}
}
</style>
</head>
<body>

<div class="bg-grid"></div>
<div class="bg-orb o1"></div>
<div class="bg-orb o2"></div>
<div class="bg-orb o3"></div>
<div class="particles"><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span></div>

<div class="login-wrapper">
    <div class="login-card">
        <div class="brand">
            <div class="brand-logo">
                <img src="assets/img/logo.png" alt="Logo" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                <div class="fallback" style="display:none">TI</div>
            </div>
            <h1>Gestão Dev ASSEGO</h1>
            <p>Sistema de Demandas</p>
        </div>

        <div class="error-msg" id="login-error">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
            <span id="error-text"></span>
        </div>

        <div class="form-group">
            <label>Usuário ou Email</label>
            <div class="input-wrap">
                <div class="input-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><rect x="2" y="4" width="20" height="16" rx="3"/><polyline points="22,4 12,13 2,4"/></svg>
                </div>
                <input type="text" id="login-email" placeholder="nome ou email" autocomplete="username" autofocus>
            </div>
        </div>

        <div class="form-group">
            <label>Senha</label>
            <div class="input-wrap">
                <div class="input-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><rect x="3" y="11" width="18" height="11" rx="3"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/><circle cx="12" cy="16.5" r="1.5"/></svg>
                </div>
                <input type="password" id="login-pass" placeholder="••••••••" autocomplete="current-password">
                <button type="button" class="toggle-pass" onclick="togglePassword()" title="Mostrar/ocultar senha">
                    <svg id="eye-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
            </div>
        </div>

        <button class="btn-login" id="btn-login" onclick="doLogin()">
            <span id="btn-text">Entrar</span>
            <div class="btn-loading" id="btn-loading"></div>
            <div class="shine"></div>
        </button>

        <div class="login-footer">
            <p>Acesso restrito ao setor de TI</p>
            <div class="hint">
                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                Senha padrão: Assego@123
            </div>
        </div>
    </div>

    <div class="status-bar">
        <div class="status-dot"></div>
        <span>Sistema Online</span>
    </div>
</div>

<script>
var passInput = document.getElementById('login-pass');
var emailInput = document.getElementById('login-email');
var btnLogin = document.getElementById('btn-login');
var btnText = document.getElementById('btn-text');
var btnLoading = document.getElementById('btn-loading');
var errorBox = document.getElementById('login-error');
var errorText = document.getElementById('error-text');

emailInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') passInput.focus();
});
passInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') doLogin();
});

function togglePassword() {
    var type = passInput.type === 'password' ? 'text' : 'password';
    passInput.type = type;
    var icon = document.getElementById('eye-icon');
    if (type === 'text') {
        icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>';
    } else {
        icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
    }
}

function showError(msg) {
    errorText.textContent = msg;
    errorBox.classList.add('show');
    setTimeout(function() { errorBox.classList.remove('show'); }, 5000);
}

function setLoading(loading) {
    btnLogin.disabled = loading;
    btnText.style.display = loading ? 'none' : 'inline';
    btnLoading.style.display = loading ? 'block' : 'none';
}

function doLogin() {
    var email = emailInput.value.trim();
    var pass = passInput.value;

    if (!email || !pass) {
        showError('Preencha usuário/email e senha');
        return;
    }

    setLoading(true);
    errorBox.classList.remove('show');

    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'api.php?action=login', true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            setLoading(false);
            if (xhr.status === 0) {
                showError('Erro de conexão com o servidor');
                return;
            }
            try {
                var data = JSON.parse(xhr.responseText);
                if (data.success) {
                    btnText.textContent = 'Redirecionando...';
                    setLoading(false);
                    window.location.href = 'index.php';
                } else {
                    showError(data.error || 'Usuário/email ou senha incorretos');
                }
            } catch (e) {
                showError('Erro no servidor (HTTP ' + xhr.status + '). Verifique check.php para diagnóstico.');
            }
        }
    };
    xhr.send(JSON.stringify({email: email, password: pass}));
}
</script>
</body>
</html>
