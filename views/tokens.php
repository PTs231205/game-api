<?php
$pageTitle = 'API Tokens';
$authClientId = $_SESSION['client_auth']['client_id'] ?? null;
$tokens = $gameManager->getApiTokens($authClientId);

ob_start();
?>
<div class="space-y-6">

    <div class="flex items-center justify-between glass-panel p-6">
        <div>
            <h2 class="text-xl font-bold text-white tracking-tight">Access Tokens</h2>
            <p class="text-slate-400 mt-1">Manage API keys for secure access to InfinityAPI services.</p>
        </div>
        <a href="/master/clients" class="bg-white/5 hover:bg-white/10 text-slate-200 font-medium py-2.5 px-6 rounded-xl transition-all border border-white/10 flex items-center gap-2">
            <ion-icon name="information-circle-outline" class="text-xl"></ion-icon>
            Contact Admin to rotate
        </a>
    </div>

    <!-- Token Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <?php foreach($tokens as $token): ?>
        <div class="glass-panel p-6 relative overflow-hidden group hover:border-brand-500/50 transition-colors">
            <div class="flex justify-between items-start mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-white/5 flex items-center justify-center text-brand-400">
                        <ion-icon name="key-outline" class="text-xl"></ion-icon>
                    </div>
                    <div>
                        <h3 class="font-bold text-white text-lg"><?= $token['name'] ?></h3>
                        <p class="text-xs text-slate-500 font-mono">ID: <?= $token['id'] ?></p>
                    </div>
                </div>
                <div class="px-2 py-1 rounded text-xs font-medium <?= $token['status'] === 'Active' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-red-500/10 text-red-400' ?>">
                    <?= $token['status'] ?>
                </div>
            </div>

            <div class="bg-black/30 rounded-lg p-3 font-mono text-sm text-slate-300 border border-white/5 break-all relative group-hover:border-brand-500/20 transition-colors">
                <?= $token['token'] ?>
                <button class="absolute top-2 right-2 text-slate-500 hover:text-white transition-colors" onclick="navigator.clipboard.writeText('<?= $token['token'] ?>')">
                    <ion-icon name="copy-outline"></ion-icon>
                </button>
            </div>

            <div class="mt-4 flex items-center justify-between text-xs text-slate-500 border-t border-white/5 pt-4">
                <span class="flex items-center gap-1">
                    <ion-icon name="shield-checkmark-outline"></ion-icon> Use this key in API calls
                </span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Security Warning -->
    <div class="glass-panel bg-yellow-500/5 border-yellow-500/20 p-4 rounded-xl flex items-start gap-3">
        <ion-icon name="warning-outline" class="text-yellow-400 text-xl mt-0.5"></ion-icon>
        <div>
            <h4 class="text-yellow-400 font-bold text-sm">Security Best Practices</h4>
            <p class="text-yellow-100/60 text-xs mt-1 leading-relaxed">
                Never share your API tokens in client-side code (frontend JavaScript). Always keep them secure on your backend server. Rotate keys every 90 days.
            </p>
        </div>
    </div>

</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
