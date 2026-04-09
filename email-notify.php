<?php
function sendEmailNotification($db, $userId, $subject, $body, $type='general'){
    try {
        $st=$db->prepare("SELECT email,name,email_notifications,email_prefs FROM usuarios WHERE id=? AND active=1");
        $st->execute([$userId]);
        $user=$st->fetch();
        if(!$user || !$user['email_notifications']) return false;
        $prefs = json_decode($user['email_prefs'] ?? '{}', true) ?: [];
        $defaults = ['demandas'=>1,'solicitacoes'=>1,'automacoes'=>1,'reunioes'=>1,'avisos'=>1,'comentarios'=>1,'aprovacoes'=>1,'relatorio'=>1];
        $prefs = array_merge($defaults, $prefs);
        $typeMap = [
            'demand'=>'demandas','demand_assigned'=>'demandas','demand_status'=>'demandas',
            'demand_completed'=>'demandas','demand_review'=>'demandas',
            'solicitation'=>'solicitacoes','solicitation_approved'=>'solicitacoes',
            'auto'=>'automacoes','auto_approve'=>'automacoes','auto_complete'=>'automacoes',
            'meeting'=>'reunioes','notice'=>'avisos',
            'comment'=>'comentarios','mention'=>'comentarios',
            'presidency'=>'aprovacoes','approval'=>'aprovacoes',
            'report'=>'relatorio','test'=>'general','general'=>'general'
        ];
        $prefKey = $typeMap[$type] ?? 'general';
        if($prefKey !== 'general' && empty($prefs[$prefKey])) return false;
        $to=$user['email'];
        $name=$user['name'];
        $siteUrl='https://gestaodev.assego.com.br/gestaodev/';
        $headers="From: GestãoDev ASSEGO <noreply@assego.org.br>\r\n";
        $headers.="Reply-To: noreply@assego.org.br\r\n";
        $headers.="MIME-Version: 1.0\r\n";
        $headers.="Content-Type: text/html; charset=UTF-8\r\n";
        $htmlBody="<div style='font-family:Arial,sans-serif;max-width:600px;margin:0 auto;background:#0a0e17;color:#e8ecf4;border-radius:12px;overflow:hidden;border:1px solid #2a3654'>";
        $htmlBody.="<div style='background:linear-gradient(135deg,#3b82f6,#8b5cf6);padding:20px 24px'>";
        $htmlBody.="<h1 style='margin:0;font-size:18px;color:#fff'>GestãoDev ASSEGO</h1></div>";
        $htmlBody.="<div style='padding:24px'>";
        $htmlBody.="<p style='margin:0 0 8px;color:#8899b8'>Olá, <strong style='color:#e8ecf4'>{$name}</strong></p>";
        $htmlBody.="<div style='background:#111827;border:1px solid #2a3654;border-radius:8px;padding:16px;margin:12px 0'>";
        $htmlBody.=$body;
        $htmlBody.="</div>";
        $htmlBody.="<p style='margin:16px 0 0;font-size:12px;color:#5a6d8f'>Notificação automática do GestãoDev ASSEGO.</p>";
        $htmlBody.="<a href='{$siteUrl}' style='display:inline-block;margin-top:12px;padding:8px 16px;background:#3b82f6;color:#fff;text-decoration:none;border-radius:6px;font-size:13px'>Acessar Sistema</a>";
        $htmlBody.="</div></div>";
        return @mail($to, '=?UTF-8?B?'.base64_encode($subject).'?=', $htmlBody, $headers);
    } catch(Exception $e) { return false; }
}

