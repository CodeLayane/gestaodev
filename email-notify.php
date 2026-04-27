<?php
require_once __DIR__.'/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Credenciais SMTP Gmail
define('SMTP_USER', 'tecnico.assego@gmail.com');
define('SMTP_PASS', 'gmyfxpsobpehsgiv');

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
        return _doSendEmail($user['email'], $user['name'], $subject, $body);
    } catch(\Throwable $e) { return false; }
}

function _doSendEmail($to, $name, $subject, $body){
    $siteUrl='https://gestaodev.assego.com.br/gestaodev/';
    $logo1=$siteUrl.'assets/img/logo.png';
    $logo2=$siteUrl.'assets/img/logoassego.png';
    $html='<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head><body style="margin:0;padding:0;background:#f0f4f8;font-family:\'Segoe UI\',Arial,Helvetica,sans-serif">'.
'<div style="max-width:600px;margin:32px auto;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,82,204,0.10)">'.

  // HEADER
  '<div style="background:linear-gradient(135deg,#0052cc 0%,#0073e6 60%,#2196f3 100%);padding:32px 28px 24px;text-align:center">'.
    '<div style="display:inline-flex;align-items:center;gap:14px;justify-content:center">'.
      '<img src="'.$logo1.'" alt="GestãoDev" style="height:52px;display:inline-block;vertical-align:middle" />'.
      '<img src="'.$logo2.'" alt="ASSEGO" style="height:52px;display:inline-block;vertical-align:middle" />'.
    '</div>'.
    '<div style="margin-top:14px;font-size:18px;font-weight:700;color:#ffffff;letter-spacing:0.5px">Gestão Dev ASSEGO</div>'.
    '<div style="margin-top:4px;font-size:11px;color:rgba(255,255,255,0.75);letter-spacing:1.5px;text-transform:uppercase;font-weight:500">Sistema de Gestão de Desenvolvimento</div>'.
  '</div>'.

  // BODY
  '<div style="padding:36px 32px 28px">'.
    '<p style="margin:0 0 20px;color:#374151;font-size:15px">Olá, <strong style="color:#0052cc">'.$name.'</strong></p>'.
    '<div style="background:#f8faff;border:1px solid #dbeafe;border-left:4px solid #0073e6;border-radius:10px;padding:22px 24px;margin:0 0 24px;color:#1e293b;font-size:14px;line-height:1.7">'.$body.'</div>'.
    '<div style="text-align:center;margin:28px 0 8px">'.
      '<a href="'.$siteUrl.'" style="display:inline-block;padding:14px 36px;background:linear-gradient(135deg,#0052cc,#0073e6);color:#ffffff;text-decoration:none;border-radius:10px;font-size:14px;font-weight:700;letter-spacing:0.3px;box-shadow:0 4px 12px rgba(0,82,204,0.3)">Acessar GestãoDev</a>'.
    '</div>'.
  '</div>'.

  // FOOTER
  '<div style="background:#f8faff;padding:18px 32px;border-top:1px solid #dbeafe;text-align:center">'.
    '<p style="margin:0 0 4px;font-size:11px;color:#64748b;font-weight:600">ASSEGO — Associação dos Subtenentes e Sargentos do Estado de Goiás</p>'.
    '<p style="margin:0;font-size:10px;color:#94a3b8">Notificação automática do GestãoDev &nbsp;·&nbsp; Não responda este email</p>'.
  '</div>'.

'</div>'.
'</body></html>';
    $mail = new PHPMailer(true);
    try {
        $mail->isMail();
        $mail->CharSet  = 'UTF-8';
        $mail->Encoding = 'base64';
        $mail->setFrom('gestaodev@assego.com.br', 'GestãoDev ASSEGO');
        $mail->addReplyTo('gestaodev@assego.com.br', 'GestãoDev ASSEGO');
        $mail->addAddress($to, $name);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $html;
        $mail->AltBody = strip_tags($body);
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('EMAIL ERROR para '.$to.': ' . $mail->ErrorInfo);
        return false;
    }
}

