<?php
/**
 * push-api.php — Endpoints de Web Push Notifications
 * Incluído pelo api.php para gerenciar subscriptions e enviar push
 * 
 * SEGURO: não crasha se push-config.php ou WebPush.php não existirem
 */

// Carrega config VAPID se existir (NÃO usar require — crasharia tudo)
$PUSH_ENABLED = false;
$vapidConfigFile = __DIR__ . '/push-config.php';
if (file_exists($vapidConfigFile)) {
    $webpushLib = __DIR__ . '/lib/WebPush.php';
    if (file_exists($webpushLib)) {
        try {
            require_once $webpushLib;
            require_once $vapidConfigFile;
            $PUSH_ENABLED = defined('VAPID_PUBLIC_KEY') && defined('VAPID_PRIVATE_KEY');
        } catch (Exception $e) {
            error_log('[WebPush] Erro ao carregar: ' . $e->getMessage());
            $PUSH_ENABLED = false;
        }
    }
}

/**
 * Auto-migrate: cria tabela push_subscriptions se não existir
 */
function ensurePushTable(PDO $db): void {
    static $checked = false;
    if ($checked) return;
    $checked = true;

    try {
        $db->exec("CREATE TABLE IF NOT EXISTS push_subscriptions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            endpoint TEXT NOT NULL,
            p256dh_key VARCHAR(255) NOT NULL,
            auth_key VARCHAR(255) NOT NULL,
            user_agent VARCHAR(500) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            UNIQUE KEY uk_endpoint (endpoint(500))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Exception $e) {
        error_log('[WebPush] Erro ao criar tabela: ' . $e->getMessage());
    }
}

/**
 * Retorna chave pública VAPID para o frontend
 */
function handlePushPublicKey(): void {
    global $PUSH_ENABLED;
    if (!$PUSH_ENABLED) {
        jsonR(['enabled' => false, 'key' => null]);
        return;
    }
    jsonR(['enabled' => true, 'key' => VAPID_PUBLIC_KEY]);
}

/**
 * Salva subscription do navegador
 */
function handlePushSubscribe(PDO $db, int $userId): void {
    global $PUSH_ENABLED;
    if (!$PUSH_ENABLED) {
        jsonR(['success' => false, 'error' => 'Push não configurado. Execute: php generate-vapid-keys.php'], 400);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (empty($input['endpoint']) || empty($input['p256dh']) || empty($input['auth'])) {
        jsonR(['success' => false, 'error' => 'Dados incompletos'], 400);
        return;
    }

    ensurePushTable($db);

    $stmt = $db->prepare("INSERT INTO push_subscriptions (user_id, endpoint, p256dh_key, auth_key, user_agent)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE user_id=VALUES(user_id), p256dh_key=VALUES(p256dh_key), auth_key=VALUES(auth_key), user_agent=VALUES(user_agent), created_at=NOW()");

    $stmt->execute([
        $userId,
        $input['endpoint'],
        $input['p256dh'],
        $input['auth'],
        $input['user_agent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? null)
    ]);

    jsonR(['success' => true]);
}

/**
 * Remove subscription
 */
function handlePushUnsubscribe(PDO $db, int $userId): void {
    ensurePushTable($db);

    $input = json_decode(file_get_contents('php://input'), true);
    if (!empty($input['endpoint'])) {
        $stmt = $db->prepare("DELETE FROM push_subscriptions WHERE endpoint = ? AND user_id = ?");
        $stmt->execute([$input['endpoint'], $userId]);
    } else {
        $stmt = $db->prepare("DELETE FROM push_subscriptions WHERE user_id = ?");
        $stmt->execute([$userId]);
    }

    jsonR(['success' => true]);
}

/**
 * Envia push notification para um usuário específico
 * SEGURO: silenciosamente ignora se push não está configurado
 */
function sendPushToUser(PDO $db, int $userId, array $notifData): void {
    global $PUSH_ENABLED;
    if (!$PUSH_ENABLED) return;

    try {
        ensurePushTable($db);

        $stmt = $db->prepare("SELECT endpoint, p256dh_key, auth_key FROM push_subscriptions WHERE user_id = ?");
        $stmt->execute([$userId]);
        $subs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($subs)) return;

        $push = new WebPush(VAPID_PUBLIC_KEY, VAPID_PRIVATE_KEY, VAPID_SUBJECT);

        $payload = json_encode([
            'title'  => $notifData['title'] ?? 'GestãoDev ASSEGO',
            'body'   => $notifData['message'] ?? '',
            'icon'   => rtrim(dirname($_SERVER['SCRIPT_NAME']),'/').'/assets/img/favicon.png',
            'tag'    => 'gestaodev-' . ($notifData['id'] ?? time()),
            'url'    => $notifData['url'] ?? '/index.php',
            'id'     => $notifData['id'] ?? null
        ]);

        foreach ($subs as $sub) {
            $result = $push->send([
                'endpoint' => $sub['endpoint'],
                'p256dh'   => $sub['p256dh_key'],
                'auth'     => $sub['auth_key']
            ], $payload);

            // Endpoint expirado → remover
            if (in_array($result['status'], [404, 410])) {
                $del = $db->prepare("DELETE FROM push_subscriptions WHERE endpoint = ?");
                $del->execute([$sub['endpoint']]);
            }
        }
    } catch (Exception $e) {
        error_log('[WebPush] Erro ao enviar push para user ' . $userId . ': ' . $e->getMessage());
    }
}

/**
 * Envia push para múltiplos usuários
 */
function sendPushToUsers(PDO $db, array $userIds, array $notifData): void {
    foreach ($userIds as $uid) {
        sendPushToUser($db, (int)$uid, $notifData);
    }
}

/**
 * Teste de push (qualquer user logado)
 */
function handlePushTest(PDO $db, int $userId): void {
    global $PUSH_ENABLED;
    if (!$PUSH_ENABLED) {
        jsonR(['success' => false, 'error' => 'Push não configurado. Execute: php generate-vapid-keys.php']);
        return;
    }
    sendPushToUser($db, $userId, [
        'title'   => '🔔 Teste de Push',
        'message' => 'Se você viu essa notificação, o Web Push está funcionando!',
        'url'     => '/index.php'
    ]);
    jsonR(['success' => true, 'message' => 'Push de teste enviado']);
}