<?php
/**
 * Gestão Dev ASSEGO - Diagnóstico v5.0
 * REMOVA APÓS CONFIRMAR QUE FUNCIONA!
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=utf-8');

echo "<h2>Diagnóstico - Gestão Dev ASSEGO v5.0</h2><pre>";

$phpVer = phpversion();
echo "PHP: $phpVer " . (version_compare($phpVer, '7.0.0', '>=') ? "✓ OK" : "✗ PRECISA 7.0+") . "\n";

foreach (array('pdo', 'pdo_mysql', 'mbstring', 'json', 'session') as $ext)
    echo "Extensão $ext: " . (extension_loaded($ext) ? "✓" : "✗ FALTANDO") . "\n";

echo "\nArquivos:\n";
foreach (array('config.php','index.php','api.php','install.php','assets/css/app.css','assets/js/app.js') as $f)
    echo "  $f: " . (file_exists(__DIR__."/$f") ? "✓ (".filesize(__DIR__."/$f")." bytes)" : "✗ NÃO ENCONTRADO") . "\n";

echo "\nBanco de dados:\n";
try {
    require_once __DIR__.'/config.php';
    $db = getDB();
    echo "  Conexão: ✓ OK\n";
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "  Tabelas: ".count($tables)." encontradas\n";
    foreach ($tables as $t) echo "    ✓ $t\n";
    echo "  Usuários: ".$db->query("SELECT COUNT(*) FROM usuarios")->fetchColumn()."\n";

    echo "\n  Colunas críticas:\n";
    foreach (array(
        array('usuarios','avatar_file'), array('usuarios','last_login'),
        array('avisos','image_file'), array('avisos','show_board'),
        array('demandas','sprint_id'), array('demandas','needs_presidency_approval'),
        array('demandas','from_solicitation_id'), array('demandas','review_observation'),
        array('solicitacoes','converted_demand_id'), array('registro_atividades','ip_address')
    ) as $c) {
        try { $db->query("SELECT `{$c[1]}` FROM `{$c[0]}` LIMIT 1"); echo "    ✓ {$c[0]}.{$c[1]}\n"; }
        catch (Exception $e) { echo "    ✗ {$c[0]}.{$c[1]} - FALTANDO!\n"; }
    }
    try { $db->query("SELECT COUNT(*) FROM arquivos"); echo "    ✓ arquivos (BLOB storage)\n"; }
    catch (Exception $e) { echo "    ✗ arquivos - FALTANDO!\n"; }

} catch (Exception $e) {
    echo "  Conexão: ✗ ERRO - ".$e->getMessage()."\n";
    echo "  Verifique config.php\n";
}

echo "\nSessão:\n";
try { initSession(); $_SESSION['test']='ok'; echo "  ✓ OK\n"; unset($_SESSION['test']); }
catch (Exception $e) { echo "  ✗ ".$e->getMessage()."\n"; }

echo "\n==========================================";
echo "\n<a href='index.php'>→ Acessar sistema</a> | <a href='install.php'>→ Reinstalar</a>";
echo "\n\n<b style='color:red'>⚠ REMOVA check.php APÓS CONFIRMAR!</b></pre>";
