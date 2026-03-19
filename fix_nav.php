<?php
// fix_nav.php - Corrige navbar com tabs
$c = file_get_contents(__DIR__.'/solicitacao.php');

// Encontrar a nav inteira e substituir
$navStart = strpos($c, '<nav ');
$navEnd = strpos($c, '<div style="height:64px"></div>');
if($navStart === false || $navEnd === false){ echo "❌ Nav não encontrada"; exit; }
$navEnd += strlen('<div style="height:64px"></div>');

$tab = '<?= $tab ?>';

$newNav = '<nav style="position:fixed;top:0;left:0;right:0;z-index:100;background:var(--bg2);border-bottom:1px solid var(--bdr);backdrop-filter:blur(12px)">
            <div style="max-width:680px;margin:0 auto;padding:12px 20px;display:flex;align-items:center;gap:12px">
                <img src="assets/img/logoassego.png" style="width:36px;height:36px;border-radius:10px;object-fit:contain">
                <div>
                    <div style="font-size:15px;font-weight:800;letter-spacing:-.3px">Gestão<span style="color:var(--acc)">Dev</span></div>
                    <div style="font-size:11px;color:var(--t3)">Nova Solicitação - Sistemas ASSEGO</div>
                </div>
                <div style="display:flex;gap:4px;margin-left:auto" id="nav-tabs">
                    <button onclick="switchTab(\'nova\')" id="nav-tab-nova" style="padding:8px 16px;border-radius:8px;border:none;font-family:inherit;font-size:12px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:5px">+ Nova</button>
                    <button onclick="switchTab(\'consultar\')" id="nav-tab-consultar" style="padding:8px 16px;border-radius:8px;border:none;font-family:inherit;font-size:12px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:5px">🔍 Consultar</button>
                </div>
            </div>
        </nav>
        <div style="height:64px"></div>';

$c = substr($c, 0, $navStart) . $newNav . substr($c, $navEnd);

// Remover tabs antigos do body (se existirem)
$oldTabsStart = strpos($c, '<div class="tabs">');
if($oldTabsStart !== false){
    // Encontrar o fechamento
    $depth = 0;
    $i = $oldTabsStart;
    $found = false;
    while($i < strlen($c)){
        if(substr($c, $i, 4) === '<div'){ $depth++; }
        if(substr($c, $i, 6) === '</div>'){ $depth--; if($depth === 0){ $i += 6; $found = true; break; } }
        $i++;
    }
    if($found){
        $c = substr($c, 0, $oldTabsStart) . substr($c, $i);
    }
}

// Atualizar switchTab para colorir os botões da navbar
$oldSwitch = "function switchTab(tab){";
$newSwitch = "function switchTab(tab){document.getElementById('nav-tab-nova').style.background=tab==='nova'?'var(--acc)':'var(--bg3)';document.getElementById('nav-tab-nova').style.color=tab==='nova'?'#fff':'var(--t3)';document.getElementById('nav-tab-consultar').style.background=tab==='consultar'?'var(--acc)':'var(--bg3)';document.getElementById('nav-tab-consultar').style.color=tab==='consultar'?'#fff':'var(--t3)';";
$c = str_replace($oldSwitch, $newSwitch, $c);

// Adicionar init script para colorir o tab ativo no load
$initScript = "\n        window.addEventListener('load',function(){switchTab('" . '<?= $tab ?>' . "')});\n    ";
$c = str_replace("</script>", $initScript . "</script>", $c);

file_put_contents(__DIR__.'/solicitacao.php', $c);

$check = shell_exec("php -l " . escapeshellarg(__DIR__.'/solicitacao.php') . " 2>&1");
echo "<pre>$check</pre>";
echo "<strong style='color:red'>DELETE ESTE ARQUIVO!</strong>";
