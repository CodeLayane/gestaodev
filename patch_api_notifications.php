<?php
/**
 * PATCH api.php — 3 correções:
 * 1. Aliases de notificações faltantes
 * 2. Relatório muda de semanal para diário (período padrão = hoje)
 * 3. Novo endpoint: bottleneck_report (gargalos)
 *
 * ONDE APLICAR:
 * Encontre o array $actAliases no topo do api.php e SUBSTITUA pelo de baixo.
 * Depois adicione os novos endpoints no local indicado.
 */

// ─── SUBSTITUA o $actAliases existente por este ───────────────────────────────
$actAliases = [
    'demands'                 => 'demandas',
    'systems'                 => 'sistemas',
    'users'                   => 'usuarios',
    'meetings'                => 'reunioes',
    'notices'                 => 'avisos',
    'notice'                  => 'aviso',          // singular
    'notifications'           => 'notificacoes',
    'notifications_unread'    => 'notificacoes_unread',
    'notifications_recent'    => 'notificacoes_recent',
    'notifications_read_all'  => 'notificacoes_read_all',
    // ← ALIASES QUE ESTAVAM FALTANDO:
    'notification_read'       => 'notificacao_lida',
    'notification_delete'     => 'notificacao_delete',
    'solicitations'           => 'solicitacoes',
    'calendar_notes'          => 'anotacoes_calendario',
    'note_folders'            => 'pastas_notas',
    'notices_form'            => 'avisos_form',
    'notice_form'             => 'avisos_form',
    'reports_daily'           => 'relatorios_diarios',
    'daily_reports'           => 'relatorios_diarios',
    'dev_list'                => 'dev_list',
    'all_users_list'          => 'all_users_list',
];
if (isset($actAliases[$act])) $act = $actAliases[$act];
// ─────────────────────────────────────────────────────────────────────────────


// ─── SUBSTITUA os handlers de notificação (notification_read e read_all) ────
// Encontre: if($act==='notification_read'&&isset($_GET['id'])){
// Substitua pelo bloco abaixo:

if ($act === 'notification_read' || $act === 'notificacao_lida') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id) {
        $db->prepare("UPDATE notificacoes SET is_read=1 WHERE id=? AND user_id=?")
           ->execute([$id, $UID]);
    }
    jsonR(['success' => true]);
}

if ($act === 'notificacoes_read_all') {
    $db->prepare("UPDATE notificacoes SET is_read=1 WHERE user_id=?")
       ->execute([$UID]);
    jsonR(['success' => true]);
}

if ($act === 'notificacao_delete' || $act === 'notification_delete') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id) {
        $db->prepare("DELETE FROM notificacoes WHERE id=? AND user_id=?")
           ->execute([$id, $UID]);
    }
    jsonR(['success' => true]);
}
// ─────────────────────────────────────────────────────────────────────────────


