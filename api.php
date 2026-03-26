<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('session.gc_maxlifetime', 2592000);
ini_set('session.cookie_lifetime', 2592000);
session_set_cookie_params(['lifetime'=>2592000,'path'=>'/','httponly'=>true,'samesite'=>'Lax']);
ini_set('log_errors', 1);

require_once __DIR__.'/config.php';
require_once __DIR__.'/push-api.php';
initSession();
header('Content-Type:application/json;charset=utf-8');

$method=$_SERVER['REQUEST_METHOD'];
$act=$_GET['action']??'';

// Aliases: JS usa nomes em inglês → api usa português
$actAliases=['demands'=>'demandas','systems'=>'sistemas','users'=>'usuarios','meetings'=>'reunioes','notices'=>'avisos','notifications'=>'notificacoes','notifications_unread'=>'notificacoes_unread','notifications_recent'=>'notificacoes_recent','notifications_read_all'=>'notificacoes_read_all','solicitations'=>'solicitacoes','calendar_notes'=>'anotacoes_calendario','note_folders'=>'pastas_notas','notices_form'=>'avisos_form','notice_form'=>'avisos_form','reports_daily'=>'relatorios_diarios'];
if(isset($actAliases[$act])) $act=$actAliases[$act];

// Serve arquivos do banco (BLOB)
if($act==="arquivo"){
    $fn=$_GET["f"]??""; if(!$fn){ http_response_code(400); exit; }
    $db=getDB();
    $s=$db->prepare("SELECT tipo_mime,dados FROM arquivos WHERE nome_arquivo=?"); $s->execute([$fn]); $r=$s->fetch();
    if(!$r){ http_response_code(404); exit; }
    header("Content-Type: ".$r["tipo_mime"]);
    header("Cache-Control: public, max-age=86400");
    header("Content-Length: ".strlen($r["dados"]));
    echo $r["dados"]; exit;
}

