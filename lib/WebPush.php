<?php
/**
 * WebPush.php — Implementação standalone de Web Push (RFC 8291 + VAPID RFC 8292)
 * Sem dependências externas, usa apenas openssl nativo do PHP 7.4+
 */
class WebPush {
    private $vapidPublicKey;  // raw 65 bytes
    private $vapidPrivateKey; // PEM format
    private $vapidSubject;

    /**
     * @param string $publicKeyB64  Chave pública VAPID base64url (65 bytes raw)
     * @param string $privateKeyPem Chave privada VAPID em formato PEM
     * @param string $subject       mailto: ou URL do responsável
     */
    public function __construct(string $publicKeyB64, string $privateKeyPem, string $subject) {
        $this->vapidPublicKey = self::base64UrlDecode($publicKeyB64);
        $this->vapidPrivateKey = $privateKeyPem;
        $this->vapidSubject = $subject;
    }

    /**
     * Envia notificação push para uma subscription
     *
     * @param array  $subscription ['endpoint'=>..., 'p256dh'=>..., 'auth'=>...]
     * @param string $payload      JSON string do conteúdo
     * @param int    $ttl          Tempo de vida em segundos
     * @return array ['success'=>bool, 'status'=>int, 'reason'=>string]
     */
    public function send(array $subscription, string $payload, int $ttl = 86400): array {
        try {
            $endpoint = $subscription['endpoint'];
            $userPublicKey = self::base64UrlDecode($subscription['p256dh']);
            $userAuth = self::base64UrlDecode($subscription['auth']);

            // 1. Encriptar payload (RFC 8291 - aes128gcm)
            $encrypted = $this->encrypt($payload, $userPublicKey, $userAuth);

            // 2. Gerar headers VAPID
            $vapidHeaders = $this->createVapidHeaders($endpoint);

            // 3. Enviar via cURL
            return $this->sendRequest($endpoint, $encrypted, $vapidHeaders, $ttl);
        } catch (\Exception $e) {
            return ['success' => false, 'status' => 0, 'reason' => $e->getMessage()];
        }
    }

    /**
     * Envia push para múltiplas subscriptions
     */
    public function sendBatch(array $subscriptions, string $payload, int $ttl = 86400): array {
        $results = [];
        foreach ($subscriptions as $sub) {
            $results[] = array_merge(['endpoint' => $sub['endpoint']], $this->send($sub, $payload, $ttl));
        }
        return $results;
    }

    // ==================== ENCRYPTION (RFC 8291) ====================

    private function encrypt(string $payload, string $userPublicKey, string $userAuth): string {
        // Gerar salt aleatório (16 bytes)
        $salt = random_bytes(16);

        // Gerar par de chaves EC locais (P-256)
        $localKey = openssl_pkey_new([
            'curve_name'       => 'prime256v1',
            'private_key_type' => OPENSSL_KEYTYPE_EC
        ]);
        if (!$localKey) throw new \Exception('Falha ao gerar chave EC local');

        $localDetails = openssl_pkey_get_details($localKey);
        // Chave pública local em formato não-comprimido (65 bytes: 0x04 + x + y)
        $localPublicKey = "\x04" . str_pad($localDetails['ec']['x'], 32, "\x00", STR_PAD_LEFT)
                                 . str_pad($localDetails['ec']['y'], 32, "\x00", STR_PAD_LEFT);

        // ECDH: derivar shared secret
        $userPublicKeyPem = $this->rawPublicKeyToPem($userPublicKey);
        $userKeyResource = openssl_pkey_get_public($userPublicKeyPem);
        if (!$userKeyResource) throw new \Exception('Chave pública do user inválida');

$sharedSecret = openssl_pkey_derive($userKeyResource, $localKey, 256);
        if ($sharedSecret === false) {
            throw new \Exception('Falha no ECDH: ' . openssl_error_string());
        }

        // IKM = HKDF(sharedSecret, auth, "WebPush: info\0" + ua_public + local_public, 32)
        $keyInfo = "WebPush: info\x00" . $userPublicKey . $localPublicKey;
        $ikm = $this->hkdf($sharedSecret, $userAuth, $keyInfo, 32);

        // CEK = HKDF(ikm, salt, "Content-Encoding: aes128gcm\0", 16)
        $cekInfo = "Content-Encoding: aes128gcm\x00";
        $cek = $this->hkdf($ikm, $salt, $cekInfo, 16);

        // Nonce = HKDF(ikm, salt, "Content-Encoding: nonce\0", 12)
        $nonceInfo = "Content-Encoding: nonce\x00";
        $nonce = $this->hkdf($ikm, $salt, $nonceInfo, 12);

        // Padding: payload + delimiter byte (0x02)
        $padded = $payload . "\x02";

        // AES-128-GCM encrypt
        $tag = '';
        $ciphertext = openssl_encrypt($padded, 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, $tag, '', 16);
        if ($ciphertext === false) throw new \Exception('Falha na encriptação AES-GCM');

        // Construir body: salt(16) + rs(4) + idlen(1) + keyid(65) + ciphertext + tag
        $rs = pack('N', 4096); // record size = 4096
        $idlen = chr(65);       // length of keyid (local public key)

        return $salt . $rs . $idlen . $localPublicKey . $ciphertext . $tag;
    }