function sendWeeklyReport($db, $userId){
    try {
        $user=$db->prepare("SELECT email,name,email_notifications,email_prefs FROM usuarios WHERE id=? AND active=1");
        $user->execute([$userId]);
        $user=$user->fetch();
        if(!$user||!$user['email_notifications']) return false;
        $prefs=json_decode($user['email_prefs']??'{}',true)?:[];
        if(isset($prefs['relatorio'])&&!$prefs['relatorio']) return false;

        $role=$user['role']??'dev';
        $isAdmin=strpos($role,'admin')!==false||strpos($role,'diretor')!==false;
        $uid=$userId;

        // Stats gerais
        $total=$db->query("SELECT COUNT(*) FROM demandas")->fetchColumn();
        $concluidas=$db->query("SELECT COUNT(*) FROM demandas WHERE status='Concluída'")->fetchColumn();
        $andamento=$db->query("SELECT COUNT(*) FROM demandas WHERE status='Em Andamento'")->fetchColumn();
        $revisao=$db->query("SELECT COUNT(*) FROM demandas WHERE status='Em Revisão'")->fetchColumn();
        $aguardando=$db->query("SELECT COUNT(*) FROM demandas WHERE status='Aguardando Aceite'")->fetchColumn();
        $atrasadas=$db->query("SELECT COUNT(*) FROM demandas WHERE deadline<CURDATE() AND status NOT IN('Concluída','Cancelada')")->fetchColumn();

        // Minhas demandas (se dev)
        $minhas=$db->prepare("SELECT COUNT(*) FROM demanda_devs WHERE user_id=? AND demand_id IN(SELECT id FROM demandas WHERE status NOT IN('Concluída','Cancelada'))");
        $minhas->execute([$uid]);
        $minhasCount=$minhas->fetchColumn();

        // Concluídas esta semana
        $semana=$db->query("SELECT COUNT(*) FROM demandas WHERE status='Concluída' AND completed_at>=DATE_SUB(NOW(),INTERVAL 7 DAY)")->fetchColumn();

        // Solicitações pendentes
        $solPend=$db->query("SELECT COUNT(*) FROM solicitacoes WHERE status='Pendente'")->fetchColumn();

        $name=$user['name'];
        $to=$user['email'];
        $subject="Relatório Semanal - GestãoDev ASSEGO";

        $cardStyle="style='display:inline-block;width:30%;min-width:120px;background:#111827;border:1px solid #2a3654;border-radius:8px;padding:12px;text-align:center;margin:4px;vertical-align:top'";
        $numStyle="style='font-size:24px;font-weight:800;margin:0'";
        $lblStyle="style='font-size:10px;color:#5a6d8f;margin:4px 0 0;text-transform:uppercase;letter-spacing:.5px'";

        $body="<h3 style='color:#818cf8;margin:0 0 16px;font-size:16px'>Relatório Semanal</h3>";
        $body.="<div style='text-align:center;margin-bottom:16px'>";
        $body.="<div {$cardStyle}><p {$numStyle} style='color:#6366f1'>{$total}</p><p {$lblStyle}>Total</p></div>";
        $body.="<div {$cardStyle}><p {$numStyle} style='color:#10b981'>{$concluidas}</p><p {$lblStyle}>Concluídas</p></div>";
        $body.="<div {$cardStyle}><p {$numStyle} style='color:#d4a017'>{$aguardando}</p><p {$lblStyle}>Aguardando</p></div>";
        $body.="<div {$cardStyle}><p {$numStyle} style='color:#3b82f6'>{$andamento}</p><p {$lblStyle}>Andamento</p></div>";
        $body.="<div {$cardStyle}><p {$numStyle} style='color:#f59e0b'>{$revisao}</p><p {$lblStyle}>Revisão</p></div>";
        $body.="<div {$cardStyle}><p {$numStyle} style='color:#ef4444'>{$atrasadas}</p><p {$lblStyle}>Atrasadas</p></div>";
        $body.="</div>";

        $body.="<div style='background:#0d1321;border:1px solid #2a3654;border-radius:8px;padding:14px;margin:12px 0'>";
        $body.="<table style='width:100%;border-collapse:collapse;font-size:13px'>";
        $body.="<tr><td style='padding:6px 0;color:#8899b8'>Concluídas esta semana</td><td style='text-align:right;font-weight:700;color:#10b981'>{$semana}</td></tr>";
        $body.="<tr><td style='padding:6px 0;color:#8899b8'>Suas demandas ativas</td><td style='text-align:right;font-weight:700;color:#3b82f6'>{$minhasCount}</td></tr>";
        if($isAdmin) $body.="<tr><td style='padding:6px 0;color:#8899b8'>Solicitações pendentes</td><td style='text-align:right;font-weight:700;color:#f59e0b'>{$solPend}</td></tr>";
        $body.="</table></div>";

        $body.="<p style='color:#5a6d8f;font-size:11px;margin:12px 0 0'>Gerado em ".date('d/m/Y H:i')."</p>";

        return sendEmailNotification($db, $userId, $subject, $body, 'report');
    } catch(Exception $e) { return false; }
}
