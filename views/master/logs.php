<?php
$pageTitle = 'Access Logs';
$logs = $masterManager->getRecentAccessLogs();

ob_start();
?>
<div class="max-w-6xl mx-auto space-y-8 mt-4">

    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-white">System Audit Logs</h1>
            <p class="text-slate-400 text-sm mt-1">Review administrative actions and security-related events.</p>
        </div>
        <div class="flex items-center gap-2">
            <button class="bg-slate-800 hover:bg-slate-700 text-slate-300 px-4 py-2 rounded-lg text-xs font-medium transition-all flex items-center gap-2 border border-white/5">
                <ion-icon name="filter-outline" class="text-lg"></ion-icon> Filter Results
            </button>
            <button class="bg-slate-800 hover:bg-slate-700 text-slate-300 px-4 py-2 rounded-lg text-xs font-medium transition-all flex items-center gap-2 border border-white/5">
                <ion-icon name="cloud-download-outline" class="text-lg"></ion-icon> Export CSV
            </button>
        </div>
    </div>

    <!-- Logs List -->
    <div class="bg-slate-900/40 border border-white/5 rounded-2xl overflow-hidden backdrop-blur-sm">
        <div class="p-6 border-b border-white/5 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="bg-slate-800 p-2 rounded-lg text-indigo-400 flex items-center justify-center">
                    <ion-icon name="pulse-outline" class="text-xl"></ion-icon>
                </div>
                <div>
                    <h2 class="text-white font-medium">Activity History</h2>
                    <p class="text-xs text-slate-500">Showing last 50 events</p>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="text-[11px] font-bold uppercase tracking-wider text-slate-500 border-b border-white/5">
                        <th class="px-6 py-4">Event Details</th>
                        <th class="px-6 py-4">Administrator</th>
                        <th class="px-6 py-4">Source IP</th>
                        <th class="px-6 py-4 text-right">Result</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <?php foreach($logs as $log): ?>
                    <tr class="group hover:bg-white/[0.02] transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-slate-950 flex items-center justify-center text-indigo-500 border border-white/5">
                                    <ion-icon name="flash-outline"></ion-icon>
                                </div>
                                <div class="space-y-0.5">
                                    <div class="text-sm font-medium text-white"><?= htmlspecialchars($log['action']) ?></div>
                                    <div class="text-[10px] text-slate-500 font-mono"><?= $log['time'] ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded-full bg-indigo-500/20 flex items-center justify-center text-[10px] font-bold text-indigo-400 border border-indigo-500/10">
                                    <?= strtoupper(substr($log['user'], 0, 1)) ?>
                                </div>
                                <span class="text-xs text-slate-300"><?= htmlspecialchars($log['user']) ?></span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-xs font-mono text-slate-400">
                             <?= $log['ip'] ?>
                        </td>
                        <td class="px-6 py-4 text-right">
                             <div class="inline-flex items-center px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider bg-emerald-500/10 text-emerald-500 border border-emerald-500/20">
                                <span class="w-1 h-1 rounded-full bg-emerald-500 mr-1.5"></span>
                                Authorized
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';

