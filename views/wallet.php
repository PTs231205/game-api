<?php
$pageTitle = $langManager->trans('wallet.balance');
$authClientId = $_SESSION['client_auth']['client_id'] ?? null;
$walletBalance = $gameManager->getWalletBalance($authClientId);
$ggrBalance = $gameManager->getGgrBalance($authClientId);
$currency = $gameManager->getCurrency($authClientId);
$changes = $gameManager->getBalanceChanges($authClientId, 25);

ob_start();
?>
<div class="space-y-8">
    
    <!-- Top Balance Section -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="md:col-span-2 glass-panel p-8 relative overflow-hidden flex items-center justify-between">
            <div class="relative z-10">
                <h2 class="text-slate-400 font-medium mb-1">Total Balance</h2>
                <div class="text-4xl font-bold text-white tracking-tight flex items-baseline gap-2">
                    <?= number_format($walletBalance, 2) ?>
                    <span class="text-xl text-brand-400"><?= $currency ?></span>
                </div>
                <div class="mt-6 flex gap-3">
                    <button class="bg-brand-600 hover:bg-brand-500 text-white px-6 py-2.5 rounded-xl font-medium transition-all shadow-lg shadow-brand-500/20 active:scale-95 flex items-center gap-2">
                        <ion-icon name="add-circle-outline" class="text-lg"></ion-icon>
                        Deposit Funds
                    </button>
                </div>
            </div>
            <div class="absolute right-0 top-0 h-full w-1/2 bg-gradient-to-l from-brand-600/10 to-transparent"></div>
            <ion-icon name="wallet" class="absolute right-8 bottom-8 text-6xl text-brand-500/20"></ion-icon>
        </div>

        <div class="glass-panel p-8 flex flex-col justify-center relative overflow-hidden">
            <h3 class="text-slate-400 font-medium mb-4">GGR %</h3>
            <div class="text-3xl font-bold text-emerald-400 flex items-baseline gap-2">
                <?= number_format($ggrBalance, 2) ?>%
            </div>
            <p class="mt-2 text-xs text-slate-400">Updated by Master Admin.</p>
        </div>
    </div>

    <!-- Balance Change History -->
    <div class="glass-panel p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-bold text-white">Balance Changes</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-400">
                <thead>
                    <tr class="text-xs uppercase tracking-wider text-slate-500 border-b border-white/10">
                        <th class="pb-3 pl-4">Time</th>
                        <th class="pb-3 text-right">Wallet Before</th>
                        <th class="pb-3 text-right">Wallet After</th>
                        <th class="pb-3 text-right">GGR % Before</th>
                        <th class="pb-3 text-right">GGR % After</th>
                        <th class="pb-3 pr-4">Note</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <?php if (empty($changes)): ?>
                        <tr><td colspan="6" class="py-6 text-center text-slate-500">No balance changes yet.</td></tr>
                    <?php endif; ?>
                    <?php foreach($changes as $ch): ?>
                    <tr class="hover:bg-white/5 transition-colors group">
                        <td class="py-4 pl-4 font-mono text-xs text-slate-300"><?= htmlspecialchars((string)($ch['created_at'] ?? '')) ?></td>
                        <td class="py-4 text-right font-mono"><?= number_format((float)($ch['wallet_before'] ?? 0), 2) ?></td>
                        <td class="py-4 text-right font-mono text-emerald-400"><?= number_format((float)($ch['wallet_after'] ?? 0), 2) ?></td>
                        <td class="py-4 text-right font-mono"><?= number_format((float)($ch['ggr_before'] ?? 0), 2) ?>%</td>
                        <td class="py-4 text-right font-mono text-emerald-400"><?= number_format((float)($ch['ggr_after'] ?? 0), 2) ?>%</td>
                        <td class="py-4 pr-4 text-xs text-slate-500"><?= htmlspecialchars((string)($ch['note'] ?? '')) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Deposit Modal -->
