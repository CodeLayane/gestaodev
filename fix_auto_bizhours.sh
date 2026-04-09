#!/bin/bash
API="/var/www/html/layane/gestaodev/api.php"

python3 << 'PYEOF'
API = "/var/www/html/layane/gestaodev/api.php"
with open(API, 'r') as f:
    c = f.read()

fixes = 0

# 1. Replace the simple time calculation with business hours function
old_limit = """    // Calcular datetime limite (6h úteis = aprox 6h em horário comercial)
    // Simplificação: usar 6h reais por enquanto, considerando que a maioria cai em horário útil
    $limitDate=date('Y-m-d H:i:s',strtotime("-{$timeoutH} hours"));"""

new_limit = """    // Calcular datetime limite em horas ÚTEIS (8h-18h, seg-sex)
    function calcBusinessHoursAgo($hours){
        $now=new DateTime();
        $remaining=$hours*60; // em minutos
        $cur=clone $now;
        while($remaining>0){
            $cur->modify('-1 minute');
            $dow=(int)$cur->format('N'); // 1=seg, 7=dom
            $h=(int)$cur->format('G');
            if($dow<=5 && $h>=8 && $h<18){
                $remaining--;
            }
        }
        return $cur->format('Y-m-d H:i:s');
    }
    $limitDate=calcBusinessHoursAgo($timeoutH);"""

if old_limit in c:
    c = c.replace(old_limit, new_limit, 1)
    fixes += 1
    print("  1. Calculo horas uteis implementado")

# 2. Improve notifications - add presidency to auto-approve notifications
old_notify_approve = """            // Notificar admins
            $admins=$db->query("SELECT id FROM usuarios WHERE (role LIKE '%admin%' OR role LIKE '%diretor%') AND active=1")->fetchAll();
            foreach($admins as $a){
                notify($a['id'],'solicitation',"Solicitação #{$sol['id']} auto-aprovada (sem análise em {$timeoutH}h)","Título: {$sol['title']}","demand:{$did}",'demand',$did);
            }"""

new_notify_approve = """            // Notificar admins, diretores e presidencia
            $notifyRoles=$db->query("SELECT id,name,role FROM usuarios WHERE (role LIKE '%admin%' OR role LIKE '%diretor%' OR role LIKE '%presidencia%') AND active=1")->fetchAll();
            foreach($notifyRoles as $a){
                notify($a['id'],'solicitation',"[AUTO] Solicitação #{$sol['id']} aprovada automaticamente","Sem análise em {$timeoutH}h úteis. Título: {$sol['title']}. Convertida em demanda #{$did}.","demand:{$did}",'demand',$did);
                sendPushToUser($db,(int)$a['id'],['title'=>'⚙️ Auto-aprovação','message'=>"Solicitação #{$sol['id']} auto-aprovada: {$sol['title']}",'url'=>'/index.php#demandas']);
            }"""

if old_notify_approve in c:
    c = c.replace(old_notify_approve, new_notify_approve, 1)
    fixes += 1
    print("  2. Notificacao auto-aprovar melhorada")

# 3. Improve notifications for auto-complete reviews
old_notify_review = """            // Notificar admins
            $admins=$db->query("SELECT id FROM usuarios WHERE (role LIKE '%admin%' OR role LIKE '%diretor%') AND active=1")->fetchAll();
            foreach($admins as $a){
                notify($a['id'],'demand_completed',"Demanda #{$rev['id']} auto-concluída (revisão sem análise em {$timeoutH}h)","Título: {$rev['title']}","demand:{$rev['id']}",'demand',$rev['id']);
            }"""

new_notify_review = """            // Notificar admins, diretores e presidencia
            $notifyRoles2=$db->query("SELECT id FROM usuarios WHERE (role LIKE '%admin%' OR role LIKE '%diretor%' OR role LIKE '%presidencia%') AND active=1")->fetchAll();
            foreach($notifyRoles2 as $a){
                notify($a['id'],'demand_completed',"[AUTO] Demanda #{$rev['id']} concluída automaticamente","Revisão sem análise em {$timeoutH}h úteis. Título: {$rev['title']}","demand:{$rev['id']}",'demand',$rev['id']);
                sendPushToUser($db,(int)$a['id'],['title'=>'⚙️ Auto-conclusão','message'=>"Demanda #{$rev['id']} auto-concluída: {$rev['title']}",'url'=>'/index.php#demandas']);
            }"""

if old_notify_review in c:
    c = c.replace(old_notify_review, new_notify_review, 1)
    fixes += 1
    print("  3. Notificacao auto-concluir melhorada")

with open(API, 'w') as f:
    f.write(c)

print(f"\n  Total: {fixes}")
PYEOF

php -l "$API" 2>&1 | head -3
echo "  Subir: api.php + Ctrl+F5"
