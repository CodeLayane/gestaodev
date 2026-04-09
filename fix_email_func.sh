#!/bin/bash

# 1. Create the email function file
cat > /var/www/html/layane/gestaodev/email-notify.php << 'PHPEOF'
<?php
function sendEmailNotification($db, $userId, $subject, $body){
    try {
        $st=$db->prepare("SELECT email,name,email_notifications FROM usuarios WHERE id=? AND active=1");
        $st->execute([$userId]);
        $user=$st->fetch();
        if(!$user || !$user['email_notifications']) return false;
        $to=$user['email'];
        $name=$user['name'];
        $headers="From: GestãoDev ASSEGO <noreply@assego.org.br>\r\n";
        $headers.="MIME-Version: 1.0\r\n";
        $headers.="Content-Type: text/html; charset=UTF-8\r\n";
        $htmlBody="<div style='font-family:Arial,sans-serif;max-width:600px;margin:0 auto;background:#0a0e17;color:#e8ecf4;border-radius:12px;overflow:hidden;border:1px solid #2a3654'>";
        $htmlBody.="<div style='background:linear-gradient(135deg,#3b82f6,#8b5cf6);padding:20px 24px'>";
        $htmlBody.="<h1 style='margin:0;font-size:18px;color:#fff'>GestãoDev ASSEGO</h1></div>";
        $htmlBody.="<div style='padding:24px'>";
        $htmlBody.="<p style='margin:0 0 8px;color:#8899b8'>Olá, <strong style='color:#e8ecf4'>{$name}</strong></p>";
        $htmlBody.="<div style='background:#111827;border:1px solid #2a3654;border-radius:8px;padding:16px;margin:12px 0'>";
        $htmlBody.=$body;
        $htmlBody.="</div>";
        $htmlBody.="<p style='margin:16px 0 0;font-size:12px;color:#5a6d8f'>Notificação automática do GestãoDev ASSEGO.</p>";
        $htmlBody.="</div></div>";
        return @mail($to, '=?UTF-8?B?'.base64_encode($subject).'?=', $htmlBody, $headers);
    } catch(Exception $e) { return false; }
}
PHPEOF

echo "  ✅ 1. email-notify.php criado"

# 2. Add require_once in api.php after push-api.php
python3 -c "
f='/var/www/html/layane/gestaodev/api.php'
c=open(f).read()
old=\"require_once __DIR__.'/push-api.php';\"
new=\"require_once __DIR__.'/push-api.php';\nrequire_once __DIR__.'/email-notify.php';\"
if 'email-notify.php' not in c:
    c=c.replace(old,new,1)
    open(f,'w').write(c)
    print('  OK 2. require adicionado')
else:
    print('  JA EXISTE')
"

php -l /var/www/html/layane/gestaodev/api.php 2>&1 | head -3
echo "  Subir: api.php + email-notify.php + assets/js/app.js"
