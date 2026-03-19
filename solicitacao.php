<?php
/**
 * Solicitação Pública - ASSEGO / GestãoDev
 * Página externa para qualquer pessoa enviar solicitações
 * e consultar o status pelo número de protocolo.
 */
ob_start();
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/push-api.php';

$db = getDB();

// Auto-migration - compatível com MySQL 5.x
$addCol = function($table, $col, $def) use ($db) {
    try {
        $chk = $db->query("SHOW COLUMNS FROM {$table} LIKE '{$col}'");
        if ($chk->rowCount() == 0) {
            $db->exec("ALTER TABLE {$table} ADD COLUMN {$col} {$def}");
        }
    } catch (Exception $e) {}
};
$addCol('solicitacoes', 'requester_name', 'VARCHAR(150) DEFAULT NULL');
$addCol('solicitacoes', 'requester_department', 'VARCHAR(150) DEFAULT NULL');
$addCol('solicitacoes', 'requester_email', 'VARCHAR(200) DEFAULT NULL');
$addCol('solicitacoes', 'requester_phone', 'VARCHAR(30) DEFAULT NULL');
$addCol('solicitacoes', 'protocol_token', 'VARCHAR(64) DEFAULT NULL');
$addCol('solicitacoes', 'is_external', 'TINYINT(1) DEFAULT 0');

