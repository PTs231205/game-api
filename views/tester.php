<?php
$pageTitle = 'Request Tester';
ob_start();
?>
<div class="glass-panel p-8">
    <h2 class="text-2xl font-bold text-white mb-6">Secure Request Tester</h2>
    <p class="text-slate-400 mb-8 max-w-2xl">Use this tool to manually test API requests and validate responses. All requests are encrypted before transmission.</p>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        <!-- Request Panel -->
        <div class="space-y-6">
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">Endpoint</label>
                <div class="flex">
                    <span class="inline-flex items-center px-4 rounded-l-xl border border-r-0 border-white/10 bg-white/5 text-slate-400 text-sm">/v1/</span>
                    <input type="text" class="flex-1 bg-white/5 border border-white/10 rounded-r-xl px-4 py-3 text-white placeholder-slate-500 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none" placeholder="wallet/process">
                </div>
            </div>

            <div>
                 <label class="block text-sm font-medium text-slate-300 mb-2">Method</label>
                 <select class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:border-brand-500 outline-none appearance-none">
                     <option>POST</option>
                     <option>GET</option>
                 </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">Payload (JSON)</label>
                <textarea class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white font-mono text-xs placeholder-slate-600 focus:border-brand-500 h-40 outline-none resize-none" placeholder='{
  "user_id": "test_user_001",
  "amount": 50.00
}'></textarea>
            </div>

            <button class="w-full bg-brand-600 hover:bg-brand-500 text-white font-medium py-3 px-4 rounded-xl transition-all duration-300 shadow-lg shadow-brand-500/20 active:scale-[0.98]">
                Send Request
            </button>
        </div>

        <!-- Response Panel -->
        <div class="glass-panel bg-black/40 border border-white/5 rounded-xl p-6 font-mono text-sm relative group">
            <div class="absolute top-4 right-4 flex items-center gap-2">
                 <div class="w-2.5 h-2.5 rounded-full bg-green-500 animate-pulse"></div>
                 <span class="text-xs text-green-400">200 OK</span>
            </div>
            <pre class="text-slate-300 overflow-x-auto">
{
  "status": "success",
  "data": {
    "transaction_id": "tx_8f7d9a1c2e",
    "updated_balance": 12550.50,
    "timestamp": "2024-02-14T10:45:00Z"
  }
}
            </pre>
            <div class="mt-4 pt-4 border-t border-white/10 text-xs text-slate-500 flex justify-between">
                <span>Latency: 42ms</span>
                <span>Size: 1.2kb</span>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
