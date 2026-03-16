<?php
$pageTitle = 'Dashboard';
$stats = $masterManager->getGlobalStats();


ob_start();
?>
<!-- Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-8">
    
    <!-- Total Bet (24h) -->
    <div class="glass-panel p-4 relative overflow-hidden group">
        <div class="text-xs font-medium text-slate-400 mb-1">Total Bet (24h)</div>
        <div class="text-xl font-bold text-white tracking-tight flex items-baseline gap-1">
            <span class="text-sm text-slate-500">₹</span>
            <?= number_format($stats['total_bet_24h'], 2) ?>
        </div>
        <div class="absolute top-4 right-4 text-[10px] font-bold text-emerald-400 bg-emerald-500/10 px-1.5 py-0.5 rounded">LIVE</div>
    </div>

    <!-- Total Loss (24h) -->
    <div class="glass-panel p-4 relative overflow-hidden group">
        <div class="text-xs font-medium text-slate-400 mb-1">Total Loss (24h)</div>
        <div class="text-xl font-bold text-white tracking-tight flex items-baseline gap-1">
            <span class="text-sm text-slate-500">₹</span>
            <?= number_format($stats['total_loss_24h'], 2) ?>
        </div>
        <div class="absolute top-4 right-4 text-[10px] font-bold text-purple-400 bg-purple-500/10 px-1.5 py-0.5 rounded">VOLATILITY</div>
    </div>

    <!-- User RTP -->
    <div class="glass-panel p-4 relative overflow-hidden group">
        <div class="text-xs font-medium text-slate-400 mb-1">System RTP</div>
        <div class="text-xl font-bold text-white tracking-tight flex items-baseline gap-1">
            <?= number_format($stats['avg_rtp'], 2) ?>%
        </div>
         <div class="absolute top-4 right-4 text-[10px] font-bold text-blue-400 bg-blue-500/10 px-1.5 py-0.5 rounded">VERIFIED</div>
    </div>

    <!-- Wallet Balance -->
    <div class="glass-panel p-4 relative overflow-hidden group">
        <div class="text-xs font-medium text-slate-400 mb-1">Total Balance</div>
        <div class="text-xl font-bold text-white tracking-tight flex items-baseline gap-1">
            <span class="text-sm text-slate-500">₹</span>
            <?= number_format($stats['system_balance'], 2) ?>
        </div>
         <div class="absolute top-4 right-4 text-[10px] font-bold text-amber-400 bg-amber-500/10 px-1.5 py-0.5 rounded">SECURE</div>
    </div>

    <!-- Active Sessions -->
    <div class="glass-panel p-4 relative overflow-hidden group">
        <div class="text-xs font-medium text-slate-400 mb-1">Active Sessions</div>
        <div class="text-xl font-bold text-white tracking-tight flex items-baseline gap-1">
            <?= number_format($stats['active_sessions']) ?>
        </div>
         <div class="absolute top-4 right-4 text-[10px] font-bold text-green-400 bg-green-500/10 px-1.5 py-0.5 rounded">ONLINE</div>
    </div>

</div>


<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
