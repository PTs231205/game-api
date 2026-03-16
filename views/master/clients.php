<?php
$pageTitle = 'Clients Management';
$pageTitle = 'Clients Management';
$clients = $masterManager->getClients();
$allProviders = $masterManager->getProviders();
$ok = $_GET['ok'] ?? null;
$err = $_GET['err'] ?? null;
$flashCreds = $_SESSION['flash_client_creds'] ?? null;
if ($flashCreds) {
    unset($_SESSION['flash_client_creds']);
}

ob_start();
?>
<div class="space-y-4">

    <!-- Header -->
    <div class="flex items-center justify-between bg-slate-900 p-4 border border-white/10">
        <h1 class="text-xl font-bold text-white">Clients List (<?= count($clients) ?>)</h1>
        <form method="POST" action="/master/clients/quick-create">
            <button class="bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 rounded text-sm font-bold">
                + Quick Create
            </button>
        </form>
    </div>

    <!-- Feedback -->
    <?php if ($ok): ?><div class="bg-emerald-900/50 text-emerald-300 p-2 text-sm border border-emerald-500/50">Success: <?= htmlspecialchars((string)$ok) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="bg-red-900/50 text-red-300 p-2 text-sm border border-red-500/50">Error: <?= htmlspecialchars((string)$err) ?></div><?php endif; ?>

    <!-- Fresh Credentials -->
    <?php if ($flashCreds && is_array($flashCreds)): ?>
        <div class="bg-slate-900 p-4 border border-indigo-500/50">
            <h3 class="text-indigo-400 font-bold mb-2">NEW CLIENT CREATED (COPY THESE NOW):</h3>
            <div class="grid grid-cols-1 gap-2 text-xs font-mono">
                <div class="flex gap-2"><span>CLIENT_ID:</span> <span class="text-white"><?= htmlspecialchars((string)($flashCreds['client_id'] ?? '')) ?></span></div>
                <div class="flex gap-2"><span>ACCESS_KEY:</span> <span class="text-amber-400"><?= htmlspecialchars((string)($flashCreds['access_key'] ?? '')) ?></span></div>
                <div class="flex gap-2"><span>API_TOKEN:</span> <span class="text-indigo-400 font-bold"><?= htmlspecialchars((string)($flashCreds['api_key'] ?? '')) ?></span></div>
                <div class="flex gap-2"><span>CLIENT_SECRET:</span> <span class="text-emerald-300 font-bold"><?= htmlspecialchars((string)($flashCreds['client_secret'] ?? '')) ?></span></div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Table -->
    <div class="bg-slate-900 border border-white/10 overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead>
                <tr class="bg-white/5 border-b border-white/10">
                    <th class="p-3">Client Name</th>
                    <th class="p-3">Login (Client ID)</th>
                    <th class="p-3">Password (Access Key)</th>
                    <th class="p-3">API Token</th>
                    <th class="p-3">Wallet</th>
                    <th class="p-3">GGR %</th>
                    <th class="p-3">Forward Callback URL</th>
                    <th class="p-3">Game Providers</th>
                    <th class="p-3">IP Whitelist</th>
                    <th class="p-3">Status</th>
                    <th class="p-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                <?php if (!$clients): ?><tr><td colspan="9" class="p-4 text-center text-slate-500">No clients.</td></tr><?php endif; ?>
                <?php foreach($clients as $client): 
                    $accessKey = $masterManager->revealClientAccessKey($client);
                    $apiKey = (string)($client['api_key'] ?? '');
                    $ipEnabled = (int)($client['ip_whitelist_enabled'] ?? 0);
                    $walletBalance = (float)($client['wallet_balance'] ?? 0);
                    $ggrBalance = (float)($client['ggr_balance'] ?? 0);
                    $loginId = (string)($client['client_id'] ?? '');
                    $providerCfg = $client['provider'] ?? [];
                    if (!is_array($providerCfg)) $providerCfg = [];
                    $forwardCallbackUrl = (string)($providerCfg['forward_callback_url'] ?? '');
                    $ips = $client['ip_whitelist'] ?? [];
                    if (is_string($ips)) {
                        $decoded = json_decode($ips, true);
                        $ips = is_array($decoded) ? $decoded : [];
                    }
                    $ipsStr = is_array($ips) ? implode(', ', $ips) : '';
                ?>
                    <tr class="hover:bg-white/5 transition-colors">
                        <td class="p-3">
                            <div class="font-bold text-white"><?= htmlspecialchars((string)($client['name'] ?? '')) ?></div>
                        </td>
                        <td class="p-3">
                            <code class="text-slate-200 bg-white/5 p-1 rounded font-mono"><?= htmlspecialchars($loginId) ?></code>
                            <?php if ($loginId !== ''): ?>
                                <button onclick="navigator.clipboard.writeText('<?= addslashes($loginId) ?>')" class="ml-1 text-slate-500 hover:text-white" title="Copy Login">
                                    <ion-icon name="copy-outline"></ion-icon>
                                </button>
                            <?php endif; ?>
                        </td>
                        <td class="p-3">
                            <?php if ($accessKey): ?>
                                <code class="text-amber-300 bg-amber-500/10 p-1 rounded"><?= htmlspecialchars($accessKey) ?></code>
                                <button onclick="navigator.clipboard.writeText('<?= addslashes($accessKey) ?>')" class="ml-1 text-slate-500 hover:text-white"><ion-icon name="copy-outline"></ion-icon></button>
                            <?php else: ?>
                                <div class="text-red-300 text-[11px] font-bold">HIDDEN</div>
                                <div class="text-slate-500 text-[11px]">Click reset key to view</div>
                            <?php endif; ?>
                        </td>
                        <td class="p-3">
                            <code class="text-indigo-300 bg-indigo-500/10 p-1 rounded"><?= htmlspecialchars($apiKey) ?></code>
                            <?php if ($apiKey !== ''): ?>
                                <button onclick="navigator.clipboard.writeText('<?= addslashes($apiKey) ?>')" class="ml-1 text-slate-500 hover:text-white" title="Copy API Token">
                                    <ion-icon name="copy-outline"></ion-icon>
                                </button>
                            <?php endif; ?>
                        </td>
                        <td class="p-3">
                            <form method="POST" action="/master/clients/balances" class="flex items-center gap-2">
                                <input type="hidden" name="id" value="<?= htmlspecialchars((string)($client['id'] ?? '')) ?>">
                                <input name="wallet_balance" value="<?= htmlspecialchars(number_format($walletBalance, 2, '.', '')) ?>" class="w-24 bg-black/30 border border-white/10 rounded px-2 py-1 text-[11px] text-white outline-none" />
                        </td>
                        <td class="p-3">
                                <input name="ggr_balance" value="<?= htmlspecialchars(number_format($ggrBalance, 2, '.', '')) ?>" class="w-24 bg-black/30 border border-white/10 rounded px-2 py-1 text-[11px] text-white outline-none" />
                                <span class="text-slate-500 text-[11px] ml-1">%</span>
                                <button class="bg-slate-600/20 text-slate-200 px-2 py-1 rounded hover:bg-slate-600 hover:text-white transition-colors text-[11px] font-bold">Save</button>
                            </form>
                        </td>
                        <td class="p-3">
                            <form method="POST" action="/master/clients/forward-callback" class="flex items-center gap-2">
                                <input type="hidden" name="id" value="<?= htmlspecialchars((string)($client['id'] ?? '')) ?>">
                                <input name="url" value="<?= htmlspecialchars($forwardCallbackUrl) ?>" class="w-72 bg-black/30 border border-white/10 rounded px-2 py-1 text-[11px] text-white outline-none" placeholder="https://clientdomain.com/callback.php" />
                                <button class="bg-indigo-600/20 text-indigo-300 px-2 py-1 rounded hover:bg-indigo-600 hover:text-white transition-colors text-[11px] font-bold">Save</button>
                            </form>
                            <?php if ($forwardCallbackUrl === ''): ?>
                                <div class="text-slate-500 text-[11px] mt-1">Not set (callbacks will not be forwarded).</div>
                            <?php endif; ?>
                        </td>
                        </td>
                        <!-- Providers Column -->
                        <td class="p-3">
                            <?php 
                                $blockedJson = $client['blocked_providers'] ?? '[]';
                                $blocked = json_decode($blockedJson, true);
                                if (!is_array($blocked)) $blocked = [];
                                $totalProviders = count($allProviders);
                                $blockedCount = count($blocked);
                                $activeCount = $totalProviders - $blockedCount;
                            ?>
                             <div class="text-[11px]">
                                <span class="font-bold text-emerald-300"><?= $activeCount ?> Active</span> / 
                                <span class="text-slate-500"><?= $totalProviders ?> Total</span>
                            </div>
                            <?php if ($blockedCount > 0): ?>
                                <div class="text-[10px] text-red-400 mt-0.5"><?= $blockedCount ?> Blocked</div>
                            <?php endif; ?>
                            
                            <button onclick="openProviderModal('<?= $client['id'] ?>', '<?= htmlspecialchars(addslashes($client['name'])) ?>', '<?= htmlspecialchars(addslashes($blockedJson)) ?>')" 
                                class="mt-2 bg-indigo-600/20 text-indigo-300 px-2 py-1 rounded hover:bg-indigo-600 hover:text-white transition-colors text-[11px] font-bold w-full">
                                Manage
                            </button>
                        </td>
                        <td class="p-3">
                            <div class="flex items-center justify-between gap-2">
                                <div class="text-[11px] font-bold <?= $ipEnabled ? 'text-emerald-300' : 'text-slate-500' ?>">
                                    <?= $ipEnabled ? 'ENABLED' : 'DISABLED' ?>
                                </div>
                                <form method="POST" action="/master/clients/ip-whitelist-toggle" class="inline">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars((string)($client['id'] ?? '')) ?>">
                                    <input type="hidden" name="enabled" value="<?= $ipEnabled ? '0' : '1' ?>">
                                    <button class="<?= $ipEnabled ? 'bg-red-600/20 text-red-300 hover:bg-red-600' : 'bg-emerald-600/20 text-emerald-300 hover:bg-emerald-600' ?> px-2 py-1 rounded text-[11px] font-bold transition-colors hover:text-white">
                                        <?= $ipEnabled ? 'Disable' : 'Enable' ?>
                                    </button>
                                </form>
                            </div>
                            <?php if ($ipsStr === ''): ?>
                                <div class="text-red-300 text-[11px] font-bold">NOT SET</div>
                                <div class="text-slate-500 text-[11px]">Anyone with api_key can call.</div>
                            <?php else: ?>
                                <div class="text-emerald-300 text-[11px] font-mono break-all"><?= htmlspecialchars($ipsStr) ?></div>
                            <?php endif; ?>
                            <form method="POST" action="/master/clients/ip-whitelist" class="mt-2 flex gap-2">
                                <input type="hidden" name="id" value="<?= htmlspecialchars((string)($client['id'] ?? '')) ?>">
                                <input name="ip_whitelist" value="<?= htmlspecialchars($ipsStr) ?>" class="flex-1 bg-black/30 border border-white/10 rounded px-2 py-1 text-[11px] text-white outline-none" placeholder="e.g. 1.2.3.4, 5.6.7.8">
                                <button class="bg-emerald-600/20 text-emerald-300 px-2 rounded hover:bg-emerald-600 hover:text-white transition-colors text-[11px] font-bold">Save</button>
                            </form>
                        </td>
                        <td class="p-3">
                            <span class="px-2 py-0.5 rounded font-bold uppercase <?= ($client['status'] ?? 'Active') == 'Active' ? 'text-emerald-500 bg-emerald-500/10' : 'text-red-500 bg-red-500/10' ?>">
                                <?= htmlspecialchars((string)($client['status'] ?? 'Active')) ?>
                            </span>
                        </td>
                        <td class="p-3 text-right whitespace-nowrap">
                            <div class="flex items-center justify-end gap-2">
                                <form method="POST" action="/master/clients/rotate-key" class="inline">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars((string)($client['id'] ?? '')) ?>">
                                    <button class="bg-indigo-600/20 text-indigo-400 p-1 rounded hover:bg-indigo-600 hover:text-white transition-colors" title="Rotate API Key">
                                        <ion-icon name="refresh-outline"></ion-icon>
                                    </button>
                                </form>
                                <form method="POST" action="/master/clients/reset-access-key" class="inline" onsubmit="return confirm('Reset password?');">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars((string)($client['id'] ?? '')) ?>">
                                    <button class="bg-amber-600/20 text-amber-400 p-1 rounded hover:bg-amber-600 hover:text-white transition-colors" title="Reset Password">
                                        <ion-icon name="key-outline"></ion-icon>
                                    </button>
                                </form>
                                <form method="POST" action="/master/clients/delete" class="inline" onsubmit="return confirm('Delete?');">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars((string)($client['id'] ?? '')) ?>">
                                    <button class="bg-red-600/20 text-red-400 p-1 rounded hover:bg-red-600 hover:text-white transition-colors" title="Delete">
                                        <ion-icon name="trash-outline"></ion-icon>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Provider Access Modal -->
