<?php
$pageTitle = 'IP Whitelist';
$authClientId = $_SESSION['client_auth']['client_id'] ?? null;
$whitelistedIPs = $gameManager->getWhitelistedIPs($authClientId);
$ipEnabled = $gameManager->isIpWhitelistEnabled($authClientId);

ob_start();
?>
<div class="space-y-6">

    <div class="glass-panel p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold text-white">Whitelisted IPs</h2>
            <div class="text-xs font-bold <?= $ipEnabled ? 'text-emerald-300' : 'text-slate-500' ?>">
                <?= $ipEnabled ? 'ENABLED' : 'DISABLED' ?>
            </div>
        </div>

        <p class="text-slate-400 mt-1">This list is managed by Master Admin. When enabled, only these IPs can call your API key (unless you also use signature).</p>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-400">
                <thead>
                    <tr class="text-xs uppercase tracking-wider text-slate-500 border-b border-white/10">
                        <th class="pb-3 pl-4">IP Address</th>
                        <th class="pb-3 pr-4">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <?php foreach($whitelistedIPs as $ip): ?>
                    <tr class="hover:bg-white/5 transition-colors group">
                        <td class="py-4 pl-4 font-mono text-xs text-slate-300 flex items-center gap-2">
                             <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
                             <?= htmlspecialchars((string)$ip) ?>
                        </td>
                        <td class="py-4 pr-4 text-xs <?= $ipEnabled ? 'text-emerald-300' : 'text-slate-500' ?>"><?= $ipEnabled ? 'ACTIVE' : 'NOT ENFORCED' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if(empty($whitelistedIPs)): ?>
        <div class="text-center py-12 text-slate-500">
            <ion-icon name="shield-checkmark-outline" class="text-4xl text-slate-600 mb-2"></ion-icon>
            <p>No IPs whitelisted for your client.</p>
        </div>
        <?php endif; ?>
    </div>
    
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
