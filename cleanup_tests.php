<?php
require_once __DIR__.'/config.php';
$db = getDB();
$db->exec("DELETE FROM solicitacoes WHERE title IN ('Testeeetete','layane','LAYANE','tesatetetete')");
echo "Removidos: " . $db->query("SELECT ROW_COUNT()")->fetchColumn() . "\n";
