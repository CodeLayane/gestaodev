<?php
require_once __DIR__.'/config.php';
$db = getDB();
// Limpar duplicatas de teste
$db->exec("DELETE FROM solicitacoes WHERE title='tesatetetete'");
echo "Limpas: " . $db->query("SELECT ROW_COUNT()")->fetchColumn() . " duplicatas\n";
// Mostrar últimas
$rows = $db->query("SELECT id, title, requester_name, requester_department FROM solicitacoes ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
foreach($rows as $r) echo "#{$r['id']} | {$r['title']} | {$r['requester_name']} | {$r['requester_department']}\n";
