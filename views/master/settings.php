<?php
$pageTitle = 'Setup & Settings';
ob_start();

$paymentMethods = $masterManager->getPaymentMethods();
?>

<div class="flex justify-between items-center mb-8">
    <div>
        <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-400 to-indigo-400 bg-clip-text text-transparent">
            System Settings
        </h1>
        <p class="text-slate-400 mt-2">Manage global configurations and payment methods.</p>
    </div>
</div>

<div class="grid grid-cols-1 gap-8">
    
    <!-- Payment Methods Section -->
    <div class="bg-gray-800/50 backdrop-blur-xl border border-white/5 rounded-2xl p-6">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-xl font-bold text-white mb-1">
                    <ion-icon name="wallet-outline" class="inline-block mr-2 align-middle text-indigo-400"></ion-icon>
                    Deposit Payment Methods
                </h2>
                <p class="text-sm text-slate-400">Configure wallets and UPI IDs displayed on the client panel.</p>
            </div>
            <button onclick="openPaymentModal()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-bold rounded-xl transition-all shadow-lg shadow-indigo-500/20 flex items-center gap-2">
                <ion-icon name="add-circle-outline" class="text-lg"></ion-icon>
                Add Method
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            <?php foreach($paymentMethods as $pm): ?>
            <div class="group bg-slate-900/50 border border-white/5 rounded-xl p-4 hover:border-indigo-500/30 transition-all relative overflow-hidden">
                <div class="absolute top-0 right-0 p-2 opacity-0 group-hover:opacity-100 transition-opacity flex gap-2">
                    <button onclick='editPayment(<?= json_encode($pm) ?>)' class="p-2 bg-slate-800 rounded-lg text-indigo-400 hover:text-white hover:bg-indigo-600 transition-colors">
                        <ion-icon name="create-outline"></ion-icon>
                    </button>
                    <button onclick="deletePayment(<?= $pm['id'] ?>)" class="p-2 bg-slate-800 rounded-lg text-red-400 hover:text-white hover:bg-red-600 transition-colors">
                        <ion-icon name="trash-outline"></ion-icon>
                    </button>
                </div>

                <div class="flex items-start gap-4">
                    <div class="w-16 h-16 bg-white rounded-lg flex items-center justify-center p-1 shrink-0">
                        <?php if ($pm['qr_image']): ?>
                            <img src="<?= htmlspecialchars($pm['qr_image']) ?>" alt="QR" class="w-full h-full object-contain">
                        <?php else: ?>
                            <ion-icon name="qr-code-outline" class="text-3xl text-slate-800"></ion-icon>
                        <?php endif; ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-xs font-bold px-2 py-0.5 rounded bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                                <?= htmlspecialchars($pm['type']) ?>
                            </span>
                             <?php if ($pm['status'] === 'Active'): ?>
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            <?php else: ?>
                                <span class="w-2 h-2 rounded-full bg-slate-600"></span>
                            <?php endif; ?>
                        </div>
                        <h3 class="font-bold text-white truncate text-lg"><?= htmlspecialchars($pm['name']) ?></h3>
                        <p class="text-xs text-slate-400 font-mono truncate bg-black/30 p-1.5 rounded mt-1 select-all">
                            <?= htmlspecialchars($pm['address']) ?>
                        </p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            
            <?php if (empty($paymentMethods)): ?>
                <div class="col-span-full py-12 text-center border-2 border-dashed border-white/5 rounded-xl text-slate-500">
                    <ion-icon name="wallet-outline" class="text-4xl mb-2 opacity-50"></ion-icon>
                    <p>No payment methods configured.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add/Edit Payment Modal -->
<div id="paymentModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" onclick="closePaymentModal()"></div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-lg bg-slate-900 border border-white/10 rounded-2xl shadow-2xl p-6">
        <h3 id="modalTitle" class="text-xl font-bold text-white mb-6">Add Payment Method</h3>
        
        <form id="paymentForm" method="POST" action="/master/payment-methods/create" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="id" id="pm_id">
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1">Display Name</label>
                    <input type="text" name="name" id="pm_name" class="w-full bg-black/30 border border-white/10 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-indigo-500" placeholder="e.g. USDT TRC20" required>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1">Type</label>
                     <select name="type" id="pm_type" class="w-full bg-black/30 border border-white/10 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-indigo-500">
                        <option value="TRC20">TRC20</option>
                        <option value="BEP20">BEP20</option>
                        <option value="ERC20">ERC20</option>
                        <option value="UPI">UPI</option>
                        <option value="BANK">Bank Transfer</option>
                        <option value="OTHER">Other</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1">Wallet Address / UPI ID</label>
                <input type="text" name="address" id="pm_address" class="w-full bg-black/30 border border-white/10 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-indigo-500 font-mono" placeholder="0x..." required>
            </div>

            <div>
                 <label class="block text-xs font-medium text-slate-400 mb-1">Upload QR Code (Optional)</label>
                 <input type="file" name="qr_image" accept="image/*" class="w-full text-sm text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-600 file:text-white hover:file:bg-indigo-500">
                 <p class="text-[10px] text-slate-500 mt-1">Leave empty to keep existing QR if editing.</p>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1">Instructions / Notes (Optional)</label>
                <textarea name="instructions" id="pm_instructions" rows="2" class="w-full bg-black/30 border border-white/10 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-indigo-500 text-sm"></textarea>
            </div>
            
             <div class="flex items-center gap-2">
                <input type="hidden" name="status" value="Inactive">
                <input type="checkbox" name="status" id="pm_status" value="Active" class="w-4 h-4 rounded border-slate-600 text-indigo-600 focus:ring-indigo-500 bg-black/30" checked>
                <label for="pm_status" class="text-sm text-slate-300">Active</label>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-white/10">
                <button type="button" onclick="closePaymentModal()" class="px-4 py-2 text-slate-300 hover:text-white text-sm font-medium">Cancel</button>
                <button type="submit" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg shadow-lg shadow-indigo-500/20 text-sm font-bold">Save Method</button>
            </div>
        </form>
    </div>
</div>

<form id="deleteForm" method="POST" action="/master/payment-methods/delete" class="hidden">
    <input type="hidden" name="id" id="deleteId">
</form>

<script>
function openPaymentModal() {
    document.getElementById('paymentModal').classList.remove('hidden');
    document.getElementById('modalTitle').textContent = 'Add Payment Method';
    document.getElementById('paymentForm').action = '/master/payment-methods/create';
    document.getElementById('paymentForm').reset();
    document.getElementById('pm_id').value = '';
}

function editPayment(data) {
    openPaymentModal();
    document.getElementById('modalTitle').textContent = 'Edit Payment Method';
    document.getElementById('paymentForm').action = '/master/payment-methods/update';
    
    document.getElementById('pm_id').value = data.id;
    document.getElementById('pm_name').value = data.name;
    document.getElementById('pm_type').value = data.type;
    document.getElementById('pm_address').value = data.address;
    document.getElementById('pm_instructions').value = data.instructions || '';
    document.getElementById('pm_status').checked = (data.status === 'Active');
}

function closePaymentModal() {
    document.getElementById('paymentModal').classList.add('hidden');
}

function deletePayment(id) {
    if(confirm('Are you sure you want to delete this payment method?')) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>