try {

$db=getDB();


// Verificar limite de demandas em andamento por dev
function checkDevWorkLimit($db, $userId, $demandId=null) {
    // Contar demandas em andamento do dev (excluindo a atual)
    $sql = "SELECT d.id, d.title FROM devs_demandas dd 
            JOIN demandas d ON d.id = dd.demand_id 
            WHERE dd.user_id = ? AND d.status = 'Em Andamento'";
    $params = [$userId];
    if ($demandId) {
        $sql .= " AND d.id != ?";
        $params[] = $demandId;
    }
    $s = $db->prepare($sql);
    $s->execute($params);
    $activeList = $s->fetchAll(PDO::FETCH_ASSOC);
    $count = count($activeList);
    
    if ($count === 0) return ['allowed' => true];
    
    // Verificar se tem autorização aprovada para esta demanda específica
    if ($demandId) {
        $chk = $db->prepare("SELECT status FROM multi_demand_requests WHERE user_id = ? AND demand_id = ? AND status = 'Aprovada'");
        $chk->execute([$userId, $demandId]);
        if ($chk->fetch()) return ['allowed' => true];
    }
    
    // Admin pode sempre (tem todas as permissões)
    $role = $db->prepare("SELECT role FROM usuarios WHERE id = ?");
    $role->execute([$userId]);
    $userRole = $role->fetchColumn() ?: '';
    if (strpos($userRole, 'admin') !== false) return ['allowed' => true];
    
    return [
        'allowed' => false,
        'count' => $count,
        'active_demands' => $activeList
    ];
}

// Auto-migrate missing columns (runs once per session)
if(empty($_SESSION['_migrated'])){
    // user_permissions table
    try{ $db->exec("CREATE TABLE IF NOT EXISTS user_permissions (id INT AUTO_INCREMENT PRIMARY KEY,user_id INT NOT NULL,permission VARCHAR(64) NOT NULL,granted TINYINT(1) DEFAULT 1,updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,UNIQUE KEY uq_up (user_id,permission)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); }catch(Exception $e){}

    $autoMig=[
        ['avisos','active','TINYINT(1) DEFAULT 1'],
        ['devs_demandas','assigned_by','INT DEFAULT NULL'],
        ['devs_demandas','acceptance',"ENUM('Pendente','Aceita','Recusada') DEFAULT 'Pendente'"],
        ['devs_demandas','rejection_reason','TEXT'],
        ['historico_demandas','action','VARCHAR(255)'],
        ['historico_demandas','details','TEXT'],
        ['demandas','from_solicitation_id','INT DEFAULT NULL'],
        ['demandas','presidency_approved_by','INT DEFAULT NULL'],
        ['demandas','presidency_approved_at','DATETIME DEFAULT NULL'],
        ['solicitacoes','converted_demand_id','INT DEFAULT NULL'],
        ['imagens_demandas','mime_type','VARCHAR(100) DEFAULT NULL'],
    ];
    foreach($autoMig as $m){
        try{
            $chk=$db->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='".DB_NAME."' AND TABLE_NAME='{$m[0]}' AND COLUMN_NAME='{$m[1]}'")->fetchColumn();
            if(!$chk) $db->exec("ALTER TABLE `{$m[0]}` ADD COLUMN `{$m[1]}` {$m[2]}");
        }catch(Exception $e){}
    }
    
    // === New tables: docs, surveys, departments ===
    $newTables=[
        "CREATE TABLE IF NOT EXISTS departments (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(200) NOT NULL, description TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS documentations (id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(300) NOT NULL, description TEXT, content LONGTEXT, system_id INT DEFAULT NULL, category VARCHAR(100) DEFAULT 'Geral', password VARCHAR(255) DEFAULT NULL, password_plain VARCHAR(255) DEFAULT NULL, created_by INT NOT NULL, updated_by INT DEFAULT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS doc_files (id INT AUTO_INCREMENT PRIMARY KEY, doc_id INT NOT NULL, filename VARCHAR(255) NOT NULL, original_name VARCHAR(255) NOT NULL, file_size BIGINT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS surveys (id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(300) NOT NULL, description TEXT, type ENUM('single','multiple','rating') DEFAULT 'single', active TINYINT(1) DEFAULT 1, anonymous TINYINT(1) DEFAULT 0, created_by INT NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, expires_at DATETIME DEFAULT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS survey_options (id INT AUTO_INCREMENT PRIMARY KEY, survey_id INT NOT NULL, label VARCHAR(300) NOT NULL, sort_order INT DEFAULT 0, FOREIGN KEY (survey_id) REFERENCES surveys(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS survey_votes (id INT AUTO_INCREMENT PRIMARY KEY, survey_id INT NOT NULL, option_id INT NOT NULL, user_id INT NOT NULL, rating INT DEFAULT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (survey_id) REFERENCES surveys(id) ON DELETE CASCADE, FOREIGN KEY (option_id) REFERENCES survey_options(id) ON DELETE CASCADE, UNIQUE KEY uq_vote (survey_id, user_id, option_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    ];
    foreach($newTables as $sql){try{$db->exec($sql);}catch(\Exception $e){}}
    try{$db->exec("ALTER TABLE documentations ADD COLUMN password_plain VARCHAR(255) DEFAULT NULL");}catch(\Exception $e){}
    // Add department_id to users if missing
    try{$db->exec("ALTER TABLE users ADD COLUMN department_id INT DEFAULT NULL");}catch(\Exception $e){}

    
    // Tabela para solicitações de múltiplas demandas simultâneas
    try{$db->exec("CREATE TABLE IF NOT EXISTS multi_demand_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        demand_id INT NOT NULL,
        justification TEXT NOT NULL,
        status ENUM('Pendente','Aprovada','Rejeitada') DEFAULT 'Pendente',
        reviewed_by INT DEFAULT NULL,
        review_notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        reviewed_at DATETIME DEFAULT NULL,
        UNIQUE KEY uq_user_demand (user_id, demand_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");}catch(Exception $e){}

    try{$db->exec("CREATE TABLE IF NOT EXISTS checklist_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        demand_id INT NOT NULL,
        text VARCHAR(500) NOT NULL,
        done TINYINT(1) DEFAULT 0,
        sort_order INT DEFAULT 0,
        created_by INT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        completed_at DATETIME DEFAULT NULL,
        INDEX idx_demand (demand_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");}catch(Exception $e){}
    $_SESSION["_migrated"]=1;
    // Complexidade e métricas
    $metricCols = [
        ['demandas','type',"ENUM('Melhoria','Correção','Nova Funcionalidade','Sugestão de Usuário') DEFAULT 'Melhoria'"],
        ['demandas','complexity',"ENUM('Simples','Moderada','Complexa','Muito Complexa') DEFAULT 'Moderada'"],
        ['demandas','effort_points',"TINYINT UNSIGNED DEFAULT 0"],
        ['demandas','started_at',"DATETIME DEFAULT NULL"],
        ['usuarios','work_hours',"TINYINT DEFAULT 6"],
        ['solicitacoes','complexity',"ENUM('Simples','Moderada','Complexa','Muito Complexa') DEFAULT NULL"],
        ['solicitacoes','requester_name','VARCHAR(150) DEFAULT NULL'],
        ['solicitacoes','requester_department','VARCHAR(150) DEFAULT NULL'],
    ];
    foreach($metricCols as $mc){
        $chkM=$db->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='".DB_NAME."' AND TABLE_NAME='{$mc[0]}' AND COLUMN_NAME='{$mc[1]}'")->fetchColumn();
        if(!$chkM) $db->exec("ALTER TABLE {$mc[0]} ADD COLUMN {$mc[1]} {$mc[2]}");
    }
}

// ===== AUTH =====
if($act==='login'){
    $d=json_decode(file_get_contents('php://input'),true);
    $email=trim($d['email']??''); $pass=$d['password']??'';
    if(!$email||!$pass) jsonR(['error'=>'Usuário/email e senha obrigatórios'],400);
    $s=$db->prepare("SELECT * FROM usuarios WHERE (email=? OR name=?) AND active=1"); $s->execute([$email,$email]); $u=$s->fetch();
    if(!$u||!password_verify($pass,$u['password'])) jsonR(['error'=>'Usuário/email ou senha incorretos'],401);
    $_SESSION['user_id']=$u['id']; $_SESSION['user_role']=$u['role'];
    $db->prepare("UPDATE usuarios SET last_login=NOW() WHERE id=?")->execute([$u['id']]);
    logActivity($u['id'],'Login','user',$u['id']);
    jsonR(['success'=>true,'user'=>['id'=>$u['id'],'name'=>$u['name'],'email'=>$u['email'],'role'=>$u['role'],'avatar_color'=>$u['avatar_color'],'avatar_file'=>isset($u['avatar_file'])?$u['avatar_file']:null]]);
}
if($act==='logout'){ if(isLoggedIn()) logActivity($_SESSION['user_id'],'Logout','user',$_SESSION['user_id']); session_destroy(); jsonR(['success'=>true]); }
if($act==='me'){ $u=getCurrentUser(); if(!$u) jsonR(['error'=>'Não autenticado'],401); jsonR(['user'=>$u]); }

// ===== AUTH MIDDLEWARE =====
if(!isLoggedIn()) jsonR(['error'=>'Não autenticado'],401);
$ME=getCurrentUser(); if(!$ME) jsonR(['error'=>'Usuário inválido'],401);
$UID=$ME['id']; $ROLE=$ME['role'];
$IS_ADMIN=strpos($ROLE,'admin')!==false; $IS_PRES=strpos($ROLE,'presidencia')!==false; $IS_DEV=strpos($ROLE,'dev')!==false; $IS_USER=$ROLE==='usuario'; $IS_DIR=strpos($ROLE,'diretor')!==false;
$ROLES=array_map('trim',explode(',',$ROLE));
function meHasRole($r){global $ROLES;if(is_array($r))return count(array_intersect($ROLES,$r))>0;return in_array($r,$ROLES);}
$IS_ELEVATED=$IS_ADMIN||$IS_PRES||$IS_DIR;

// ── Minhas permissões (qualquer usuário logado) ──
if($act==='my_permissions'){
    $st=$db->prepare("SELECT permission,granted FROM user_permissions WHERE user_id=?");
    $st->execute([$UID]);
    $rows=[];
    while($r=$st->fetch()){ $rows[$r['permission']]=(int)$r['granted']; }
    jsonR(['permissions'=>$rows,'user_id'=>$UID,'role'=>$ROLE]);
}

// ===== PUSH NOTIFICATION ENDPOINTS =====
if($act==='push_public_key'){
    handlePushPublicKey();
}
if($act==='push_subscribe'){
    handlePushSubscribe($db, $UID);
}
if($act==='push_unsubscribe'){
    handlePushUnsubscribe($db, $UID);
}
if($act==='push_test'){
    handlePushTest($db, $UID);
}

// ===== NOTIFICATIONS =====
if($act==='notificacoes'){
    if($method==='GET'){
        $s=$db->prepare("SELECT * FROM notificacoes WHERE user_id=? ORDER BY created_at DESC LIMIT 50"); $s->execute([$UID]);
        jsonR($s->fetchAll());
    }
}
if($act==='notificacoes_unread'){
    $s=$db->prepare("SELECT COUNT(*) as c FROM notificacoes WHERE user_id=? AND is_read=0"); $s->execute([$UID]);
    jsonR($s->fetch());
}
if($act==='notificacoes_recent'){
    $s=$db->prepare("SELECT id,type,title,message,link,is_read,created_at FROM notificacoes WHERE user_id=? AND is_read=0 ORDER BY created_at DESC LIMIT 10"); $s->execute([$UID]);
    jsonR($s->fetchAll());
}
if($act==='check_deadlines'){
    $today=date('Y-m-d');
    $soon=date('Y-m-d',strtotime('+3 days'));
    if($IS_ADMIN||$IS_DIR){
        $s=$db->prepare("SELECT d.id,d.title,d.deadline,d.priority,d.status,GROUP_CONCAT(u.name SEPARATOR ', ') as dev_names
            FROM demandas d LEFT JOIN devs_demandas dd ON d.id=dd.demand_id LEFT JOIN usuarios u ON dd.user_id=u.id
            WHERE d.status NOT IN ('Concluída','Cancelada') AND d.deadline IS NOT NULL AND d.deadline<=?
            GROUP BY d.id ORDER BY d.deadline ASC");
        $s->execute([$soon]);
    } else {
        $s=$db->prepare("SELECT d.id,d.title,d.deadline,d.priority,d.status
            FROM demandas d JOIN devs_demandas dd ON d.id=dd.demand_id
            WHERE dd.user_id=? AND d.status NOT IN ('Concluída','Cancelada') AND d.deadline IS NOT NULL AND d.deadline<=?
            ORDER BY d.deadline ASC");
        $s->execute([$UID,$soon]);
    }
    $demands=$s->fetchAll();
    $warnings=[];
    foreach($demands as $dm){
        $diff=(int)((strtotime($dm['deadline'])-strtotime($today))/86400);
        if($diff<0) $type='overdue';
        elseif($diff===0) $type='today';
        elseif($diff<=1) $type='tomorrow';
        else $type='soon';
        $warnings[]=['id'=>$dm['id'],'title'=>$dm['title'],'deadline'=>$dm['deadline'],'priority'=>$dm['priority'],
            'status'=>$dm['status'],'type'=>$type,'days'=>$diff,'dev_names'=>$dm['dev_names']??''];
    }
    foreach($warnings as $w){
        if($w['type']==='overdue'||$w['type']==='today'){
            $check=$db->prepare("SELECT 1 FROM notificacoes WHERE user_id=? AND type='deadline_warning' AND entity_id=? AND created_at>DATE_SUB(NOW(),INTERVAL 24 HOUR)");
            $check->execute([$UID,$w['id']]);
            if(!$check->fetch()){
                $msg=$w['type']==='overdue'
                    ?"ATRASADA (".abs($w['days'])."d): {$w['title']} - Prazo: ".date('d/m',strtotime($w['deadline']))
                    :"VENCE HOJE: {$w['title']}";
                notify($UID,'deadline_warning',$msg,$w['priority'],"demand:{$w['id']}",'demand',$w['id']);
                sendPushToUser($db, $UID, ['title'=>'⚠️ Prazo!','message'=>$msg,'url'=>'/index.php#demandas']);
            }
        }
    }
    jsonR($warnings);
}
if($act==='notification_read'&&isset($_GET['id'])){
    $db->prepare("UPDATE notificacoes SET is_read=1 WHERE id=? AND user_id=?")->execute([$_GET['id'],$UID]);
    jsonR(['success'=>true]);
}
if($act==='notificacoes_read_all'){
    $db->prepare("UPDATE notificacoes SET is_read=1 WHERE user_id=?")->execute([$UID]);
    jsonR(['success'=>true]);
}

// ===== STATS =====
if($act==='stats'){
    $w=''; $p=[];
    if(!empty($_GET['dev_id'])){ $w=' WHERE d.id IN (SELECT demand_id FROM devs_demandas WHERE user_id=?)'; $p[]=$_GET['dev_id']; }
    $s=$db->prepare("SELECT COUNT(*) as total,
        SUM(d.status='Aberta') as abertas, SUM(d.status='Aguardando Aceite') as aguardando,
        SUM(d.status='Em Andamento') as andamento, SUM(d.status='Em Revisão') as revisao,
        SUM(d.status='Concluída') as concluidas, SUM(d.status='Cancelada') as canceladas,
        SUM(d.priority='Urgente' AND d.status NOT IN('Concluída','Cancelada')) as urgentes,
        SUM(d.needs_presidency_approval=1 AND d.presidency_status='Pendente') as pend_pres
    FROM demandas d".$w);
    $s->execute($p); jsonR($s->fetch());
}

// ===== DEMANDS =====
if($act==='demandas'){
    if($method==='GET'){
        $w=['1=1']; $p=[];
        if(!empty($_GET['status'])){ $w[]='d.status=?'; $p[]=$_GET['status']; }
        if(!empty($_GET['priority'])){ $w[]='d.priority=?'; $p[]=$_GET['priority']; }
        if(!empty($_GET['system_id'])){ $w[]='d.system_id=?'; $p[]=$_GET['system_id']; }
        if(!empty($_GET['search'])){ $w[]='(d.title LIKE ? OR d.description LIKE ?)'; $p[]='%'.$_GET['search'].'%'; $p[]='%'.$_GET['search'].'%'; }
        if(!empty($_GET['dev_id'])){ $w[]="(d.id IN (SELECT demand_id FROM devs_demandas WHERE user_id=?) OR (d.status='Aberta' AND d.id NOT IN (SELECT demand_id FROM devs_demandas)))"; $p[]=$_GET['dev_id']; }
        if(!empty($_GET['sprint_id'])){ $w[]='d.sprint_id=?'; $p[]=$_GET['sprint_id']; }
        if(!empty($_GET['type'])){ $w[]='d.type=?'; $p[]=$_GET['type']; }
        if(isset($_GET['no_sprint'])&&$_GET['no_sprint']=='1'){ $w[]='d.sprint_id IS NULL'; }
        if(!empty($_GET['presidency_status'])){ $w[]='d.needs_presidency_approval=1 AND d.presidency_status=?'; $p[]=$_GET['presidency_status']; }
        if(!empty($_GET['date_from'])){ $w[]='d.created_at>=?'; $p[]=$_GET['date_from'].' 00:00:00'; }
        if(!empty($_GET['date_to'])){ $w[]='d.created_at<=?'; $p[]=$_GET['date_to'].' 23:59:59'; }

        $sql="SELECT d.*,(SELECT COUNT(*) FROM checklist_items WHERE demand_id=d.id) as checklist_total,(SELECT COUNT(*) FROM checklist_items WHERE demand_id=d.id AND done=1) as checklist_done, s.name as system_name, s.technology as system_tech, s.url as system_url, s.github_url,
            c.name as creator_name, pa.name as approver_name, sp.name as sprint_name, sp.status as sprint_status
            FROM demandas d LEFT JOIN sistemas s ON d.system_id=s.id
            LEFT JOIN usuarios c ON d.created_by=c.id LEFT JOIN usuarios pa ON d.presidency_approved_by=pa.id
            LEFT JOIN sprints sp ON d.sprint_id=sp.id
            WHERE ".implode(' AND ',$w)." ORDER BY d.id DESC";
        $s=$db->prepare($sql); $s->execute($p); $demands=$s->fetchAll();

        foreach($demands as &$dm){
            $s2=$db->prepare("SELECT dd.*, u.name, u.avatar_color, u.avatar_file, u.role, u.email FROM devs_demandas dd JOIN usuarios u ON dd.user_id=u.id WHERE dd.demand_id=?");
            $s2->execute([$dm['id']]); $dm['devs']=$s2->fetchAll();
            $s3=$db->prepare("SELECT COUNT(*) as c FROM imagens_demandas WHERE demand_id=?"); $s3->execute([$dm['id']]); $dm['img_count']=$s3->fetch()['c'];
            $s4=$db->prepare("SELECT COUNT(*) as c FROM comentarios_demandas WHERE demand_id=?"); $s4->execute([$dm['id']]); $dm['cmt_count']=$s4->fetch()['c'];
        }
        jsonR($demands);
    }
    if($method==='POST'){
        if(!$IS_ADMIN&&!$IS_DIR) jsonR(['error'=>'Apenas administradores e diretores podem criar demandas. Use Solicitações.'],403);
        $d=json_decode(file_get_contents('php://input'),true);
        $title=trim($d['title']??''); if(!$title) jsonR(['error'=>'Título obrigatório'],400);
        $devIds=$d['dev_ids']??[];
        $status=count($devIds)>0?'Aguardando Aceite':'Aberta';

        $s=$db->prepare("INSERT INTO demandas (title,description,system_id,priority,status,requester,start_date,deadline,needs_presidency_approval,sprint_id,type,complexity,effort_points,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $effortMap=['Simples'=>1,'Moderada'=>2,'Complexa'=>3,'Muito Complexa'=>5]; $cpx=$d['complexity']??'Moderada'; $eff=$effortMap[$cpx]??2; $tipo=$d['type']??'Melhoria';
        $s->execute([$title,$d['description']??'',$d['system_id']?:null,$d['priority']??'Média',$status,$d['requester']??'',$d['start_date']?:null,$d['deadline']?:null,$d['needs_presidency_approval']?1:0,$d['sprint_id']?:null,$tipo,$cpx,$eff,$UID]);
        $did=$db->lastInsertId();

        // Atribuir devs
        if($devIds){
            $ins=$db->prepare("INSERT INTO devs_demandas (demand_id,user_id,assigned_by) VALUES (?,?,?)");
            foreach($devIds as $devId){
                $ins->execute([$did,$devId,$UID]);
                notify($devId,'demand_assigned',"Nova demanda atribuída: {$title}","Você foi designado para a demanda #{$did}","demand:{$did}",'demand',$did);
                sendPushToUser($db, (int)$devId, ['title'=>'📋 Nova Demanda','message'=>"Você foi designado: {$title}",'url'=>'/index.php#demandas']);
            }
        }

        // Notify ALL active usuarios about new demand
        $allActive=$db->prepare("SELECT id FROM usuarios WHERE active=1 AND (role LIKE '%admin%' OR role LIKE '%diretor%') AND id!=?"); $allActive->execute([$UID]);
        $notifiedDevs=$devIds?:array();
        foreach($allActive->fetchAll() as $au){
            if(!in_array($au['id'],$notifiedDevs)){
                notify($au['id'],'demand_new',"Nova demanda criada: {$title}","Por {$ME['name']}".($d['requester']?" · Solicitante: {$d['requester']}":''),"demand:{$did}",'demand',$did);
            }
        }

        $reqInfo=($d['requester']??'')?(" · Solicitante: ".$d['requester']):'';
        $db->prepare("INSERT INTO historico_demandas (demand_id,user_id,action,new_value,details) VALUES (?,?,'Criada',?,?)")->execute([$did,$UID,$status,"Criada por {$ME['name']}{$reqInfo}"]);
        logActivity($UID,"Criou demanda #{$did}: {$title}",'demand',$did);
        jsonR(['success'=>true,'id'=>$did],201);
    }
}

// ===== DEMAND DETAIL =====
if($act==='demand'&&isset($_GET['id'])){
    $id=(int)$_GET['id'];
    if($method==='GET'){
        $s=$db->prepare("SELECT d.*, s.name as system_name, s.technology as system_tech, s.url as system_url, s.github_url,
            c.name as creator_name, pa.name as approver_name, sp.name as sprint_name, sp.status as sprint_status
            FROM demandas d LEFT JOIN sistemas s ON d.system_id=s.id
            LEFT JOIN usuarios c ON d.created_by=c.id LEFT JOIN usuarios pa ON d.presidency_approved_by=pa.id
            LEFT JOIN sprints sp ON d.sprint_id=sp.id WHERE d.id=?");
        $s->execute([$id]); $dm=$s->fetch(); if(!$dm) jsonR(['error'=>'Não encontrada'],404);

        $s=$db->prepare("SELECT dd.*, u.name, u.avatar_color, u.avatar_file, u.role FROM devs_demandas dd JOIN usuarios u ON dd.user_id=u.id WHERE dd.demand_id=?"); $s->execute([$id]); $dm['devs']=$s->fetchAll();
        $s=$db->prepare("SELECT di.*, u.name as uploader FROM imagens_demandas di LEFT JOIN usuarios u ON di.uploaded_by=u.id WHERE di.demand_id=? ORDER BY di.created_at DESC"); $s->execute([$id]); $dm['images']=$s->fetchAll();
        $s=$db->prepare("SELECT dc.*, u.name as user_name, u.avatar_color FROM comentarios_demandas dc LEFT JOIN usuarios u ON dc.user_id=u.id WHERE dc.demand_id=? ORDER BY dc.created_at ASC"); $s->execute([$id]); $dm['comments']=$s->fetchAll();
        $s=$db->prepare("SELECT dh.*, u.name as user_name FROM historico_demandas dh LEFT JOIN usuarios u ON dh.user_id=u.id WHERE dh.demand_id=? ORDER BY dh.created_at DESC"); $s->execute([$id]); $dm['history']=$s->fetchAll();
        $s=$db->prepare("SELECT ci.*,u.name as creator_name FROM checklist_items ci LEFT JOIN usuarios u ON ci.created_by=u.id WHERE ci.demand_id=? ORDER BY ci.sort_order,ci.id");$s->execute([$id]);$dm['checklist']=$s->fetchAll();
        jsonR($dm);
    }
    if($method==='PUT'){
        if(!$IS_ADMIN&&!$IS_DIR) jsonR(['error'=>'Sem permissão para editar demandas'],403);
        $d=json_decode(file_get_contents('php://input'),true);
        $s=$db->prepare("SELECT * FROM demandas WHERE id=?"); $s->execute([$id]); $old=$s->fetch(); if(!$old) jsonR(['error'=>'Não encontrada'],404);

        $newStatus=$d['status']??$old['status'];
        $completedAt=$old['completed_at'];
        if($newStatus==='Concluída'&&$old['status']!=='Concluída') $completedAt=date('Y-m-d H:i:s');
        if($newStatus!=='Concluída') $completedAt=null;
        $reviewAt=$newStatus==='Em Revisão'?date('Y-m-d H:i:s'):($old['review_at']??null);if($newStatus==='Em Andamento')$reviewAt=null;

        $db->prepare("UPDATE demandas SET title=?,description=?,system_id=?,priority=?,status=?,requester=?,start_date=?,deadline=?,needs_presidency_approval=?,sprint_id=?,type=?,complexity=?,effort_points=?,completed_at=?,review_at=? WHERE id=?")
            ->execute([$d['title']??$old['title'],$d['description']??$old['description'],$d['system_id']?:$old['system_id'],$d['priority']??$old['priority'],$newStatus,$d['requester']??$old['requester'],$d['start_date']?:$old['start_date'],$d['deadline']?:$old['deadline'],isset($d['needs_presidency_approval'])?($d['needs_presidency_approval']?1:0):$old['needs_presidency_approval'],$d['sprint_id']??$old['sprint_id']??null,$d['type']??$old['type']??'Melhoria',$d['complexity']??$old['complexity']??'Moderada',(['Simples'=>1,'Moderada'=>2,'Complexa'=>3,'Muito Complexa'=>5])[$d['complexity']??$old['complexity']??'Moderada']??2,$completedAt,$reviewAt,$id]);

        // Atualizar devs se fornecido
        if(isset($d['dev_ids'])){
            $oldDevs=$db->prepare("SELECT user_id FROM devs_demandas WHERE demand_id=?"); $oldDevs->execute([$id]); $oldIds=array_column($oldDevs->fetchAll(),'user_id');
            $newIds=$d['dev_ids'];
            $toAdd=array_diff($newIds,$oldIds); $toRm=array_diff($oldIds,$newIds);
            foreach($toRm as $rid) $db->prepare("DELETE FROM devs_demandas WHERE demand_id=? AND user_id=?")->execute([$id,$rid]);
            $ins=$db->prepare("INSERT IGNORE INTO devs_demandas (demand_id,user_id,assigned_by) VALUES (?,?,?)");
            foreach($toAdd as $aid){
                $ins->execute([$id,$aid,$UID]);
                notify($aid,'demand_assigned',"Nova demanda: ".trim($d['title']??$old['title']??''),'',"demand:{$id}",'demand',$id);
                sendPushToUser($db, (int)$aid, ['title'=>'📋 Nova Demanda','message'=>'Você foi designado: '.trim($d['title']??$old['title']??''),'url'=>'/index.php#demandas']);
            }
        }

        if($newStatus!==$old['status']){
            $db->prepare("INSERT INTO historico_demandas (demand_id,user_id,action,old_value,new_value) VALUES (?,?,'Status alterado',?,?)")->execute([$id,$UID,$old['status'],$newStatus]);
            $devs=$db->prepare("SELECT user_id FROM devs_demandas WHERE demand_id=?"); $devs->execute([$id]);
            $ttl=trim($d['title']??$old['title']??'');
            foreach($devs->fetchAll() as $dv){
                if($dv['user_id']!=$UID){
                    notify($dv['user_id'],'demand_status',"{$ttl}: {$newStatus}",'',"demand:{$id}",'demand',$id);
                    sendPushToUser($db, (int)$dv['user_id'], ['title'=>'Demanda Atualizada','message'=>"{$ttl}: {$newStatus}",'url'=>'/index.php#demandas']);
                }
            }
        }
        logActivity($UID,"Atualizou: ".trim($d['title']??$old['title']??''),'demand',$id);
        jsonR(['success'=>true]);
    }
    if($method==='DELETE'){
        if(!$IS_ADMIN) jsonR(['error'=>'Sem permissão'],403);
        $db->prepare("DELETE FROM demandas WHERE id=?")->execute([$id]);
        logActivity($UID,"Excluiu demanda #{$id}",'demand',$id);
        jsonR(['success'=>true]);
    }
}

// Aceite/Recusa de demanda pelo dev
if($act==='demand_accept'&&isset($_GET['id'])){
    $id=(int)$_GET['id']; $d=json_decode(file_get_contents('php://input'),true);
    $acceptance=$d['acceptance']??'Aceita'; $reason=$d['reason']??'';
    $dem=$db->prepare("SELECT title,status FROM demandas WHERE id=?"); $dem->execute([$id]); $demRow=$dem->fetch();
    $demTitle=$demRow['title']??'';

    $db->prepare("UPDATE devs_demandas SET acceptance=?,assigned_at=NOW(),rejection_reason=? WHERE demand_id=? AND user_id=?")->execute([$acceptance,$reason,$id,$UID]);
    $db->prepare("INSERT INTO historico_demandas (demand_id,user_id,action,new_value,details) VALUES (?,?,?,?,?)")
        ->execute([$id,$UID,$acceptance==='Aceita'?'Dev aceitou':'Dev recusou',$acceptance,$reason]);

    if($acceptance==='Aceita'){
        if(in_array($demRow['status'],['Aberta','Aguardando Aceite'])){
            // Verificar limite de demandas simultâneas
            $wlCheck = checkDevWorkLimit($db, $UID, $id);
            if (!$wlCheck['allowed']) {
                // Reverter aceite — precisa de autorização
                $db->prepare("UPDATE devs_demandas SET acceptance='Pendente' WHERE demand_id=? AND user_id=?")->execute([$id, $UID]);
                jsonR([
                    'needs_multi_auth' => true,
                    'active_count' => $wlCheck['count'],
                    'active_demands' => $wlCheck['active_demands'],
                    'demand_id' => $id,
                    'demand_title' => $demTitle,
                    'message' => 'Você já tem '.$wlCheck['count'].' demanda(s) em andamento. Justifique para solicitar autorização.'
                ]);
            }
            $db->prepare("UPDATE demandas SET status='Em Andamento', started_at=COALESCE(started_at,NOW()) WHERE id=?")->execute([$id]);
            $db->prepare("INSERT INTO historico_demandas (demand_id,user_id,action,old_value,new_value) VALUES (?,?,'Status alterado',?,'Em Andamento')")->execute([$id,$UID,$demRow['status']]);
        }
    }
    $admins=$db->query("SELECT id FROM usuarios WHERE role LIKE '%admin%' AND active=1")->fetchAll();
    $nm=$ME['name']." ".($acceptance==='Aceita'?'aceitou':'recusou').": {$demTitle}";
    foreach($admins as $a){
        if($a['id']!=$UID){
            notify($a['id'],'demand_accept',$nm,$reason,"demand:{$id}",'demand',$id);
            sendPushToUser($db, (int)$a['id'], ['title'=>$acceptance==='Aceita'?'✅ Aceite':'❌ Recusa','message'=>$nm,'url'=>'/index.php#demandas']);
        }
    }
    logActivity($UID,"{$ME['name']} {$acceptance}: {$demTitle}",'demand',$id);
    jsonR(['success'=>true]);
}

// Dev claims (pega) an open demand
if($act==='demand_claim'&&isset($_GET['id'])){
    if(!$IS_DEV&&!$IS_ADMIN&&!$IS_DIR) jsonR(['error'=>'Sem permissão'],403);
    $id=(int)$_GET['id'];
    $dem=$db->prepare("SELECT title,status FROM demandas WHERE id=?"); $dem->execute([$id]); $demRow=$dem->fetch();
    if(!$demRow) jsonR(['error'=>'Demanda não encontrada'],404);
    $chk=$db->prepare("SELECT acceptance FROM devs_demandas WHERE demand_id=? AND user_id=?"); $chk->execute([$id,$UID]);
    $existing=$chk->fetch();
    $d=json_decode(file_get_contents('php://input'),true);
    if($existing){
        if($existing['acceptance']==='Pendente'){
            $db->prepare("UPDATE devs_demandas SET acceptance='Aceita',assigned_at=NOW() WHERE demand_id=? AND user_id=?")->execute([$id,$UID]);
            $db->prepare("INSERT INTO historico_demandas (demand_id,user_id,action,new_value) VALUES (?,?,'Dev aceitou','Aceita')")->execute([$id,$UID]);
        }
        if(in_array($demRow['status'],['Aberta','Aguardando Aceite'])){
            $db->prepare("UPDATE demandas SET status='Em Andamento', started_at=COALESCE(started_at,NOW()) WHERE id=?")->execute([$id]);
            $db->prepare("INSERT INTO historico_demandas (demand_id,user_id,action,old_value,new_value) VALUES (?,?,'Status alterado',?,'Em Andamento')")->execute([$id,$UID,$demRow['status']]);
            $admins=$db->query("SELECT id FROM usuarios WHERE (role LIKE '%admin%' OR role LIKE '%diretor%') AND active=1")->fetchAll();
            foreach($admins as $a){
                if($a['id']!=$UID){
                    notify($a['id'],'demand_status',"{$ME['name']} iniciou: {$demRow['title']}",'','demand:'.$id,'demand',$id);
                    sendPushToUser($db, (int)$a['id'], ['title'=>'🚀 Demanda Iniciada','message'=>"{$ME['name']} iniciou: {$demRow['title']}",'url'=>'/index.php#demandas']);
                }
            }
            logActivity($UID,"{$ME['name']} iniciou desenvolvimento: {$demRow['title']}",'demand',$id);
            jsonR(['success'=>true,'started'=>true]);
        }
        jsonR(['success'=>true,'already'=>true]);
    }
    $others=$db->prepare("SELECT u.id,u.name FROM devs_demandas dd JOIN usuarios u ON dd.user_id=u.id WHERE dd.demand_id=?"); $others->execute([$id]);
    $otherDevs=$others->fetchAll();
    $force=$d['force']??false;
    if(!empty($otherDevs)&&!$force){
        jsonR(['conflict'=>true,'devs'=>array_column($otherDevs,'name'),'message'=>'Esta demanda já tem devs atribuídos: '.implode(', ',array_column($otherDevs,'name')).'. Deseja continuar?']);
    }
    $db->prepare("INSERT IGNORE INTO devs_demandas (demand_id,user_id,assigned_by,acceptance) VALUES (?,?,?,'Aceita')")->execute([$id,$UID,$UID]);
    $db->prepare("INSERT INTO historico_demandas (demand_id,user_id,action,new_value) VALUES (?,?,'Dev assumiu a demanda',?)")->execute([$id,$UID,$ME['name']]);
    if(in_array($demRow['status'],['Aberta','Aguardando Aceite'])){
        $db->prepare("UPDATE demandas SET status='Em Andamento', started_at=COALESCE(started_at,NOW()) WHERE id=?")->execute([$id]);
        $db->prepare("INSERT INTO historico_demandas (demand_id,user_id,action,old_value,new_value) VALUES (?,?,'Status alterado',?,'Em Andamento')")->execute([$id,$UID,$demRow['status']]);
    }
    $admins=$db->query("SELECT id FROM usuarios WHERE role LIKE '%admin%' AND active=1")->fetchAll();
    foreach($admins as $a) if($a['id']!=$UID) notify($a['id'],'demand_assigned',"{$ME['name']} assumiu: {$demRow['title']}",'',"demand:{$id}",'demand',$id);
    foreach($otherDevs as $od) notify($od['id']??0,'demand_assigned',"{$ME['name']} assumiu a demanda: {$demRow['title']}",'',"demand:{$id}",'demand',$id);
    logActivity($UID,"{$ME['name']} assumiu: {$demRow['title']}",'demand',$id);
    jsonR(['success'=>true]);
}

// Status rápido (with workflow rules)
if($act==='demand_status'&&isset($_GET['id'])){
    $id=(int)$_GET['id']; $d=json_decode(file_get_contents('php://input'),true); $ns=$d['status']??''; $justification=$d['justification']??'';
    $old=$db->prepare("SELECT status,title FROM demandas WHERE id=?"); $old->execute([$id]); $oRow=$old->fetch(); $os=$oRow['status']??''; $demTitle=$oRow['title']??'';
    if($os===$ns) jsonR(['success'=>true,'unchanged'=>true]);
    if($ns==='Cancelada'&&!$IS_ADMIN&&!$IS_DIR) jsonR(['error'=>'Apenas admin/diretor pode cancelar demandas. Use Solicitar Cancelamento.'],403);
    if($ns==='Em Andamento'&&$os!=='Em Revisão'&&!$IS_ADMIN&&!$IS_DIR) jsonR(['error'=>'Use Assumir Demanda para iniciar'],403);
    if($ns==='Concluída'&&$os==='Em Revisão'&&!$IS_ADMIN&&!$IS_DIR) jsonR(['error'=>'Apenas admin/diretor pode concluir demandas em revisão'],403);
    if($ns==='Em Andamento'&&$os==='Em Revisão'&&!$IS_ADMIN&&!$IS_DIR) jsonR(['error'=>'Apenas admin/diretor pode devolver demandas'],403);
    $completedAt=null; if($ns==='Concluída') $completedAt=date('Y-m-d H:i:s');
    $reviewAt=($ns==='Em Revisão')?date('Y-m-d H:i:s'):null;
    $db->prepare("UPDATE demandas SET status=?,completed_at=?,review_at=? WHERE id=?")->execute([$ns,$completedAt,$reviewAt??null,$id]);
    $action='Status alterado';
    if($os==='Em Revisão'&&$ns==='Em Andamento') $action='Devolvida para ajustes';
    $db->prepare("INSERT INTO historico_demandas (demand_id,user_id,action,old_value,new_value,details) VALUES (?,?,?,?,?,?)")->execute([$id,$UID,$action,$os,$ns,$justification]);
    // Notify devs
    $devs=$db->prepare("SELECT user_id FROM devs_demandas WHERE demand_id=?"); $devs->execute([$id]);
    $notifMsg=$action==='Devolvida para ajustes'?"{$demTitle}: Devolvida — {$justification}":"{$demTitle}: {$ns}";
    $devIds=[];
    foreach($devs->fetchAll() as $dv){
        $devIds[]=$dv['user_id'];
        if($dv['user_id']!=$UID){
            $pushTitle=$ns==='Concluída'?'🎉 Concluída!':'Demanda Atualizada';
            $pushMsg=$ns==='Concluída'?"Parabéns! \"{$demTitle}\" foi concluída!":$notifMsg;
            notify($dv['user_id'],$ns==='Concluída'?'demand_completed':'demand_status',$ns==='Concluída'?"🎉 Parabéns \"{$demTitle}\" - Concluída!":$notifMsg,'',"demand:{$id}",'demand',$id);
            sendPushToUser($db, (int)$dv['user_id'], ['title'=>$pushTitle,'message'=>$pushMsg,'url'=>'/index.php#demandas']);
        }
    }
    if($ns==='Concluída'){
        $admins=$db->query("SELECT id FROM usuarios WHERE (role LIKE '%admin%' OR role LIKE '%diretor%') AND active=1")->fetchAll();
        foreach($admins as $a) if($a['id']!=$UID&&!in_array($a['id'],$devIds)) notify($a['id'],'demand_completed',"🎉 {$demTitle}: Concluída!",'',  "demand:{$id}",'demand',$id);
    }
    if($ns==='Em Revisão'){
        $admins=$db->query("SELECT id FROM usuarios WHERE (role LIKE '%admin%' OR role LIKE '%diretor%') AND active=1")->fetchAll();
        foreach($admins as $a){
            if($a["id"]!=$UID&&!in_array($a["id"],$devIds)){
                notify($a['id'],'demand_status',"{$demTitle}: Enviada para Revisão",'',"demand:{$id}",'demand',$id);
                sendPushToUser($db, (int)$a['id'], ['title'=>'📝 Em Revisão','message'=>"{$demTitle}: Enviada para Revisão",'url'=>'/index.php#demandas']);
            }
        }
    }
    logActivity($UID,"Status: {$demTitle} → {$ns}",'demand',$id);
    jsonR(['success'=>true]);
}

// Delegate demand to another dev
if($act==='demand_delegate'&&isset($_GET['id'])){
    $id=(int)$_GET['id']; $d=json_decode(file_get_contents('php://input'),true);
    $targetId=(int)($d['target_user_id']??0);
    if(!$targetId) jsonR(['error'=>'Dev destino obrigatório'],400);
    $dem=$db->prepare("SELECT title,status FROM demandas WHERE id=?"); $dem->execute([$id]); $demRow=$dem->fetch();
    if(!$demRow) jsonR(['error'=>'Demanda não encontrada'],404);
    $chk=$db->prepare("SELECT 1 FROM devs_demandas WHERE demand_id=? AND user_id=?"); $chk->execute([$id,$UID]);
    if(!$chk->fetch()&&!$IS_ADMIN) jsonR(['error'=>'Você não está nesta demanda'],403);
    $chk2=$db->prepare("SELECT 1 FROM devs_demandas WHERE demand_id=? AND user_id=?"); $chk2->execute([$id,$targetId]);
    if($chk2->fetch()) jsonR(['error'=>'Dev já está nesta demanda'],400);
    $tgt=$db->prepare("SELECT name FROM usuarios WHERE id=?"); $tgt->execute([$targetId]); $tgtName=$tgt->fetch()['name']??'';
    $db->prepare("INSERT INTO devs_demandas (demand_id,user_id,assigned_by,acceptance) VALUES (?,?,?,'Pendente')")->execute([$id,$targetId,$UID]);
    if($IS_DEV&&($d['remove_self']??false)){
        $db->prepare("DELETE FROM devs_demandas WHERE demand_id=? AND user_id=?")->execute([$id,$UID]);
        $db->prepare("INSERT INTO historico_demandas (demand_id,user_id,action,new_value) VALUES (?,?,?,?)")->execute([$id,$UID,'Delegou para',$tgtName]);
    } else {
        $db->prepare("INSERT INTO historico_demandas (demand_id,user_id,action,new_value) VALUES (?,?,?,?)")->execute([$id,$UID,'Adicionou dev',$tgtName]);
    }
    notify($targetId,'demand_assigned',"{$ME['name']} delegou: {$demRow['title']}",'',"demand:{$id}",'demand',$id);
    // Notificar admins sobre delegação
    $admsD=$db->prepare("SELECT id FROM usuarios WHERE FIND_IN_SET('admin',role) AND active=1 AND id!=?"); $admsD->execute([$UID]);
    foreach($admsD->fetchAll() as $ad) notify($ad['id'],'demand_assigned',"{$ME['name']} adicionou {$tgtName} em: {$demRow['title']}",'',"demand:{$id}",'demand',$id);
    sendPushToUser($db, $targetId, ['title'=>'📋 Demanda Delegada','message'=>"{$ME['name']} delegou: {$demRow['title']}",'url'=>'/index.php#demandas']);
    logActivity($UID,"Delegou {$demRow['title']} → {$tgtName}",'demand',$id);
    jsonR(['success'=>true]);
}

// Aprovação presidência

// Remover dev de uma demanda


if($act==='comment_delete'&&isset($_GET['id'])){
    $cid=(int)$_GET['id'];
    $c=$db->prepare("SELECT user_id,demand_id FROM comentarios_demandas WHERE id=?");$c->execute([$cid]);$row=$c->fetch();
    if(!$row) jsonR(['error'=>'Comentário não encontrado'],404);
    if($row['user_id']!=$UID && strpos($ROLE,'admin')===false) jsonR(['error'=>'Sem permissão'],403);
    $db->prepare("DELETE FROM comentarios_demandas WHERE id=?")->execute([$cid]);
    logActivity($UID,'Comentário removido','demand',$row['demand_id']);
    jsonR(['success'=>true]);
}

if($act==='demand_remove_dev'&&isset($_GET['id'])){
    $id=(int)$_GET['id']; $d=json_decode(file_get_contents('php://input'),true);
    $targetId=(int)($d['user_id']??0);
    if(!$targetId) jsonR(['error'=>'ID inválido'],400);
    // Permissao: admin sempre; dev aceito pode remover pendentes
    if(strpos($ROLE,'admin')===false){
        $myAcc=$db->prepare("SELECT acceptance FROM devs_demandas WHERE demand_id=? AND user_id=?");
        $myAcc->execute([$id,$UID]);$myStatus=$myAcc->fetchColumn();
        $tgtAcc=$db->prepare("SELECT acceptance FROM devs_demandas WHERE demand_id=? AND user_id=?");
        $tgtAcc->execute([$id,$targetId]);$tgtStatus=$tgtAcc->fetchColumn();
        if($myStatus!=='Aceita'||$tgtStatus!=='Pendente') jsonR(['error'=>'Sem permissao para remover este dev'],403);
    }
    // Get demand info
    $dem=$db->prepare("SELECT title,status FROM demandas WHERE id=?"); $dem->execute([$id]); $demRow=$dem->fetch();
    if(!$demRow) jsonR(['error'=>'Demanda não encontrada'],404);
    // Check if target is actually assigned
    $chk=$db->prepare("SELECT 1 FROM devs_demandas WHERE demand_id=? AND user_id=?"); $chk->execute([$id,$targetId]);
    if(!$chk->fetch()) jsonR(['error'=>'Dev não está nesta demanda'],400);
    // Count current devs
    $cnt=$db->prepare("SELECT COUNT(*) as c FROM devs_demandas WHERE demand_id=?"); $cnt->execute([$id]);
    $devCount=$cnt->fetch()['c'];
    // Allow removal (even last dev - demand goes back to "Aberta")
    // Get target name
    $tgt=$db->prepare("SELECT name FROM usuarios WHERE id=?"); $tgt->execute([$targetId]); $tgtName=$tgt->fetch()['name']??'Dev';
    // Remove
    $db->prepare("DELETE FROM devs_demandas WHERE demand_id=? AND user_id=?")->execute([$id,$targetId]);
    // Log history
    $db->prepare("INSERT INTO historico_demandas (demand_id,user_id,action,old_value,new_value) VALUES (?,?,?,?,?)")->execute([$id,$UID,'Removeu dev',$tgtName,'']);
    // If no more devs, set status back to Aberta
    $cnt2=$db->prepare("SELECT COUNT(*) as c FROM devs_demandas WHERE demand_id=?"); $cnt2->execute([$id]);
    if($cnt2->fetch()['c']==0 && !in_array($demRow['status'],['Concluída','Cancelada'])){
        $db->prepare("UPDATE demandas SET status='Aberta' WHERE id=?")->execute([$id]);
        $db->prepare("INSERT INTO historico_demandas (demand_id,user_id,action,old_value,new_value) VALUES (?,?,?,?,?)")->execute([$id,$UID,'Status alterado',$demRow['status'],'Aberta']);
    }
    // Notify removed dev
    notify($targetId,'demand_status',"{$ME['name']} removeu você de: {$demRow['title']}",'',"demand:{$id}",'demand',$id);
    logActivity($UID,"Removeu {$tgtName} de: {$demRow['title']}",'demand',$id);
    jsonR(['success'=>true]);
}
if($act==='demand_approve'&&isset($_GET['id'])){
    if(!$IS_ELEVATED) jsonR(['error'=>'Sem permissão'],403);
    $id=(int)$_GET['id']; $d=json_decode(file_get_contents('php://input'),true);
    $st=$d['presidency_status']??'Aprovada'; $notes=$d['presidency_notes']??'';
    $dem=$db->prepare("SELECT title,created_by FROM demandas WHERE id=?"); $dem->execute([$id]); $demRow=$dem->fetch();
    $demTitle=$demRow['title']??'';
    if($st==='Rejeitada'){
        $db->prepare("UPDATE demandas SET presidency_status='Rejeitada',presidency_approved_by=?,presidency_approved_at=NOW(),presidency_notes=?,status='Aberta' WHERE id=?")->execute([$UID,$notes,$id]);
    } else {
        $db->prepare("UPDATE demandas SET presidency_status=?,presidency_approved_by=?,presidency_approved_at=NOW(),presidency_notes=?,status='Aguardando Aceite' WHERE id=?")->execute([$st,$UID,$notes,$id]);
        $db->prepare("INSERT INTO historico_demandas (demand_id,user_id,action,old_value,new_value) VALUES (?,?,'Status alterado','Aberta','Aguardando Aceite')")->execute([$id,$UID]);
        $db->prepare("UPDATE devs_demandas SET acceptance='Pendente',assigned_at=NULL WHERE demand_id=?")->execute([$id]);
    }
    $db->prepare("INSERT INTO historico_demandas (demand_id,user_id,action,new_value,details) VALUES (?,?,CONCAT('Presidência: ',?),?,?)")->execute([$id,$UID,$st,$st,$notes]);
    $devs=$db->prepare("SELECT user_id FROM devs_demandas WHERE demand_id=?"); $devs->execute([$id]);
    $notifyIds=array_unique(array_merge(array_column($devs->fetchAll(),'user_id'),$demRow['created_by']?[$demRow['created_by']]:[]));
    foreach($notifyIds as $nid){
        if($nid!=$UID){
            notify($nid,'presidency',"{$demTitle}: Presidência {$st}",$notes,"demand:{$id}",'demand',$id);
            sendPushToUser($db, (int)$nid, ['title'=>$st==='Aprovada'?'✅ Aprovada':'❌ Rejeitada','message'=>"Presidência {$st}: {$demTitle}",'url'=>'/index.php#demandas']);
        }
    }
    if($st==='Rejeitada'){$admins=$db->query("SELECT id FROM usuarios WHERE role LIKE '%admin%' AND active=1")->fetchAll(); foreach($admins as $a) if($a['id']!=$UID) notify($a['id'],'presidency_rejected',"Presidência: {$demTitle} REJEITADA pela Presidência",$notes,"demand:{$id}",'demand',$id);}
    logActivity($UID,"Presidência {$st}: {$demTitle}",'demand',$id);
    jsonR(['success'=>true]);
}

// Resubmit demand for presidency approval after rejection
if($act==='demand_resubmit'&&isset($_GET['id'])){
    if(!$IS_ADMIN) jsonR(['error'=>'Sem permissão'],403);
    $id=(int)$_GET['id'];
    $db->prepare("UPDATE demandas SET presidency_status='Pendente',presidency_approved_by=NULL,presidency_approved_at=NULL,presidency_notes=NULL WHERE id=?")->execute([$id]);
    $db->prepare("INSERT INTO historico_demandas (demand_id,user_id,action,new_value) VALUES (?,?,'Reenviada para aprovação','Pendente')")->execute([$id,$UID]);
    $pres=$db->query("SELECT id FROM usuarios WHERE (role LIKE '%presidencia%' OR role LIKE '%admin%') AND active=1")->fetchAll();
    $dem=$db->prepare("SELECT title FROM demandas WHERE id=?"); $dem->execute([$id]); $demT=$dem->fetch()['title']??'';
    foreach($pres as $p){
        if($p['id']!=$UID){
            notify($p['id'],'presidency',"Demanda #{$id} reenviada para aprovação",'',"demand:{$id}",'demand',$id);
            sendPushToUser($db, (int)$p['id'], ['title'=>'📋 Aprovação Pendente','message'=>"Demanda reenviada: {$demT}",'url'=>'/index.php#aprovacoes']);
        }
    }
    logActivity($UID,"Reenviou #{$id} para aprovação",'demand',$id);
    jsonR(['success'=>true]);
}

// Comentário
if($act==='demand_comment'&&isset($_GET['id'])){
    $id=(int)$_GET['id']; $d=json_decode(file_get_contents('php://input'),true);
    $txt=trim($d['text']??''); if(!$txt) jsonR(['error'=>'Vazio'],400);
    $mentions=$d['mentions']??[];
    $db->prepare("INSERT INTO comentarios_demandas (demand_id,user_id,text) VALUES (?,?,?)")->execute([$id,$UID,$txt]);
    $dem=$db->prepare("SELECT title,created_by FROM demandas WHERE id=?"); $dem->execute([$id]); $demRow=$dem->fetch();
    $demTitle=$demRow['title']??'';
    $msg="{$ME['name']} comentou em: {$demTitle}";
    $notified=[$UID];
    // Notify mentioned usuarios first
    if(!empty($mentions)){
        $mentionMsg="{$ME['name']} mencionou você em: {$demTitle}";
        foreach($mentions as $mid){
            $mid=(int)$mid;
            if($mid && !in_array($mid,$notified)){
                notify($mid,'comment',$mentionMsg,$txt,"demand:{$id}",'demand',$id);
                sendPushToUser($db, $mid, ['title'=>'💬 Menção','message'=>$mentionMsg,'url'=>'/index.php#demandas']);
                $notified[]=$mid;
            }
        }
    }
    $devs=$db->prepare("SELECT user_id FROM devs_demandas WHERE demand_id=?"); $devs->execute([$id]);
    foreach($devs->fetchAll() as $dv){
        if(!in_array($dv['user_id'],$notified)){
            notify($dv['user_id'],'comment',$msg,$txt,"demand:{$id}",'demand',$id);
            sendPushToUser($db, (int)$dv['user_id'], ['title'=>'💬 Comentário','message'=>$msg,'url'=>'/index.php#demandas']);
            $notified[]=$dv['user_id'];
        }
    }
    $admins=$db->query("SELECT id FROM usuarios WHERE role LIKE '%admin%' AND active=1")->fetchAll();
    foreach($admins as $a){ if(!in_array($a['id'],$notified)){ notify($a['id'],'comment',$msg,$txt,"demand:{$id}",'demand',$id); $notified[]=$a['id']; }}
    if($demRow['created_by']&&!in_array($demRow['created_by'],$notified)) notify($demRow['created_by'],'comment',$msg,$txt,"demand:{$id}",'demand',$id);
    logActivity($UID,"Comentou: {$demTitle}",'demand',$id);
    jsonR(['success'=>true]);
}

// Upload imagem
if($act==='demand_upload'&&isset($_GET['id'])){
    $id=(int)$_GET['id'];
    if(!$IS_ADMIN&&!$IS_DIR){
        $chkDev=$db->prepare("SELECT 1 FROM devs_demandas WHERE demand_id=? AND user_id=?"); $chkDev->execute([$id,$UID]);
        if(!$chkDev->fetch()) jsonR(['error'=>'Sem permissão'],403);
    }
    if(empty($_FILES['image'])) jsonR(['error'=>'Sem arquivo'],400);
    $f=$_FILES['image']; if($f['error']!==0) jsonR(['error'=>'Erro upload: code '.$f['error']],400);
    if($f['size']>20*1024*1024) jsonR(['error'=>'Arquivo muito grande (max 20MB)'],400);
    $ext=strtolower(pathinfo($f['name'],PATHINFO_EXTENSION));
    $allExts=['png','jpg','jpeg','gif','webp','svg','bmp','ico','pdf','doc','docx','xls','xlsx','ppt','pptx','txt','md','csv','json','xml','html','css','js','php','py','sql','log','zip','rar','7z','mp4','mp3','wav','ogg','mov','avi'];
    if(!in_array($ext,$allExts)) jsonR(['error'=>'Formato nao permitido: '.$ext],400);
$fn='d'.$id.'_'.bin2hex(random_bytes(8)).'.'.strtolower(pathinfo($f['name'],PATHINFO_EXTENSION));
    $mime=mime_content_type($f["tmp_name"])?:"image/jpeg"; $data=file_get_contents($f["tmp_name"]); $db->prepare("INSERT INTO arquivos (nome_arquivo,nome_original,tipo_mime,dados,criado_por) VALUES (?,?,?,?,?)")->execute([$fn,$f["name"],$mime,$data,$UID]);
    $db->prepare("INSERT INTO imagens_demandas (demand_id,filename,original_name,uploaded_by) VALUES (?,?,?,?)")->execute([$id,$fn,$f['name'],$UID]);
    $db->prepare("INSERT INTO historico_demandas (demand_id,user_id,action,new_value) VALUES (?,?,'Imagem adicionada',?)")->execute([$id,$UID,$f['name']]);
    jsonR(['success'=>true,'filename'=>$fn]);
}

if($act==='demand_image_delete'){
    $demandId=(int)($_GET['demand_id']??0);$imgId=(int)($_GET['image_id']??0);
    if(!$demandId||!$imgId) jsonR(['error'=>'IDs obrigatórios'],400);
    if(!$IS_ADMIN&&!$IS_DIR){
        $chkDev=$db->prepare("SELECT 1 FROM devs_demandas WHERE demand_id=? AND user_id=?");$chkDev->execute([$demandId,$UID]);
        if(!$chkDev->fetch()) jsonR(['error'=>'Sem permissão'],403);
    }
    $img=$db->prepare("SELECT filename,original_name FROM imagens_demandas WHERE id=? AND demand_id=?");$img->execute([$imgId,$demandId]);$imgRow=$img->fetch();
    if(!$imgRow) jsonR(['error'=>'Imagem não encontrada'],404);
    $db->prepare("DELETE FROM imagens_demandas WHERE id=?")->execute([$imgId]);
    $db->prepare("DELETE FROM arquivos WHERE nome_arquivo=?")->execute([$imgRow['filename']]);
    $db->prepare("INSERT INTO historico_demandas (demand_id,user_id,action,new_value) VALUES (?,?,'Imagem removida',?)")->execute([$demandId,$UID,$imgRow['original_name']??$imgRow['filename']]);
    logActivity($UID,"Removeu imagem da demanda #{$demandId}",'demand',$demandId);
    jsonR(['success'=>true]);
}

// ===== DEPARTMENTS ===== (FIX: movido para nível top-level)
if($act==='departments'){
    if($method==='GET'){
        $s=$db->query("SELECT d.*, (SELECT COUNT(*) FROM usuarios u WHERE u.department_id=d.id) as user_count FROM departments d ORDER BY d.name");
        jsonR($s->fetchAll());
    }
    if($method==='POST'){
        if(!$IS_ADMIN) jsonR(['error'=>'Sem permissão'],403);
        $d=json_decode(file_get_contents('php://input'),true);
        $db->prepare("INSERT INTO departments (name,description) VALUES (?,?)")->execute([trim($d['name']??''),trim($d['description']??'')]);
        $newId=$db->lastInsertId();
        if(!empty($d['dev_ids'])){$db->prepare("UPDATE demandas SET status='Aguardando Aceite' WHERE id=? AND status='Aberta'")->execute([$newId]);}
        jsonR(['success'=>true,'id'=>$newId]);
    }
}
if($act==='department'&&isset($_GET['id'])){
    if($method==='DELETE'){
        if(!$IS_ADMIN) jsonR(['error'=>'Sem permissão'],403);
        $db->prepare("DELETE FROM departments WHERE id=?")->execute([$_GET['id']]);
        jsonR(['success'=>true]);
    }
    if($method==='PUT'){
        if(!$IS_ADMIN) jsonR(['error'=>'Sem permissão'],403);
        $d=json_decode(file_get_contents('php://input'),true);
        $db->prepare("UPDATE departments SET name=?,description=? WHERE id=?")->execute([trim($d['name']??''),trim($d['description']??''),$_GET['id']]);
        jsonR(['success'=>true]);
    }
}

// ===== DOCUMENTATIONS ===== (FIX: movido para nível top-level)
if($act==='docs'||$act==='doc'||$act==='doc_upload'||$act==='doc_file_delete'){
    try{
        $db->exec("CREATE TABLE IF NOT EXISTS documentations (id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(300) NOT NULL, description TEXT, content LONGTEXT, system_id INT DEFAULT NULL, category VARCHAR(100) DEFAULT 'Geral', password VARCHAR(255) DEFAULT NULL, password_plain VARCHAR(255) DEFAULT NULL, created_by INT NOT NULL, updated_by INT DEFAULT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $db->exec("CREATE TABLE IF NOT EXISTS doc_files (id INT AUTO_INCREMENT PRIMARY KEY, doc_id INT NOT NULL, filename VARCHAR(255) NOT NULL, original_name VARCHAR(255) NOT NULL, file_size BIGINT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $dbN2=DB_NAME;
        $existCols=$db->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='$dbN2' AND TABLE_NAME='documentations'")->fetchAll(PDO::FETCH_COLUMN);
        foreach(["description TEXT","content LONGTEXT","category VARCHAR(100) DEFAULT 'Geral'","password VARCHAR(255) DEFAULT NULL","password_plain VARCHAR(255) DEFAULT NULL","updated_by INT DEFAULT NULL","system_id INT DEFAULT NULL"] as $colDef){
            $cn=explode(' ',$colDef)[0];
            if(!in_array($cn,$existCols)) try{$db->exec("ALTER TABLE documentations ADD COLUMN $colDef");}catch(Exception $e){}
        }
    }catch(Exception $e){}
}
if($act==='docs'){
    if($method==='GET'){
        try{
            $sysId=$_GET['system_id']??''; $cat=$_GET['category']??'';
            $sql="SELECT d.id,d.title,d.description,d.system_id,d.category,d.password,d.password_plain,d.created_by,d.updated_by,d.created_at,d.updated_at,u.name as author_name,s.name as system_name FROM documentations d LEFT JOIN usuarios u ON d.created_by=u.id LEFT JOIN sistemas s ON d.system_id=s.id WHERE 1=1";
            $p=[];
            if($sysId){$sql.=" AND d.system_id=?";$p[]=$sysId;}
            if($cat){$sql.=" AND d.category=?";$p[]=$cat;}
            $sql.=" ORDER BY d.updated_at DESC";
            $s=$db->prepare($sql);$s->execute($p);
            $docs=$s->fetchAll();
            foreach($docs as &$doc){
                $doc['has_password']=!empty($doc['password']);
                unset($doc['password']);
                if($doc['created_by']!=$UID) unset($doc['password_plain']);
                try{$sf=$db->prepare("SELECT id,original_name,file_size,created_at FROM doc_files WHERE doc_id=?");$sf->execute([$doc['id']]);$doc['files']=$sf->fetchAll();}catch(Exception $e){$doc['files']=[];}
            }
            jsonR($docs);
        }catch(Exception $e){ jsonR([]); }
    }
    if($method==='POST'){
        try{
            $d=json_decode(file_get_contents('php://input'),true);
            $title=trim($d['title']??'');$desc=trim($d['description']??'');$content=$d['content']??'';
            $sysId=$d['system_id']?:null;$cat=$d['category']??'Geral';$pw=$d['password']??'';
            if(!$title) jsonR(['error'=>'Título obrigatório'],400);
            $pwHash=$pw?password_hash($pw,PASSWORD_BCRYPT):null;
            $pwPlain=$pw?:null;
            $db->prepare("INSERT INTO documentations (title,description,content,system_id,category,password,password_plain,created_by) VALUES (?,?,?,?,?,?,?,?)")
                ->execute([$title,$desc,$content,$sysId,$cat,$pwHash,$pwPlain,$UID]);
            $newId=$db->lastInsertId();
            logActivity($UID,'Criou documentação','documentation',$newId,'Título: '.$title.($pwPlain?' | Protegida':''));
            jsonR(['success'=>true,'id'=>$newId]);
        }catch(Exception $e){ jsonR(['error'=>$e->getMessage()],500); }
    }
}
if($act==='doc'&&isset($_GET['id'])){
    $docId=(int)$_GET['id'];
    if($method==='GET'){
        try{
            $s=$db->prepare("SELECT d.*,u.name as author_name,s.name as system_name FROM documentations d LEFT JOIN usuarios u ON d.created_by=u.id LEFT JOIN sistemas s ON d.system_id=s.id WHERE d.id=?");
            $s->execute([$docId]);$doc=$s->fetch();
            if(!$doc) jsonR(['error'=>'Não encontrado'],404);
            if(!empty($doc['password'])){
                $inputPw=$_GET['password']??'';
                if($doc['created_by']==$UID||$IS_ADMIN){$doc['locked']=false;$doc['password_visible']=$doc['password_plain']??null;}
                elseif(!password_verify($inputPw,$doc['password'])){unset($doc['content']);$doc['locked']=true;}
                else{$doc['locked']=false;}
            }else{$doc['locked']=false;}
            $doc['has_password']=!empty($doc['password']);unset($doc['password']);unset($doc['password_plain']);
            try{$sf=$db->prepare("SELECT id,original_name,filename,file_size,created_at FROM doc_files WHERE doc_id=?");$sf->execute([$docId]);$doc['files']=$sf->fetchAll();}catch(Exception $e){$doc['files']=[];}
            jsonR($doc);
        }catch(Exception $e){ jsonR(['error'=>$e->getMessage()],500); }
    }
    if($method==='PUT'){
        try{
            $d=json_decode(file_get_contents('php://input'),true);
            $sets=[];$params=[];
            if(isset($d['title'])){$sets[]='title=?';$params[]=$d['title'];}
            if(isset($d['description'])){$sets[]='description=?';$params[]=$d['description'];}
            if(isset($d['content'])){$sets[]='content=?';$params[]=$d['content'];}
            if(isset($d['system_id'])){$sets[]='system_id=?';$params[]=$d['system_id']?:null;}
            if(isset($d['category'])){$sets[]='category=?';$params[]=$d['category'];}
            if(array_key_exists('password',$d)){
                $sets[]='password=?';$params[]=$d['password']?password_hash($d['password'],PASSWORD_BCRYPT):null;
                $sets[]='password_plain=?';$params[]=$d['password']?:null;
            }
            if($sets){$sets[]='updated_by=?';$params[]=$UID;$params[]=$docId;
                $db->prepare("UPDATE documentations SET ".implode(',',$sets)." WHERE id=?")->execute($params);}
            logActivity($UID,'Editou documentação','documentation',$docId,'ID: '.$docId);
            jsonR(['success'=>true]);
        }catch(Exception $e){ jsonR(['error'=>$e->getMessage()],500); }
    }
    if($method==='DELETE'){
        try{
            $dt=$db->prepare("SELECT title,created_by FROM documentations WHERE id=?");$dt->execute([$docId]);$dtR=$dt->fetch();
            if($dtR&&$dtR['created_by']!=$UID&&!$IS_ADMIN) jsonR(['error'=>'Sem permissão'],403);
            $db->prepare("DELETE FROM documentations WHERE id=?")->execute([$docId]);
            $db->prepare("DELETE FROM doc_files WHERE doc_id=?")->execute([$docId]);
            logActivity($UID,'Excluiu documentação','documentation',$docId,'Título: '.($dtR['title']??'N/A'));
            jsonR(['success'=>true]);
        }catch(Exception $e){ jsonR(['error'=>$e->getMessage()],500); }
    }
}
if($act==='doc_upload'&&isset($_GET['id'])){
    $docId=(int)$_GET['id'];
    if(!empty($_FILES['file'])){
        try{
            $f=$_FILES['file'];
            if($f['error']!==UPLOAD_ERR_OK) jsonR(['error'=>'Erro upload: '.$f['error']],400);
            $ext=strtolower(pathinfo($f['name'],PATHINFO_EXTENSION));
            $allowed=['pdf','doc','docx','xls','xlsx','ppt','pptx','txt','md','png','jpg','jpeg','gif','zip','rar','csv'];
            if(!in_array($ext,$allowed)) jsonR(['error'=>'Tipo não permitido'],400);
            if($f['size']>20*1024*1024) jsonR(['error'=>'Máximo 20MB'],400);
            $fileData=file_get_contents($f['tmp_name']);
            $mime=$f['type']?:mime_content_type($f['tmp_name']);
            $db->prepare("INSERT INTO doc_files (doc_id,original_name,file_size,mime_type,file_data) VALUES (?,?,?,?,?)")
                ->execute([$docId,$f['name'],$f['size'],$mime,$fileData]);
            $fid=$db->lastInsertId();
            logActivity($UID,'Upload em documentação','documentation',$docId,$f['name']);
            jsonR(['success'=>true,'file'=>['id'=>$fid,'original_name'=>$f['name'],'file_size'=>$f['size']]]);
        }catch(Exception $e){ jsonR(['error'=>$e->getMessage()],500); }
    } else { jsonR(['error'=>'Nenhum arquivo'],400); }
}
if($act==='doc_file_download'&&isset($_GET['id'])){
    try{
        $fid=(int)$_GET['id'];
        $st=$db->prepare("SELECT original_name,mime_type,file_data FROM doc_files WHERE id=?");
        $st->execute([$fid]);$row=$st->fetch();
        if(!$row) jsonR(['error'=>'Não encontrado'],404);
        header('Content-Type: '.($row['mime_type']?:'application/octet-stream'));
        header('Content-Disposition: inline; filename="'.$row['original_name'].'"');
        header('Content-Length: '.strlen($row['file_data']));
        echo $row['file_data'];
        exit;
    }catch(Exception $e){ jsonR(['error'=>$e->getMessage()],500); }
}
if($act==='doc_file_delete'&&isset($_GET['id'])){
    try{
        $fid=(int)$_GET['id'];
        $df=$db->prepare("SELECT df.*,d.created_by FROM doc_files df JOIN documentations d ON df.doc_id=d.id WHERE df.id=?");
        $df->execute([$fid]);$dfR=$df->fetch();
        if(!$dfR) jsonR(['error'=>'Não encontrado'],404);
        if($dfR['created_by']!=$UID&&!$IS_ADMIN) jsonR(['error'=>'Sem permissão'],403);
        $db->prepare("DELETE FROM doc_files WHERE id=?")->execute([$fid]);
        logActivity($UID,'Removeu arquivo','documentation',$dfR['doc_id'],$dfR['original_name']);
        jsonR(['success'=>true]);
    }catch(Exception $e){ jsonR(['error'=>$e->getMessage()],500); }
}

// ===== SURVEYS ===== (FIX: movido para nível top-level)
if($act==='surveys'){
    if($method==='GET'){
        $sql="SELECT s.*,u.name as author_name,(SELECT COUNT(DISTINCT sv.user_id) FROM survey_votes sv WHERE sv.survey_id=s.id) as total_votes FROM surveys s LEFT JOIN usuarios u ON s.created_by=u.id ORDER BY s.created_at DESC";
        $s=$db->query($sql);$surveys=$s->fetchAll();
        foreach($surveys as &$sv){
            $so=$db->prepare("SELECT so.*,(SELECT COUNT(*) FROM survey_votes v WHERE v.option_id=so.id) as votes FROM survey_options so WHERE so.survey_id=? ORDER BY so.sort_order");
            $so->execute([$sv['id']]);$sv['options']=$so->fetchAll();
            $vc=$db->prepare("SELECT option_id FROM survey_votes WHERE survey_id=? AND user_id=?");$vc->execute([$sv['id'],$UID]);
            $sv['my_votes']=$vc->fetchAll(PDO::FETCH_COLUMN);
        }
        jsonR($surveys);
    }
    if($method==='POST'){
        if(!$IS_ADMIN&&!$IS_DIR) jsonR(['error'=>'Sem permissão'],403);
        $d=json_decode(file_get_contents('php://input'),true);
        $title=trim($d['title']??'');$desc=trim($d['description']??'');
        $type=$d['type']??'single';$anon=$d['anonymous']??0;$expires=$d['expires_at']??null;
        if(!$title) jsonR(['error'=>'Título obrigatório'],400);
        $db->prepare("INSERT INTO surveys (title,description,type,anonymous,expires_at,created_by) VALUES (?,?,?,?,?,?)")
            ->execute([$title,$desc,$type,$anon,$expires,$UID]);
        $sid=$db->lastInsertId();
        foreach(($d['options']??[]) as $i=>$opt){
            $db->prepare("INSERT INTO survey_options (survey_id,label,sort_order) VALUES (?,?,?)")->execute([$sid,trim($opt),$i]);
        }
        jsonR(['success'=>true,'id'=>$sid]);
    }
}
if($act==='survey_vote'&&isset($_GET['id'])){
    $sid=(int)$_GET['id'];
    $d=json_decode(file_get_contents('php://input'),true);
    $optIds=$d['option_ids']??[];
    $sv=$db->prepare("SELECT type FROM surveys WHERE id=?");$sv->execute([$sid]);$svType=$sv->fetchColumn();
    $db->prepare("DELETE FROM survey_votes WHERE survey_id=? AND user_id=?")->execute([$sid,$UID]);
    foreach($optIds as $oid){
        try{$db->prepare("INSERT INTO survey_votes (survey_id,option_id,user_id) VALUES (?,?,?)")->execute([$sid,$oid,$UID]);}catch(\Exception $e){}
    }
    jsonR(['success'=>true]);
}
if($act==='survey_toggle'&&isset($_GET['id'])){
    if(!$IS_ADMIN&&!$IS_DIR) jsonR(['error'=>'Sem permissão'],403);
    $db->prepare("UPDATE surveys SET active=NOT active WHERE id=?")->execute([$_GET['id']]);
    jsonR(['success'=>true]);
}
if($act==='survey_delete'&&isset($_GET['id'])){
    if(!$IS_ADMIN&&!$IS_DIR) jsonR(['error'=>'Sem permissão'],403);
    $db->prepare("DELETE FROM surveys WHERE id=?")->execute([$_GET['id']]);
    jsonR(['success'=>true]);
}

// ===== REPORTS: DEPARTMENT STATS =====
if($act==='reports'&&($_GET['type']??'')==='by_department'){
    $df=$_GET['date_from']??date('Y-m-d',strtotime('-90 days'));
    $dt=$_GET['date_to']??date('Y-m-d').' 23:59:59';
    $sql="SELECT dep.id,dep.name as department,
        COUNT(DISTINCT d.id) as total_demands,
        SUM(CASE WHEN d.status='Concluída' THEN 1 ELSE 0 END) as concluidas,
        SUM(CASE WHEN d.status NOT IN('Concluída','Cancelada') THEN 1 ELSE 0 END) as ativas,
        AVG(CASE WHEN d.completed_at IS NOT NULL THEN DATEDIFF(d.completed_at,d.created_at) END) as avg_days,
        COUNT(DISTINCT u.id) as total_users
        FROM departments dep
        LEFT JOIN usuarios u ON u.department_id=dep.id
        LEFT JOIN demandas d ON d.requested_by=u.id AND d.created_at BETWEEN ? AND ?
        GROUP BY dep.id,dep.name ORDER BY total_demands DESC";
    $s=$db->prepare($sql);$s->execute([$df,$dt]);
    jsonR($s->fetchAll());
}

// ── SYSTEM DETAIL (comprehensive) ────────────────────────────────────────
if ($act === 'system_detail' && isset($_GET['id'])) {
    try {
        $sysId = (int)$_GET['id'];
        $st = $db->prepare("SELECT s.*,
            GROUP_CONCAT(DISTINCT u.name ORDER BY u.name SEPARATOR ', ') as dev_names,
            GROUP_CONCAT(DISTINCT u.avatar_color ORDER BY u.name SEPARATOR ',') as dev_colors,
            GROUP_CONCAT(DISTINCT IFNULL(u.avatar_file,'') ORDER BY u.name SEPARATOR ',') as dev_avatars,
            GROUP_CONCAT(DISTINCT IFNULL(u.role,'dev') ORDER BY u.name SEPARATOR '|') as dev_roles,
            GROUP_CONCAT(DISTINCT u.id ORDER BY u.name SEPARATOR ',') as dev_ids
            FROM sistemas s
            LEFT JOIN devs_sistemas sd ON s.id = sd.system_id
            LEFT JOIN usuarios u ON sd.user_id = u.id
            WHERE s.id = ?
            GROUP BY s.id");
        $st->execute([$sysId]);
        $sys = $st->fetch(PDO::FETCH_ASSOC);
        if (!$sys) jsonR(['error' => 'Sistema não encontrado'], 404);

        $stD = $db->prepare("SELECT d.id, d.title, d.status, d.priority, d.deadline, d.start_date,
            d.created_at, d.completed_at, d.requester,
            d.needs_presidency_approval, d.presidency_status
            FROM demandas d WHERE d.system_id = ? ORDER BY d.created_at DESC");
        $stD->execute([$sysId]);
        $demands = $stD->fetchAll(PDO::FETCH_ASSOC);

        foreach ($demands as &$dem) {
            $stDv = $db->prepare("SELECT dd.user_id, u.name, u.avatar_color, u.avatar_file, u.role, dd.acceptance
                FROM devs_demandas dd JOIN usuarios u ON dd.user_id = u.id WHERE dd.demand_id = ?");
            $stDv->execute([$dem['id']]);
            $dem['devs'] = $stDv->fetchAll(PDO::FETCH_ASSOC);
        }

        $stDoc = $db->prepare("SELECT d.id, d.title, d.description, d.category, d.password, d.created_at, d.updated_at,
            u.name as author_name,
            (SELECT COUNT(*) FROM doc_files df WHERE df.doc_id = d.id) as file_count
            FROM documentations d
            LEFT JOIN usuarios u ON d.created_by = u.id
            WHERE d.system_id = ? ORDER BY d.updated_at DESC");
        $stDoc->execute([$sysId]);
        $docs = $stDoc->fetchAll(PDO::FETCH_ASSOC);
        foreach ($docs as &$doc) {
            $doc['has_password'] = !empty($doc['password']);
            unset($doc['password']);
        }

        $history = [];
        try {
            $stH = $db->prepare("SELECT h.*, u.name as user_name, d.title as demand_title
                FROM registro_atividades h
                LEFT JOIN usuarios u ON h.user_id = u.id
                LEFT JOIN demandas d ON h.entity_type = 'demand' AND h.entity_id = d.id
                WHERE (h.entity_type = 'demand' AND h.entity_id IN (SELECT id FROM demandas WHERE system_id = ?))
                   OR (h.entity_type = 'documentation' AND h.entity_id IN (SELECT id FROM documentations WHERE system_id = ?))
                   OR (h.entity_type = 'system' AND h.entity_id = ?)
                ORDER BY h.created_at DESC
                LIMIT 100");
            $stH->execute([$sysId, $sysId, $sysId]);
            $history = $stH->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) { $history = []; }

        $total = count($demands);
        $done = count(array_filter($demands, fn($d) => $d['status'] === 'Concluída'));
        $active = count(array_filter($demands, fn($d) => in_array($d['status'], ['Em Andamento', 'Em Revisão', 'Aguardando Aceite'])));
        $urgent = count(array_filter($demands, fn($d) => $d['priority'] === 'Urgente' && !in_array($d['status'], ['Concluída', 'Cancelada'])));

        $avgDays = null;
        $completedWithDates = array_filter($demands, fn($d) => $d['status'] === 'Concluída' && $d['completed_at'] && $d['created_at']);
        if (count($completedWithDates) > 0) {
            $totalDays = 0;
            foreach ($completedWithDates as $cd) {
                $totalDays += (strtotime($cd['completed_at']) - strtotime($cd['created_at'])) / 86400;
            }
            $avgDays = round($totalDays / count($completedWithDates), 1);
        }

        $slaTotal = 0; $slaOnTime = 0;
        foreach ($demands as $dd) {
            if ($dd['deadline'] && $dd['status'] === 'Concluída' && $dd['completed_at']) {
                $slaTotal++;
                if (strtotime($dd['completed_at']) <= strtotime($dd['deadline'] . ' 23:59:59')) $slaOnTime++;
            }
        }

        // Comments from all demands of this system
        $comments = [];
        $demandIds = array_column($demands, 'id');
        if (!empty($demandIds)) {
            $ph = implode(',', array_fill(0, count($demandIds), '?'));
            $stC = $db->prepare("SELECT c.*, u.name as user_name, u.avatar_color, d.title as demand_title, d.id as demand_id
                FROM comentarios_demandas c
                LEFT JOIN usuarios u ON c.user_id = u.id
                LEFT JOIN demandas d ON c.demand_id = d.id
                WHERE c.demand_id IN ($ph)
                ORDER BY c.created_at DESC LIMIT 50");
            $stC->execute($demandIds);
            $comments = $stC->fetchAll(PDO::FETCH_ASSOC);
        }
        // Files from all demands of this system
        $sysFiles = [];
        if (!empty($demandIds)) {
            $stF = $db->prepare("SELECT i.*, u.name as uploader_name, d.title as demand_title, d.id as demand_id
                FROM imagens_demandas i
                LEFT JOIN usuarios u ON i.uploaded_by = u.id
                LEFT JOIN demandas d ON i.demand_id = d.id
                WHERE i.demand_id IN ($ph)
                ORDER BY i.created_at DESC");
            $stF->execute($demandIds);
            $sysFiles = $stF->fetchAll(PDO::FETCH_ASSOC);
        }

        $sys['demands'] = $demands;
        $sys['docs'] = $docs;
        $sys['history'] = $history;
        $sys['comments'] = $comments;
        $sys['files'] = $sysFiles;
        $sys['stats'] = [
            'total' => $total, 'done' => $done, 'active' => $active, 'urgent' => $urgent,
            'avg_days' => $avgDays, 'sla_total' => $slaTotal, 'sla_on_time' => $slaOnTime
        ];
        jsonR($sys);
    } catch (Exception $e) {
        jsonR(['error' => $e->getMessage()], 500);
    }
}

// ===== SYSTEMS =====
if($act==='sistemas'){
    if($method==='GET'){
        $s=$db->query("SELECT s.*, GROUP_CONCAT(DISTINCT u.name ORDER BY u.name SEPARATOR ', ') as dev_names, GROUP_CONCAT(DISTINCT u.id ORDER BY u.name) as dev_ids, GROUP_CONCAT(DISTINCT u.avatar_color ORDER BY u.name) as dev_colors, GROUP_CONCAT(DISTINCT IFNULL(u.avatar_file,'') ORDER BY u.name SEPARATOR ',') as dev_avatars, GROUP_CONCAT(DISTINCT u.role ORDER BY u.name SEPARATOR '|') as dev_roles FROM sistemas s LEFT JOIN devs_sistemas sd ON s.id=sd.system_id LEFT JOIN usuarios u ON sd.user_id=u.id GROUP BY s.id ORDER BY s.name");
        jsonR($s->fetchAll());
    }
    if($method==='POST'){
        if(!$IS_ADMIN) jsonR(['error'=>'Sem permissão'],403);
        $d=json_decode(file_get_contents('php://input'),true);
        $name=trim($d['name']??''); if(!$name) jsonR(['error'=>'Nome obrigatório'],400);
        $db->prepare("INSERT INTO sistemas (name,description,technology,status,url,github_url,department) VALUES (?,?,?,?,?,?,?)")
            ->execute([$name,$d['description']??'',$d['technology']??'PHP',$d['status']??'Em desenvolvimento',$d['url']??'',$d['github_url']??'',$d['department']??'']);
        $sid=$db->lastInsertId();
        if(!empty($d['dev_ids'])){ $ins=$db->prepare("INSERT INTO devs_sistemas (system_id,user_id) VALUES (?,?)"); foreach($d['dev_ids'] as $di) $ins->execute([$sid,$di]); }
        logActivity($UID,"Criou sistema: {$name}",'system',$sid);
        jsonR(['success'=>true,'id'=>$sid],201);
    }
}
if($act==='system'&&isset($_GET['id'])){
    $id=(int)$_GET['id'];
    if($method==='PUT'){
        if(!$IS_ADMIN) jsonR(['error'=>'Sem permissão'],403);
        $d=json_decode(file_get_contents('php://input'),true);
        $db->prepare("UPDATE sistemas SET name=?,description=?,technology=?,status=?,url=?,github_url=?,department=? WHERE id=?")
            ->execute([$d['name']??'',$d['description']??'',$d['technology']??'PHP',$d['status']??'Em uso',$d['url']??'',$d['github_url']??'',$d['department']??'',$id]);
        $db->prepare("DELETE FROM devs_sistemas WHERE system_id=?")->execute([$id]);
        if(!empty($d['dev_ids'])){ $ins=$db->prepare("INSERT INTO devs_sistemas (system_id,user_id) VALUES (?,?)"); foreach($d['dev_ids'] as $di) $ins->execute([$id,$di]); }
        jsonR(['success'=>true]);
    }
    if($method==='DELETE'){ if(!$IS_ADMIN) jsonR(['error'=>'Sem permissão'],403); $db->prepare("DELETE FROM sistemas WHERE id=?")->execute([$id]); jsonR(['success'=>true]); }
}

// ===== USERS =====
if($act==='usuarios'){
    if($method==='GET'){
        if(($IS_DEV||$IS_USER)&&!$IS_ADMIN&&!$IS_DIR){ $s=$db->prepare("SELECT id,name,email,role,avatar_color,avatar_file,active,created_at FROM usuarios WHERE id=?"); $s->execute([$UID]); }
        else $s=$db->query("SELECT id,name,email,role,avatar_color,avatar_file,active,created_at FROM usuarios WHERE active=1 ORDER BY role,name");
        jsonR($s->fetchAll());
    }
    if($method==='POST'){
        if(!$IS_ADMIN) jsonR(['error'=>'Sem permissão'],403);
        $d=json_decode(file_get_contents('php://input'),true);
        $name=trim($d['name']??''); $email=trim($d['email']??''); $pass=$d['password']??'';
        if(!$name||!$email||!$pass) jsonR(['error'=>'Campos obrigatórios'],400);
        $chk=$db->prepare("SELECT id FROM usuarios WHERE email=?"); $chk->execute([$email]); if($chk->fetch()) jsonR(['error'=>'Email já existe'],400);
        $db->prepare("INSERT INTO usuarios (name,email,password,role,avatar_color) VALUES (?,?,?,?,?)")->execute([$name,$email,password_hash($pass,PASSWORD_DEFAULT),$d['role']??'dev',$d['avatar_color']??'#3b82f6']);
        jsonR(['success'=>true,'id'=>$db->lastInsertId()],201);
    }
}
if($act==='user'&&isset($_GET['id'])){
    $tid=(int)$_GET['id'];
    if($IS_DEV&&$tid!=$UID) jsonR(['error'=>'Acesso negado'],403);
    if($method==='PUT'){
        if(!$IS_ADMIN&&$tid!=$UID) jsonR(['error'=>'Acesso negado'],403);
        $d=json_decode(file_get_contents('php://input'),true); $f=[]; $p=[];
        if(isset($d['name'])){$f[]='name=?';$p[]=$d['name'];}
        if(isset($d['email'])){$f[]='email=?';$p[]=$d['email'];}
        if(isset($d['avatar_color'])){$f[]='avatar_color=?';$p[]=$d['avatar_color'];}
        if($IS_ADMIN){ if(isset($d['role'])){$f[]='role=?';$p[]=$d['role'];} if(isset($d['active'])){$f[]='active=?';$p[]=$d['active']?1:0;} }
        if(!empty($d['password'])){$f[]='password=?';$p[]=password_hash($d['password'],PASSWORD_DEFAULT);}
        if($f){ $p[]=$tid; $db->prepare("UPDATE usuarios SET ".implode(',',$f)." WHERE id=?")->execute($p); }
        jsonR(['success'=>true]);
    }
    if($method==='DELETE'){ if(!$IS_ADMIN) jsonR(['error'=>'Sem permissão'],403); $db->prepare("UPDATE usuarios SET active=0 WHERE id=?")->execute([$tid]); jsonR(['success'=>true]); }
}

// ── DEV DETAIL (comprehensive profile) ──────────────────────────────────
if ($act === 'dev_detail' && isset($_GET['id'])) {
    try {
        $devId = (int)$_GET['id'];
        if(!$IS_ADMIN && !$IS_DIR && $devId != $UID) jsonR(['error'=>'Acesso negado'],403);

        $st = $db->prepare("SELECT id,name,email,role,avatar_color,avatar_file,last_login,created_at,active FROM usuarios WHERE id=?");
        $st->execute([$devId]);
        $user = $st->fetch(PDO::FETCH_ASSOC);
        if (!$user) jsonR(['error' => 'Usuário não encontrado'], 404);

        $stD = $db->prepare("SELECT d.id, d.title, d.status, d.priority, d.deadline, d.start_date,
            d.created_at, d.completed_at, d.requester, d.description,
            s.name as system_name, s.id as system_id,
            dd.acceptance, dd.assigned_at, dd.rejection_reason
            FROM demandas d
            JOIN devs_demandas dd ON d.id = dd.demand_id
            LEFT JOIN sistemas s ON d.system_id = s.id
            WHERE dd.user_id = ?
            ORDER BY d.created_at DESC");
        $stD->execute([$devId]);
        $demands = $stD->fetchAll(PDO::FETCH_ASSOC);

        $total = count($demands);
        $concluidas = count(array_filter($demands, fn($d) => $d['status'] === 'Concluída'));
        $ativas = count(array_filter($demands, fn($d) => in_array($d['status'], ['Aberta','Aguardando Aceite','Em Andamento','Em Revisão'])));

        $avgDays = null;
        $completed = array_filter($demands, fn($d) => $d['status'] === 'Concluída' && $d['completed_at'] && $d['created_at']);
        if (count($completed) > 0) {
            $totalDays = 0;
            foreach ($completed as $cd) {
                $totalDays += (strtotime($cd['completed_at']) - strtotime($cd['created_at'])) / 86400;
            }
            $avgDays = round($totalDays / count($completed), 1);
        }

        $accSt = $db->prepare("SELECT acceptance, COUNT(*) as c FROM devs_demandas WHERE user_id=? GROUP BY acceptance");
        $accSt->execute([$devId]);
        $accRaw = $accSt->fetchAll(PDO::FETCH_ASSOC);
        $acceptances = [];
        foreach ($accRaw as $a) { $acceptances[$a['acceptance']] = (int)$a['c']; }

        $relCount = 0;
        try {
            $relSt = $db->prepare("SELECT COUNT(*) as c FROM relatorios_diarios WHERE user_id=?");
            $relSt->execute([$devId]);
            $relCount = (int)$relSt->fetch()['c'];
        } catch (Exception $e) {}

        $monthlySt = $db->prepare("SELECT DATE_FORMAT(d.completed_at,'%Y-%m') as month, COUNT(*) as total
            FROM demandas d JOIN devs_demandas dd ON d.id=dd.demand_id
            WHERE dd.user_id=? AND d.status='Concluída' AND d.completed_at IS NOT NULL
            GROUP BY month ORDER BY month DESC LIMIT 12");
        $monthlySt->execute([$devId]);
        $monthly = $monthlySt->fetchAll(PDO::FETCH_ASSOC);

        $sysSt = $db->prepare("SELECT s.id, s.name, s.status, s.url, s.technology,
            (SELECT COUNT(*) FROM demandas WHERE system_id=s.id) as demand_count,
            (SELECT COUNT(*) FROM documentations WHERE system_id=s.id) as doc_count
            FROM sistemas s JOIN devs_sistemas sd ON s.id = sd.system_id
            WHERE sd.user_id = ?
            ORDER BY s.name");
        $sysSt->execute([$devId]);
        $sistemas = $sysSt->fetchAll(PDO::FETCH_ASSOC);

        $activity = [];
        try {
            $actSt = $db->prepare("SELECT a.*, d.title as demand_title
                FROM registro_atividades a
                LEFT JOIN demandas d ON a.entity_type='demand' AND a.entity_id = d.id
                WHERE a.user_id = ?
                ORDER BY a.created_at DESC
                LIMIT 100");
            $actSt->execute([$devId]);
            $activity = $actSt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) { $activity = []; }

        $user['demands'] = $demands;
        $user['sistemas'] = $sistemas;
        $user['activity'] = $activity;
        $user['monthly'] = $monthly;
        $user['acceptances'] = $acceptances;
        $user['stats'] = [
            'total' => $total, 'concluidas' => $concluidas, 'ativas' => $ativas,
            'avg_completion_days' => $avgDays, 'relatorios_diarios' => $relCount
        ];
        jsonR($user);
    } catch (Exception $e) {
        jsonR(['error' => $e->getMessage()], 500);
    }
}

if($act==='user_stats'&&isset($_GET['id'])){
    $tid=(int)$_GET['id'];
    if(!$IS_ADMIN&&!$IS_DIR&&$tid!=$UID) jsonR(['error'=>'Acesso negado'],403);
    $s=$db->prepare("SELECT COUNT(*) as total, COALESCE(SUM(CASE WHEN status IN('Aberta','Aguardando Aceite','Em Andamento','Em Revisão') THEN 1 ELSE 0 END),0) as ativas, COALESCE(SUM(CASE WHEN status='Concluída' THEN 1 ELSE 0 END),0) as concluidas FROM demandas WHERE id IN (SELECT demand_id FROM devs_demandas WHERE user_id=?)");
    $s->execute([$tid]); $st=$s->fetch();
    $s=$db->prepare("SELECT s.id,s.name FROM sistemas s JOIN devs_sistemas sd ON s.id=sd.system_id WHERE sd.user_id=?"); $s->execute([$tid]); $st['sistemas']=$s->fetchAll();
    $s=$db->prepare("SELECT acceptance,COUNT(*) as c FROM devs_demandas WHERE user_id=? GROUP BY acceptance"); $s->execute([$tid]); $st['acceptances']=$s->fetchAll();
    $s=$db->prepare("SELECT AVG(DATEDIFF(d.completed_at,d.created_at)) as avg_days FROM demandas d JOIN devs_demandas dd ON d.id=dd.demand_id WHERE dd.user_id=? AND d.status='Concluída' AND d.completed_at IS NOT NULL");
    $s->execute([$tid]); $st['avg_completion_days']=$s->fetch()['avg_days'];
    $s=$db->prepare("SELECT COUNT(*) as c FROM relatorios_diarios WHERE user_id=?"); $s->execute([$tid]); $st['relatorios_diarios']=$s->fetch()['c'];
    $s=$db->prepare("SELECT DATE_FORMAT(d.completed_at,'%Y-%m') as month, COUNT(*) as total FROM demandas d JOIN devs_demandas dd ON d.id=dd.demand_id WHERE dd.user_id=? AND d.status='Concluída' AND d.completed_at IS NOT NULL GROUP BY month ORDER BY month DESC LIMIT 12");
    $s->execute([$tid]); $st['monthly']=$s->fetchAll();
    jsonR($st);
}
// ===== TEAM REALTIME (Dashboard) =====
if($act==='team_realtime'){
    $s=$db->prepare("SELECT u.id, u.name, u.avatar_color, u.avatar_file, u.role, u.last_login,
        COUNT(DISTINCT CASE WHEN d.status='Aberta' THEN d.id END) as abertas,
        COUNT(DISTINCT CASE WHEN d.status='Aguardando Aceite' THEN d.id END) as aguardando,
        COUNT(DISTINCT CASE WHEN d.status='Em Andamento' THEN d.id END) as andamento,
        COUNT(DISTINCT CASE WHEN d.status='Em Revisão' THEN d.id END) as revisao,
        COUNT(DISTINCT CASE WHEN d.status='Concluída' THEN d.id END) as concluidas,
        COUNT(DISTINCT CASE WHEN d.status NOT IN('Concluída','Cancelada') THEN d.id END) as ativas,
        (SELECT GROUP_CONCAT(DISTINCT s2.name ORDER BY s2.name SEPARATOR ', ')
         FROM devs_sistemas ds JOIN sistemas s2 ON ds.system_id=s2.id WHERE ds.user_id=u.id) as sistemas
    FROM usuarios u
    LEFT JOIN devs_demandas dd ON u.id=dd.user_id
    LEFT JOIN demandas d ON dd.demand_id=d.id
    WHERE u.active=1 AND u.role LIKE '%dev%'
    GROUP BY u.id
    ORDER BY ativas DESC, u.name");
    $s->execute();
    jsonR($s->fetchAll());
}

if($act==='dev_list'){
    $s=$db->query("SELECT id,name,avatar_color,avatar_file,role,work_hours FROM usuarios WHERE active=1 AND role LIKE '%dev%' ORDER BY name");
    jsonR($s->fetchAll());
}
if($act==='all_users_list'){
    $s=$db->query("SELECT id,name,avatar_color,avatar_file,role,work_hours FROM usuarios WHERE active=1 ORDER BY name");
    jsonR($s->fetchAll());
}

// ===== MY PROFILE (dev history) =====
if($act==='my_history'){
    $s=$db->prepare("SELECT d.id,d.title,d.status,d.priority,d.created_at,d.completed_at,s.name as system_name,dd.acceptance,dd.assigned_at FROM demandas d JOIN devs_demandas dd ON d.id=dd.demand_id LEFT JOIN sistemas s ON d.system_id=s.id WHERE dd.user_id=? ORDER BY d.id DESC");
    $s->execute([$UID]); jsonR($s->fetchAll());
}

// ===== NOTICES =====
if($act==='avisos'){
    if($method==='GET'){
        $w=["n.active=1","(n.expires_at IS NULL OR n.expires_at>=CURDATE())"]; $p=[];
        $targetRoles=["'todos'"];
        if($IS_ADMIN) $targetRoles[]="'admin'";
        if($IS_DEV) $targetRoles[]="'dev'";
        if($IS_DIR) $targetRoles[]="'diretor'";
        if($IS_PRES) $targetRoles[]="'presidencia'";
        if($IS_USER) $targetRoles[]="'usuario'";
        $w[]="n.target_role IN(".implode(',',$targetRoles).")";
        $s=$db->prepare("SELECT n.*,u.name as author_name FROM avisos n LEFT JOIN usuarios u ON n.created_by=u.id WHERE ".implode(' AND ',$w)." ORDER BY n.pinned DESC,n.created_at DESC");
        $s->execute($p); jsonR($s->fetchAll());
    }
    if($method==='POST'){
        if(!$IS_ELEVATED) jsonR(['error'=>'Sem permissão'],403);
        $d=json_decode(file_get_contents('php://input'),true);
        $db->prepare("INSERT INTO avisos (title,content,priority,target_role,created_by,pinned,expires_at,show_board,show_calendar,event_date) VALUES (?,?,?,?,?,?,?,?,?,?)")
            ->execute([trim($d['title']??''),trim($d['content']??''),$d['priority']??'normal',$d['target_role']??'todos',$UID,$d['pinned']?1:0,$d['expires_at']?:null,$d['show_board']?1:0,$d['show_calendar']?1:0,$d['event_date']?:null]);
        $nid=$db->lastInsertId();
        $noticeTitle=trim($d['title']??'');
        $targetRole=$d['target_role']??'todos';
        $w='active=1 AND id!=?';$p=[$UID];
        if($targetRole!=='todos'){$w.=" AND role LIKE ?";$p[]="%{$targetRole}%";}
        $targets=$db->prepare("SELECT id FROM usuarios WHERE {$w}");$targets->execute($p);
        foreach($targets->fetchAll() as $t){
            notify($t['id'],'notice',"Novo aviso: {$noticeTitle}",'',"notice:{$nid}",'notice',$nid);
            sendPushToUser($db, (int)$t['id'], ['title'=>'📢 Novo Aviso','message'=>$noticeTitle,'url'=>'/index.php#avisos']);
        }
        logActivity($UID,"Aviso: {$noticeTitle}",'notice',$nid);
        jsonR(['success'=>true,'id'=>$nid],201);
    }
}
if($act==='notice'&&isset($_GET['id'])){
    $id=(int)$_GET['id'];
    if($method==='GET'){
        $s=$db->prepare("SELECT n.*,u.name as author_name FROM avisos n LEFT JOIN usuarios u ON n.created_by=u.id WHERE n.id=?"); $s->execute([$id]); $n=$s->fetch();
        if($n) jsonR($n); else jsonR(['error'=>'Não encontrado'],404);
    }
    if($method==='PUT'){
        if(!$IS_ELEVATED) jsonR(['error'=>'Sem permissão'],403);
        $d=json_decode(file_get_contents('php://input'),true);
        $db->prepare("UPDATE avisos SET title=?,content=?,priority=?,target_role=?,pinned=?,expires_at=?,show_board=?,show_calendar=?,event_date=? WHERE id=?")
            ->execute([$d['title'],$d['content'],$d['priority']??'normal',$d['target_role']??'todos',$d['pinned']?1:0,$d['expires_at']?:null,$d['show_board']?1:0,$d['show_calendar']?1:0,$d['event_date']?:null,$id]);
        jsonR(['success'=>true]);
    }
    if($method==='DELETE'){ if(!$IS_ADMIN) jsonR(['error'=>'Sem permissão'],403); $db->prepare("UPDATE avisos SET active=0 WHERE id=?")->execute([$id]); jsonR(['success'=>true]); }
}

// ===== MEETINGS =====
if($act==='reunioes'){
    if($method==='GET'){
        if($IS_DEV||$IS_USER){
            $sql="SELECT m.*,u.name as creator_name,GROUP_CONCAT(DISTINCT u2.name SEPARATOR ', ') as participant_names,GROUP_CONCAT(DISTINCT u2.id) as participant_ids FROM reunioes m LEFT JOIN usuarios u ON m.created_by=u.id LEFT JOIN participantes_reunioes mp ON m.id=mp.meeting_id LEFT JOIN usuarios u2 ON mp.user_id=u2.id WHERE m.id IN(SELECT meeting_id FROM participantes_reunioes WHERE user_id=?) OR m.created_by=? GROUP BY m.id ORDER BY m.meeting_date DESC,m.meeting_time DESC";
            $s=$db->prepare($sql); $s->execute([$UID,$UID]);
        } else {
            $s=$db->query("SELECT m.*,u.name as creator_name,GROUP_CONCAT(DISTINCT u2.name SEPARATOR ', ') as participant_names,GROUP_CONCAT(DISTINCT u2.id) as participant_ids FROM reunioes m LEFT JOIN usuarios u ON m.created_by=u.id LEFT JOIN participantes_reunioes mp ON m.id=mp.meeting_id LEFT JOIN usuarios u2 ON mp.user_id=u2.id GROUP BY m.id ORDER BY m.meeting_date DESC,m.meeting_time DESC");
        }
        jsonR($s->fetchAll());
    }
    if($method==='POST'){
        if(!$IS_ADMIN&&!$IS_DEV&&!$IS_DIR) jsonR(['error'=>'Apenas administradores podem agendar reuniões'],403);
        $d=json_decode(file_get_contents('php://input'),true);
        $db->prepare("INSERT INTO reunioes (title,description,meeting_date,meeting_time,duration_minutes,location,is_online,online_link,created_by) VALUES (?,?,?,?,?,?,?,?,?)")
            ->execute([trim($d['title']??''),$d['description']??'',$d['meeting_date'],$d['meeting_time']??'10:00',$d['duration_minutes']??60,$d['location']??'Sala TI',$d['is_online']?1:0,$d['online_link']??'',$UID]);
        $mid=$db->lastInsertId();
        if(!empty($d['participant_ids'])){
            $ins=$db->prepare("INSERT INTO participantes_reunioes (meeting_id,user_id) VALUES (?,?)");
            foreach($d['participant_ids'] as $pid){
                $ins->execute([$mid,$pid]);
                notify($pid,'meeting',"Reunião agendada: ".trim($d['title']??''),date('d/m',strtotime($d['meeting_date']))." às ".substr($d['meeting_time']??'10:00',0,5),"meeting:{$mid}",'meeting',$mid);
                sendPushToUser($db, (int)$pid, ['title'=>'📅 Reunião Agendada','message'=>trim($d['title']??'').' — '.date('d/m',strtotime($d['meeting_date'])).' '.substr($d['meeting_time']??'10:00',0,5),'url'=>'/index.php#reunioes']);
            }
        }
        jsonR(['success'=>true,'id'=>$mid],201);
    }
}
if($act==='meeting'&&isset($_GET['id'])){
    $id=(int)$_GET['id'];
    if($method==='PUT'){
        if(!$IS_ADMIN&&!$IS_DEV&&!$IS_DIR) jsonR(['error'=>'Apenas administradores podem editar reuniões'],403);
        $d=json_decode(file_get_contents('php://input'),true);
        $oldParts=$db->prepare("SELECT user_id FROM participantes_reunioes WHERE meeting_id=?"); $oldParts->execute([$id]); $oldIds=array_column($oldParts->fetchAll(),'user_id');
        $db->prepare("UPDATE reunioes SET title=?,description=?,meeting_date=?,meeting_time=?,duration_minutes=?,location=?,is_online=?,online_link=?,status=? WHERE id=?")
            ->execute([$d['title'],$d['description']??'',$d['meeting_date'],$d['meeting_time'],$d['duration_minutes']??60,$d['location']??'Sala TI',$d['is_online']?1:0,$d['online_link']??'',$d['status']??'Agendada',$id]);
        $db->prepare("DELETE FROM participantes_reunioes WHERE meeting_id=?")->execute([$id]);
        $newIds=$d['participant_ids']??[];
        if($newIds){
            $ins=$db->prepare("INSERT INTO participantes_reunioes (meeting_id,user_id) VALUES (?,?)");
            foreach($newIds as $pid){
                $ins->execute([$id,$pid]);
                if(in_array($pid,$oldIds)){
                    if($pid!=$UID){
                        notify($pid,'meeting_updated',"Reunião atualizada: ".trim($d['title']??''),date('d/m',strtotime($d['meeting_date']))." às ".substr($d['meeting_time']??'10:00',0,5),"meeting:{$id}",'meeting',$id);
                        sendPushToUser($db, (int)$pid, ['title'=>'📅 Reunião Atualizada','message'=>trim($d['title']??'').' — '.date('d/m',strtotime($d['meeting_date'])),'url'=>'/index.php#reunioes']);
                    }
                } else {
                    notify($pid,'meeting',"Você foi adicionado à reunião: ".trim($d['title']??''),date('d/m',strtotime($d['meeting_date']))." às ".substr($d['meeting_time']??'10:00',0,5),"meeting:{$id}",'meeting',$id);
                    sendPushToUser($db, (int)$pid, ['title'=>'📅 Nova Reunião','message'=>'Você foi adicionado: '.trim($d['title']??''),'url'=>'/index.php#reunioes']);
                }
            }
        }
        logActivity($UID,"Editou reunião #{$id}",'meeting',$id);
        jsonR(['success'=>true]);
    }
    if($method==='DELETE'){ if(!$IS_ADMIN&&!$IS_DEV&&!$IS_DIR) jsonR(['error'=>'Sem permissão'],403); $db->prepare("DELETE FROM reunioes WHERE id=?")->execute([$id]); logActivity($UID,"Excluiu reunião #{$id}",'meeting',$id); jsonR(['success'=>true]); }
}

// ===== SOLICITATIONS =====
if($act==='solicitacoes'){
    if($method==='GET'){
        $w=['1=1']; $p=[];
        if($IS_USER){ $w[]='s.created_by=?'; $p[]=$UID; }
        if(!empty($_GET['status'])){ $w[]='s.status=?'; $p[]=$_GET['status']; }
        $s=$db->prepare("SELECT s.*,sy.name as system_name,u.name as creator_name,r.name as reviewer_name FROM solicitacoes s LEFT JOIN sistemas sy ON s.system_id=sy.id LEFT JOIN usuarios u ON s.created_by=u.id LEFT JOIN usuarios r ON s.reviewed_by=r.id WHERE ".implode(' AND ',$w)." ORDER BY s.id DESC");
        $s->execute($p); jsonR($s->fetchAll());
    }
    if($method==='POST'){
        $d=json_decode(file_get_contents('php://input'),true);
        $title=trim($d['title']??''); if(!$title) jsonR(['error'=>'Título obrigatório'],400);
        $db->prepare("INSERT INTO solicitacoes (title,description,system_id,type,priority,created_by,requester_name,requester_department) VALUES (?,?,?,?,?,?,?,?)")
            ->execute([$title,$d['description']??'',$d['system_id']?:null,$d['type']??'Melhoria',$d['priority']??'Média',$UID,$ME['name']??'',$ME['department_name']??'']);
        $sid=$db->lastInsertId();
        $reviewers=$db->query("SELECT id FROM usuarios WHERE (role LIKE '%admin%' OR role LIKE '%diretor%') AND active=1")->fetchAll();
        foreach($reviewers as $a){
            if($a['id']!=$UID){
                notify($a['id'],'solicitation',"Nova solicitação: {$title}",'',"solicitation:{$sid}",'solicitation',$sid);
                sendPushToUser($db, (int)$a['id'], ['title'=>'📝 Nova Solicitação','message'=>"De {$ME['name']}: {$title}",'url'=>'/index.php#solicitacoes']);
            }
        }
        logActivity($UID,"Solicitação #{$sid}: {$title}",'solicitation',$sid);
        jsonR(['success'=>true,'id'=>$sid],201);
    }
}

if($action==='solicitation_delete'){
    requireRole(['admin']);
    $id=(int)($_GET['id']??0); if(!$id) jsonR(['error'=>'ID obrigatório'],400);
    $sol=$db->prepare("SELECT title FROM solicitacoes WHERE id=?"); $sol->execute([$id]); $solRow=$sol->fetch();
    if(!$solRow) jsonR(['error'=>'Não encontrada'],404);
    $db->prepare("DELETE FROM solicitacoes WHERE id=?")->execute([$id]);
    $db->prepare("DELETE FROM notificacoes WHERE entity_type='solicitation' AND entity_id=?")->execute([$id]);
    logActivity($UID,"Apagou solicitação #{$id}: {$solRow['title']}",'solicitation',$id);
    jsonR(['success'=>true]);
}
if($act==='solicitation_review'&&isset($_GET['id'])){
    if(!$IS_ADMIN&&!$IS_DIR) jsonR(['error'=>'Sem permissão'],403);
    $id=(int)$_GET['id']; $d=json_decode(file_get_contents('php://input'),true);
    $status=$d['status']??'Aprovada'; $notes=$d['review_notes']??'';
    $existingDemandId=$d['demand_id']??null;
    $db->prepare("UPDATE solicitacoes SET status=?,reviewed_by=?,reviewed_at=NOW(),review_notes=? WHERE id=?")->execute([$status,$UID,$notes,$id]);

    if($status==='Aprovada'){
        $sol=$db->prepare("SELECT * FROM solicitacoes WHERE id=?"); $sol->execute([$id]); $sol=$sol->fetch();
        if($existingDemandId){
            $did=(int)$existingDemandId;
            $db->prepare("UPDATE demandas SET from_solicitation_id=? WHERE id=?")->execute([$id,$did]);
        } else {
            $solCreatorPre=$db->prepare("SELECT u.name FROM usuarios u WHERE u.id=?"); $solCreatorPre->execute([$sol['created_by']??0]); $solRequester=$solCreatorPre->fetchColumn()?:('Solicitação #'.$id);
            $db->prepare("INSERT INTO demandas (title,description,system_id,priority,status,requester,needs_presidency_approval,from_solicitation_id,created_by) VALUES (?,?,?,?,'Aberta',?,0,?,?)")
                ->execute([$sol['title'],$sol['description'],$sol['system_id'],$sol['priority'],$solRequester,$id,$UID]);
            $did=$db->lastInsertId();
        }
        $db->prepare("UPDATE solicitacoes SET status='Convertida',converted_demand_id=? WHERE id=?")->execute([$did,$id]);
        $solCreator=$db->prepare("SELECT u.name FROM usuarios u WHERE u.id=?"); $solCreator->execute([$sol['created_by']??0]); $solCreatorName=$solCreator->fetchColumn();
        if($solCreatorName) $db->prepare("UPDATE demandas SET requester=? WHERE id=? AND (requester IS NULL OR requester='' OR requester LIKE 'Solicitação%')")->execute([$solCreatorName,$did]);
        $histDetail="Origem: Solicitação #{$id}".($solCreatorName?" · Solicitante: {$solCreatorName}":'');
        $db->prepare("INSERT INTO historico_demandas (demand_id,user_id,action,new_value,details) VALUES (?,?,'Criada de solicitação','Aberta',?)")->execute([$did,$UID,$histDetail]);
        if($sol['created_by']){
            notify($sol['created_by'],'solicitation_approved',"Solicitação #{$id} aprovada e convertida em demanda #{$did}",$notes,"demand:{$did}",'demand',$did);
            sendPushToUser($db, (int)$sol['created_by'], ['title'=>'✅ Solicitação Aprovada','message'=>"Sua solicitação foi aprovada e virou demanda #{$did}",'url'=>'/index.php#demandas']);
        }
        $allU=$db->prepare("SELECT id FROM usuarios WHERE active=1 AND (role LIKE '%admin%' OR role LIKE '%diretor%') AND id!=? AND id!=?"); $allU->execute([$UID,$sol['created_by']??0]);
        foreach($allU->fetchAll() as $au) notify($au['id'],'demand_new',"Nova demanda: ".($sol['title']??""),"Convertida da solicitação #{$id} por {$ME['name']}","demand:{$did}",'demand',$did);
    } else {
        $sol=$db->prepare("SELECT created_by FROM solicitacoes WHERE id=?"); $sol->execute([$id]); $sol=$sol->fetch();
        if($sol['created_by']){
            notify($sol['created_by'],'solicitation_rejected',"Solicitação #{$id} rejeitada",$notes,"",'solicitation',$id);
            sendPushToUser($db, (int)$sol['created_by'], ['title'=>'❌ Solicitação Rejeitada','message'=>"Sua solicitação #{$id} foi rejeitada",'url'=>'/index.php#solicitacoes']);
        }
    }
    logActivity($UID,"Solicitação #{$id}: {$status}",'solicitation',$id);
    jsonR(['success'=>true]);
}

// ===== DAILY REPORTS =====
if($act==='relatorios_diarios'){
    if($method==='GET'){
        $w=['1=1']; $p=[];
        if($IS_DEV){ $w[]='dr.user_id=?'; $p[]=$UID; }
        if(!empty($_GET['user_id'])&&!$IS_DEV){ $w[]='dr.user_id=?'; $p[]=$_GET['user_id']; }
        if(!empty($_GET['date_from'])){ $w[]='dr.report_date>=?'; $p[]=$_GET['date_from']; }
        if(!empty($_GET['date_to'])){ $w[]='dr.report_date<=?'; $p[]=$_GET['date_to']; }
        $s=$db->prepare("SELECT dr.*,u.name,u.avatar_color FROM relatorios_diarios dr JOIN usuarios u ON dr.user_id=u.id WHERE ".implode(' AND ',$w)." ORDER BY dr.report_date DESC,u.name LIMIT 200");
        $s->execute($p); jsonR($s->fetchAll());
    }
    if($method==='POST'){
        $d=json_decode(file_get_contents('php://input'),true);
        $date=$d['report_date']??date('Y-m-d');
        $db->prepare("INSERT INTO relatorios_diarios (user_id,report_date,tasks_done,tasks_planned,blockers,hours_worked) VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE tasks_done=VALUES(tasks_done),tasks_planned=VALUES(tasks_planned),blockers=VALUES(blockers),hours_worked=VALUES(hours_worked)")
            ->execute([$UID,$date,$d['tasks_done']??'',$d['tasks_planned']??'',$d['blockers']??'',$d['hours_worked']??8]);
        jsonR(['success'=>true]);
    }
}

// ===== REPORTS =====
if($act==='reports'){
    if(!$IS_ADMIN && !$IS_DIR && !$IS_DEV && !$IS_PRES) jsonR(['error'=>'Sem permissão'],403);
    $type=$_GET['type']??'general';
    $dateFrom=$_GET['date_from']??date('Y-m-d',strtotime('-90 days'));
    $dateTo=$_GET['date_to']??date('Y-m-d');

    if($type==='by_dev'){
        $s=$db->prepare("SELECT u.id,u.name,u.avatar_color,
            COUNT(DISTINCT d.id) as total,
            COUNT(DISTINCT CASE WHEN d.status='Aberta' THEN d.id END) as abertas,
            COUNT(DISTINCT CASE WHEN d.status='Aguardando Aceite' THEN d.id END) as aguardando,
            COUNT(DISTINCT CASE WHEN d.status='Em Andamento' THEN d.id END) as andamento,
            COUNT(DISTINCT CASE WHEN d.status='Em Revisão' THEN d.id END) as revisao,
            COUNT(DISTINCT CASE WHEN d.status='Concluída' THEN d.id END) as concluidas,
            COUNT(DISTINCT CASE WHEN d.status='Cancelada' THEN d.id END) as canceladas,
            AVG(CASE WHEN d.status='Concluída' AND d.completed_at IS NOT NULL THEN DATEDIFF(d.completed_at,d.created_at) END) as avg_days,
            COALESCE((SELECT COUNT(*) FROM relatorios_diarios dr2 WHERE dr2.user_id=u.id AND dr2.report_date BETWEEN ? AND ?),0) as relatorios_diarios
        FROM usuarios u
        LEFT JOIN devs_demandas dd ON u.id=dd.user_id
        LEFT JOIN demandas d ON dd.demand_id=d.id AND d.created_at BETWEEN ? AND ?
        WHERE u.role LIKE '%dev%' AND u.active=1
        GROUP BY u.id ORDER BY concluidas DESC");
        $s->execute([$dateFrom,$dateTo,$dateFrom.' 00:00:00',$dateTo.' 23:59:59']);
        jsonR($s->fetchAll());
    }
    if($type==='by_system'){
        $s=$db->prepare("SELECT s.id,s.name,COUNT(d.id) as total,
            COALESCE(SUM(CASE WHEN d.status IN('Aberta','Aguardando Aceite') THEN 1 ELSE 0 END),0) as abertas,
            COALESCE(SUM(CASE WHEN d.status IN('Em Andamento','Em Revisão') THEN 1 ELSE 0 END),0) as andamento,
            COALESCE(SUM(CASE WHEN d.status='Concluída' THEN 1 ELSE 0 END),0) as concluidas,
            AVG(CASE WHEN d.status='Concluída' AND d.completed_at IS NOT NULL THEN DATEDIFF(d.completed_at,d.created_at) END) as avg_days
        FROM sistemas s LEFT JOIN demandas d ON s.id=d.system_id AND d.created_at BETWEEN ? AND ?
        GROUP BY s.id HAVING COUNT(d.id)>0 ORDER BY total DESC");
        $s->execute([$dateFrom.' 00:00:00',$dateTo.' 23:59:59']); jsonR($s->fetchAll());
    }
    if($type==='by_priority'){
        $s=$db->prepare("SELECT priority,COUNT(*) as total,SUM(status NOT IN('Concluída','Cancelada')) as ativas FROM demandas WHERE created_at BETWEEN ? AND ? GROUP BY priority");
        $s->execute([$dateFrom.' 00:00:00',$dateTo.' 23:59:59']); jsonR($s->fetchAll());
    }
    if($type==='timeline'){
        $s=$db->prepare("SELECT DATE_FORMAT(created_at,'%Y-%m') as month,COUNT(*) as criadas,SUM(status='Concluída') as concluidas FROM demandas WHERE created_at BETWEEN ? AND ? GROUP BY month ORDER BY month");
        $s->execute([$dateFrom.' 00:00:00',$dateTo.' 23:59:59']); jsonR($s->fetchAll());
    }
    if($type==='acceptance'){
        $s=$db->prepare("SELECT u.name,u.avatar_color,dd.acceptance,COUNT(*) as c FROM devs_demandas dd JOIN usuarios u ON dd.user_id=u.id JOIN demandas d ON dd.demand_id=d.id WHERE d.created_at BETWEEN ? AND ? GROUP BY u.id,dd.acceptance ORDER BY u.name");
        $s->execute([$dateFrom.' 00:00:00',$dateTo.' 23:59:59']); jsonR($s->fetchAll());
    }
    if($type==='productivity'){
        $s=$db->prepare("SELECT u.id,u.name,u.avatar_color,u.avatar_file,u.role,
            COUNT(DISTINCT CASE WHEN d.status='Concluída' THEN d.id END) as concluidas,
            COUNT(DISTINCT CASE WHEN d.status NOT IN('Concluída','Cancelada') THEN d.id END) as em_aberto,
            AVG(CASE WHEN d.status='Concluída' AND d.completed_at IS NOT NULL THEN DATEDIFF(d.completed_at,d.created_at) END) as avg_days,
            COALESCE((SELECT SUM(dr2.hours_worked) FROM relatorios_diarios dr2 WHERE dr2.user_id=u.id AND dr2.report_date BETWEEN ? AND ?),0) as total_hours,
            COALESCE((SELECT COUNT(*) FROM relatorios_diarios dr3 WHERE dr3.user_id=u.id AND dr3.report_date BETWEEN ? AND ?),0) as reports_count
        FROM usuarios u
        LEFT JOIN devs_demandas dd ON u.id=dd.user_id
        LEFT JOIN demandas d ON dd.demand_id=d.id AND d.created_at BETWEEN ? AND ?
        WHERE u.role LIKE '%dev%' AND u.active=1
        GROUP BY u.id ORDER BY concluidas DESC");
        $s->execute([$dateFrom,$dateTo,$dateFrom,$dateTo,$dateFrom.' 00:00:00',$dateTo.' 23:59:59']);
        jsonR($s->fetchAll());
    }
    if($type==='general_stats'){
        $result = [];
        $s=$db->prepare("SELECT COUNT(*) as total,COALESCE(SUM(CASE WHEN status='Concluída' THEN 1 ELSE 0 END),0) as concluidas,COALESCE(SUM(CASE WHEN status='Cancelada' THEN 1 ELSE 0 END),0) as canceladas,COALESCE(SUM(CASE WHEN status NOT IN('Concluída','Cancelada') THEN 1 ELSE 0 END),0) as ativas,AVG(CASE WHEN status='Concluída' AND completed_at IS NOT NULL THEN DATEDIFF(completed_at,created_at) END) as avg_days FROM demandas WHERE created_at BETWEEN ? AND ?");
        $s->execute([$dateFrom.' 00:00:00',$dateTo.' 23:59:59']); $result['overview']=$s->fetch();
        $s=$db->prepare("SELECT COUNT(*) as total,SUM(CASE WHEN completed_at<=CONCAT(deadline,' 23:59:59') THEN 1 ELSE 0 END) as on_time FROM demandas WHERE status='Concluída' AND deadline IS NOT NULL AND completed_at IS NOT NULL AND created_at BETWEEN ? AND ?");
        $s->execute([$dateFrom.' 00:00:00',$dateTo.' 23:59:59']); $result['sla']=$s->fetch();
        $s=$db->prepare("SELECT status,COUNT(*) as c FROM demandas WHERE created_at BETWEEN ? AND ? GROUP BY status ORDER BY c DESC");
        $s->execute([$dateFrom.' 00:00:00',$dateTo.' 23:59:59']); $result['status_dist']=$s->fetchAll();
        jsonR($result);
    }
    if($type==='system_health'){
        $s=$db->prepare("SELECT s.id,s.name,s.technology,s.status as sys_status,COUNT(d.id) as total_demands,
            COALESCE(SUM(CASE WHEN d.status='Concluída' THEN 1 ELSE 0 END),0) as concluidas,
            COALESCE(SUM(CASE WHEN d.status NOT IN('Concluída','Cancelada') THEN 1 ELSE 0 END),0) as abertas,
            COALESCE(SUM(CASE WHEN d.priority='Urgente' AND d.status NOT IN('Concluída','Cancelada') THEN 1 ELSE 0 END),0) as urgentes,
            AVG(CASE WHEN d.status='Concluída' AND d.completed_at IS NOT NULL THEN DATEDIFF(d.completed_at,d.created_at) END) as avg_days,
            MAX(d.created_at) as last_demand
        FROM sistemas s LEFT JOIN demandas d ON s.id=d.system_id AND d.created_at BETWEEN ? AND ?
        GROUP BY s.id HAVING COUNT(d.id)>0 ORDER BY abertas DESC, total_demands DESC");
        $s->execute([$dateFrom.' 00:00:00',$dateTo.' 23:59:59']); jsonR($s->fetchAll());
    }
}

// ===== ACTIVITIES =====
if($act==='activities'){
    if($IS_DEV){ $s=$db->prepare("SELECT a.*,u.name as user_name FROM registro_atividades a LEFT JOIN usuarios u ON a.user_id=u.id WHERE a.user_id=? ORDER BY a.created_at DESC LIMIT 30"); $s->execute([$UID]); }
    else $s=$db->query("SELECT a.*,u.name as user_name FROM registro_atividades a LEFT JOIN usuarios u ON a.user_id=u.id ORDER BY a.created_at DESC LIMIT 50");
    jsonR($s->fetchAll());
}

// ===== CALENDAR =====
if($act==='calendar'){
    $month=$_GET['month']??date('Y-m');
    $start=$month.'-01';
    $end=date('Y-m-t',strtotime($start));
    $w=['1=1']; $p=[];
    $s=$db->prepare("SELECT d.id,d.title,d.deadline,d.start_date,d.status,d.priority,d.completed_at,s.name as system_name,
        GROUP_CONCAT(DISTINCT u.name SEPARATOR ', ') as dev_names
        FROM demandas d
        LEFT JOIN sistemas s ON d.system_id=s.id
        LEFT JOIN devs_demandas dd ON d.id=dd.demand_id
        LEFT JOIN usuarios u ON dd.user_id=u.id
        WHERE ".implode(' AND ',$w)." AND (
            (d.deadline BETWEEN ? AND ?) OR
            (d.start_date BETWEEN ? AND ?) OR
            (d.completed_at BETWEEN ? AND ?)
        )
        GROUP BY d.id ORDER BY d.deadline");
    $p=array_merge($p,[$start,$end,$start,$end,$start.' 00:00:00',$end.' 23:59:59']);
    $s->execute($p);
    $demands=$s->fetchAll();
    $meetW="m.meeting_date BETWEEN ? AND ?";
    $meetP=[$start,$end];
    if($IS_DEV||$IS_USER){
        $meetW.=" AND (m.id IN (SELECT meeting_id FROM participantes_reunioes WHERE user_id=?) OR m.created_by=?)";
        $meetP[]=$UID; $meetP[]=$UID;
    }
    $s=$db->prepare("SELECT m.id,m.title,m.meeting_date,m.meeting_time,m.duration_minutes,m.is_online,m.location FROM reunioes m WHERE $meetW ORDER BY m.meeting_date,m.meeting_time");
    $s->execute($meetP);
    $meetings=$s->fetchAll();
    $s=$db->prepare("SELECT sp.*,
        (SELECT COUNT(*) FROM demandas WHERE sprint_id=sp.id) as demand_count,
        (SELECT COUNT(*) FROM demandas WHERE sprint_id=sp.id AND status='Concluída') as done_count
        FROM sprints sp WHERE sp.start_date<=? AND sp.end_date>=? ORDER BY sp.start_date");
    $s->execute([$end,$start]);
    $sprints=$s->fetchAll();
    $nW="n.active=1 AND n.show_calendar=1 AND n.event_date BETWEEN ? AND ?";
    $nP=[$start,$end];
    $roleConds=["n.target_role='todos'"];
    if($IS_ADMIN) $roleConds[]="n.target_role='admin'";
    if($IS_DEV) $roleConds[]="n.target_role='dev'";
    if($IS_DIR) $roleConds[]="n.target_role='diretor'";
    if($IS_PRES) $roleConds[]="n.target_role='presidencia'";
    $nW.=" AND (".implode(' OR ',$roleConds).")";
    $s=$db->prepare("SELECT n.id,n.title,n.priority,n.event_date FROM avisos n WHERE {$nW} ORDER BY n.event_date");
    $s->execute($nP);
    $notices=$s->fetchAll();
    $s=$db->prepare("SELECT id,note_date,content FROM anotacoes_calendario WHERE user_id=? AND note_date BETWEEN ? AND ? ORDER BY note_date");
    $s->execute([$UID,$start,$end]);
    $notes=$s->fetchAll();
    jsonR(['demandas'=>$demands,'reunioes'=>$meetings,'sprints'=>$sprints,'avisos'=>$notices,'notes'=>$notes]);
}

// ===== SPRINTS =====
if($act==='sprints'){
    if($method==='GET'){
        $s=$db->query("SELECT sp.*, u.name as creator_name,
            (SELECT COUNT(*) FROM demandas WHERE sprint_id=sp.id) as demand_count,
            (SELECT COUNT(*) FROM demandas WHERE sprint_id=sp.id AND status='Concluída') as done_count
            FROM sprints sp LEFT JOIN usuarios u ON sp.created_by=u.id ORDER BY sp.start_date DESC");
        jsonR($s->fetchAll());
    }
    if($method==='POST'){
        if(!$IS_ADMIN&&!$IS_DIR) jsonR(['error'=>'Sem permissão'],403);
        $d=json_decode(file_get_contents('php://input'),true);
        $db->prepare("INSERT INTO sprints (name,goal,start_date,end_date,status,created_by) VALUES (?,?,?,?,?,?)")
            ->execute([$d['name']??'',$d['goal']??'',$d['start_date']??'',$d['end_date']??'',$d['status']??'Planejada',$UID]);
        logActivity($UID,"Criou sprint: ".($d['name']??''),'sprint',$db->lastInsertId());
        jsonR(['success'=>true,'id'=>$db->lastInsertId()]);
    }
}
if($act==='sprint'&&isset($_GET['id'])){
    $id=(int)$_GET['id'];
    if($method==='PUT'){
        if(!$IS_ADMIN&&!$IS_DIR) jsonR(['error'=>'Sem permissão'],403);
        $d=json_decode(file_get_contents('php://input'),true);
        $db->prepare("UPDATE sprints SET name=?,goal=?,start_date=?,end_date=?,status=? WHERE id=?")
            ->execute([$d['name']??'',$d['goal']??'',$d['start_date']??'',$d['end_date']??'',$d['status']??'Planejada',$id]);
        logActivity($UID,"Editou sprint #$id",'sprint',$id);
        jsonR(['success'=>true]);
    }
    if($method==='DELETE'){
        if(!$IS_ADMIN) jsonR(['error'=>'Sem permissão'],403);
        $db->prepare("UPDATE demandas SET sprint_id=NULL WHERE sprint_id=?")->execute([$id]);
        $db->prepare("DELETE FROM sprints WHERE id=?")->execute([$id]);
        logActivity($UID,"Excluiu sprint #$id",'sprint',$id);
        jsonR(['success'=>true]);
    }
}
if($act==='demand_sprint'&&isset($_GET['id'])){
    if(!$IS_ADMIN&&!$IS_DIR) jsonR(['error'=>'Sem permissão'],403);
    $id=(int)$_GET['id']; $d=json_decode(file_get_contents('php://input'),true);
    $sprintId=$d['sprint_id']??null; if($sprintId===''||$sprintId===0)$sprintId=null;
    $db->prepare("UPDATE demandas SET sprint_id=? WHERE id=?")->execute([$sprintId,$id]);
    $dem=$db->prepare("SELECT title FROM demandas WHERE id=?"); $dem->execute([$id]); $dt=$dem->fetch()['title']??'';
    logActivity($UID,"Sprint atualizada: {$dt}",'demand',$id);
    jsonR(['success'=>true]);
}

// ===== AUDIT =====
if($act==='audit'){
    if(!$IS_ADMIN) jsonR(['error'=>'Sem permissão'],403);
    $limit=(int)($_GET['limit']??100); if($limit>500)$limit=500;
    $w=['1=1']; $p=[];
    if(!empty($_GET['user_id'])){ $w[]='a.user_id=?'; $p[]=$_GET['user_id']; }
    if(!empty($_GET['entity_type'])){ $w[]='a.entity_type=?'; $p[]=$_GET['entity_type']; }
    if(!empty($_GET['date_from'])){ $w[]='a.created_at>=?'; $p[]=$_GET['date_from'].' 00:00:00'; }
    if(!empty($_GET['date_to'])){ $w[]='a.created_at<=?'; $p[]=$_GET['date_to'].' 23:59:59'; }
    if(!empty($_GET['search'])){ $w[]='(a.action LIKE ? OR a.details LIKE ?)'; $p[]='%'.$_GET['search'].'%'; $p[]='%'.$_GET['search'].'%'; }
    $s=$db->prepare("SELECT a.*,u.name as user_name,u.email as user_email,u.role as user_role FROM registro_atividades a LEFT JOIN usuarios u ON a.user_id=u.id WHERE ".implode(' AND ',$w)." ORDER BY a.created_at DESC LIMIT $limit");
    $s->execute($p);
    $logs=$s->fetchAll();
    $s2=$db->prepare("SELECT COUNT(*) as total FROM registro_atividades a WHERE ".implode(' AND ',$w));
    $s2->execute($p);
    $total=$s2->fetch()['total'];
    $s3=$db->query("SELECT u.name,u.role,COUNT(a.id) as actions FROM registro_atividades a JOIN usuarios u ON a.user_id=u.id GROUP BY a.user_id ORDER BY actions DESC LIMIT 20");
    $byUser=$s3->fetchAll();
    $s4=$db->query("SELECT entity_type,COUNT(*) as c FROM registro_atividades WHERE entity_type IS NOT NULL GROUP BY entity_type ORDER BY c DESC");
    $byType=$s4->fetchAll();
    jsonR(['logs'=>$logs,'total'=>$total,'by_user'=>$byUser,'by_type'=>$byType]);
}

// ===== CALENDAR NOTES =====
if($act==='anotacoes_calendario'){
    if($method==='GET'){
        $w=['cn.user_id=?']; $p=[$UID];
        $search=trim($_GET['search']??'');
        $folder=$_GET['folder']??'';
        $archived=(int)($_GET['archived']??0);
        $w[]='cn.archived=?'; $p[]=$archived;
        if($search){$w[]='cn.content LIKE ?'; $p[]="%{$search}%";}
        if($folder){$w[]='cn.folder=?'; $p[]=$folder;}
        $s=$db->prepare("SELECT cn.* FROM anotacoes_calendario cn WHERE ".implode(' AND ',$w)." ORDER BY cn.note_date DESC, cn.created_at DESC");
        $s->execute($p); jsonR($s->fetchAll());
    }
    if($method==='POST'){
        $d=json_decode(file_get_contents('php://input'),true);
        $date=$d['note_date']??''; $content=trim($d['content']??'');
        $folder=trim($d['folder']??''); $color=$d['color']??null;
        if(!$date||!$content) jsonR(['error'=>'Data e conteúdo obrigatórios'],400);
        $db->prepare("INSERT INTO anotacoes_calendario (user_id,note_date,content,folder,color) VALUES (?,?,?,?,?)")
            ->execute([$UID,$date,$content,$folder?:null,$color]);
        jsonR(['success'=>true,'id'=>$db->lastInsertId()]);
    }
}
if($act==='calendar_note'&&isset($_GET['id'])){
    $id=(int)$_GET['id'];
    if($method==='PUT'){
        $d=json_decode(file_get_contents('php://input'),true);
        $sets=[]; $p=[];
        if(isset($d['content'])){$sets[]='content=?'; $p[]=trim($d['content']);}
        if(isset($d['folder'])){$sets[]='folder=?'; $p[]=$d['folder']?:null;}
        if(isset($d['note_date'])){$sets[]='note_date=?'; $p[]=$d['note_date'];}
        if(isset($d['archived'])){$sets[]='archived=?'; $p[]=(int)$d['archived'];}
        if(isset($d['color'])){$sets[]='color=?'; $p[]=$d['color'];}
        if(!count($sets)) jsonR(['error'=>'Nada para atualizar'],400);
        $p[]=$id; $p[]=$UID;
        $db->prepare("UPDATE anotacoes_calendario SET ".implode(',',$sets)." WHERE id=? AND user_id=?")->execute($p);
        jsonR(['success'=>true]);
    }
    if($method==='DELETE'){
        $db->prepare("DELETE FROM anotacoes_calendario WHERE id=? AND user_id=?")->execute([$id,$UID]);
        jsonR(['success'=>true]);
    }
}
if($act==='pastas_notas'){
    if($method==='GET'){
        $s=$db->prepare("SELECT * FROM pastas_notas WHERE user_id=? ORDER BY name"); $s->execute([$UID]);
        jsonR($s->fetchAll());
    }
    if($method==='POST'){
        $d=json_decode(file_get_contents('php://input'),true);
        $name=trim($d['name']??''); $color=$d['color']??'#6366f1';
        if(!$name) jsonR(['error'=>'Nome obrigatório'],400);
        $db->prepare("INSERT INTO pastas_notas (user_id,name,color) VALUES (?,?,?)")->execute([$UID,$name,$color]);
        jsonR(['success'=>true,'id'=>$db->lastInsertId()]);
    }
    if($method==='DELETE'&&isset($_GET['id'])){
        $fid=(int)$_GET['id'];
        $db->prepare("UPDATE anotacoes_calendario SET folder=NULL WHERE user_id=? AND folder=(SELECT name FROM pastas_notas WHERE id=? AND user_id=?)")->execute([$UID,$fid,$UID]);
        $db->prepare("DELETE FROM pastas_notas WHERE id=? AND user_id=?")->execute([$fid,$UID]);
        jsonR(['success'=>true]);
    }
}

// ===== PROFILE =====
if($act==='profile'){
    if($method==='GET'){
        $s=$db->prepare("SELECT id,name,email,role,avatar_color,avatar_file,last_login,created_at,work_hours FROM usuarios WHERE id=?");
        $s->execute([$UID]);
        $u=$s->fetch();
        $st1=$db->prepare("SELECT COUNT(*) as c FROM devs_demandas WHERE user_id=?"); $st1->execute([$UID]);
        $st2=$db->prepare("SELECT COUNT(*) as c FROM devs_demandas dd JOIN demandas d ON d.id=dd.demand_id WHERE dd.user_id=? AND d.status='Concluída'"); $st2->execute([$UID]);
        $st3=$db->prepare("SELECT COUNT(*) as c FROM comentarios_demandas WHERE user_id=?"); $st3->execute([$UID]);
        $st4=$db->prepare("SELECT COUNT(*) as c FROM devs_demandas dd JOIN demandas d ON d.id=dd.demand_id WHERE dd.user_id=? AND d.status='Em Andamento'"); $st4->execute([$UID]);
        $st5=$db->prepare("SELECT COUNT(*) as c FROM devs_demandas dd JOIN demandas d ON d.id=dd.demand_id WHERE dd.user_id=? AND d.status='Em Revisão'"); $st5->execute([$UID]);
        $st6=$db->prepare("SELECT COUNT(*) as c FROM devs_demandas dd JOIN demandas d ON d.id=dd.demand_id WHERE dd.user_id=? AND d.status='Cancelada'"); $st6->execute([$UID]);
        $stP=$db->prepare("SELECT d.priority,COUNT(*) as c FROM devs_demandas dd JOIN demandas d ON d.id=dd.demand_id WHERE dd.user_id=? GROUP BY d.priority"); $stP->execute([$UID]);
        $byPri=array();while($r=$stP->fetch())$byPri[$r['priority']]=(int)$r['c'];
        $stS=$db->prepare("SELECT s.name,COUNT(*) as c FROM devs_demandas dd JOIN demandas d ON d.id=dd.demand_id LEFT JOIN sistemas s ON d.system_id=s.id WHERE dd.user_id=? GROUP BY s.name ORDER BY c DESC LIMIT 5"); $stS->execute([$UID]);
        $bySys=$stS->fetchAll();
        $stM=$db->prepare("SELECT DATE_FORMAT(d.completed_at,'%Y-%m') as mes,COUNT(*) as c FROM devs_demandas dd JOIN demandas d ON d.id=dd.demand_id WHERE dd.user_id=? AND d.status='Concluída' AND d.completed_at>=DATE_SUB(NOW(),INTERVAL 6 MONTH) GROUP BY mes ORDER BY mes"); $stM->execute([$UID]);
        $monthly=$stM->fetchAll();
        $stAvg=$db->prepare("SELECT AVG(DATEDIFF(d.completed_at,d.created_at)) as avg_days FROM devs_demandas dd JOIN demandas d ON d.id=dd.demand_id WHERE dd.user_id=? AND d.status='Concluída' AND d.completed_at IS NOT NULL"); $stAvg->execute([$UID]);
        $avgDays=$stAvg->fetch()['avg_days'];
        $stRec=$db->prepare("SELECT d.id,d.title,d.status,d.priority,d.created_at,d.completed_at,d.deadline,d.start_date,d.started_at,d.system_id,s.name as system_name FROM devs_demandas dd JOIN demandas d ON d.id=dd.demand_id LEFT JOIN sistemas s ON d.system_id=s.id WHERE dd.user_id=? ORDER BY d.created_at DESC LIMIT 10"); $stRec->execute([$UID]);
        $recentDem=$stRec->fetchAll();
        $stWorking=$db->prepare("SELECT d.id,d.title,d.status,d.priority,d.deadline,d.started_at,d.start_date,s.name as system_name FROM devs_demandas dd JOIN demandas d ON d.id=dd.demand_id LEFT JOIN sistemas s ON d.system_id=s.id WHERE dd.user_id=? AND d.status IN('Em Andamento','Em Revisão') ORDER BY d.created_at DESC"); $stWorking->execute([$UID]);
        $workingNow=$stWorking->fetchAll();
        $stWeekly=$db->prepare("SELECT DATE(d.completed_at) as day,COUNT(*) as c FROM devs_demandas dd JOIN demandas d ON d.id=dd.demand_id WHERE dd.user_id=? AND d.status='Concluída' AND d.completed_at>=DATE_SUB(NOW(),INTERVAL 30 DAY) GROUP BY day ORDER BY day"); $stWeekly->execute([$UID]);
        $dailyCompletions=$stWeekly->fetchAll();
        $stStreak=$db->prepare("SELECT DISTINCT DATE(d.completed_at) as day FROM devs_demandas dd JOIN demandas d ON d.id=dd.demand_id WHERE dd.user_id=? AND d.status='Concluída' AND d.completed_at IS NOT NULL ORDER BY day DESC LIMIT 60"); $stStreak->execute([$UID]);
        $streakDays=$stStreak->fetchAll(PDO::FETCH_COLUMN);
        $streak=0;$checkDate=new DateTime();for($si=0;$si<60;$si++){$ds=$checkDate->format('Y-m-d');if(in_array($ds,$streakDays)){$streak++;}elseif($si>0){break;}$checkDate->modify('-1 day');}
        $stActivity=$db->prepare("SELECT a.action,a.entity_type,a.entity_id,a.created_at FROM registro_atividades a WHERE a.user_id=? ORDER BY a.created_at DESC LIMIT 20"); $stActivity->execute([$UID]);
        $recentActivity=$stActivity->fetchAll();

        $u['stats']=array(
            'total'=>(int)$st1->fetch()['c'],'completed'=>(int)$st2->fetch()['c'],'comments'=>(int)$st3->fetch()['c'],
            'in_progress'=>(int)$st4->fetch()['c'],'in_review'=>(int)$st5->fetch()['c'],'cancelled'=>(int)$st6->fetch()['c'],
            'by_priority'=>$byPri,'by_system'=>$bySys,'monthly'=>$monthly,
            'avg_days'=>$avgDays?round((float)$avgDays,1):null,'recent_demands'=>$recentDem,'working_now'=>$workingNow,'daily_completions'=>$dailyCompletions,'streak'=>$streak,'recent_activity'=>$recentActivity
        );
        jsonR($u);
    }
    if($method==='PUT'){
        $d=json_decode(file_get_contents('php://input'),true);
        $name=trim($d['name']??''); $email=trim($d['email']??''); $color=$d['avatar_color']??'#3b82f6';
        if(!$name||!$email) jsonR(['error'=>'Nome e email obrigatórios'],400);
        $ck=$db->prepare("SELECT id FROM usuarios WHERE email=? AND id!=?"); $ck->execute([$email,$UID]);
        if($ck->fetch()) jsonR(['error'=>'Email já em uso'],400);
        $db->prepare("UPDATE usuarios SET name=?,email=?,avatar_color=? WHERE id=?")->execute([$name,$email,$color,$UID]);
        jsonR(['success'=>true]);
    }
}
if($act==='profile_avatar'&&$method==='POST'){
    if(empty($_FILES['avatar'])) jsonR(['error'=>'Nenhum arquivo'],400);
    $filename=handleUploadBlob($_FILES["avatar"],$db);
    if(!$filename) jsonR(['error'=>'Erro no upload. Verifique tipo/tamanho.'],400);
    $old=$db->prepare("SELECT avatar_file FROM usuarios WHERE id=?"); $old->execute([$UID]); $oldFile=$old->fetchColumn();
    $db->prepare("UPDATE usuarios SET avatar_file=? WHERE id=?")->execute([$filename,$UID]);
    logActivity($UID,'avatar_updated','user',$UID);
    jsonR(['success'=>true,'filename'=>$filename]);
}
if($act==='profile_password'&&$method==='POST'){
    $d=json_decode(file_get_contents('php://input'),true);
    $cur=trim($d['current']??''); $new=trim($d['new']??'');
    if(!$cur||!$new) jsonR(['error'=>'Preencha todos os campos'],400);
    if(strlen($new)<6) jsonR(['error'=>'Senha deve ter no mínimo 6 caracteres'],400);
    $s=$db->prepare("SELECT password FROM usuarios WHERE id=?"); $s->execute([$UID]); $hash=$s->fetchColumn();
    if(!password_verify($cur,$hash)) jsonR(['error'=>'Senha atual incorreta'],400);
    $db->prepare("UPDATE usuarios SET password=? WHERE id=?")->execute([password_hash($new,PASSWORD_BCRYPT,array('cost'=>BCRYPT_COST)),$UID]);
    logActivity($UID,'password_changed','user',$UID);
    jsonR(['success'=>true]);
}

// ===== ADMIN USER MANAGEMENT =====
if($act==='admin_users'){
    if(!$IS_ADMIN) jsonR(['error'=>'Sem permissão'],403);
    if($method==='GET'){
        $s=$db->query("SELECT id,name,email,role,avatar_color,avatar_file,active,last_login,created_at FROM usuarios ORDER BY name");
        jsonR($s->fetchAll());
    }
    if($method==='POST'){
        $d=json_decode(file_get_contents('php://input'),true);
        $name=trim($d['name']??''); $email=trim($d['email']??''); $pass=trim($d['password']??'');
        $role=$d['role']??'dev'; $color=$d['avatar_color']??'#3b82f6';
        if(!$name||!$email||!$pass) jsonR(['error'=>'Nome, email e senha obrigatórios'],400);
        if(strlen($pass)<6) jsonR(['error'=>'Senha deve ter no mínimo 6 caracteres'],400);
        $ck=$db->prepare("SELECT id FROM usuarios WHERE email=?"); $ck->execute([$email]);
        if($ck->fetch()) jsonR(['error'=>'Email já cadastrado'],400);
        $hash=password_hash($pass,PASSWORD_BCRYPT,array('cost'=>BCRYPT_COST));
        $db->prepare("INSERT INTO usuarios (name,email,password,role,avatar_color) VALUES (?,?,?,?,?)")
            ->execute([$name,$email,$hash,$role,$color]);
        logActivity($UID,'user_created','user',$db->lastInsertId(),$name);
        jsonR(['success'=>true,'id'=>$db->lastInsertId()]);
    }
}
if($act==='admin_user'&&isset($_GET['id'])){
    if(!$IS_ADMIN) jsonR(['error'=>'Sem permissão'],403);
    $uid=(int)$_GET['id'];
    if($method==='GET'){
        $s=$db->prepare("SELECT id,name,email,role,avatar_color,avatar_file,active,last_login,created_at FROM usuarios WHERE id=?");
        $s->execute([$uid]); $u=$s->fetch();
        if(!$u) jsonR(['error'=>'Usuário não encontrado'],404);
        jsonR($u);
    }
    if($method==='PUT'){
        $d=json_decode(file_get_contents('php://input'),true);
        $name=trim($d['name']??''); $email=trim($d['email']??'');
        $role=$d['role']??'dev'; $color=$d['avatar_color']??'#3b82f6';
        if(!$name||!$email) jsonR(['error'=>'Nome e email obrigatórios'],400);
        $ck=$db->prepare("SELECT id FROM usuarios WHERE email=? AND id!=?"); $ck->execute([$email,$uid]);
        if($ck->fetch()) jsonR(['error'=>'Email já em uso'],400);
        $db->prepare("UPDATE usuarios SET name=?,email=?,role=?,avatar_color=?,work_hours=? WHERE id=?")->execute([$name,$email,$role,$color,$d['work_hours']??6,$uid]);
        logActivity($UID,'user_updated','user',$uid,$name);
        jsonR(['success'=>true]);
    }
}
if($act==='admin_reset_pw'&&$method==='POST'){
    if(!$IS_ADMIN) jsonR(['error'=>'Sem permissão'],403);
    $d=json_decode(file_get_contents('php://input'),true);
    $uid=(int)($d['user_id']??0); $pass=trim($d['password']??'');
    if(!$uid||!$pass) jsonR(['error'=>'ID e senha obrigatórios'],400);
    if(strlen($pass)<6) jsonR(['error'=>'Mínimo 6 caracteres'],400);
    $hash=password_hash($pass,PASSWORD_BCRYPT,array('cost'=>BCRYPT_COST));
    $db->prepare("UPDATE usuarios SET password=? WHERE id=?")->execute([$hash,$uid]);
    logActivity($UID,'password_reset','user',$uid);
    jsonR(['success'=>true]);
}
if($act==='admin_user_toggle'&&$method==='POST'){
    if(!$IS_ADMIN) jsonR(['error'=>'Sem permissão'],403);
    $d=json_decode(file_get_contents('php://input'),true);
    $uid=(int)($d['user_id']??0); $active=(int)($d['active']??1);
    if(!$uid) jsonR(['error'=>'ID obrigatório'],400);
    if($uid===$UID) jsonR(['error'=>'Você não pode bloquear a si mesmo'],400);
    $db->prepare("UPDATE usuarios SET active=? WHERE id=?")->execute([$active,$uid]);
    logActivity($UID,$active?'user_unblocked':'user_blocked','user',$uid);
    jsonR(['success'=>true]);
}

// ===== NOTICE FORM (with image upload) =====
if($act==='avisos_form'&&$method==='POST'){
    if(!meHasRole(['admin','presidencia','diretor'])) jsonR(['error'=>'Sem permissão'],403);
    $title=trim($_POST['title']??''); $content=trim($_POST['content']??'');
    $priority=$_POST['priority']??'normal'; $target=$_POST['target_role']??'todos';
    $pinned=(int)($_POST['pinned']??0); $expires=$_POST['expires_at']??null;
    $showBoard=(int)($_POST['show_board']??1); $showCal=(int)($_POST['show_calendar']??0);
    $eventDate=$_POST['event_date']??null;
    if(!$title||!$content) jsonR(['error'=>'Título e conteúdo obrigatórios'],400);
    if(empty($expires)) $expires=null;
    if(empty($eventDate)) $eventDate=null;
    $imgFile=null;
    if(!empty($_FILES['image'])&&$_FILES['image']['error']===UPLOAD_ERR_OK){
        $imgFile=handleUploadBlob($_FILES["image"],$db);
    }
    $db->prepare("INSERT INTO avisos (title,content,priority,target_role,created_by,pinned,expires_at,show_board,show_calendar,event_date,image_file) VALUES (?,?,?,?,?,?,?,?,?,?,?)")
        ->execute([$title,$content,$priority,$target,$UID,$pinned,$expires,$showBoard,$showCal,$eventDate,$imgFile]);
    $nid=$db->lastInsertId();
    logActivity($UID,'notice_created','notice',$nid,$title);
    $users=$db->query("SELECT id FROM usuarios WHERE active=1 AND id!=$UID")->fetchAll();
    foreach($users as $u){
        notify($u['id'],'notice','Novo Aviso: '.$title,'','notice:'.$nid,'notice',$nid);
        sendPushToUser($db, (int)$u['id'], ['title'=>'📢 Novo Aviso','message'=>$title,'url'=>'/index.php#avisos']);
    }
    jsonR(['success'=>true,'id'=>$nid]);
}
if($act==='avisos_form'&&isset($_GET['id'])&&$method==='POST'){
    if(!meHasRole(['admin','presidencia','diretor'])) jsonR(['error'=>'Sem permissão'],403);
    $id=(int)$_GET['id'];
    $title=trim($_POST['title']??''); $content=trim($_POST['content']??'');
    $priority=$_POST['priority']??'normal'; $target=$_POST['target_role']??'todos';
    $pinned=(int)($_POST['pinned']??0); $expires=$_POST['expires_at']??null;
    $showBoard=(int)($_POST['show_board']??1); $showCal=(int)($_POST['show_calendar']??0);
    $eventDate=$_POST['event_date']??null;
    if(!$title||!$content) jsonR(['error'=>'Título e conteúdo obrigatórios'],400);
    if(empty($expires)) $expires=null;
    if(empty($eventDate)) $eventDate=null;
    $imgFile=null;
    if(!empty($_FILES['image'])&&$_FILES['image']['error']===UPLOAD_ERR_OK){
        $imgFile=handleUploadBlob($_FILES["image"],$db);
    }
    $sql="UPDATE avisos SET title=?,content=?,priority=?,target_role=?,pinned=?,expires_at=?,show_board=?,show_calendar=?,event_date=?";
    $params=[$title,$content,$priority,$target,$pinned,$expires,$showBoard,$showCal,$eventDate];
    if($imgFile){ $sql.=",image_file=?"; $params[]=$imgFile; }
    $sql.=" WHERE id=?"; $params[]=$id;
    $db->prepare($sql)->execute($params);
    logActivity($UID,'notice_updated','notice',$id,$title);
    jsonR(['success'=>true]);
}

// ── Permissões de usuário ────────────────────────────────
if($act==='user_permissions'){
    $db->exec("CREATE TABLE IF NOT EXISTS user_permissions (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, permission VARCHAR(100) NOT NULL, granted TINYINT(1) DEFAULT 0, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, UNIQUE KEY uk_user_perm (user_id, permission)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    if(!$IS_ADMIN) jsonR(['error'=>'Sem permissão'],403);
    $uid=(int)($_GET['user_id']??0);
    if(!$uid) jsonR(['error'=>'user_id obrigatorio'],400);
    if($method==='GET'){
        $st=$db->prepare("SELECT permission,granted FROM user_permissions WHERE user_id=?");
        $st->execute([$uid]);
        $rows=[];
        while($r=$st->fetch()){ $rows[$r['permission']]=(int)$r['granted']; }
        jsonR(['permissions'=>$rows,'user_id'=>$uid]);
    }
    if($method==='POST'){
        $d=json_decode(file_get_contents('php://input'),true);
        $perms=$d['permissions']??[];
        $st=$db->prepare("INSERT INTO user_permissions (user_id,permission,granted) VALUES (?,?,?) ON DUPLICATE KEY UPDATE granted=VALUES(granted),updated_at=NOW()");
        foreach($perms as $p){ $st->execute([$uid,$p['permission'],$p['granted']?1:0]); }
        logActivity($UID,'permissions_updated','user',$uid,'Permissoes atualizadas');
        jsonR(['success'=>true]);
    }
}
if($act==='user_permissions_reset'&&$method==='POST'){
    if(!$IS_ADMIN) jsonR(['error'=>'Sem permissão'],403);
    $d=json_decode(file_get_contents('php://input'),true);
    $uid=(int)($d['user_id']??0);
    if(!$uid) jsonR(['error'=>'user_id obrigatorio'],400);
    $db->prepare("DELETE FROM user_permissions WHERE user_id=?")->execute([$uid]);
    logActivity($UID,'permissions_reset','user',$uid,'Permissoes resetadas');
    jsonR(['success'=>true]);
}


// ===== PONTO DIGITAL =====
function _ept($db){static $x=false;if($x)return;$x=true;$db->exec("CREATE TABLE IF NOT EXISTS ponto_sessions(id INT AUTO_INCREMENT PRIMARY KEY,user_id INT NOT NULL,date DATE NOT NULL,clock_in DATETIME NOT NULL,clock_out DATETIME DEFAULT NULL,INDEX idx_ud(user_id,date))ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");try{$db->exec("ALTER TABLE usuarios ADD COLUMN jornada_hours TINYINT UNSIGNED NOT NULL DEFAULT 8");}catch(Exception $ex){}$db->exec("CREATE TABLE IF NOT EXISTS ponto_folgas(id INT AUTO_INCREMENT PRIMARY KEY,user_id INT NOT NULL,date DATE NOT NULL,tipo VARCHAR(50) NOT NULL DEFAULT 'Folga',obs TEXT,created_by INT NOT NULL,created_at DATETIME DEFAULT CURRENT_TIMESTAMP,UNIQUE KEY uq_ud(user_id,date))ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");}

// Auto-insert lunch break 12:00-13:00 if user forgot
function autoLunchBreak($db, $userId, $date) {
    $now = new DateTime();
    $today = $now->format('Y-m-d');
    
    // Só aplica para o dia de hoje e após 15:00
    if ($date !== $today) return;
    if ((int)$now->format('H') < 15) return;
    
    // Verificar se já tem uma pausa que cobre 12:00-13:00
    // (ou seja, alguma sessão termina entre 11:30-12:30 E outra começa entre 12:30-13:30)
    $lunchStart = $date . ' 12:00:00';
    $lunchEnd = $date . ' 13:00:00';
    
    // Buscar sessões do dia
    $st = $db->prepare("SELECT id, clock_in, clock_out FROM ponto_sessions WHERE user_id = ? AND date = ? ORDER BY clock_in ASC");
    $st->execute([$userId, $date]);
    $sessions = $st->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($sessions)) return;
    
    // Verificar se já existe uma gap que cobre ~12:00-13:00
    for ($i = 0; $i < count($sessions) - 1; $i++) {
        $curOut = $sessions[$i]['clock_out'];
        $nextIn = $sessions[$i + 1]['clock_in'];
        if ($curOut && $nextIn) {
            $outTime = strtotime($curOut);
            $inTime = strtotime($nextIn);
            // Se existe um gap entre 11:30 e 13:30 de pelo menos 30min, consideramos como almoço já feito
            $gapStart = strtotime($date . ' 11:30:00');
            $gapEnd = strtotime($date . ' 13:30:00');
            if ($outTime >= $gapStart && $outTime <= $gapEnd && $inTime >= $gapStart && $inTime <= $gapEnd) {
                $gapMinutes = ($inTime - $outTime) / 60;
                if ($gapMinutes >= 30) return; // Já tem pausa de almoço
            }
        }
    }
    
    // Verificar se alguma sessão CRUZA o período 12:00-13:00 continuamente
    $lunchStartTs = strtotime($lunchStart);
    $lunchEndTs = strtotime($lunchEnd);
    
    foreach ($sessions as $sess) {
        $inTs = strtotime($sess['clock_in']);
        $outTs = $sess['clock_out'] ? strtotime($sess['clock_out']) : time();
        
        // Sessão começou antes das 12:00 e termina (ou ainda ativa) depois das 13:00
        if ($inTs < $lunchStartTs && $outTs > $lunchEndTs) {
            // Esta sessão cruza 12:00-13:00 sem pausa — inserir almoço automático
            $sessId = $sess['id'];
            $wasActive = empty($sess['clock_out']); // sessão ainda ativa?
            
            // 1. Fechar sessão original às 12:00
            $db->prepare("UPDATE ponto_sessions SET clock_out = ? WHERE id = ?")
                ->execute([$lunchStart, $sessId]);
            
            // 2. Criar nova sessão começando às 13:00
            if ($wasActive) {
                // Sessão estava ativa — nova sessão às 13:00 sem clock_out (continua ativa)
                $db->prepare("INSERT INTO ponto_sessions (user_id, date, clock_in) VALUES (?, ?, ?)")
                    ->execute([$userId, $date, $lunchEnd]);
            } else {
                // Sessão já estava fechada — nova sessão 13:00 até o clock_out original
                $db->prepare("INSERT INTO ponto_sessions (user_id, date, clock_in, clock_out) VALUES (?, ?, ?, ?)")
                    ->execute([$userId, $date, $lunchEnd, $sess['clock_out']]);
            }
            
            // Log
            try {
                $db->prepare("INSERT INTO registro_atividades (user_id, action, entity_type, entity_id, details) VALUES (?, ?, ?, ?, ?)")
                    ->execute([$userId, 'Almoço automático 12:00-13:00', 'ponto', $sessId, 'Sessão dividida automaticamente (esqueceu de pausar)']);
            } catch (Exception $e) {}
            
            return; // Só precisa tratar uma sessão
        }
    }
}


if($act==='ponto_status'){_ept($db);$t=date('Y-m-d');autoLunchBreak($db,$UID,$t);$a=$db->prepare("SELECT id,clock_in FROM ponto_sessions WHERE user_id=? AND date=? AND clock_out IS NULL ORDER BY id DESC LIMIT 1");$a->execute([$UID,$t]);$as=$a->fetch();$c=$db->prepare("SELECT COALESCE(SUM(TIMESTAMPDIFF(SECOND,clock_in,clock_out)),0)s FROM ponto_sessions WHERE user_id=? AND date=? AND clock_out IS NOT NULL");$c->execute([$UID,$t]);$cs=(int)$c->fetch()['s'];$j=$db->prepare("SELECT COALESCE(work_hours,jornada_hours,8)h FROM usuarios WHERE id=?");$j->execute([$UID]);$jh=(int)($j->fetch()['h']??8);$wh=$db->prepare("SELECT COALESCE(work_hours,jornada_hours,6)h FROM usuarios WHERE id=?");$wh->execute([$UID]);$wk=(int)($wh->fetch()['h']??6);jsonR(['date'=>$t,'user_id'=>$UID,'active'=>!!$as,'status'=>$as?'Ativo':'Pausado','active_since'=>$as?$as['clock_in']:null,'total_seconds'=>$cs,'worked_seconds'=>$cs,'clock_in'=>$as?$as['clock_in']:null,'work_hours'=>$wk,'jornada_hours'=>$jh]);}

if($act==='ponto_today'){_ept($db);$t=date('Y-m-d');autoLunchBreak($db,$UID,$t);$a=$db->prepare("SELECT id,clock_in FROM ponto_sessions WHERE user_id=? AND date=? AND clock_out IS NULL ORDER BY id DESC LIMIT 1");$a->execute([$UID,$t]);$as=$a->fetch();$c=$db->prepare("SELECT COALESCE(SUM(TIMESTAMPDIFF(SECOND,clock_in,clock_out)),0)s FROM ponto_sessions WHERE user_id=? AND date=? AND clock_out IS NOT NULL");$c->execute([$UID,$t]);$cs=(int)$c->fetch()['s'];$j=$db->prepare("SELECT COALESCE(work_hours,jornada_hours,8)h FROM usuarios WHERE id=?");$j->execute([$UID]);$jh=(int)($j->fetch()['h']??8);jsonR(['date'=>$t,'user_id'=>$UID,'status'=>$as?'Ativo':'Pausado','active_since'=>$as?$as['clock_in']:null,'worked_seconds'=>$cs,'jornada_hours'=>$jh]);}
if($act==='ponto_clock_in'){_ept($db);$t=date('Y-m-d');$n=date('Y-m-d H:i:s');$db->prepare("UPDATE ponto_sessions SET clock_out=? WHERE user_id=? AND date=? AND clock_out IS NULL")->execute([$n,$UID,$t]);$db->prepare("INSERT INTO ponto_sessions(user_id,date,clock_in)VALUES(?,?,?)")->execute([$UID,$t,$n]);jsonR(['success'=>true,'clock_in'=>$n]);}
if($act==='ponto_clock_out'){_ept($db);$t=date('Y-m-d');$n=date('Y-m-d H:i:s');$u=$db->prepare("UPDATE ponto_sessions SET clock_out=? WHERE user_id=? AND date=? AND clock_out IS NULL");$u->execute([$n,$UID,$t]);if($u->rowCount()===0)jsonR(['error'=>'Nenhuma sessão ativa'],400);jsonR(['success'=>true,'clock_out'=>$n]);}
if($act==='ponto_team_today'){_ept($db);$t=date('Y-m-d');$_allU=$db->query("SELECT id FROM usuarios WHERE active=1");foreach($_allU->fetchAll()as$_u)autoLunchBreak($db,$_u['id'],$t);$s=$db->prepare("SELECT u.id AS user_id,u.name,u.avatar_color,u.avatar_file,u.role,COALESCE(u.work_hours,u.jornada_hours,8)AS jornada_hours,IF(EXISTS(SELECT 1 FROM ponto_sessions p2 WHERE p2.user_id=u.id AND p2.date=? AND p2.clock_out IS NULL),'Ativo','Pausado')AS status,( COALESCE((SELECT SUM(TIMESTAMPDIFF(SECOND,p3.clock_in,p3.clock_out)) FROM ponto_sessions p3 WHERE p3.user_id=u.id AND p3.date=? AND p3.clock_out IS NOT NULL),0) + COALESCE((SELECT TIMESTAMPDIFF(SECOND,p5.clock_in,NOW()) FROM ponto_sessions p5 WHERE p5.user_id=u.id AND p5.date=? AND p5.clock_out IS NULL ORDER BY p5.id DESC LIMIT 1),0) )AS worked_seconds,(SELECT p4.clock_in FROM ponto_sessions p4 WHERE p4.user_id=u.id AND p4.date=? AND p4.clock_out IS NULL ORDER BY p4.id DESC LIMIT 1)AS active_since FROM usuarios u WHERE u.active=1 ORDER BY u.name");$s->execute([$t,$t,$t,$t]);jsonR($s->fetchAll(PDO::FETCH_ASSOC));}
if($act==='ponto_history'){_ept($db);$u2=isset($_GET['user_id'])&&$IS_ADMIN?(int)$_GET['user_id']:$UID;$fr=$_GET['from']??date('Y-m-01');$to=$_GET['to']??date('Y-m-d');try{$d=$db->prepare("SELECT ps.date,COALESCE(u.work_hours,8)jh,SUM(TIMESTAMPDIFF(SECOND,ps.clock_in,COALESCE(ps.clock_out,NOW())))ws FROM ponto_sessions ps JOIN usuarios u ON ps.user_id=u.id WHERE ps.user_id=? AND ps.date BETWEEN ? AND ? GROUP BY ps.date,u.work_hours ORDER BY ps.date DESC");$d->execute([$u2,$fr,$to]);$days=$d->fetchAll(PDO::FETCH_ASSOC);}catch(Exception $ex){$d2=$db->prepare("SELECT ps.date,SUM(TIMESTAMPDIFF(SECOND,ps.clock_in,COALESCE(ps.clock_out,NOW())))ws FROM ponto_sessions ps WHERE ps.user_id=? AND ps.date BETWEEN ? AND ? GROUP BY ps.date ORDER BY ps.date DESC");$d2->execute([$u2,$fr,$to]);$days=array_map(function($r){$r['jh']=8;return $r;},$d2->fetchAll(PDO::FETCH_ASSOC));}$s2=$db->prepare("SELECT date,clock_in,clock_out FROM ponto_sessions WHERE user_id=? AND date BETWEEN ? AND ? ORDER BY date DESC,clock_in ASC");$s2->execute([$u2,$fr,$to]);$sm=[];foreach($s2->fetchAll(PDO::FETCH_ASSOC)as $r)$sm[$r['date']][]=$r;$res=[];foreach($days as $dx){$jS=(int)($dx['jh']??8)*3600;$wS=(int)($dx['ws']??0);$res[]=['date'=>$dx['date'],'user_id'=>$u2,'jornada_hours'=>(int)($dx['jh']??8),'worked_seconds'=>$wS,'balance_seconds'=>$wS-$jS,'sessions'=>$sm[$dx['date']]??[]];}jsonR($res);}

if($act==='ponto_folga'){_ept($db);if($method==='GET'){$fr=$_GET['from']??date('Y-m-01');$to=$_GET['to']??date('Y-m-d');$q=$db->prepare("SELECT f.*,u.name AS user_name,c.name AS created_by_name FROM ponto_folgas f JOIN usuarios u ON f.user_id=u.id JOIN usuarios c ON f.created_by=c.id WHERE f.date BETWEEN ? AND ? ORDER BY f.date DESC,u.name ASC");$q->execute([$fr,$to]);jsonR($q->fetchAll(PDO::FETCH_ASSOC));}if($method==='POST'){if(!$IS_ADMIN)jsonR(['error'=>'Sem permissão'],403);$b=json_decode(file_get_contents('php://input'),true)??[];$u3=(int)($b['user_id']??0);$dt=$b['date']??'';$tp=$b['tipo']??'Folga';$ob=$b['obs']??'';if(!$u3||!$dt)jsonR(['error'=>'Obrigatórios: user_id e date'],400);$db->prepare("INSERT INTO ponto_folgas(user_id,date,tipo,obs,created_by)VALUES(?,?,?,?,?)ON DUPLICATE KEY UPDATE tipo=VALUES(tipo),obs=VALUES(obs),created_by=VALUES(created_by)")->execute([$u3,$dt,$tp,$ob,$UID]);jsonR(['success'=>true]);}if($method==='DELETE'){if(!$IS_ADMIN)jsonR(['error'=>'Sem permissão'],403);$id2=(int)($_GET['id']??0);if(!$id2)jsonR(['error'=>'id obrigatório'],400);$db->prepare("DELETE FROM ponto_folgas WHERE id=?")->execute([$id2]);jsonR(['success'=>true]);}jsonR(['error'=>'Método não suportado'],405);}
if($act==='ponto_edit'){if(!$IS_ADMIN)jsonR(['error'=>'Sem permissão'],403);_ept($db);$b=json_decode(file_get_contents('php://input'),true)??[];$id=(int)($b['id']??0);if(!$id)jsonR(['error'=>'id obrigatório'],400);$db->prepare("UPDATE ponto_sessions SET clock_in=COALESCE(?,clock_in),clock_out=? WHERE id=?")->execute([$b['clock_in']??null,$b['clock_out']??null,$id]);jsonR(['success'=>true]);}


// ===== MULTI-DEMAND AUTHORIZATION =====
if($act==='multi_demand_request'&&$method==='POST'){
    $d=json_decode(file_get_contents('php://input'),true);
    $demandId=(int)($d['demand_id']??0);
    $justification=trim($d['justification']??'');
    if(!$demandId||!$justification) jsonR(['error'=>'Demanda e justificativa obrigatórios'],400);
    
    $dem=$db->prepare("SELECT title FROM demandas WHERE id=?");$dem->execute([$demandId]);$demTitle=$dem->fetchColumn();
    
    $db->prepare("INSERT INTO multi_demand_requests (user_id,demand_id,justification) VALUES (?,?,?) ON DUPLICATE KEY UPDATE justification=VALUES(justification),status='Pendente',reviewed_by=NULL,reviewed_at=NULL")
        ->execute([$UID,$demandId,$justification]);
    
    // Notificar admins
    $admins=$db->query("SELECT id FROM usuarios WHERE (role LIKE '%admin%') AND active=1")->fetchAll();
    foreach($admins as $a){
        if($a['id']!=$UID){
            notify($a['id'],'demand_new',"{$ME['name']} solicita autorização para demanda simultânea","Demanda: {$demTitle} — Justificativa: {$justification}","demand:{$demandId}",'demand',$demandId);
            sendPushToUser($db,(int)$a['id'],['title'=>'⚠️ Autorização Necessária','message'=>"{$ME['name']} quer iniciar demanda simultânea: {$demTitle}",'url'=>'/index.php#demandas']);
        }
    }
    logActivity($UID,"Solicitou demanda simultânea: {$demTitle}",'demand',$demandId);
    jsonR(['success'=>true,'message'=>'Solicitação enviada! Aguarde aprovação do admin.']);
}

if($act==='multi_demand_review'&&$method==='POST'){
    if(!$IS_ADMIN) jsonR(['error'=>'Apenas admin pode aprovar'],403);
    $d=json_decode(file_get_contents('php://input'),true);
    $requestId=(int)($d['request_id']??0);
    $status=$d['status']??'';
    $notes=trim($d['review_notes']??'');
    if(!$requestId||!in_array($status,['Aprovada','Rejeitada'])) jsonR(['error'=>'Dados inválidos'],400);
    
    $req=$db->prepare("SELECT mr.*,u.name as user_name,d.title as demand_title FROM multi_demand_requests mr JOIN usuarios u ON mr.user_id=u.id JOIN demandas d ON mr.demand_id=d.id WHERE mr.id=?");
    $req->execute([$requestId]);$reqRow=$req->fetch();
    if(!$reqRow) jsonR(['error'=>'Solicitação não encontrada'],404);
    
    $db->prepare("UPDATE multi_demand_requests SET status=?,reviewed_by=?,review_notes=?,reviewed_at=NOW() WHERE id=?")
        ->execute([$status,$UID,$notes,$requestId]);
    
    $msg=$status==='Aprovada'
        ?"Autorização APROVADA para demanda simultânea: {$reqRow['demand_title']}"
        :"Autorização REJEITADA para demanda simultânea: {$reqRow['demand_title']}".($notes?" — {$notes}":'');
    
    notify($reqRow['user_id'],$status==='Aprovada'?'demand_status':'demand_status',$msg,$notes,"demand:{$reqRow['demand_id']}",'demand',$reqRow['demand_id']);
    sendPushToUser($db,(int)$reqRow['user_id'],['title'=>$status==='Aprovada'?'✅ Autorização Aprovada':'❌ Autorização Rejeitada','message'=>$msg,'url'=>'/index.php#demandas']);
    
    // Se aprovada, automaticamente aceitar e iniciar a demanda
    if($status==='Aprovada'){
        $db->prepare("UPDATE devs_demandas SET acceptance='Aceita',assigned_at=NOW() WHERE demand_id=? AND user_id=?")->execute([$reqRow['demand_id'],$reqRow['user_id']]);
        $curStatus=$db->prepare("SELECT status FROM demandas WHERE id=?");$curStatus->execute([$reqRow['demand_id']]);$cs=$curStatus->fetchColumn();
        if(in_array($cs,['Aberta','Aguardando Aceite'])){
            $db->prepare("UPDATE demandas SET status='Em Andamento',started_at=COALESCE(started_at,NOW()) WHERE id=?")->execute([$reqRow['demand_id']]);
            $db->prepare("INSERT INTO historico_demandas (demand_id,user_id,action,new_value,details) VALUES (?,?,'Status alterado','Em Andamento','Autorizado para demanda simultânea')")->execute([$reqRow['demand_id'],$reqRow['user_id']]);
        }
    }
    
    logActivity($UID,"Multi-demanda {$status}: {$reqRow['demand_title']} para {$reqRow['user_name']}",'demand',$reqRow['demand_id']);
    jsonR(['success'=>true]);
}

if($act==='multi_demand_pending'){
    if(!$IS_ADMIN) jsonR(['error'=>'Sem permissão'],403);
    $s=$db->prepare("SELECT mr.*,u.name as user_name,u.avatar_color,u.avatar_file,d.title as demand_title,d.priority,d.system_id,s.name as system_name,
        (SELECT COUNT(*) FROM devs_demandas dd2 JOIN demandas d2 ON dd2.demand_id=d2.id WHERE dd2.user_id=mr.user_id AND d2.status='Em Andamento') as current_active
        FROM multi_demand_requests mr 
        JOIN usuarios u ON mr.user_id=u.id 
        JOIN demandas d ON mr.demand_id=d.id 
        LEFT JOIN sistemas s ON d.system_id=s.id
        WHERE mr.status='Pendente' 
        ORDER BY mr.created_at DESC");
    $s->execute();
    jsonR($s->fetchAll());
}


// ===== 404 FALLBACK =====

// ===== CHECKLIST =====
if($act==='checklist'&&isset($_GET['demand_id'])){
    $did=(int)$_GET['demand_id'];
    if($method==='GET'){
        $s=$db->prepare("SELECT ci.*,u.name as creator_name FROM checklist_items ci LEFT JOIN usuarios u ON ci.created_by=u.id WHERE ci.demand_id=? ORDER BY ci.sort_order,ci.id");
        $s->execute([$did]);jsonR($s->fetchAll());
    }
    if($method==='POST'){
        $d=json_decode(file_get_contents('php://input'),true);
        $text=trim($d['text']??'');if(!$text)jsonR(['error'=>'Texto obrigatório'],400);
        $maxOrder=$db->prepare("SELECT COALESCE(MAX(sort_order),0)+1 FROM checklist_items WHERE demand_id=?");$maxOrder->execute([$did]);$order=$maxOrder->fetchColumn();
        $db->prepare("INSERT INTO checklist_items(demand_id,text,sort_order,created_by)VALUES(?,?,?,?)")->execute([$did,$text,$order,$UID]);
        jsonR(['success'=>true,'id'=>$db->lastInsertId()]);
    }
}
if($act==='checklist_toggle'&&isset($_GET['id'])){
    $cid=(int)$_GET['id'];
    $item=$db->prepare("SELECT * FROM checklist_items WHERE id=?");$item->execute([$cid]);$row=$item->fetch();
    if(!$row)jsonR(['error'=>'Item não encontrado'],404);
    $newDone=$row['done']?0:1;
    $completedAt=$newDone?date('Y-m-d H:i:s'):null;
    $db->prepare("UPDATE checklist_items SET done=?,completed_at=? WHERE id=?")->execute([$newDone,$completedAt,$cid]);
    jsonR(['success'=>true,'done'=>$newDone]);
}
if($act==='checklist_delete'&&isset($_GET['id'])){
    $db->prepare("DELETE FROM checklist_items WHERE id=?")->execute([(int)$_GET['id']]);
    jsonR(['success'=>true]);
}
if($act==='checklist_reorder'&&isset($_GET['demand_id'])){
    $d=json_decode(file_get_contents('php://input'),true);
    $ids=$d['ids']??[];
    foreach($ids as $i=>$cid){$db->prepare("UPDATE checklist_items SET sort_order=? WHERE id=? AND demand_id=?")->execute([$i,(int)$cid,(int)$_GET['demand_id']]);}
    jsonR(['success'=>true]);
}

jsonR(['error'=>'Endpoint não encontrado'],404);


// ============================================================
// ============================================================
} catch (Exception $e) {
    jsonR(['error'=>'Erro interno: '.$e->getMessage()],500);
}
// ============================================================
// ============================================================