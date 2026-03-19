<?php
require_once __DIR__.'/config.php';
$db = getDB();
$cols = $db->query("SHOW COLUMNS FROM solicitacoes")->fetchAll(PDO::FETCH_ASSOC);
foreach($cols as $c) echo $c['Field'].' - '.$c['Type'].' - '.$c['Default']."\n";
