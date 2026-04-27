<?php
// REMOVA ESTE ARQUIVO APÓS O TESTE
require_once __DIR__.'/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);
try {
    $mail->isSendmail();
    $mail->CharSet = 'UTF-8';
    $mail->setFrom('gestaodev@assego.com.br', 'GestãoDev ASSEGO');
    $mail->addAddress('tecnico.assego@gmail.com'); // teste enviando para Gmail
    $mail->isHTML(true);
    $mail->Subject = 'Teste GestãoDev';
    $mail->Body    = '<p>Email de teste funcionando!</p>';
    $mail->send();
    echo '<b style="color:green">EMAIL ENVIADO COM SUCESSO!</b>';
} catch (Exception $e) {
    echo '<b style="color:red">ERRO:</b><br>';
    echo nl2br(htmlspecialchars($mail->ErrorInfo));
}
