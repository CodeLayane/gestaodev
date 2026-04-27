#!/usr/bin/env php
<?php
/**
 * cron_relatorio_diario.php
 * 0 8 * * * /usr/local/bin/php /home/wwasse/public_html/gestaodev/cron_relatorio_diario.php >> /home/wwasse/logs/relatorio_diario.log 2>&1
 */
define('CRON_RUN', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/mailer.php';

$db = getDB();
echo "[" . date('Y-m-d H:i:s') . "] Iniciando relatorio diario...\n";

$destinatarios = $db->query("
    SELECT id, name, email, role FROM usuarios
    WHERE active=1 AND email_notifications=1
      AND (role LIKE '%admin%' OR role LIKE '%diretor%' OR role LIKE '%presidencia%')
    ORDER BY name
")->fetchAll(PDO::FETCH_ASSOC);

if (empty($destinatarios)) {
    echo "[" . date('H:i:s') . "] Nenhum destinatario com notificacoes ativas.\n";
    exit;
}

$ok=0; $fail=0;
foreach ($destinatarios as $user) {
    $result = sendDailyReport($db, $user['id'], true);
    if ($result) { echo "[" . date('H:i:s') . "] OK -> {$user['name']} <{$user['email']}>\n"; $ok++; }
    else          { echo "[" . date('H:i:s') . "] FAIL -> {$user['name']} <{$user['email']}>\n"; $fail++; }
}
echo "[" . date('H:i:s') . "] Fim. OK:{$ok} Falhas:{$fail}\n";
