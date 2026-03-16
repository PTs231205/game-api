<?php
$pageTitle = 'Users Management';
$users = $masterManager->getUsers();
$ok = $_GET['ok'] ?? null;
$err = $_GET['err'] ?? null;

ob_start();
?>
<div class="space-y-4">

    <!-- Header -->
    <div class="flex items-center justify-between bg-slate-900 p-4 border border-white/10">
        <h1 class="text-xl font-bold text-white">Users List (<?= count($users) ?>)</h1>
        <a href="/master/users/new" target="_blank" class="bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 rounded text-sm font-bold">
            + Create User
        </a>
    </div>

    <!-- Feedback -->
    <?php if ($ok): ?><div class="bg-emerald-900/50 text-emerald-300 p-2 text-sm border border-emerald-500/50">Success: <?= htmlspecialchars((string)$ok) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="bg-red-900/50 text-red-300 p-2 text-sm border border-red-500/50">Error: <?= htmlspecialchars((string)$err) ?></div><?php endif; ?>

    <!-- Table -->
    <div class="bg-slate-900 border border-white/10 overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead>
                <tr class="bg-white/5 border-b border-white/10">
                    <th class="p-3">Player User ID</th>
                    <th class="p-3">Balance</th>
                    <th class="p-3">Currency</th>
                    <th class="p-3">Language</th>
                    <th class="p-3">Created Date</th>
                    <th class="p-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                <?php if (!$users): ?><tr><td colspan="6" class="p-4 text-center text-slate-500">No users.</td></tr><?php endif; ?>
                <?php foreach ($users as $u): ?>
                    <tr class="hover:bg-white/5 transition-colors">
                        <td class="p-3 font-mono font-bold text-white"><?= htmlspecialchars((string)($u['user_id'] ?? '')) ?></td>
                        <td class="p-3 text-emerald-400 font-bold"><?= number_format((float)($u['balance'] ?? 0), 2) ?></td>
                        <td class="p-3"><?= htmlspecialchars((string)($u['currency_code'] ?? 'INR')) ?></td>
                        <td class="p-3 uppercase"><?= htmlspecialchars((string)($u['language'] ?? 'EN')) ?></td>
                        <td class="p-3 text-slate-500 font-mono"><?= $u['created_at'] ?></td>
                        <td class="p-3 text-right">
                            <form method="POST" action="/master/users/delete" onsubmit="return confirm('Delete?');" class="inline">
                                <input type="hidden" name="user_id" value="<?= htmlspecialchars((string)($u['user_id'] ?? '')) ?>" />
                                <button class="bg-red-600/20 text-red-400 p-1 rounded hover:bg-red-600 hover:text-white transition-colors" title="Delete">
                                    <ion-icon name="trash-outline"></ion-icon>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';