<div id="depositModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" onclick="closeDepositModal()"></div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-4xl bg-slate-900 border border-white/10 rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
        <div class="p-4 border-b border-white/10 flex items-center justify-between bg-slate-800">
            <h3 class="text-lg font-bold text-white flex items-center gap-2">
                <ion-icon name="wallet-outline" class="text-brand-400"></ion-icon>
                Deposit Funds
            </h3>
            <button onclick="closeDepositModal()" class="text-slate-400 hover:text-white text-2xl leading-none">&times;</button>
        </div>
        
        <div class="flex-1 overflow-hidden flex flex-col md:flex-row">
            <?php 
            $methods = $gameManager->getActivePaymentMethods(); 
            ?>
            
            <!-- Sidebar Tabs -->
            <div class="w-full md:w-1/3 bg-slate-950/50 border-r border-white/5 flex flex-col overflow-y-auto">
                <?php if (empty($methods)): ?>
                    <div class="p-6 text-center text-slate-500 text-sm">No payment methods available.</div>
                <?php else: ?>
                    <?php foreach($methods as $index => $m): ?>
                    <button onclick="showMethod('method_<?= $m['id'] ?>')" class="tab-btn w-full text-left p-4 hover:bg-white/5 border-b border-white/5 flex items-center gap-3 transition-colors <?= $index === 0 ? 'bg-white/10 border-l-4 border-l-brand-500' : 'border-l-4 border-l-transparent' ?>" data-target="method_<?= $m['id'] ?>">
                        <div class="w-10 h-10 rounded bg-slate-800 flex items-center justify-center text-xl shrink-0">
                            <?php if ($m['type'] === 'UPI'): ?>
                                <span class="font-bold text-xs">UPI</span>
                            <?php elseif (strpos($m['type'], 'USDT') !== false || strpos($m['name'], 'USDT') !== false): ?>
                                <span class="text-emerald-500">$</span>
                            <?php else: ?>
                                <ion-icon name="qr-code-outline"></ion-icon>
                            <?php endif; ?>
                        </div>
                        <div>
                            <div class="font-bold text-white text-sm"><?= htmlspecialchars($m['name']) ?></div>
                            <div class="text-[10px] text-slate-500 uppercase"><?= htmlspecialchars($m['type']) ?></div>
                        </div>
                    </button>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Content Area -->
            <div class="flex-1 bg-slate-900 p-6 md:p-8 overflow-y-auto">
                <?php if (empty($methods)): ?>
                    <div class="h-full flex flex-col items-center justify-center text-slate-500">
                        <ion-icon name="ban-outline" class="text-4xl mb-2"></ion-icon>
                         <p>Please contact support to deposit funds.</p>
                    </div>
                <?php else: ?>
                    <?php foreach($methods as $index => $m): ?>
                    <div id="method_<?= $m['id'] ?>" class="method-content <?= $index === 0 ? '' : 'hidden' ?>">
                        <div class="flex flex-col items-center text-center">
                            
                            <?php if ($m['qr_image']): ?>
                                <div class="bg-white p-2 rounded-xl mb-6 shadow-lg shadow-black/50">
                                    <img src="<?= htmlspecialchars($m['qr_image']) ?>" alt="QR Code" class="w-48 h-48 md:w-64 md:h-64 object-contain">
                                </div>
                            <?php else: ?>
                                <div class="w-48 h-48 md:w-64 md:h-64 bg-white/5 rounded-xl mb-6 flex items-center justify-center border-2 border-dashed border-white/10">
                                    <span class="text-slate-500 text-sm">No QR Image</span>
                                </div>
                            <?php endif; ?>

                            <div class="w-full max-w-md bg-black/30 rounded-lg p-4 mb-6 border border-white/10 relative group">
                                <p class="text-[10px] text-slate-500 uppercase tracking-widest mb-1">Deposit Address / ID</p>
                                <p class="font-mono text-slate-200 text-sm break-all select-all mr-8"><?= htmlspecialchars($m['address']) ?></p>
                                <button onclick="copyToClipboard('<?= htmlspecialchars($m['address']) ?>')" class="absolute top-1/2 -translate-y-1/2 right-3 text-brand-400 hover:text-white p-2 rounded hover:bg-white/10 transition-colors" title="Copy">
                                    <ion-icon name="copy-outline" class="text-lg"></ion-icon>
                                </button>
                            </div>

                            <?php if (!empty($m['instructions'])): ?>
                                <div class="w-full max-w-md text-left bg-blue-500/10 border border-blue-500/20 rounded-lg p-4">
                                    <div class="flex items-start gap-3">
                                        <ion-icon name="information-circle-outline" class="text-blue-400 text-xl shrink-0 mt-0.5"></ion-icon>
                                        <div class="text-sm text-slate-300">
                                            <p class="font-bold text-blue-400 mb-1">Instructions:</p>
                                            <p class="whitespace-pre-wrap"><?= htmlspecialchars($m['instructions']) ?></p>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="mt-8 pt-6 border-t border-white/5 w-full">
                                <p class="text-xs text-slate-500 mb-2">After payment, please send screenshot to support.</p>
                            </div>

                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function openDepositModal() {
    document.getElementById('depositModal').classList.remove('hidden');
}

function closeDepositModal() {
    document.getElementById('depositModal').classList.add('hidden');
}

function showMethod(id) {
    // Hide all contents
    document.querySelectorAll('.method-content').forEach(el => el.classList.add('hidden'));
    // Show target
    document.getElementById(id).classList.remove('hidden');
    
    // Update tabs
    document.querySelectorAll('.tab-btn').forEach(btn => {
        if(btn.dataset.target === id) {
             btn.classList.add('bg-white/10', 'border-l-brand-500');
             btn.classList.remove('border-l-transparent');
        } else {
             btn.classList.remove('bg-white/10', 'border-l-brand-500');
             btn.classList.add('border-l-transparent');
        }
    });
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        // Could add a toast here
        alert('Copied to clipboard!');
    }).catch(err => {
        console.error('Failed to copy: ', err);
    });
}

// Attach listener to Deposit Button
document.addEventListener('DOMContentLoaded', () => {
    const depositBtn = document.querySelector('button[onclick="openDepositModal()"]') || 
                      Array.from(document.querySelectorAll('button')).find(el => el.textContent.includes('Deposit Funds'));
    
    if (depositBtn) {
        depositBtn.setAttribute('onclick', 'openDepositModal()');
    }
});
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
