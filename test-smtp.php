<?php
// Teste de conectividade SMTP — DELETE após testar!
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__.'/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

define('SMTP_USER', 'tecnico.assego@gmail.com');
define('SMTP_PASS', 'gmyfxpsobpehsgiv');

$destino = $_GET['to'] ?? 'layanesilv560@gmail.com';

echo "<pre style='font-family:monospace;font-size:12px'>";
echo "================================================\n";
echo "TESTE DE CONECTIVIDADE SMTP\n";
echo "Destino: $destino\n";
echo "================================================\n\n";

// 1. Teste de conectividade básica
$hosts = [
    ['smtp.gmail.com', 587, 'STARTTLS'],
    ['smtp.gmail.com', 465, 'SSL'],
    ['mail.assego.com.br', 587, 'STARTTLS'],
    ['mail.assego.com.br', 465, 'SSL'],
    ['smtp.assego.com.br', 587, 'STARTTLS'],
    ['smtp.assego.com.br', 465, 'SSL'],
    ['localhost', 25, 'NONE'],
];

echo "1) Testando conectividade TCP nas portas:\n";
echo "--------------------------------------------------\n";
foreach($hosts as $h){
    $host=$h[0]; $port=$h[1]; $sec=$h[2];
    $start=microtime(true);
    $fp = @fsockopen($host, $port, $errno, $errstr, 5);
    $time = round((microtime(true)-$start)*1000);
    if($fp){
        echo "✅ $host:$port ($sec) — CONECTADO em {$time}ms\n";
        fclose($fp);
    } else {
        echo "❌ $host:$port ($sec) — FALHOU: $errstr (errno: $errno)\n";
    }
}

echo "\n2) Tentando enviar via Gmail (587 STARTTLS):\n";
echo "--------------------------------------------------\n";
try {
    $mail = new PHPMailer(true);
    $mail->SMTPDebug   = SMTP::DEBUG_SERVER;
    $mail->Debugoutput = 'html';
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USER;
    $mail->Password   = SMTP_PASS;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->Timeout    = 10;
    $mail->CharSet    = 'UTF-8';
    $mail->setFrom(SMTP_USER, 'GestãoDev TESTE');
    $mail->addAddress($destino);
    $mail->isHTML(true);
    $mail->Subject = 'Teste SMTP 587 - '.date('H:i:s');
    $mail->Body    = '<h1>Teste 587</h1><p>'.date('d/m/Y H:i:s').'</p>';
    $mail->send();
    echo "\n✅ SUCESSO via 587!";
} catch (Exception $e) {
    echo "\n❌ FALHOU 587: " . $mail->ErrorInfo . "\n";
}

echo "\n\n3) Tentando enviar via Gmail (465 SSL):\n";
echo "--------------------------------------------------\n";
try {
    $mail = new PHPMailer(true);
    $mail->SMTPDebug   = SMTP::DEBUG_SERVER;
    $mail->Debugoutput = 'html';
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USER;
    $mail->Password   = SMTP_PASS;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;
    $mail->Timeout    = 10;
    $mail->CharSet    = 'UTF-8';
    $mail->setFrom(SMTP_USER, 'GestãoDev TESTE');
    $mail->addAddress($destino);
    $mail->isHTML(true);
    $mail->Subject = 'Teste SMTP 465 - '.date('H:i:s');
    $mail->Body    = '<h1>Teste 465</h1><p>'.date('d/m/Y H:i:s').'</p>';
    $mail->send();
    echo "\n✅ SUCESSO via 465!";
} catch (Exception $e) {
    echo "\n❌ FALHOU 465: " . $mail->ErrorInfo . "\n";
}

echo "\n\n4) Tentando enviar via mail() do PHP:\n";
echo "--------------------------------------------------\n";
$headers = "From: gestaodev@assego.com.br\r\n";
$headers.= "MIME-Version: 1.0\r\n";
$headers.= "Content-Type: text/html; charset=UTF-8\r\n";
$ok = @mail($destino, 'Teste mail() - '.date('H:i:s'), '<h1>Teste mail()</h1><p>'.date('d/m/Y H:i:s').'</p>', $headers);
echo $ok ? "✅ mail() retornou TRUE (verifique inbox/spam)\n" : "❌ mail() retornou FALSE\n";

echo "\n================================================\n";
echo "Resultados acima vão indicar qual método funciona\n";
echo "================================================\n";
