<?php
$pageTitle = 'Integration Guide';
ob_start();
?>
<div class="glass-panel p-8">
    <h2 class="text-2xl font-bold text-white mb-2">InfinityAPI Documentation</h2>
    <p class="text-slate-400 mb-6">
        Ye docs aapko InfinityAPI integrate karne me help karega. Aapko <b>API Key</b> + <b>Client Secret</b> (HMAC signature) chahiye.
        Ye aapko Master Admin provide karega (ya aap <b>Tokens</b> page me dekh sakte ho).
    </p>

    <div class="space-y-6">

        <div class="bg-black/30 p-5 rounded-xl border border-white/10">
            <h3 class="text-white font-bold mb-2">1) Base URL</h3>
            <code class="block bg-black/50 p-3 rounded text-xs text-brand-300 font-mono break-all">https://visionmall.fun</code>
            <div class="text-xs text-slate-400 mt-2">All endpoints below isi domain pe hain.</div>
        </div>

        <div class="bg-black/30 p-5 rounded-xl border border-white/10">
            <h3 class="text-white font-bold mb-2">2) Authentication + Security</h3>
            <div class="text-sm text-slate-300 space-y-2">
                <div>- <b>api_key</b>: har request me query parameter me pass hota hai.</div>
                <div>- <b>Signature (Recommended)</b>: <code class="bg-black/50 px-1 py-0.5 rounded text-xs text-emerald-300 font-mono">ts</code> aur <code class="bg-black/50 px-1 py-0.5 rounded text-xs text-emerald-300 font-mono">sig</code> se request verify hota hai.</div>
                <div>- <b>IP Whitelist (Optional)</b>: agar Master ne enable kiya hai to only whitelisted IPs allowed.</div>
            </div>

            <div class="mt-4">
                <div class="text-xs font-bold text-slate-200 mb-2">Signature format (HMAC-SHA256)</div>
                <code class="block bg-black/50 p-3 rounded text-xs text-slate-200 font-mono break-all">
                    data = api_key|user_id|game_uid|balance|ts
                    <br>
                    sig  = hmac_sha256(data, client_secret)
                </code>
                <div class="text-xs text-slate-400 mt-2">
                    - <b>ts</b> seconds timestamp recommended (ms bhi chalega). Time window ±120 seconds.
                    <br>
                    - <b>sig</b> lowercase hex.
                </div>
            </div>
        </div>

        <div class="bg-black/30 p-5 rounded-xl border border-white/10">
            <h3 class="text-white font-bold mb-4">3) Launch Game - Complete PHP Example</h3>
            
            <?php
            // Fetch client credentials for dynamic display
            $authClientId = $_SESSION['client_auth']['client_id'] ?? null;
            $tokens = $gameManager->getApiTokens($authClientId);
            $apiKey = '';
            $clientSecret = '';
            foreach ($tokens as $t) {
                if ($t['id'] === 'api_key') $apiKey = $t['token'];
                if ($t['id'] === 'client_secret') $clientSecret = $t['token'];
            }
            ?>

            <div class="mt-4">
                <div class="text-xs font-bold text-emerald-400 mb-2">PHP Integration Code (Copy & Paste)</div>
                <pre class="bg-black/50 p-4 rounded text-xs text-slate-200 overflow-x-auto border border-white/10 font-mono"><code><?php echo htmlspecialchars(<<<PHP
<?php
// Configuration
\$apiKey = '{$apiKey}'; // Your Actual API Key
\$clientSecret = '{$clientSecret}'; // Your Actual Client Secret
\$apiUrl = 'https://visionmall.fun/v1/launch';

// User & Game Data
\$userId = 'user_12345'; // Your system's user ID
\$gameUid = '3978';      // Game UID (find in Game List)
\$balance = '100.00';    // User's current balance
\$ts = time();           // Current timestamp (seconds)

// Generate Signature
// Format: api_key|user_id|game_uid|balance|ts
\$dataToSign = \$apiKey . '|' . \$userId . '|' . \$gameUid . '|' . \$balance . '|' . \$ts;
\$sig = hash_hmac('sha256', \$dataToSign, \$clientSecret);

// Build URL with parameters
\$queryString = http_build_query([
    'api_key' => \$apiKey,
    'user_id' => \$userId,
    'game_uid' => \$gameUid,
    'balance' => \$balance,
    'ts' => \$ts,
    'sig' => \$sig,
    // Optional parameters
    // 'currency_code' => 'INR',
    // 'language' => 'en',
    // 'return_url' => 'https://your-site.com/return',
    // 'callback_url' => 'https://your-site.com/callback' (for client-side reference mainly)
]);

\$fullUrl = \$apiUrl . '?' . \$queryString;

// Execute Request
// You can redirect user directly OR fetch JSON if you want to inspect response first
\$response = file_get_contents(\$fullUrl);

if (\$response !== false) {
    \$result = json_decode(\$response, true);

    if (isset(\$result['ok']) && \$result['ok'] === true && isset(\$result['game_url'])) {
        // Success! Redirect user to game
        header("Location: " . \$result['game_url']);
        exit;
    } else {
        // Handle Error
        echo "Error Launching Game: " . (\$result['error'] ?? 'Unknown Error');
    }
} else {
    echo "API Request Failed (Network/Server Error)";
}
?>
PHP
); ?></code></pre>
            </div>
        </div>

        <div class="bg-black/30 p-5 rounded-xl border border-white/10">
            <h3 class="text-white font-bold mb-4">4) Callback Handler - PHP Example</h3>
            <div class="text-sm text-slate-300 mb-4">
                Create a file (e.g., <code>callback.php</code>) on your server and set its URL in the <b>Master Panel > Clients > Settings</b> (Forward Callback URL).
            </div>

            <div class="mt-4">
                <div class="text-xs font-bold text-brand-400 mb-2">callback.php Code</div>
                <pre class="bg-black/50 p-4 rounded text-xs text-slate-200 overflow-x-auto border border-white/10 font-mono"><code><?php echo htmlspecialchars(<<<PHP
<?php
// Receives JSON POST data from InfinityAPI
header('Content-Type: application/json');

// 1. Read Payload
\$rawInput = file_get_contents('php://input');
\$data = json_decode(\$rawInput, true);

if (!\$data) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON']);
    exit;
}