function sendReport($db, $userId, $tipo='diario', $force=false){
    try {
        $user=$db->prepare("SELECT email,name,email_notifications,email_prefs,role FROM usuarios WHERE id=? AND active=1");
        $user->execute([$userId]);
        $user=$user->fetch();
        if(!$user) return false;
        if(!$force && !$user['email_notifications']) return false;
        $prefs=json_decode($user['email_prefs']??'{}',true)?:[];
        if(!$force && isset($prefs['relatorio'])&&!$prefs['relatorio']) return false;
        $role=$user['role']??'dev';
        $isAdmin=strpos($role,'admin')!==false||strpos($role,'diretor')!==false;
        $isSemanal=$tipo==='semanal';
        $intervalo=$isSemanal?'7 DAY':'1 DAY';
        $labelTipo=$isSemanal?'Semanal':'Diário';
        $labelPeriodo=$isSemanal?'esta semana':'hoje';
        $total=$db->query("SELECT COUNT(*) FROM demandas")->fetchColumn();
        $concluidas=$db->query("SELECT COUNT(*) FROM demandas WHERE status='Concluída'")->fetchColumn();
        $andamento=$db->query("SELECT COUNT(*) FROM demandas WHERE status='Em Andamento'")->fetchColumn();
        $revisao=$db->query("SELECT COUNT(*) FROM demandas WHERE status='Em Revisão'")->fetchColumn();
        $aguardando=$db->query("SELECT COUNT(*) FROM demandas WHERE status='Aguardando Aceite'")->fetchColumn();
        $atrasadas=$db->query("SELECT COUNT(*) FROM demandas WHERE deadline<CURDATE() AND status NOT IN('Concluída','Cancelada')")->fetchColumn();
        $minhas=$db->prepare("SELECT COUNT(*) FROM devs_demandas WHERE user_id=? AND demand_id IN(SELECT id FROM demandas WHERE status NOT IN('Concluída','Cancelada'))");
        $minhas->execute([$userId]);$minhasCount=$minhas->fetchColumn();
        $periodo=$db->query("SELECT COUNT(*) FROM demandas WHERE status='Concluída' AND completed_at>=DATE_SUB(NOW(),INTERVAL {$intervalo})")->fetchColumn();
        $solPend=$db->query("SELECT COUNT(*) FROM solicitacoes WHERE status='Pendente'")->fetchColumn();
        $cs='display:inline-block;width:28%;min-width:100px;background:#0a0e17;border:1px solid #1e293b;border-radius:10px;padding:14px 8px;text-align:center;margin:4px;vertical-align:top';
        $body="<h2 style=\"color:#818cf8;margin:0 0 6px;font-size:18px;font-weight:800\">Relatório {$labelTipo}</h2>";
        $body.='<p style="color:#64748b;margin:0 0 20px;font-size:12px">'.date('d/m/Y').' — Resumo do sistema</p>';
        $body.='<div style="text-align:center;margin-bottom:20px">';
        $labelPeriodoCard=$isSemanal?'Semana':'Hoje';
        $cards=[['v'=>$total,'l'=>'Total','c'=>'#6366f1'],['v'=>$concluidas,'l'=>'Concluídas','c'=>'#10b981'],['v'=>$aguardando,'l'=>'Aguardando','c'=>'#d4a017'],['v'=>$andamento+$revisao,'l'=>'Em Execução','c'=>'#3b82f6'],['v'=>$atrasadas,'l'=>'Atrasadas','c'=>'#ef4444'],['v'=>$periodo,'l'=>$labelPeriodoCard,'c'=>'#8b5cf6']];
        foreach($cards as $cd){$body.="<div style=\"{$cs}\"><div style=\"font-size:28px;font-weight:800;color:{$cd['c']};line-height:1.2\">{$cd['v']}</div><div style=\"font-size:9px;color:#64748b;margin-top:6px;text-transform:uppercase;letter-spacing:.8px;font-weight:600\">{$cd['l']}</div></div>";}
        $body.='</div>';
        $body.='<div style="background:#0a0e17;border:1px solid #1e293b;border-radius:10px;padding:16px;margin:16px 0"><table style="width:100%;border-collapse:collapse;font-size:13px">';
        $body.="<tr><td style=\"padding:8px 0;color:#94a3b8;border-bottom:1px solid #1e293b\">Concluídas {$labelPeriodo}</td><td style=\"text-align:right;font-weight:700;color:#10b981;border-bottom:1px solid #1e293b\">{$periodo}</td></tr>";
        $body.="<tr><td style=\"padding:8px 0;color:#94a3b8;border-bottom:1px solid #1e293b\">Suas demandas ativas</td><td style=\"text-align:right;font-weight:700;color:#3b82f6;border-bottom:1px solid #1e293b\">{$minhasCount}</td></tr>";
        if($isAdmin)$body.="<tr><td style=\"padding:8px 0;color:#94a3b8;border-bottom:1px solid #1e293b\">Solicitações pendentes</td><td style=\"text-align:right;font-weight:700;color:#f59e0b;border-bottom:1px solid #1e293b\">{$solPend}</td></tr>";
        $body.="<tr><td style=\"padding:8px 0;color:#94a3b8\">Demandas atrasadas</td><td style=\"text-align:right;font-weight:700;color:#ef4444\">{$atrasadas}</td></tr>";
        $body.='</table></div>';
        return _doSendEmail($user['email'], $user['name'], "Relatório {$labelTipo} — GestãoDev ASSEGO", $body);
    } catch(\Throwable $e) { return false; }
}

function sendWeeklyReport($db, $userId, $force=false){
    return sendReport($db, $userId, 'semanal', $force);
}
