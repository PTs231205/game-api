<?php
$pageTitle = 'Users';

$ok = $_GET['ok'] ?? null;
$err = $_GET['err'] ?? null;

ob_start();
?>

<div class="glass-panel p-6 rounded-xl">
    <div class="flex items-center justify-between gap-4 mb-4">
        <div>
            <h3 class="text-lg font-bold text-white flex items-center gap-2">
                <ion-icon name="person-add-outline" class="text-primary-500"></ion-icon> Create User
            </h3>
            <p class="text-xs text-slate-500 mt-1">This page is intended to be opened in a new tab.</p>
        </div>
        <a href="/master/users" class="text-xs px-3 py-2 rounded-lg bg-white/5 text-slate-300 border border-white/10 hover:bg-white/10 transition-colors">
            Back to Users
        </a>
    </div>

    <?php if ($ok): ?>
        <div class="mb-4 p-4 rounded-xl border border-emerald-500/20 bg-emerald-500/5">
            <div class="text-sm text-emerald-300 font-medium">Success: <?= htmlspecialchars((string)$ok) ?></div>
        </div>
    <?php endif; ?>
    <?php if ($err): ?>
        <div class="mb-4 p-4 rounded-xl border border-red-500/20 bg-red-500/5">
            <div class="text-sm text-red-300 font-medium">Error: <?= htmlspecialchars((string)$err) ?></div>
        </div>
    <?php endif; ?>

    <form method="POST" action="/master/users/create" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <input type="hidden" name="redirect_to" value="new_tab" />

        <div>
            <label class="text-xs font-semibold text-slate-400 block mb-1">User ID</label>
            <input name="user_id" required class="w-full bg-black/30 border border-white/10 rounded-lg px-3 py-2 text-sm text-white outline-none focus:border-primary-500/60" placeholder="e.g. 23213" />
        </div>

        <div>
            <label class="text-xs font-semibold text-slate-400 block mb-1">Balance</label>
            <input name="balance" required inputmode="decimal" class="w-full bg-black/30 border border-white/10 rounded-lg px-3 py-2 text-sm text-white outline-none focus:border-primary-500/60" placeholder="e.g. 40" />
        </div>

        <div>
            <label class="text-xs font-semibold text-slate-400 block mb-1">Currency (optional)</label>
            <input name="currency_code" class="w-full bg-black/30 border border-white/10 rounded-lg px-3 py-2 text-sm text-white outline-none focus:border-primary-500/60" placeholder="USD" />
        </div>

        <div>
            <label class="text-xs font-semibold text-slate-400 block mb-1">Language (optional)</label>
            <input name="language" class="w-full bg-black/30 border border-white/10 rounded-lg px-3 py-2 text-sm text-white outline-none focus:border-primary-500/60" placeholder="en" />
        </div>

        <div class="md:col-span-2 flex items-center gap-3 mt-2">
            <button class="bg-primary-600 hover:bg-primary-500 transition-colors text-white font-semibold rounded-lg py-2.5 px-6 text-sm">
                Create User
            </button>
            <p class="text-xs text-slate-500">
                Users can be used in launch calls (e.g. <span class="font-mono text-slate-300">/v1/launch</span>).
            </p>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';