<div id="providerModal" class="fixed inset-0 z-50 hidden">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" onclick="closeProviderModal()"></div>
    
    <!-- Modal -->
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-2xl bg-slate-900 border border-white/10 rounded-xl shadow-2xl flex flex-col max-h-[90vh]">
        <div class="p-4 border-b border-white/10 flex items-center justify-between bg-slate-800 rounded-t-xl">
            <h3 class="text-lg font-bold text-white flex items-center gap-2">
                <ion-icon name="game-controller-outline"></ion-icon>
                Manage Providers for <span id="modalClientName" class="text-indigo-400">Client</span>
            </h3>
            <button onclick="closeProviderModal()" class="text-slate-400 hover:text-white text-2xl leading-none">&times;</button>
        </div>
        
        <form method="POST" action="/master/clients/providers" class="flex-1 overflow-hidden flex flex-col">
            <input type="hidden" name="id" id="modalClientId">
            
            <div class="p-4 overflow-y-auto flex-1 custom-scrollbar">
                <div class="flex items-center justify-between mb-4">
                     <div class="text-xs text-slate-400">Uncheck to DISABLE provider for this client.</div>
                     <div class="flex gap-2">
                         <button type="button" onclick="toggleAllProviders(true)" class="text-xs text-indigo-400 hover:text-indigo-300">Select All</button>
                         <span class="text-slate-600">|</span>
                         <button type="button" onclick="toggleAllProviders(false)" class="text-xs text-indigo-400 hover:text-indigo-300">Deselect All</button>
                     </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    <?php foreach($allProviders as $prov): ?>
                    <label class="flex items-center gap-3 p-3 rounded bg-white/5 border border-white/5 hover:bg-white/10 cursor-pointer group select-none transition-colors">
                        <input type="checkbox" name="providers[]" value="<?= $prov['brand_id'] ?>" class="peer w-4 h-4 rounded border-slate-600 text-indigo-600 focus:ring-indigo-500 bg-slate-800 provider-checkbox">
                        <div class="flex flex-col overflow-hidden">
                            <span class="text-xs font-medium text-slate-300 peer-checked:text-white group-hover:text-white truncate" title="<?= htmlspecialchars($prov['name']) ?>"><?= htmlspecialchars($prov['name']) ?></span>
                            <?php if ($prov['status'] == 'Inactive'): ?>
                                <span class="text-[9px] text-red-500 uppercase font-bold">Global Disabled</span>
                            <?php else: ?>
                                <span class="text-[9px] text-emerald-500/50 peer-checked:text-emerald-400 uppercase font-bold">Active</span>
                            <?php endif; ?>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="p-4 border-t border-white/10 bg-slate-800 rounded-b-xl flex justify-end gap-3">
                <button type="button" onclick="closeProviderModal()" class="px-4 py-2 text-sm font-medium text-slate-300 hover:text-white">Cancel</button>
                <button type="submit" class="px-6 py-2 text-sm font-bold bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg shadow-lg shadow-indigo-500/20">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function openProviderModal(clientId, clientName, blockedJson) {
    document.getElementById('modalClientId').value = clientId;
    document.getElementById('modalClientName').textContent = clientName;
    
    // Parse blocked list
    let blocked = [];
    try {
        blocked = JSON.parse(blockedJson);
    } catch(e) {}
    
    // Set verify state checkboxes
    // Concept: UI shows "Active" (checked) vs "Blocked" (unchecked)
    // blocked_providers contains IDs of BLOCKED providers.
    // So if brand_id is IN blocked, checkbox should be UNCHECKED.
    // If brand_id is NOT IN blocked, checkbox should be CHECKED.
    
    const checkboxes = document.querySelectorAll('.provider-checkbox');
    checkboxes.forEach(cb => {
        if (blocked.includes(cb.value)) {
            cb.checked = false;
        } else {
            cb.checked = true;
        }
    });

    document.getElementById('providerModal').classList.remove('hidden');
}

function closeProviderModal() {
    document.getElementById('providerModal').classList.add('hidden');
}

function toggleAllProviders(state) {
    const checkboxes = document.querySelectorAll('.provider-checkbox');
    checkboxes.forEach(cb => cb.checked = state);
}
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';


