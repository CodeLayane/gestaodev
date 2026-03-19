<?php
require_once __DIR__.'/config.php';
$db = getDB();
$rows = $db->query("SELECT id, title, requester_name, created_by FROM solicitacoes WHERE requester_name LIKE '%layane%' ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
foreach($rows as $r) echo "#{$r['id']} | {$r['title']} | req:{$r['requester_name']} | criado_por:{$r['created_by']}\n";
