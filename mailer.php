<?php
require_once __DIR__.'/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ─── Credenciais Gmail ────────────────────────────────────────────────────────
define('SMTP_USER',      'tecnico.assego@gmail.com');
define('SMTP_PASS',      'gmyfxpsobpehsgiv');
define('SMTP_FROM',      'tecnico.assego@gmail.com');
define('SMTP_FROM_NAME', 'GestãoDev ASSEGO');
// ─────────────────────────────────────────────────────────────────────────────

function sendEmailNotification($db, $userId, $subject, $body, $type = 'general') {
    try {
        $st = $db->prepare("SELECT email,name,email_notifications,email_prefs FROM usuarios WHERE id=? AND active=1");
        $st->execute([$userId]);
        $user = $st->fetch();
        if (!$user || !$user['email_notifications']) return false;

        $prefs    = json_decode($user['email_prefs'] ?? '{}', true) ?: [];
        $defaults = ['demandas'=>1,'solicitacoes'=>1,'automacoes'=>1,'reunioes'=>1,'avisos'=>1,'comentarios'=>1,'aprovacoes'=>1,'relatorio'=>1];
        $prefs    = array_merge($defaults, $prefs);

        $typeMap = [
            'demand'               => 'demandas',
            'demand_assigned'      => 'demandas',
            'demand_status'        => 'demandas',
            'demand_completed'     => 'demandas',
            'demand_review'        => 'demandas',
            'solicitation'         => 'solicitacoes',
            'solicitation_approved'=> 'solicitacoes',
            'auto'                 => 'automacoes',
            'auto_approve'         => 'automacoes',
            'auto_complete'        => 'automacoes',
            'meeting'              => 'reunioes',
            'notice'               => 'avisos',
            'comment'              => 'comentarios',
            'mention'              => 'comentarios',
            'presidency'           => 'aprovacoes',
            'approval'             => 'aprovacoes',
            'report'               => 'relatorio',
            'test'                 => 'general',
            'general'              => 'general',
        ];

        $prefKey = $typeMap[$type] ?? 'general';
        if ($prefKey !== 'general' && empty($prefs[$prefKey])) return false;

        return _doSendEmail($user['email'], $user['name'], $subject, $body);
    } catch (\Throwable $e) {
        error_log('[Mailer] sendEmailNotification: ' . $e->getMessage());
        return false;
    }
}

function _doSendEmail($to, $name, $subject, $body) {
    $siteUrl = 'https://gestaodev.assego.com.br/gestaodev/';
    $logo1   = $siteUrl . 'assets/img/logo.png';
    $logo2   = $siteUrl . 'assets/img/logoassego.png';

    $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>'
          . '<body style="margin:0;padding:0;background:#f0f4f8;font-family:\'Segoe UI\',Arial,Helvetica,sans-serif">'
          . '<div style="max-width:600px;margin:32px auto;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,82,204,0.10)">'

          // HEADER
          . '<div style="background:linear-gradient(135deg,#0052cc 0%,#0073e6 60%,#2196f3 100%);padding:32px 28px 24px;text-align:center">'
          . '<div style="display:inline-flex;align-items:center;gap:14px;justify-content:center">'
          . '<img src="'.$logo1.'" alt="GestãoDev" style="height:52px;display:inline-block;vertical-align:middle" />'
          . '<img src="'.$logo2.'" alt="ASSEGO" style="height:52px;display:inline-block;vertical-align:middle" />'
          . '</div>'
          . '<div style="margin-top:14px;font-size:18px;font-weight:700;color:#ffffff;letter-spacing:0.5px">Gestão Dev ASSEGO</div>'
          . '<div style="margin-top:4px;font-size:11px;color:rgba(255,255,255,0.75);letter-spacing:1.5px;text-transform:uppercase;font-weight:500">Sistema de Gestão de Desenvolvimento</div>'
          . '</div>'

          // BODY
          . '<div style="padding:36px 32px 28px">'
          . '<p style="margin:0 0 20px;color:#374151;font-size:15px">Olá, <strong style="color:#0052cc">' . htmlspecialchars($name) . '</strong></p>'
          . '<div style="background:#f8faff;border:1px solid #dbeafe;border-left:4px solid #0073e6;border-radius:10px;padding:22px 24px;margin:0 0 24px;color:#1e293b;font-size:14px;line-height:1.7">' . $body . '</div>'
          . '<div style="text-align:center;margin:28px 0 8px">'
          . '<a href="'.$siteUrl.'" style="display:inline-block;padding:14px 36px;background:linear-gradient(135deg,#0052cc,#0073e6);color:#ffffff;text-decoration:none;border-radius:10px;font-size:14px;font-weight:700;letter-spacing:0.3px;box-shadow:0 4px 12px rgba(0,82,204,0.3)">Acessar GestãoDev</a>'
          . '</div></div>'

          // FOOTER
          . '<div style="background:#f8faff;padding:18px 32px;border-top:1px solid #dbeafe;text-align:center">'
          . '<p style="margin:0 0 4px;font-size:11px;color:#64748b;font-weight:600">ASSEGO — Associação dos Subtenentes e Sargentos do Estado de Goiás</p>'
          . '<p style="margin:0;font-size:10px;color:#94a3b8">Notificação automática do GestãoDev &nbsp;·&nbsp; Não responda este email</p>'
          . '</div></div></body></html>';

    try {
        $mail = new PHPMailer(true);

        // ── CORREÇÃO: trocado isSendmail() por isSMTP() ──────────────────────
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL porta 465
        $mail->Port       = 465;
        // ─────────────────────────────────────────────────────────────────────

        $mail->CharSet = 'UTF-8';
        $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
        $mail->addReplyTo(SMTP_FROM, SMTP_FROM_NAME);
        $mail->addAddress($to, $name);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $html;
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('[Mailer] Falha ao enviar para ' . $to . ': ' . $e->getMessage());
        return false;
    }
}