$sistemas = $db->query("SELECT id, name FROM sistemas WHERE status='Em uso' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$msg = '';
$msgType = '';
$protocolo = null;
$consulta = null;
$listaConsultas = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'nova') {
        $title = trim($_POST['title'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $name = trim($_POST['requester_name'] ?? '');
        $dept = trim($_POST['requester_department'] ?? '');
        $type = $_POST['type'] ?? 'Melhoria';
        $system = !empty($_POST['system_id']) ? (int)$_POST['system_id'] : null;
        $priority = $_POST['priority'] ?? 'Média';
        if (!$title || !$desc || !$name) {
            $msg = 'Preencha os campos obrigatórios: Nome, Título e Descrição.';
            $msgType = 'error';
        } else {
            $token = bin2hex(random_bytes(16));
            $stmt = $db->prepare("INSERT INTO solicitacoes (title, description, type, system_id, priority, status, requester_name, requester_department, protocol_token, is_external, created_at) VALUES (?, ?, ?, ?, ?, 'Pendente', ?, ?, ?, 1, NOW())");
            $sysText = trim($_POST['system_text'] ?? '');
            if (!$system && $sysText) $desc = "[Sistema: {$sysText}]\n" . $desc;
            $result = $stmt->execute([$title, $desc, $type, $system, $priority, $name, $dept, $token]);
            $newId = $db->lastInsertId();
            if (empty($newId)) {
                $lastRow = $db->query("SELECT id FROM solicitacoes ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
                $newId = $lastRow['id'] ?? null;
            }
            if (empty($newId)) $newId = $token;
            try {
                $admins = $db->query("SELECT id FROM usuarios WHERE FIND_IN_SET('admin', role) AND active=1")->fetchAll();
                foreach ($admins as $a) {
                    $db->prepare("INSERT INTO notificacoes (user_id, type, title, message, link, entity_type, entity_id) VALUES (?, 'solicitation', ?, ?, ?, 'solicitation', ?)")
                       ->execute([$a['id'], "Nova solicitação externa: {$title}", "De: {$name}" . ($dept ? " ({$dept})" : ""), "solicitation:{$newId}", $newId]);
                }
            } catch (Exception $e) {}
            try {
                foreach ($admins as $a) {
                    sendPushToUser($db, (int)$a['id'], ['title'=>'Nova Solicitação Externa','message'=>"De {$name}: {$title}",'url'=>'/index.php#solicitacoes']);
                }
            } catch (Exception $e2) {}
            header("Location: solicitacao.php?enviado={$newId}");
            exit;
        }
    }
    if ($_POST['action'] === 'consultar') {
        $search = trim($_POST['protocol'] ?? '');
        if (!$search) {
            $msg = 'Informe o número do protocolo.';
            $msgType = 'error';
        } else {
            $stmt = $db->prepare("SELECT s.*, si.name as system_name FROM solicitacoes s LEFT JOIN sistemas si ON s.system_id=si.id WHERE s.id=? OR s.protocol_token=? LIMIT 1");
            $stmt->execute([(int)$search, $search]);
            $consulta = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$consulta) {
                $msg = 'Protocolo não encontrado.';
                $msgType = 'error';
            }
        }
    }
}
if (isset($_GET['enviado'])) {
    $protocolo = $_GET['enviado'];
    $msg = "Solicitação enviada com sucesso!";
    $msgType = 'success';
}
if (isset($_GET['ver_id'])) {
    $stmtVer = $db->prepare("SELECT s.*, si.name as system_name FROM solicitacoes s LEFT JOIN sistemas si ON s.system_id=si.id WHERE s.id=?");
    $stmtVer->execute([(int)$_GET['ver_id']]);
    $consulta = $stmtVer->fetch(PDO::FETCH_ASSOC);
}


$tab = isset($_GET['consultar']) || $consulta || $listaConsultas ? 'consultar' : 'nova';
if ($protocolo) $tab = 'proto';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nova Solicitação - Sistemas ASSEGO</title>
   <link rel="icon" type="image/png" href="assets/img/favicon.png">
<link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        :root{--bg1:#0a0e1a;--bg2:#111827;--bg3:#1a2236;--bg4:#222d42;--acc:#3b82f6;--acc2:#2563eb;--ok:#10b981;--okb:#10b98120;--err:#ef4444;--errb:#ef444420;--warn:#f59e0b;--warnb:#f59e0b20;--inf:#6366f1;--t1:#f1f5f9;--t2:#94a3b8;--t3:#64748b;--bdr:#1e293b;--r:12px}
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Inter',sans-serif;background:var(--bg1);color:var(--t1);min-height:100vh;display:flex;flex-direction:column;align-items:center}
        .bg-pattern{position:fixed;inset:0;background:radial-gradient(ellipse at 20% 0%,#3b82f615 0%,transparent 50%),radial-gradient(ellipse at 80% 100%,#6366f110 0%,transparent 50%);pointer-events:none;z-index:0}
        .container{position:relative;z-index:1;width:100%;max-width:680px;padding:20px}
        .header{text-align:center;padding:40px 0 30px}
        .header-logo{display:inline-flex;align-items:center;gap:10px;margin-bottom:12px}
        .header h1{font-size:24px;font-weight:800;letter-spacing:-.5px}
        .header h1 span{color:var(--acc)}
        .header p{color:var(--t2);font-size:14px;margin-top:6px}
        .tabs{display:flex;gap:4px;background:var(--bg2);border-radius:10px;padding:4px;margin-bottom:24px;border:1px solid var(--bdr)}
        .tab{flex:1;padding:12px 16px;border-radius:8px;border:none;background:none;color:var(--t3);font-family:inherit;font-size:13px;font-weight:600;cursor:pointer;transition:all .2s;display:flex;align-items:center;justify-content:center;gap:6px}
        .tab:hover{color:var(--t2);background:var(--bg3)}
        .tab.active{background:var(--acc);color:#fff;box-shadow:0 2px 8px #3b82f640}
        .tab svg{width:16px;height:16px}
        .card{background:var(--bg2);border:1px solid var(--bdr);border-radius:var(--r);padding:28px;margin-bottom:20px}
        .sol-flow{background:linear-gradient(135deg,#1e3a5f,#1e40af,#1d4ed8);border-radius:14px;padding:20px;margin-bottom:24px;position:relative;overflow:hidden}
        .sol-flow::after{content:'';position:absolute;top:0;left:-100%;width:60%;height:100%;background:linear-gradient(90deg,transparent,rgba(255,255,255,.08),transparent);animation:shimmer 4s ease-in-out infinite}
        @keyframes shimmer{0%{left:-100%}50%{left:100%}100%{left:100%}}
        .sol-flow h3{font-size:16px;font-weight:700;margin-bottom:4px}
        .sol-flow>p{font-size:12px;opacity:.85;margin-bottom:16px}
        .sol-steps{display:flex;gap:8px}
        .sol-step{flex:1;text-align:center;background:rgba(255,255,255,.15);border-radius:10px;padding:12px 6px;backdrop-filter:blur(4px)}
        .sol-step .sn{width:28px;height:28px;border-radius:50%;background:rgba(255,255,255,.25);display:flex;align-items:center;justify-content:center;margin:0 auto 6px;font-weight:700;font-size:13px}
        .sol-step .st{font-weight:700;font-size:11px;margin-bottom:2px}
        .sol-step .sd{font-size:9px;opacity:.75}
        .fg{margin-bottom:18px}
        .fg label{display:block;font-size:12px;font-weight:600;color:var(--t2);margin-bottom:6px}
        .fg label .req{color:var(--err)}
        .fg input,.fg select,.fg textarea{width:100%;padding:12px 14px;background:var(--bg3);border:1px solid var(--bdr);border-radius:8px;color:var(--t1);font-family:inherit;font-size:14px;transition:border-color .2s,box-shadow .2s;outline:none}
        .fg input:focus,.fg select:focus,.fg textarea:focus{border-color:var(--acc);box-shadow:0 0 0 3px #3b82f620}
        .fg textarea{min-height:120px;resize:vertical;line-height:1.6}
        .fg select{cursor:pointer}
        .fg-row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
        .fg .hint{font-size:11px;color:var(--t3);margin-top:4px}
        .pri-tip{position:relative;display:inline-flex;cursor:help;margin-left:4px}
        .pri-tip svg{width:14px;height:14px;color:var(--t3);opacity:.6}
        .pri-tip:hover svg{opacity:1;color:var(--acc)}
        .pri-tip-box{display:none;position:absolute;bottom:calc(100% + 8px);left:50%;transform:translateX(-50%);background:var(--bg2);border:1px solid var(--bdr);border-radius:8px;padding:10px 14px;min-width:220px;z-index:999;box-shadow:0 8px 24px rgba(0,0,0,.4)}
        .pri-tip:hover .pri-tip-box{display:block}
        .pri-tip-row{display:flex;align-items:center;gap:8px;padding:4px 0;font-size:11px;color:var(--t2)}
        .pri-tip-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}
        .btn-row{display:flex;gap:10px;justify-content:flex-end;margin-top:24px;padding-top:20px;border-top:1px solid var(--bdr)}
        .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:12px 24px;border-radius:10px;border:none;font-family:inherit;font-size:14px;font-weight:700;cursor:pointer;transition:all .2s}
        .btn-primary{background:var(--acc);color:#fff}
        .btn-primary:hover{background:var(--acc2)}
        .btn-ghost{background:var(--bg3);color:var(--t2);border:1px solid var(--bdr)}
        .btn-ghost:hover{color:var(--t1);border-color:var(--t3)}
        .btn-full{width:100%}
        .alert{padding:14px 18px;border-radius:10px;font-size:13px;font-weight:500;margin-bottom:20px;display:flex;align-items:center;gap:10px}
        .alert-ok{background:var(--okb);color:var(--ok);border:1px solid #10b98130}
        .alert-err{background:var(--errb);color:var(--err);border:1px solid #ef444430}
        .proto-card{text-align:center;padding:36px}
        .proto-card .icon{width:64px;height:64px;background:var(--okb);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:28px}
        .proto-card h3{font-size:18px;margin-bottom:8px}
        .proto-num{font-family:'JetBrains Mono','Fira Code',monospace;font-size:32px;font-weight:800;color:var(--acc);background:var(--bg3);padding:12px 24px;border-radius:10px;display:inline-block;margin:12px 0;letter-spacing:2px}
        .proto-card p{color:var(--t2);font-size:13px}
        .status-card{padding:0;overflow:hidden}
        .status-header{padding:20px 24px;border-bottom:1px solid var(--bdr);display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
        .status-header h3{font-size:16px}
        .status-badge{padding:5px 14px;border-radius:20px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;white-space:nowrap}
        .st-pendente{background:var(--warnb);color:var(--warn)}
        .st-aprovada{background:var(--okb);color:var(--ok)}
        .st-rejeitada{background:var(--errb);color:var(--err)}
        .st-convertida{background:#3b82f620;color:var(--acc)}
        .status-body{padding:24px}
        .status-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
        .status-item label{font-size:10px;color:var(--t3);text-transform:uppercase;font-weight:700;letter-spacing:.5px;display:block;margin-bottom:4px}
        .status-item span{font-size:14px;color:var(--t1)}
        .status-timeline{margin-top:20px;padding-top:20px;border-top:1px solid var(--bdr)}
        .tl-title{font-size:10px;color:var(--t3);text-transform:uppercase;font-weight:700;letter-spacing:.5px;margin-bottom:12px}
        .tl-step{display:flex;align-items:center;gap:12px;padding:10px 0}
        .tl-dot{width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;flex-shrink:0}
        .tl-dot.done{background:var(--okb);color:var(--ok)}
        .tl-dot.current{background:var(--acc);color:#fff;animation:pulse 2s infinite}
        .tl-dot.pending{background:var(--bg4);color:var(--t3)}
        .tl-step-text{font-size:13px}
        .tl-step-text small{display:block;color:var(--t3);font-size:11px;margin-top:2px}
        @keyframes pulse{0%,100%{box-shadow:0 0 0 0 #3b82f640}50%{box-shadow:0 0 0 8px #3b82f600}}
        .review-box{margin-top:16px;padding:14px;background:var(--bg3);border-radius:8px;border-left:3px solid var(--warn)}
        .review-box label{font-size:10px;color:var(--t3);text-transform:uppercase;font-weight:700;display:block;margin-bottom:4px}
        .review-box p{font-size:13px;color:var(--t2);line-height:1.5}
        .footer{text-align:center;padding:30px 0;color:var(--t3);font-size:12px}
        .footer a{color:var(--acc);text-decoration:none}
        .footer a:hover{text-decoration:underline}
        @media(max-width:600px){.container{padding:12px}.card{padding:20px}.fg-row{grid-template-columns:1fr}.status-grid{grid-template-columns:1fr}.proto-num{font-size:24px}.header h1{font-size:20px}.sol-steps{flex-wrap:wrap}.sol-step{min-width:calc(33% - 6px)}.btn-row{flex-direction:column}.btn-row .btn{width:100%}}
    </style>
</head>
<body>
    <div class="bg-pattern"></div>
    <div class="container">
        <nav style="position:fixed;top:0;left:0;right:0;z-index:100;background:var(--bg2);border-bottom:1px solid var(--bdr);backdrop-filter:blur(12px)">
            <div style="max-width:680px;margin:0 auto;padding:12px 20px;display:flex;align-items:center;gap:12px">
                <img src="assets/img/logoassego.png" style="width:36px;height:36px;border-radius:10px;object-fit:contain">
                <div>
                    <div style="font-size:15px;font-weight:800;letter-spacing:-.3px">Gestão<span style="color:var(--acc)">Dev</span></div>
                    <div style="font-size:11px;color:var(--t3)">Nova Solicitação - Sistemas ASSEGO</div>
                </div>
                <div style="display:flex;gap:4px;margin-left:auto" id="nav-tabs">
                    <button onclick="switchTab('nova')" id="nav-tab-nova" style="padding:8px 16px;border-radius:8px;border:none;font-family:inherit;font-size:12px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:5px"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> Nova</button>
                    <button onclick="switchTab('consultar')" id="nav-tab-consultar" style="padding:8px 16px;border-radius:8px;border:none;font-family:inherit;font-size:12px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:5px"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg> Consultar</button>
                </div>
            </div>
        </nav>
        <div style="height:64px"></div>

        

        <?php if ($msg && !$protocolo): ?>
            <div class="alert alert-<?= $msgType === 'success' ? 'ok' : 'err' ?>"><?= $msgType === 'success' ? '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>' : '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v4"/><path d="M12 17h.01"/><circle cx="12" cy="12" r="10"/></svg>' ?> <?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <?php if ($protocolo): ?>
        <div id="proto-section" class="card proto-card">
            <div class="icon"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg></div>
            <h3>Solicitação Enviada!</h3>
            <p>Guarde seu número de protocolo para acompanhar o status:</p>
            <div class="proto-num"><?= $protocolo ?></div>
            <p style="margin-top:12px">Você poderá consultar o andamento a qualquer momento.</p>
            <div style="margin-top:20px">
                <button class="btn btn-ghost" onclick="location.href='solicitacao.php?ver_id=<?= $protocolo ?>'" style="display:inline-flex"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg> Consultar Status</button>
            </div>
        </div>
        <?php endif; ?>

        <!-- Nova Solicitação -->
        <div id="tab-nova" style="display:<?= $tab === 'nova' && !$protocolo ? 'block' : 'none' ?>">
            <div class="card">
                <div class="sol-flow">
                    <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1.5" opacity=".8"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/></svg>
                        <div>
                            <h3>Solicitações passam por aprovação</h3>
                            <p style="margin:0">Após enviar, um desenvolvedor irá analisar e aprovar sua solicitação antes da execução.</p>
                        </div>
                    </div>
                    <div class="sol-steps">
                        <div class="sol-step"><div class="sn" style="background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2)"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div><div class="st">Você solicita</div><div class="sd">Descreve a alteração</div></div>
                        <div class="sol-step"><div class="sn" style="background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2)"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg></div><div class="st">Presidência analisa</div><div class="sd">Avalia viabilidade</div></div>
                        <div class="sol-step"><div class="sn" style="background:#10b981"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg></div><div class="st">Execução</div><div class="sd">Alteração implementada</div></div>
                    </div>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="nova">
                    <div class="fg-row">
                        <div class="fg">
                            <label>Solicitante <span class="req">*</span></label>
                            <input type="text" name="requester_name" placeholder="Seu nome completo" required>
                        </div>
                        <div class="fg">
                            <label>Departamento / Setor</label>
                            <input type="text" name="requester_department" placeholder="Ex: Financeiro, RH, Jurídico...">
                        </div>
                    </div>
                    <div class="fg">
                        <label>Título <span class="req">*</span></label>
                        <input type="text" name="title" placeholder="Ex: Adicionar campo no relatório" required>
                    </div>
                    <div class="fg">
                        <label>Descrição detalhada <span class="req">*</span></label>
                        <textarea name="description" placeholder="• Qual sistema/módulo?&#10;• O que precisa ser alterado?&#10;• Qual o resultado esperado?" required></textarea>
                    </div>
                    <div class="fg-row">
                        <div class="fg">
                            <label>Tipo</label>
                            <select name="type">
                                <option value="Melhoria">Melhoria</option>
                                <option value="Correção">Correção</option>
                                <option value="Nova Funcionalidade">Nova Funcionalidade</option>
                                <option value="Sugestão de Usuário">Sugestão de Usuário</option>
                            </select>
                        </div>
                        <div class="fg">
                            <label>Sistema</label>
                            <input type="text" id="sys-search" placeholder="Digite para buscar..." autocomplete="off" oninput="filterSys(this.value)" onfocus="this.nextElementSibling.style.display='block'" style="position:relative">
                            <div id="sys-dd" style="display:none;position:relative;z-index:50;background:var(--bg3);border:1px solid var(--bdr);border-radius:8px;max-height:180px;overflow-y:auto;margin-top:4px;width:100%">
                                <?php foreach ($sistemas as $s): ?>
                                <div class="sys-opt" data-id="<?= $s['id'] ?>" data-name="<?= htmlspecialchars($s['name']) ?>" onclick="pickSys(this)" style="padding:10px 14px;cursor:pointer;font-size:13px;transition:background .15s" onmouseover="this.style.background='var(--bg4)'" onmouseout="this.style.background='none'"><?= htmlspecialchars($s['name']) ?></div>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" name="system_id" id="sys-id-val">
                            <input type="hidden" name="system_text" id="sys-text-val">
                        </div>
                    </div>
                    <div class="fg">
                        <label>Prioridade</label>
                        <select name="priority">
                            <option value="Baixa">● Baixa</option>
                            <option value="Média" selected>● Média</option>
                            <option value="Alta">● Alta</option>
                            <option value="Urgente">● Urgente</option>
                        </select>
                    </div>
                    <div class="btn-row">
                        <button type="button" class="btn btn-ghost" onclick="switchTab('consultar')">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Enviar Solicitação</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Consultar Status -->
        <div id="tab-consultar" style="display:<?= ($tab === 'consultar' || $consulta || $listaConsultas) && !$protocolo ? 'block' : 'none' ?>">
            <?php if (!$consulta && !$listaConsultas): ?>
            <div class="card">
                <form method="POST">
                    <input type="hidden" name="action" value="consultar">
                    <div class="fg">
                        <label>Número do Protocolo</label>
                        <input type="text" name="protocol" placeholder="Ex: 79" value="<?= htmlspecialchars($_POST['protocol'] ?? '') ?>" style="font-family:monospace;font-size:18px;text-align:center;letter-spacing:2px">
                        <div class="hint">Digite o número do protocolo recebido</div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-full" style="margin-top:12px"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg> Consultar</button>
                </form>
            </div>
            <?php endif; ?>

            <?php if ($listaConsultas): ?>
            <div class="card">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
                    <h3 style="font-size:16px">Solicitações de <span style="color:var(--acc)"><?= htmlspecialchars($_POST['protocol'] ?? $_GET['busca'] ?? '') ?></span></h3>
                    <span style="font-size:12px;color:var(--t3)"><?= count($listaConsultas) ?> encontrada(s)</span>
                </div>
                <div style="display:flex;flex-direction:column;gap:8px">
                <?php foreach ($listaConsultas as $item):
                    $stColors = ['Pendente'=>'#f59e0b','Aprovada'=>'#10b981','Rejeitada'=>'#ef4444','Convertida'=>'#3b82f6'];
                    $stColor = $stColors[$item['status']] ?? '#94a3b8';
                ?>
                    <a href="solicitacao.php?ver_id=<?= $item['id'] ?>&busca=<?= urlencode($_POST['protocol'] ?? $_GET['busca'] ?? '') ?>" style="display:flex;align-items:center;gap:14px;padding:14px 16px;background:var(--bg3);border:1px solid var(--bdr);border-radius:10px;text-decoration:none;color:inherit;transition:border-color .2s,background .2s" onmouseover="this.style.borderColor='var(--acc)';this.style.background='var(--bg4)'" onmouseout="this.style.borderColor='var(--bdr)';this.style.background='var(--bg3)'">
                        <div style="width:40px;height:40px;border-radius:10px;background:<?= $stColor ?>15;color:<?= $stColor ?>;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:12px;flex-shrink:0">#<?= str_pad($item['id'],2,'0',STR_PAD_LEFT) ?></div>
                        <div style="flex:1;min-width:0">
                            <div style="font-weight:600;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($item['title']) ?></div>
                            <div style="font-size:11px;color:var(--t3);margin-top:2px;display:flex;gap:8px;flex-wrap:wrap">
                                <span><?= htmlspecialchars($item['type'] ?? 'Melhoria') ?></span>
                                <span>&middot;</span>
                                <span><?= htmlspecialchars($item['system_name'] ?? 'Geral') ?></span>
                                <span>&middot;</span>
                                <span><?= date('d/m/Y', strtotime($item['created_at'])) ?></span>
                            </div>
                        </div>
                        <div style="flex-shrink:0">
                            <span style="padding:4px 10px;border-radius:6px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;background:<?= $stColor ?>20;color:<?= $stColor ?>"><?= $item['status'] ?></span>
                        </div>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--t3)" stroke-width="2" style="flex-shrink:0"><polyline points="9 18 15 12 9 6"/></svg>
                    </a>
                <?php endforeach; ?>
                </div>
                <div style="margin-top:16px;text-align:center">
                    <button class="btn btn-ghost" onclick="location.href='solicitacao.php?consultar'" style="display:inline-flex">&larr; Nova consulta</button>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($consulta): ?>
            <?php
                $st = $consulta['status'];
                $stClass = ['Pendente'=>'st-pendente','Aprovada'=>'st-aprovada','Rejeitada'=>'st-rejeitada','Convertida'=>'st-convertida'][$st] ?? 'st-pendente';
                $steps = [
                    ['label'=>'Solicitação Enviada','desc'=>'Recebida pela equipe','done'=>true],
                    ['label'=>'Em Análise','desc'=>'Demanda em Avaliação','done'=>in_array($st,['Aprovada','Rejeitada','Convertida']),'current'=>$st==='Pendente'],
                    ['label'=>'Decisão','desc'=>$st==='Aprovada'?'Aprovada!':($st==='Rejeitada'?'Não aprovada':($st==='Convertida'?'Aprovada e convertida':'Aguardando')),'done'=>in_array($st,['Aprovada','Rejeitada','Convertida'])],
                ];
                if ($st === 'Convertida') {
                    // Verificar se a demanda foi concluída
                    $concluida = false;
                    $concluidaAt = '';
                    if ($consulta['converted_demand_id']) {
                        try {
                            $dChk = $db->prepare('SELECT status, completed_at FROM demandas WHERE id=?');
                            $dChk->execute([$consulta['converted_demand_id']]);
                            $dRow = $dChk->fetch(PDO::FETCH_ASSOC);
                            if ($dRow && $dRow['status'] === 'Concluída') {
                                $concluida = true;
                                $concluidaAt = $dRow['completed_at'] ? date('d/m/Y', strtotime($dRow['completed_at'])) : '';
                            }
                        } catch (Exception $e) {}
                    }
                    $emExecucao = false;
                    if ($consulta['converted_demand_id']) {
                        try {
                            $dExec = $db->prepare('SELECT started_at FROM demandas WHERE id=?');
                            $dExec->execute([$consulta['converted_demand_id']]);
                            $dExecRow = $dExec->fetch(PDO::FETCH_ASSOC);
                            if ($dExecRow && $dExecRow['started_at']) $emExecucao = true;
                        } catch (Exception $e) {}
                    }
                    $steps[] = ['label'=>'Em Execução','desc'=>$emExecucao?'Dev trabalhando':'Aguardando início','done'=>$emExecucao||$concluida,'current'=>!$emExecucao&&!$concluida];
                    $steps[] = ['label'=>'Concluída','desc'=>$concluida ? 'Entregue' . ($concluidaAt ? ' em ' . $concluidaAt : '') : 'Aguardando finalização','done'=>$concluida,'current'=>!$concluida];
                }
            ?>
            <div class="card status-card">
                <div class="status-header">
                    <h3>#<?= str_pad($consulta['id'], 4, '0', STR_PAD_LEFT) ?> — <?= htmlspecialchars($consulta['title']) ?></h3>
                    <span class="status-badge <?= $stClass ?>"><?= $st === "Convertida" ? "Em Desenvolvimento" : $st ?></span>
                </div>
                <div class="status-body">
                    <div class="status-grid">
                        <div class="status-item"><label>Tipo</label><span><?= htmlspecialchars($consulta['type'] ?? 'Melhoria') ?></span></div>
                        <div class="status-item"><label>Sistema</label><span><?= htmlspecialchars($consulta['system_name'] ?? 'Geral') ?></span></div>
                        <div class="status-item"><label>Prioridade</label><span><?= $consulta['priority'] ?></span></div>
                        <div class="status-item"><label>Data de Envio</label><span><?= date('d/m/Y H:i', strtotime($consulta['created_at'])) ?></span></div>
                    </div>
                    <?php if ($consulta['review_notes']): ?>
                    <div class="review-box">
                        <label>Observação da Equipe</label>
                        <p><?= nl2br(htmlspecialchars($consulta['review_notes'])) ?></p>
                    </div>
                    <?php endif; ?>
                    <div class="status-timeline">
                        <div class="tl-title">Acompanhamento</div>
                        <?php foreach ($steps as $s): ?>
                        <div class="tl-step">
                            <div class="tl-dot <?= $s['done'] ? 'done' : (($s['current']??false) ? 'current' : 'pending') ?>">
                                <?= $s['done'] ? '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>' : (($s['current']??false) ? '<svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>' : '<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="6"/></svg>') ?>
                            </div>
                            <div class="tl-step-text"><?= $s['label'] ?><small><?= $s['desc'] ?></small></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div style="margin-top:16px;text-align:center">
                <button class="btn btn-ghost" onclick="location.href='solicitacao.php?<?= !empty($_GET['busca']) ? 'busca='.urlencode($_GET['busca']).'&consultar' : 'consultar' ?>'" style="display:inline-flex"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg> Voltar</button>
            </div>
            <?php endif; ?>
        </div>

        <div class="footer"><p>ASSEGO — Associação dos Subtenentes e Sargentos do Estado de Goiás · Equipe de <a href="index.php" style="color:inherit;text-decoration:none">Tecnologia</a></p></div>
    </div>
    <script>
        function filterSys(v){
            document.getElementById('sys-text-val').value=v;
            const dd=document.getElementById('sys-dd');
            dd.style.display='block';
            dd.querySelectorAll('.sys-opt').forEach(o=>{
                o.style.display=o.dataset.name.toLowerCase().includes(v.toLowerCase())?'block':'none';
            });
        }
        function pickSys(el){
            document.getElementById('sys-search').value=el.dataset.name;
            document.getElementById('sys-id-val').value=el.dataset.id;
            document.getElementById('sys-dd').style.display='none';
        }
        document.addEventListener('click',function(e){
            const dd=document.getElementById('sys-dd');
            const inp=document.getElementById('sys-search');
            if(dd && inp && !dd.contains(e.target) && e.target!==inp) dd.style.display='none';
        });
        function switchTab(tab){
            var ps=document.getElementById('proto-section');
            if(tab==='proto'){
                if(ps)ps.style.display='';
                document.getElementById('tab-nova').style.display='none';
                document.getElementById('tab-consultar').style.display='none';
                document.getElementById('nav-tab-nova').style.background='var(--bg3)';document.getElementById('nav-tab-nova').style.color='var(--t3)';
                document.getElementById('nav-tab-consultar').style.background='var(--bg3)';document.getElementById('nav-tab-consultar').style.color='var(--t3)';
                return;
            }
            if(ps)ps.style.display='none';document.getElementById('nav-tab-nova').style.background=tab==='nova'?'var(--acc)':'var(--bg3)';document.getElementById('nav-tab-nova').style.color=tab==='nova'?'#fff':'var(--t3)';document.getElementById('nav-tab-consultar').style.background=tab==='consultar'?'var(--acc)':'var(--bg3)';document.getElementById('nav-tab-consultar').style.color=tab==='consultar'?'#fff':'var(--t3)';
            document.getElementById('tab-nova').style.display=tab==='nova'?'block':'none';
            document.getElementById('tab-consultar').style.display=tab==='consultar'?'block':'none';
            document.querySelectorAll('.tab').forEach((t,i)=>{t.classList.toggle('active',(i===0&&tab==='nova')||(i===1&&tab==='consultar'))});
            const proto=document.querySelector('.proto-card');
            if(proto&&tab==='nova')proto.closest('.card').style.display='none';
        }
    
        window.addEventListener('load',function(){switchTab('<?= $tab ?>')});
    </script>
</body>
</html>