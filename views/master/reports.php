<?php
$pageTitle = 'Analytics & Reports';
$period = $_GET['period'] ?? 'today';
$validPeriods = ['today' => 'Today', 'yesterday' => 'Yesterday', 'month' => 'This Month', 'year' => 'This Year'];
if (!array_key_exists($period, $validPeriods)) $period = 'today';

$report = $masterManager->getAnalyticsReport($period);
ob_start();
?>

<!-- Header & Period Filter -->
<div class="flex flex-col md:flex-row gap-4 items-center justify-between mb-8">
    <div>
        <h1 class="text-3xl font-bold text-white tracking-tight">Analytics & Reports</h1>
        <p class="text-slate-400 mt-1">Detailed breakdown of GGR, Bets, and Client Performance</p>
    </div>
    <div class="flex bg-white/5 p-1 rounded-lg border border-white/10">
        <?php foreach ($validPeriods as $k => $label): ?>
        <a href="?period=<?= $k ?>" 
           class="px-4 py-2 rounded-md text-sm font-medium transition-all <?= $period === $k ? 'bg-primary-500 text-white shadow-lg shadow-primary-500/20' : 'text-slate-400 hover:text-white hover:bg-white/5' ?>">
            <?= $label ?>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<!-- Key Metrics Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    
    <!-- Total Bet -->
    <div class="glass-panel p-5 relative overflow-hidden group">
        <div class="absolute right-0 top-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
            <ion-icon name="cash-outline" class="text-4xl text-blue-500"></ion-icon>
        </div>
        <div class="text-sm font-medium text-slate-400 mb-2">Total Bet</div>
        <div class="text-2xl font-bold text-white tracking-tight">
            <span class="text-lg text-slate-500 font-normal">₹</span>
            <?= number_format($report['total_bet'], 2) ?>
        </div>
    </div>

    <!-- Total Win -->
    <div class="glass-panel p-5 relative overflow-hidden group">
        <div class="absolute right-0 top-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
            <ion-icon name="trophy-outline" class="text-4xl text-emerald-500"></ion-icon>
        </div>
        <div class="text-sm font-medium text-slate-400 mb-2">Total Win</div>
        <div class="text-2xl font-bold text-white tracking-tight">
             <span class="text-lg text-slate-500 font-normal">₹</span>
            <?= number_format($report['total_win'], 2) ?>
        </div>
    </div>

    <!-- GGR (Revenue) -->
    <div class="glass-panel p-5 relative overflow-hidden group">
        <div class="absolute right-0 top-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
            <ion-icon name="trending-up-outline" class="text-4xl text-purple-500"></ion-icon>
        </div>
        <div class="text-sm font-medium text-slate-400 mb-2">Net Revenue (GGR)</div>
        <div class="text-2xl font-bold text-white tracking-tight">
             <span class="text-lg text-slate-500 font-normal">₹</span>
            <?= number_format($report['total_loss'], 2) ?>
        </div>
         <div class="text-xs text-slate-500 mt-1">User Loss Amount</div>
    </div>

     <!-- GGR Deductions -->
     <div class="glass-panel p-5 relative overflow-hidden group border-l-4 border-l-brand-500">
        <div class="absolute right-0 top-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
            <ion-icon name="cut-outline" class="text-4xl text-brand-500"></ion-icon>
        </div>
        <div class="text-sm font-medium text-slate-400 mb-2">Commission Deducted</div>
        <div class="text-2xl font-bold text-brand-400 tracking-tight">
             <span class="text-lg text-brand-500/50 font-normal">₹</span>
            <?= number_format($report['ggr_deduction'], 2) ?>
        </div>
        <div class="text-xs text-slate-500 mt-1">Automatically charged from clients</div>
    </div>

</div>

<!-- Client Breakdown Table -->
<div class="glass-panel rounded-2xl overflow-hidden border border-white/5">
    <div class="p-6 border-b border-white/5 flex flex-col md:flex-row gap-4 justify-between items-center">
        <h3 class="text-lg font-bold text-white flex items-center gap-2">
            <ion-icon name="people-outline" class="text-brand-500"></ion-icon>
            Client Performance Breakdown
        </h3>
        <button class="px-3 py-1.5 text-xs font-medium bg-white/5 hover:bg-white/10 text-white rounded-lg border border-white/10 transition-colors flex items-center gap-2">
            <ion-icon name="download-outline"></ion-icon> Export CSV
        </button>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-slate-400">
            <thead>
                <tr class="text-xs font-semibold uppercase tracking-wider text-slate-500 bg-white/5 border-b border-white/5">
                    <th class="px-6 py-4">Client Name</th>
                    <th class="px-6 py-4 text-right">Total Bet</th>
                    <th class="px-6 py-4 text-right">Total Win</th>
                    <th class="px-6 py-4 text-right">Net Revenue (Loss)</th>
                    <th class="px-6 py-4 text-right text-brand-400">GGR Deducted</th>
                    <th class="px-6 py-4 text-center">Margin %</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                <?php if(empty($report['client_breakdown'])): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-slate-500">
                            No data available for this period.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach($report['client_breakdown'] as $row): 
                        $margin = ($row['bets'] > 0) ? ($row['loss'] / $row['bets']) * 100 : 0;
                    ?>
                    <tr class="hover:bg-white/5 transition-colors group">
                        <td class="px-6 py-4 font-medium text-white">
                            <?= htmlspecialchars($row['client_name']) ?>
                        </td>
                        <td class="px-6 py-4 text-right font-mono">
                            <?= number_format($row['bets'], 2) ?>
                        </td>
                         <td class="px-6 py-4 text-right font-mono text-emerald-400">
                            <?= number_format($row['wins'], 2) ?>
                        </td>
                         <td class="px-6 py-4 text-right font-mono font-bold <?= $row['loss'] >= 0 ? 'text-white' : 'text-red-400' ?>">
                            <?= number_format($row['loss'], 2) ?>
                        </td>
                         <td class="px-6 py-4 text-right font-mono text-brand-400 font-bold bg-brand-500/5">
                            <?= number_format($row['ggr'], 2) ?>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="px-2 py-1 rounded text-xs font-bold <?= $margin >= 0 ? 'bg-blue-500/10 text-blue-400' : 'bg-red-500/10 text-red-400' ?>">
                                <?= number_format($margin, 1) ?>%
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
