#!/bin/bash
# apply_patches.sh
# Execute na pasta raiz do projeto: bash apply_patches.sh

echo "=== Aplicando patches ==="

# ─── FIX 1: Notificações ─────────────────────────────────────────────────────
# Garantir que notification_read existe nos aliases do api.php
grep -q "'notification_read'" api.php
if [ $? -ne 0 ]; then
    echo "[1] Adicionando alias notification_read..."
    sed -i "s/'notifications_read_all'=>'notificacoes_read_all'/'notifications_read_all'=>'notificacoes_read_all','notification_read'=>'notification_read'/" api.php
    echo "    OK"
else
    echo "[1] Alias notification_read já existe, pulando."
fi

# Fix: garantir que notificacoes_read_all não exige método POST específico
grep -q "notificacoes_read_all" api.php
if [ $? -eq 0 ]; then
    echo "[1b] Handler notificacoes_read_all encontrado. OK"
else
    echo "[1b] ATENÇÃO: handler notificacoes_read_all não encontrado no api.php"
fi

# ─── FIX 2: Relatório semanal → diário ───────────────────────────────────────
echo "[2] Corrigindo labels semanal → diário..."

# No JS
sed -i 's/Relatório Semanal/Relatório Diário/gi' assets/js/app.js
sed -i 's/relatório semanal/relatório diário/gi' assets/js/app.js
sed -i 's/Semanal/Diário/gi' assets/js/app.js 2>/dev/null

# No index.php
sed -i 's/Relatório Semanal/Relatório Diário/gi' index.php 2>/dev/null
sed -i 's/Semanal/Diário/gi' index.php 2>/dev/null

# Período padrão: de 90 dias para hoje
sed -i 's/Date.now()-90\*86400000/Date.now()-1\*86400000/g' assets/js/app.js
echo "    OK"

# ─── FIX 3: Adicionar endpoint bottleneck no api.php ─────────────────────────
echo "[3] Verificando endpoint bottleneck_report..."
grep -q "bottleneck_report" api.php
if [ $? -ne 0 ]; then
    echo "    Adicionando endpoint bottleneck_report ao api.php..."
    # Insere antes do catch() final
    BOTTLENECK=$(cat << 'PHPEOF'

// ===== BOTTLENECK REPORT =====
if($act==='bottleneck_report'){
    if(!$IS_ADMIN&&!$IS_DIR&&!$IS_PRES) jsonR(['error'=>'Sem permissão'],403);
    $dateFrom=$_GET['date_from']??date('Y-m-d',strtotime('-30 days'));
    $dateTo=$_GET['date_to']??date('Y-m-d');
    $df=$dateFrom.' 00:00:00'; $dt=$dateTo.' 23:59:59';
    $stuck=$db->prepare("SELECT d.id,d.title,d.status,d.priority,s.name as system_name,DATEDIFF(NOW(),d.updated_at) as days_stuck,d.updated_at,GROUP_CONCAT(u.name SEPARATOR ', ') as devs FROM demandas d LEFT JOIN sistemas s ON d.system_id=s.id LEFT JOIN devs_demandas dd ON d.id=dd.demand_id LEFT JOIN usuarios u ON dd.user_id=u.id WHERE d.status NOT IN('Concluída','Cancelada') AND d.updated_at<DATE_SUB(NOW(),INTERVAL 3 DAY) GROUP BY d.id ORDER BY days_stuck DESC LIMIT 20");
    $stuck->execute(); $stuckData=$stuck->fetchAll();
    $sf=$db->prepare("SELECT status,COUNT(*) as total,ROUND(AVG(DATEDIFF(COALESCE(completed_at,NOW()),created_at)),1) as avg_days,MAX(DATEDIFF(COALESCE(completed_at,NOW()),created_at)) as max_days FROM demandas WHERE created_at BETWEEN ? AND ? GROUP BY status ORDER BY avg_days DESC");
    $sf->execute([$df,$dt]); $sfData=$sf->fetchAll();
    $sb=$db->prepare("SELECT s.name,s.id,COUNT(d.id) as total_open,SUM(CASE WHEN d.priority='Urgente' THEN 1 ELSE 0 END) as urgentes,SUM(CASE WHEN d.priority='Alta' THEN 1 ELSE 0 END) as altas,ROUND(AVG(DATEDIFF(NOW(),d.created_at)),1) as avg_age_days FROM sistemas s JOIN demandas d ON s.id=d.system_id WHERE d.status NOT IN('Concluída','Cancelada') GROUP BY s.id HAVING total_open>0 ORDER BY urgentes DESC,total_open DESC LIMIT 10");
    $sb->execute(); $sbData=$sb->fetchAll();
    $ov=$db->prepare("SELECT u.id,u.name,u.avatar_color,COUNT(dd.demand_id) as em_andamento,SUM(CASE WHEN d.priority='Urgente' THEN 1 ELSE 0 END) as urgentes,SUM(CASE WHEN d.deadline<CURDATE() THEN 1 ELSE 0 END) as atrasadas FROM usuarios u JOIN devs_demandas dd ON u.id=dd.user_id JOIN demandas d ON dd.demand_id=d.id WHERE d.status='Em Andamento' AND u.active=1 GROUP BY u.id ORDER BY em_andamento DESC");
    $ov->execute(); $ovData=$ov->fetchAll();
    $np=$db->prepare("SELECT COUNT(*) as sem_prazo,(SELECT COUNT(*) FROM demandas WHERE status NOT IN('Concluída','Cancelada')) as total_ativas FROM demandas WHERE deadline IS NULL AND status NOT IN('Concluída','Cancelada')");
    $np->execute(); $npData=$np->fetch();
    jsonR(['stuck_demands'=>$stuckData,'status_flow'=>$sfData,'sys_bottleneck'=>$sbData,'overloaded_devs'=>$ovData,'no_deadline'=>$npData,'date_from'=>$dateFrom,'date_to'=>$dateTo]);
}

PHPEOF
)
    # Insere antes do catch final
    python3 -c "
import sys
content = open('api.php').read()
insert = '''${BOTTLENECK}'''
# Insere antes do último catch
pos = content.rfind('} catch')
if pos > 0:
    content = content[:pos] + insert + content[pos:]
    open('api.php','w').write(content)
    print('Inserido com sucesso')
else:
    print('AVISO: catch não encontrado, insira manualmente')
"
else
    echo "    Endpoint bottleneck_report já existe, pulando."
fi

echo ""
echo "=== Patches aplicados! ==="
echo "Agora aplique os fixes de JS manualmente conforme patch_js_fixes.js"
echo ""
echo "Próximos passos no assets/js/app.js:"
echo "1. Substitua a função readNotif pelo código do patch_js_fixes.js"
echo "2. Adicione bottleneck_report no Promise.all de loadReports()"  
echo "3. Adicione: html += buildGargaloHTML(bottleneck); antes do innerHTML"
echo "4. Cole as funções buildGargaloHTML e kpiCard no final do arquivo"