// ─── NOVO ENDPOINT: bottleneck_report ────────────────────────────────────────
// Adicione ANTES do fechamento do try{} no api.php
if ($act === 'bottleneck_report') {
    if (!$IS_ADMIN && !$IS_DIR && !$IS_PRES) jsonR(['error' => 'Sem permissão'], 403);

    $dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
    $dateTo   = $_GET['date_to']   ?? date('Y-m-d');
    $df = $dateFrom . ' 00:00:00';
    $dt = $dateTo   . ' 23:59:59';

    // 1. Demandas paradas há mais tempo por status
    $stuck = $db->prepare("
        SELECT d.id, d.title, d.status, d.priority,
               s.name as system_name,
               DATEDIFF(NOW(), d.updated_at) as days_stuck,
               d.updated_at,
               GROUP_CONCAT(u.name SEPARATOR ', ') as devs
        FROM demandas d
        LEFT JOIN sistemas s ON d.system_id = s.id
        LEFT JOIN devs_demandas dd ON d.id = dd.demand_id
        LEFT JOIN usuarios u ON dd.user_id = u.id
        WHERE d.status NOT IN ('Concluída','Cancelada')
          AND d.updated_at < DATE_SUB(NOW(), INTERVAL 3 DAY)
        GROUP BY d.id
        ORDER BY days_stuck DESC
        LIMIT 20
    ");
    $stuck->execute();
    $stuckDemands = $stuck->fetchAll();

    // 2. Tempo médio por status (onde as demandas ficam presas)
    $statusTime = $db->prepare("
        SELECT
            status,
            COUNT(*) as total,
            ROUND(AVG(DATEDIFF(
                COALESCE(completed_at, NOW()),
                created_at
            )), 1) as avg_days,
            MAX(DATEDIFF(COALESCE(completed_at, NOW()), created_at)) as max_days
        FROM demandas
        WHERE created_at BETWEEN ? AND ?
        GROUP BY status
        ORDER BY avg_days DESC
    ");
    $statusTime->execute([$df, $dt]);
    $statusFlow = $statusTime->fetchAll();

    // 3. Sistemas com mais demandas urgentes abertas
    $sysBottleneck = $db->prepare("
        SELECT s.name, s.id,
            COUNT(d.id) as total_open,
            SUM(CASE WHEN d.priority='Urgente' THEN 1 ELSE 0 END) as urgentes,
            SUM(CASE WHEN d.priority='Alta' THEN 1 ELSE 0 END) as altas,
            ROUND(AVG(DATEDIFF(NOW(), d.created_at)), 1) as avg_age_days
        FROM sistemas s
        JOIN demandas d ON s.id = d.system_id
        WHERE d.status NOT IN ('Concluída','Cancelada')
        GROUP BY s.id
        HAVING total_open > 0
        ORDER BY urgentes DESC, total_open DESC
        LIMIT 10
    ");
    $sysBottleneck->execute();
    $sysBotData = $sysBottleneck->fetchAll();

    // 4. Devs sobrecarregados (mais de 3 demandas em andamento)
    $overloaded = $db->prepare("
        SELECT u.id, u.name, u.avatar_color,
            COUNT(dd.demand_id) as em_andamento,
            SUM(CASE WHEN d.priority='Urgente' THEN 1 ELSE 0 END) as urgentes,
            SUM(CASE WHEN d.deadline < CURDATE() THEN 1 ELSE 0 END) as atrasadas
        FROM usuarios u
        JOIN devs_demandas dd ON u.id = dd.user_id
        JOIN demandas d ON dd.demand_id = d.id
        WHERE d.status = 'Em Andamento' AND u.active = 1
        GROUP BY u.id
        ORDER BY em_andamento DESC
    ");
    $overloaded->execute();
    $overloadedDevs = $overloaded->fetchAll();

    // 5. Taxa de rejeição/retorno (demandas que voltaram de revisão)
    $rejectionRate = $db->prepare("
        SELECT
            u.name,
            u.avatar_color,
            COUNT(DISTINCT h.demand_id) as retornos,
            COUNT(DISTINCT d.id) as total_demandas
        FROM historico_demandas h
        JOIN demandas d ON h.demand_id = d.id
        JOIN devs_demandas dd ON d.id = dd.demand_id
        JOIN usuarios u ON dd.user_id = u.id
        WHERE h.action LIKE '%Revisão%' AND h.new_value = 'Em Andamento'
          AND h.created_at BETWEEN ? AND ?
        GROUP BY u.id
        ORDER BY retornos DESC
        LIMIT 10
    ");
    $rejectionRate->execute([$df, $dt]);
    $rejData = $rejectionRate->fetchAll();

    // 6. Demandas sem prazo definido (risco de indefinição)
    $noPrazo = $db->prepare("
        SELECT COUNT(*) as sem_prazo,
               (SELECT COUNT(*) FROM demandas WHERE status NOT IN ('Concluída','Cancelada')) as total_ativas
        FROM demandas
        WHERE deadline IS NULL AND status NOT IN ('Concluída','Cancelada')
    ");
    $noPrazo->execute();
    $prazoData = $noPrazo->fetch();

    jsonR([
        'stuck_demands'    => $stuckDemands,
        'status_flow'      => $statusFlow,
        'sys_bottleneck'   => $sysBotData,
        'overloaded_devs'  => $overloadedDevs,
        'rejection_rate'   => $rejData,
        'no_deadline'      => $prazoData,
        'date_from'        => $dateFrom,
        'date_to'          => $dateTo,
    ]);
}
// ─────────────────────────────────────────────────────────────────────────────


// ─── RELATÓRIO DIÁRIO: mude o período padrão ────────────────────────────────
// No bloco GET de relatorios_diarios, a query já existe.
// Apenas mude o filtro padrão no JS (veja patch_js_fixes.js)
// ─────────────────────────────────────────────────────────────────────────────