// 2. Extract Data
\$userId = \$data['member_account'] ?? ''; // Your user ID passed during launch
\$gameUid = \$data['game_uid'] ?? '';
\$betAmount = (float)(\$data['bet_amount'] ?? 0);
\$winAmount = (float)(\$data['win_amount'] ?? 0);
\$netAmount = \$winAmount - \$betAmount; // Profit/Loss

// System calculated credit amount (if needed)
\$creditAmount = \$data['credit_amount'] ?? -1;

// 3. Process Transaction in YOUR Database
// Example Logic:
// \$user = getUser(\$userId);
// \$newBalance = \$user['balance'] + \$netAmount;
// updateUserBalance(\$userId, \$newBalance);
// logTransaction(\$userId, \$gameUid, \$betAmount, \$winAmount);

// 4. Log for debugging
file_put_contents('callback.log', date('[Y-m-d H:i:s] ') . \$rawInput . PHP_EOL, FILE_APPEND);

// 5. Respond "OK" (InfinityAPI doesn't strictly require a specific response body for forwarding, but HTTP 200 is good)
echo json_encode(['status' => 'success', 'new_balance' => 0]); // Replace 0 with actual new balance if you want
?>
PHP
); ?></code></pre>
            </div>
            
             <div class="text-xs text-slate-400 mt-4 bg-slate-800/50 p-3 rounded">
                <strong>Note:</strong> InfinityAPI handles the immediate credit/debit with the game provider to ensure speed. This callback is forwarded to you for <b>synchronization</b> with your database.
            </div>
        </div>

        <div class="bg-black/30 p-5 rounded-xl border border-white/10">
            <h3 class="text-white font-bold mb-2">5) Return URL (Game close)</h3>
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs font-bold bg-slate-500/10 text-slate-200 px-2 py-1 rounded">GET</span>
                <code class="bg-black/50 px-2 py-1 rounded text-xs text-brand-300 font-mono">/v1/return</code>
            </div>
            <div class="text-xs text-slate-400 mt-2">
                If you don't pass a <span class="font-mono">return_url</span> param, the user will see a default "Game Closed" page.
            </div>
        </div>

        <div class="bg-black/30 p-5 rounded-xl border border-white/10">
            <h3 class="text-white font-bold mb-4">6) Fetch Games & Providers (Optional)</h3>
            
            <div class="grid gap-6 md:grid-cols-2">
                <div>
                    <h4 class="text-sm font-bold text-brand-400 mb-2">Get All Providers</h4>
                    <div class="flex flex-wrap items-center gap-2 mb-2">
                        <span class="text-xs font-bold bg-emerald-500/10 text-emerald-300 px-2 py-1 rounded">GET</span>
                        <code class="bg-black/50 px-2 py-1 rounded text-xs text-slate-200 font-mono break-all">/api/v1/providers?api_key=YOUR_KEY</code>
                    </div>
                    <button onclick="window.open('/api/v1/providers?api_key=<?= $apiKey ?>', '_blank')" class="mt-2 text-xs bg-brand-600 hover:bg-brand-500 text-white px-3 py-1.5 rounded transition-colors flex items-center gap-2">
                        <ion-icon name="open-outline"></ion-icon> Open in New Tab
                    </button>
                    <p class="text-xs text-slate-400 mt-2">Returns list of active providers.</p>
                </div>
                
                <div>
                    <h4 class="text-sm font-bold text-brand-400 mb-2">Get Games List</h4>
                    <div class="flex flex-wrap items-center gap-2 mb-2">
                        <span class="text-xs font-bold bg-emerald-500/10 text-emerald-300 px-2 py-1 rounded">GET</span>
                        <code class="bg-black/50 px-2 py-1 rounded text-xs text-slate-200 font-mono break-all">/api/v1/games?api_key=YOUR_KEY&provider=NAME</code>
                    </div>
                    <button onclick="openGamesApiWithPrompt('<?= $apiKey ?>')" class="mt-2 text-xs bg-brand-600 hover:bg-brand-500 text-white px-3 py-1.5 rounded transition-colors flex items-center gap-2">
                         <ion-icon name="open-outline"></ion-icon> Open (Filter by Provider)
                    </button>
                    <p class="text-xs text-slate-400 mt-2">
                        Returns list of active games. <br>
                        Optional: <code class="bg-white/10 px-1 rounded">provider</code> (e.g. 'Evolution' or Brand ID)
                    </p>
                </div>
            </div>

            <script>
            function openGamesApiWithPrompt(apiKey) {
                const provider = prompt("Enter Provider Name or Brand ID (Leave empty for all):");
                let url = '/api/v1/games?api_key=' + apiKey;
                
                if (provider !== null) { // If user didn't cancel
                    if (provider.trim() !== '') {
                        url += '&provider=' + encodeURIComponent(provider.trim());
                    }
                    window.open(url, '_blank');
                }
            }
            </script>
        </div>

    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