// ─── Relatório DIÁRIO (substituiu o semanal) ─────────────────────────────────
function sendDailyReport($db, $userId, $force = false) {
    try {
        $user = $db->prepare("SELECT email,name,email_notifications,email_prefs,role FROM usuarios WHERE id=? AND active=1");
        $user->execute([$userId]);
        $user = $user->fetch();
        if (!$user) return false;
        if (!$force && !$user['email_notifications']) return false;

        $prefs = json_decode($user['email_prefs'] ?? '{}', true) ?: [];
        if (!$force && isset($prefs['relatorio']) && !$prefs['relatorio']) return false;

        $isAdmin = strpos($user['role'] ?? '', 'admin')   !== false
                || strpos($user['role'] ?? '', 'diretor')  !== false
                || strpos($user['role'] ?? '', 'presidencia') !== false;

        $hoje  = date('Y-m-d');
        $ontem = date('Y-m-d', strtotime('-1 day'));

        // KPIs
        $total      = $db->query("SELECT COUNT(*) FROM demandas")->fetchColumn();
        $concluidas = $db->query("SELECT COUNT(*) FROM demandas WHERE status='Concluída'")->fetchColumn();
        $andamento  = $db->query("SELECT COUNT(*) FROM demandas WHERE status='Em Andamento'")->fetchColumn();
        $revisao    = $db->query("SELECT COUNT(*) FROM demandas WHERE status='Em Revisão'")->fetchColumn();
        $aguardando = $db->query("SELECT COUNT(*) FROM demandas WHERE status='Aguardando Aceite'")->fetchColumn();
        $atrasadas  = $db->query("SELECT COUNT(*) FROM demandas WHERE deadline<CURDATE() AND status NOT IN('Concluída','Cancelada')")->fetchColumn();
        $venceHoje  = $db->query("SELECT COUNT(*) FROM demandas WHERE deadline='$hoje' AND status NOT IN('Concluída','Cancelada')")->fetchColumn();
        $conclOntem = $db->query("SELECT COUNT(*) FROM demandas WHERE status='Concluída' AND DATE(completed_at)='$ontem'")->fetchColumn();

        $minhasSt = $db->prepare("SELECT COUNT(*) FROM devs_demandas WHERE user_id=? AND demand_id IN(SELECT id FROM demandas WHERE status NOT IN('Concluída','Cancelada'))");
        $minhasSt->execute([$userId]);
        $minhasCount = $minhasSt->fetchColumn();

        $solPend = $isAdmin
            ? $db->query("SELECT COUNT(*) FROM solicitacoes WHERE status='Pendente'")->fetchColumn()
            : 0;

        // Devs sem relatório ontem (só admin vê)
        $semRelatorio = '';
        if ($isAdmin) {
            $nr = $db->prepare("SELECT u.name FROM usuarios u WHERE u.role LIKE '%dev%' AND u.active=1 AND u.id NOT IN (SELECT user_id FROM relatorios_diarios WHERE report_date=?)");
            $nr->execute([$ontem]);
            $nrList = $nr->fetchAll(PDO::FETCH_COLUMN);
            if (!empty($nrList)) {
                $semRelatorio = '<tr><td style="padding:8px 0;color:#94a3b8;border-bottom:1px solid #1e293b">Sem relatório ontem</td>'
                    . '<td style="text-align:right;font-weight:700;color:#ef4444;border-bottom:1px solid #1e293b">'
                    . htmlspecialchars(implode(', ', $nrList))
                    . '</td></tr>';
            }
        }

        // Cards estilo
        $cs = 'display:inline-block;width:28%;min-width:90px;background:#f0f4ff;border:1px solid #dbeafe;border-radius:10px;padding:14px 8px;text-align:center;margin:4px;vertical-align:top';

        $body  = '<h2 style="color:#0052cc;margin:0 0 6px;font-size:18px;font-weight:800">📊 Relatório Diário</h2>';
        $body .= '<p style="color:#64748b;margin:0 0 20px;font-size:12px">' . date('d/m/Y') . ' — Dados referência: ' . date('d/m/Y', strtotime($ontem)) . '</p>';

        $body .= '<div style="text-align:center;margin-bottom:20px">';
        $cards = [
            ['v' => $conclOntem,          'l' => 'Concluídas ontem', 'c' => '#10b981'],
            ['v' => $andamento + $revisao,'l' => 'Em Execução',      'c' => '#3b82f6'],
            ['v' => $aguardando,          'l' => 'Aguardando',        'c' => '#d4a017'],
            ['v' => $atrasadas,           'l' => 'Atrasadas',         'c' => '#ef4444'],
            ['v' => $venceHoje,           'l' => 'Vencem Hoje',       'c' => '#f59e0b'],
            ['v' => $minhasCount,         'l' => 'Suas Ativas',       'c' => '#8b5cf6'],
        ];
        foreach ($cards as $cd) {
            $body .= "<div style=\"{$cs}\"><div style=\"font-size:28px;font-weight:800;color:{$cd['c']};line-height:1.2\">{$cd['v']}</div>"
                  .  "<div style=\"font-size:9px;color:#64748b;margin-top:6px;text-transform:uppercase;letter-spacing:.8px;font-weight:600\">{$cd['l']}</div></div>";
        }
        $body .= '</div>';

        $body .= '<div style="background:#f8faff;border:1px solid #dbeafe;border-radius:10px;padding:16px;margin:16px 0">'
              .  '<table style="width:100%;border-collapse:collapse;font-size:13px">';
        $body .= "<tr><td style=\"padding:8px 0;color:#374151;border-bottom:1px solid #dbeafe\">Total de demandas</td><td style=\"text-align:right;font-weight:700;color:#0052cc;border-bottom:1px solid #dbeafe\">{$total}</td></tr>";
        $body .= "<tr><td style=\"padding:8px 0;color:#374151;border-bottom:1px solid #dbeafe\">Concluídas (total)</td><td style=\"text-align:right;font-weight:700;color:#10b981;border-bottom:1px solid #dbeafe\">{$concluidas}</td></tr>";
        if ($isAdmin && $solPend > 0) {
            $body .= "<tr><td style=\"padding:8px 0;color:#374151;border-bottom:1px solid #dbeafe\">Solicitações pendentes</td><td style=\"text-align:right;font-weight:700;color:#f59e0b;border-bottom:1px solid #dbeafe\">{$solPend}</td></tr>";
        }
        $body .= "<tr><td style=\"padding:8px 0;color:#374151;border-bottom:1px solid #dbeafe\">Demandas atrasadas</td><td style=\"text-align:right;font-weight:700;color:#ef4444;border-bottom:1px solid #dbeafe\">{$atrasadas}</td></tr>";
        $body .= $semRelatorio;
        $body .= '</table></div>';

        if ($atrasadas > 0) {
            $body .= '<div style="background:#fff5f5;border:1px solid #fecaca;border-radius:8px;padding:12px 16px;margin-top:12px">'
                  .  '<p style="margin:0;font-size:12px;color:#dc2626">⚠️ Atenção: há <strong>' . $atrasadas . ' demanda(s) atrasada(s)</strong>. Acesse o sistema para verificar.</p>'
                  .  '</div>';
        }

        return _doSendEmail($user['email'], $user['name'], '📊 Relatório Diário — GestãoDev ASSEGO', $body);
    } catch (\Throwable $e) {
        error_log('[Mailer] sendDailyReport: ' . $e->getMessage());
        return false;
    }
}

// Mantém alias para compatibilidade com código existente
function sendWeeklyReport($db, $userId, $force = false) {
    return sendDailyReport($db, $userId, $force);
}
