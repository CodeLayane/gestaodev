<?php
require_once __DIR__.'/config.php';
$db = getDB();
$rows = $db->query("SELECT id, title, requester_name, requester_department, created_by FROM solicitacoes ORDER BY id DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
foreach($rows as $r) echo "#{$r['id']} | {$r['title']} | nome:{$r['requester_name']} | dept:{$r['requester_department']} | created_by:{$r['created_by']}\n";
