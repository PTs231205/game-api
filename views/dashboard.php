<?php
// Main Dashboard
$pageTitle = $langManager->trans('dashboard.title');

// Fetch Data
// Fetch Data
$authClientId = $_SESSION['client_auth']['client_id'] ?? null;
$walletBalance = $gameManager->getWalletBalance($authClientId);
$ggrBalance = $gameManager->getGgrBalance($authClientId);
$currency = $gameManager->getCurrency($authClientId);
$recentCallbacks = $gameManager->getRecentCallbacks($authClientId, 5);

ob_start();
?>
<div class="space-y-8">

    <!-- Stats Grid -->
    <?php $stats = $gameManager->getDashboardStats($authClientId); ?>
    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
        <div class="glass-panel p-4">
            <div class="text-xs font-medium text-slate-400 mb-1">Requests (24h)</div>
            <div class="text-xl font-bold text-white"><?= number_format((int)$stats['requests_24h']) ?></div>
        </div>
        <div class="glass-panel p-4">
            <div class="text-xs font-medium text-slate-400 mb-1">Success (24h)</div>
            <div class="text-xl font-bold text-emerald-400"><?= number_format((int)$stats['success_24h']) ?></div>
        </div>
        <div class="glass-panel p-4">
            <div class="text-xs font-medium text-slate-400 mb-1">Failed (24h)</div>
            <div class="text-xl font-bold text-red-400"><?= number_format((int)$stats['fail_24h']) ?></div>
        </div>
        <div class="glass-panel p-4">
            <div class="text-xs font-medium text-slate-400 mb-1">Avg Latency (24h)</div>
            <div class="text-xl font-bold text-white"><?= number_format((int)$stats['avg_latency_24h']) ?>ms</div>
        </div>
        <div class="glass-panel p-4">
            <div class="text-xs font-medium text-slate-400 mb-1">Wallet Balance</div>
            <div class="text-xl font-bold text-white"><?= number_format($walletBalance, 2) ?> <span class="text-xs text-brand-400"><?= htmlspecialchars($currency) ?></span></div>
        </div>
        <div class="glass-panel p-4">
            <div class="text-xs font-medium text-slate-400 mb-1">GGR %</div>
            <div class="text-xl font-bold text-white"><?= number_format($ggrBalance, 2) ?>%</div>
        </div>
    </div>

    <!-- Providers Overview -->
    <?php 
    $provStats = $gameManager->getProviderStats($authClientId); 
    // If user has no access (e.g. invalid client id), default to empty
    $provStats = $provStats ?: [
        'total_providers' => 0,
        'enabled_providers' => 0,
        'total_games' => 0,
        'enabled_games' => 0,
        'providers_list' => []
    ];
    ?>
    <div class="glass-panel p-6 rounded-2xl">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-bold text-white flex items-center gap-2">
                <ion-icon name="game-controller-outline" class="text-brand-400"></ion-icon>
                Game Providers
            </h3>
            <div class="flex gap-4 text-sm">
                <div class="text-slate-400">
                    <span class="text-white font-bold"><?= $provStats['enabled_providers'] ?></span> / <?= $provStats['total_providers'] ?> Providers
                </div>
                <div class="text-slate-400">
                    <span class="text-white font-bold"><?= number_format($provStats['enabled_games']) ?></span> Games
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8 gap-4 max-h-[300px] overflow-y-auto custom-scrollbar pr-2">
            <?php foreach($provStats['providers_list'] as $p): ?>
            <div onclick="loadProviderGames('<?= htmlspecialchars($p['name'], ENT_QUOTES) ?>')" class="cursor-pointer bg-black/20 rounded-lg p-3 border border-white/5 <?= $p['is_enabled'] ? 'opacity-100 hover:bg-white/5' : 'opacity-40 grayscale' ?> transition-all">
                <div class="flex items-center justify-between mb-2">
                    <div class="text-[10px] bg-white/10 px-1.5 py-0.5 rounded text-slate-300"><?= $p['games_count'] ?></div>
                    <div class="w-1.5 h-1.5 rounded-full <?= $p['is_enabled'] ? 'bg-emerald-500' : 'bg-red-500' ?>"></div>
                </div>
                <div class="aspect-[3/2] flex items-center justify-center mb-2 overflow-hidden rounded bg-black/40 p-1">
                    <?php if ($p['logo']): ?>
                        <img src="<?= htmlspecialchars($p['logo']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" class="w-full h-full object-contain">
                    <?php else: ?>
                         <ion-icon name="game-controller" class="text-2xl text-slate-600"></ion-icon>
                    <?php endif; ?>
                </div>
                <div class="text-xs font-bold text-white truncate" title="<?= htmlspecialchars($p['name']) ?>">
                    <?= htmlspecialchars($p['name']) ?>
                </div>
                <div class="text-[9px] text-slate-500 mt-0.5 truncate uppercase tracking-wider">
                    <?= $p['status'] ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 text-black">
        
        <!-- Callback Logs Table -->
        <div class="lg:col-span-2 glass-panel p-6 rounded-2xl min-h-[400px]">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-bold text-white flex items-center gap-2">
                    <ion-icon name="sync-outline" class="text-brand-400"></ion-icon>
                    Recent Callbacks
                </h3>
                 <a href="/logs" class="text-xs text-brand-400 hover:text-brand-300 flex items-center gap-1 transition-colors">
                    View All <ion-icon name="arrow-forward-outline"></ion-icon>
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-400 border-separate border-spacing-y-2">
                    <thead>
                        <tr class="text-xs uppercase tracking-wider text-slate-500">
                            <th class="pb-4 pl-2 font-medium">Time</th>
                            <th class="pb-4 font-medium">User ID</th>
                            <th class="pb-4 font-medium">Game UID</th>
                             <th class="pb-4 font-medium text-right">Bet</th>
                            <th class="pb-4 font-medium text-right">Win</th>
                            <th class="pb-4 pr-2 font-medium text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($recentCallbacks as $cb): ?>
                        <tr class="bg-white/5 hover:bg-white/10 transition-colors rounded-lg group">
                            <td class="py-3 pl-4 rounded-l-lg border-y border-l border-white/5 font-mono text-xs text-slate-500"><?= date('H:i:s', strtotime($cb['time'])) ?></td>
                            <td class="py-3 border-y border-white/5 font-medium text-slate-300"><?= htmlspecialchars($cb['user_id']) ?></td>
                            <td class="py-3 border-y border-white/5 font-medium text-slate-400 text-xs"><?= htmlspecialchars($cb['game_uid']) ?></td>
                             <td class="py-3 border-y border-white/5 text-right font-mono text-xs text-red-400">-<?= number_format($cb['bet'], 2) ?></td>
                             <td class="py-3 border-y border-white/5 text-right font-mono text-xs text-emerald-400">+<?= number_format($cb['win'], 2) ?></td>
                            <td class="py-3 pr-4 rounded-r-lg border-y border-r border-white/5 text-center">
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] uppercase font-bold tracking-wide <?= ($cb['status'] === 'insufficient_funds') ? 'bg-red-500/10 text-red-400' : 'bg-slate-700 text-slate-300' ?>">
                                    <?= htmlspecialchars($cb['status']) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recentCallbacks)): ?>
                            <tr><td colspan="6" class="text-center py-6 text-slate-600 text-xs">No recent callbacks found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Quick Integration Guide -->
         <div class="glass-panel p-6 rounded-2xl flex flex-col items-start bg-gradient-to-br from-white/5 to-white/0">
             <div class="w-12 h-12 rounded-xl bg-brand-500/20 text-brand-400 flex items-center justify-center mb-4">
                 <ion-icon name="code-working-outline" class="text-2xl"></ion-icon>
             </div>
            <h3 class="text-lg font-bold text-white mb-2">Integration Guide</h3>
            <p class="text-sm text-slate-400 mb-6 leading-relaxed">
                Connect your platform to InfinityAPI in minutes. Use our encrypted request tester to validate your implementation.
            </p>
            
            <div class="w-full bg-black/30 rounded-lg p-3 mb-6 font-mono text-xs text-slate-300 border border-white/10 relative group">
                <div class="text-slate-500 mb-1"># Quick Start</div>
                <div class="flex">
                    <span class="text-purple-400">curl</span> 
                    <span class="ml-2">-X POST</span>
                    <span class="ml-2 text-green-400 text-nowrap overflow-hidden text-ellipsis">https://api.infinity.site/v1/launch</span>
                </div>
                <button class="absolute top-2 right-2 p-1 text-slate-500 hover:text-white transition-colors opacity-0 group-hover:opacity-100">
                    <ion-icon name="copy-outline"></ion-icon>
                </button>
            </div>

            <a href="/docs" class="mt-auto w-full group relative inline-flex items-center justify-center px-8 py-3 text-base font-medium text-white bg-brand-600 rounded-xl transition-all duration-200 hover:bg-brand-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 overflow-hidden">
                <span class="absolute inset-0 w-full h-full bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-700 ease-in-out"></span>
                <span class="relative flex items-center gap-2">
                    View Documentation
                    <ion-icon name="arrow-forward-outline"></ion-icon>
                </span>
            </a>
        </div>
    </div>
    <!-- Games JSON Modal -->
    <dialog id="gamesModal" class="backdrop:bg-black/80 bg-slate-900 rounded-xl border border-white/10 shadow-2xl w-full max-w-4xl p-0 m-auto text-slate-300">
        <div class="flex items-center justify-between p-4 border-b border-white/5 bg-slate-800/50">
            <h3 class="text-lg font-bold text-white flex items-center gap-2">
                <ion-icon name="game-controller-outline" class="text-brand-400"></ion-icon>
                <span id="modalProviderName">Provider</span> Games
            </h3>
            <button onclick="document.getElementById('gamesModal').close()" class="p-2 hover:bg-white/5 rounded-lg transition-colors">
                <ion-icon name="close-outline" class="text-xl"></ion-icon>
            </button>
        </div>
        <div class="p-0 overflow-hidden relative group">
            <div class="absolute top-2 right-2 z-10">
                <button onclick="copyGamesJson()" class="bg-slate-700/50 hover:bg-slate-600 text-xs px-3 py-1.5 rounded flex items-center gap-2 backdrop-blur-md border border-white/10 transition-all">
                    <ion-icon name="copy-outline"></ion-icon> Copy JSON
                </button>
            </div>
            <pre id="gamesJsonContent" class="p-6 overflow-auto max-h-[60vh] text-xs font-mono text-emerald-300 bg-black/40 custom-scrollbar"></pre>
        </div>
    </dialog>

</div>

<script>
async function loadProviderGames(providerName) {
    const modal = document.getElementById('gamesModal');
    const title = document.getElementById('modalProviderName');
    const pre = document.getElementById('gamesJsonContent');
    
    title.innerText = providerName;
    pre.innerText = 'Loading games...';
    modal.showModal();
    
    try {
        const res = await fetch(`/api/provider/games?provider=${encodeURIComponent(providerName)}`);
        const data = await res.json();
        
        if (data.ok) {
            pre.innerText = JSON.stringify(data.games, null, 2);
        } else {
            pre.innerText = '// Error: ' + (data.error || 'Unknown error');
        }
    } catch (e) {
        pre.innerText = '// Error: Failed to fetch games';
    }
}

function copyGamesJson() {
    const pre = document.getElementById('gamesJsonContent');
    navigator.clipboard.writeText(pre.innerText).then(() => {
        alert('JSON copied to clipboard');
    });
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