    // ==================== VAPID (RFC 8292) ====================

    private function createVapidHeaders(string $endpoint): array {
        $parsed = parse_url($endpoint);
        $audience = $parsed['scheme'] . '://' . $parsed['host'];

        $header = ['typ' => 'JWT', 'alg' => 'ES256'];
        $payload = [
            'aud' => $audience,
            'exp' => time() + 43200, // 12h
            'sub' => $this->vapidSubject
        ];

        $jwt = $this->createJwt($header, $payload);
        $publicKeyB64 = self::base64UrlEncode($this->vapidPublicKey);

        return [
            'Authorization' => 'vapid t=' . $jwt . ', k=' . $publicKeyB64,
            'Crypto-Key'    => '' // não usado em aes128gcm
        ];
    }

    private function createJwt(array $header, array $payload): string {
        $headerB64 = self::base64UrlEncode(json_encode($header));
        $payloadB64 = self::base64UrlEncode(json_encode($payload));
        $signingInput = $headerB64 . '.' . $payloadB64;

        $privKey = openssl_pkey_get_private($this->vapidPrivateKey);
        if (!$privKey) throw new \Exception('Chave privada VAPID inválida');

        $signature = '';
        if (!openssl_sign($signingInput, $signature, $privKey, OPENSSL_ALGO_SHA256)) {
            throw new \Exception('Falha ao assinar JWT');
        }

        // Converter assinatura DER para formato raw R||S (64 bytes)
        $rawSignature = $this->derToRaw($signature);

        return $signingInput . '.' . self::base64UrlEncode($rawSignature);
    }

    // ==================== HTTP REQUEST ====================

    private function sendRequest(string $endpoint, string $body, array $vapidHeaders, int $ttl): array {
        $headers = [
            'Content-Type: application/octet-stream',
            'Content-Encoding: aes128gcm',
            'Content-Length: ' . strlen($body),
            'TTL: ' . $ttl,
            $vapidHeaders['Authorization'] ? 'Authorization: ' . $vapidHeaders['Authorization'] : '',
        ];
        $headers = array_filter($headers);

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_FOLLOWLOCATION => true,
        ]);

        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'status' => 0, 'reason' => 'cURL: ' . $error];
        }

        return [
            'success' => ($status >= 200 && $status < 300),
            'status'  => $status,
            'reason'  => $status === 201 ? 'OK' : ($response ?: "HTTP $status")
        ];
    }

    // ==================== HELPERS ====================

    /**
     * HKDF (RFC 5869) - Extract + Expand (single round, para outputs ≤ 32 bytes)
     */
    private function hkdf(string $ikm, string $salt, string $info, int $length): string {
        // Extract
        $prk = hash_hmac('sha256', $ikm, $salt, true);
        // Expand (single round — suficiente para ≤ 32 bytes)
        $okm = hash_hmac('sha256', $info . "\x01", $prk, true);
        return substr($okm, 0, $length);
    }

    /**
     * Converter chave pública EC raw (65 bytes) para PEM
     */
    private function rawPublicKeyToPem(string $rawKey): string {
        // ASN.1 DER header para EC P-256 public key
        $derHeader = hex2bin('3059301306072a8648ce3d020106082a8648ce3d030107034200');
        $der = $derHeader . $rawKey;
        $pem = "-----BEGIN PUBLIC KEY-----\n"
             . chunk_split(base64_encode($der), 64, "\n")
             . "-----END PUBLIC KEY-----\n";
        return $pem;
    }

    /**
     * Converter assinatura DER (ECDSA) para formato raw R||S (64 bytes)
     */
    private function derToRaw(string $der): string {
        $pos = 0;
        if (ord($der[$pos++]) !== 0x30) throw new \Exception('DER: não é SEQUENCE');
        $pos++; // length

        // R
        if (ord($der[$pos++]) !== 0x02) throw new \Exception('DER: R não é INTEGER');
        $rLen = ord($der[$pos++]);
        $r = substr($der, $pos, $rLen);
        $pos += $rLen;

        // S
        if (ord($der[$pos++]) !== 0x02) throw new \Exception('DER: S não é INTEGER');
        $sLen = ord($der[$pos++]);
        $s = substr($der, $pos, $sLen);

        // Garantir 32 bytes cada (remover leading zeros ou pad)
        $r = str_pad(ltrim($r, "\x00"), 32, "\x00", STR_PAD_LEFT);
        $s = str_pad(ltrim($s, "\x00"), 32, "\x00", STR_PAD_LEFT);

        return $r . $s;
    }

    // ==================== BASE64URL ====================

    public static function base64UrlEncode(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public static function base64UrlDecode(string $data): string {
        return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', (4 - strlen($data) % 4) % 4));
    }
}
