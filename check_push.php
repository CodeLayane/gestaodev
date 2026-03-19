<?php
require_once __DIR__.'/config.php';
$db = getDB();
$rows = $db->query("SELECT ps.user_id, u.name FROM push_subscriptions ps JOIN usuarios u ON ps.user_id=u.id GROUP BY ps.user_id")->fetchAll(PDO::FETCH_ASSOC);
foreach($rows as $r) echo "User #{$r['user_id']} ({$r['name']}) tem push registrado\n";
if(!$rows) echo "Nenhuma subscription registrada\n";
