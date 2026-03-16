<?php
$pageTitle = 'Game Callbacks';
$authClientId = $_SESSION['client_auth']['client_id'] ?? null;
$logs = $gameManager->getRecentCallbacks($authClientId, 100);

ob_start();
?>
<div class="space-y-6">

    <div class="flex items-center justify-between glass-panel p-6">
        <div>
            <h2 class="text-xl font-bold text-white tracking-tight">API Activity Logs</h2>
            <p class="text-slate-400 mt-1">Recent API calls made with your API key.</p>
        </div>
        <div class="flex gap-3">
             <div class="relative">
                <input type="date" class="bg-white/5 border border-white/10 rounded-xl px-4 py-2 text-white text-xs placeholder-slate-500 focus:border-brand-500 outline-none">
            </div>
             <div class="relative">
                <select class="bg-white/5 border border-white/10 rounded-xl px-4 py-2 text-white text-xs focus:border-brand-500 outline-none appearance-none pr-8">
                    <option>All Games</option>
                    <option>Roulette</option>
                    <option>Blackjack</option>
                    <option>Slots</option>
                </select>
                <ion-icon name="chevron-down-outline" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400"></ion-icon>
            </div>
        </div>
    </div>

    <!-- Logs Table -->
    <div class="glass-panel p-6 rounded-2xl min-h-[400px]">
        <div class="overflow-x-auto">
             <table class="w-full text-left text-sm text-slate-400 border-separate border-spacing-y-2">
                <thead>
                    <tr class="text-xs uppercase tracking-wider text-slate-500 border-b border-white/10">
                        <th class="pb-3 pl-4">Time</th>
                        <th class="pb-3">User ID</th>
                        <th class="pb-3">Game UID</th>
                        <th class="pb-3">Round ID</th>
                        <th class="pb-3 text-right">Bet</th>
                        <th class="pb-3 text-right">Win</th>
                        <th class="pb-3 text-right">Balance</th>
                        <th class="pb-3 pr-2 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <?php if (empty($logs)): ?>
                        <tr><td colspan="8" class="text-center py-6 text-slate-600">No callbacks found.</td></tr>
                    <?php endif; ?>
                    <?php foreach($logs as $log): ?>
                    <tr class="hover:bg-white/5 transition-colors rounded-lg group">
                        <td class="py-3 pl-4 rounded-l-lg font-mono text-xs"><?= $log['time'] ?></td>
                        <td class="py-3 font-medium text-slate-300"><?= htmlspecialchars($log['user_id']) ?></td>
                        <td class="py-3 font-mono text-xs text-slate-400"><?= htmlspecialchars($log['game_uid']) ?></td>
                        <td class="py-3 font-mono text-xs text-slate-500"><?= htmlspecialchars($log['round_id']) ?></td>
                        <td class="py-3 text-right font-mono text-xs text-red-400">-<?= number_format($log['bet'], 2) ?></td>
                        <td class="py-3 text-right font-mono text-xs text-emerald-400">+<?= number_format($log['win'], 2) ?></td>
                        <td class="py-3 text-right font-mono text-slate-300"><?= number_format($log['balance_after'], 2) ?></td>
                         <td class="py-3 pr-4 rounded-r-lg text-center">
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] uppercase font-bold tracking-wide <?= ($log['status'] === 'insufficient_funds') ? 'bg-red-500/10 text-red-400' : 'bg-slate-700 text-slate-300' ?>">
                                <?= htmlspecialchars($log['status']) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
         <div class="mt-6 flex items-center justify-between border-t border-white/10 pt-4">
            <span class="text-xs text-slate-500">Showing latest <?= count($logs) ?> results</span>
            <div class="flex gap-2">
                <button class="p-2 rounded-lg bg-white/5 hover:bg-white/10 text-slate-400 disabled:opacity-50"><ion-icon name="chevron-back"></ion-icon></button>
                <button class="px-3 py-1 bg-brand-600 text-white text-xs rounded-md">1</button>
                <button class="px-3 py-1 hover:bg-white/10 text-slate-400 text-xs rounded-md">2</button>
                <div class="px-2 py-1 text-slate-600">...</div>
                 <button class="px-3 py-1 hover:bg-white/10 text-slate-400 text-xs rounded-md">9</button>
                 <button class="p-2 rounded-lg bg-white/5 hover:bg-white/10 text-slate-400"><ion-icon name="chevron-forward"></ion-icon></button>
            </div>
        </div>

    </div>
    
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
