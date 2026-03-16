<?php
// Game Launcher Logic

class GameLauncher {
    private $apiToken;
    private $apiSecret;
    private $serverUrl;

    public function __construct($token, $secret, $serverUrl = 'https://igamingapis.live/api/v1') {
        $this->apiToken = $token;
        $this->apiSecret = $secret;
        $this->serverUrl = $serverUrl;
    }

    public function launchGame($userId, $balance, $gameUid, $returnUrl, $callbackUrl, $currency = null, $language = null) {
        $payload = [
            'user_id' => (string)$userId,
            'balance' => (string)$balance,
            'game_uid' => (string)$gameUid,
            'token' => $this->apiToken,
            'timestamp' => round(microtime(true) * 1000),
            'return' => $returnUrl,
            'callback' => $callbackUrl
        ];
        
        if ($currency) {
            $payload['currency_code'] = $currency;
        }

        if ($language) {
            $payload['language'] = $language;
        }

        $encryptedPayload = $this->encryptPayload($payload);
        
        $url = $this->serverUrl . '?payload=' . urlencode($encryptedPayload); // Token is usually inside payload for this provider based on doc, but sometimes also query param. Doc says url = server + ?payload=ENC + &token=TOKEN. Let's follow doc.
        $url .= '&token=' . urlencode($this->apiToken);

        // Making the request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // In production, set to true with proper certs
        
        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
             return ['code' => -1, 'msg' => 'Curl Error: ' . curl_error($ch)];
        }
        
        curl_close($ch);
        
        return json_decode($response, true);
    }

    private function encryptPayload($data) {
        $json = json_encode($data);
        // AES-256-ECB encryption
        // Note: OPENSSL_RAW_DATA returns raw binary, which we then base64_encode
        $encrypted = openssl_encrypt($json, 'AES-256-ECB', $this->apiSecret, OPENSSL_RAW_DATA);
        return base64_encode($encrypted);
    }
}
