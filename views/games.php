<?php
// Games Catalog View
$pageTitle = 'Casino Games';
$gamesConfig = require BASE_PATH . 'config/games.php';
$gamesList = $gamesConfig['games'];

ob_start();
?>
<div class="space-y-8">

    <div class="glass-panel p-8 relative overflow-hidden flex flex-col items-center text-center">
        <div class="relative z-10 max-w-2xl">
            <h1 class="text-3xl font-bold text-white mb-4">Premium Casino Games</h1>
            <p class="text-slate-400 mb-6">Experience the thrill with our top-rated slots and live games.</p>
            <div class="flex justify-center gap-4">
                 <button class="bg-brand-600 hover:bg-brand-500 text-white font-medium px-6 py-2.5 rounded-xl transition-all shadow-lg shadow-brand-500/20 active:scale-95">All Games</button>
                 <button class="bg-white/5 hover:bg-white/10 text-white font-medium px-6 py-2.5 rounded-xl transition-all border border-white/5">Favorites</button>
            </div>
        </div>
        
        <!-- Background Decoration -->
        <div class="absolute top-0 left-0 w-full h-full bg-gradient-to-t from-dark-bg via-transparent to-transparent pointer-events-none"></div>
        <div class="absolute -top-40 -right-40 w-96 h-96 bg-brand-500/10 rounded-full blur-3xl"></div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6">
        <?php foreach($gamesList as $game): ?>
        <div class="group relative bg-slate-800 rounded-xl overflow-hidden shadow-lg border border-white/5 transition-transform hover:-translate-y-1 hover:shadow-2xl hover:shadow-brand-500/10">
            <!-- Image -->
            <div class="aspect-[3/2] overflow-hidden">
                <img src="<?= $game['img'] ?>" alt="<?= $game['name'] ?>" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
            </div>

            <!-- Content -->
            <div class="p-4 bg-slate-900 relative">
                <h3 class="text-white font-bold text-sm truncate"><?= $game['name'] ?></h3>
                <p class="text-[10px] text-slate-500 mt-1 uppercase tracking-wide"><?= $game['provider'] ?></p>
                
                <div class="mt-3 flex items-center justify-between">
                     <span class="text-[10px] bg-slate-800 text-brand-400 px-2 py-1 rounded border border-white/5">RTP <?= $game['rtp'] ?></span>
                     <form action="/game/launch" method="POST">
                         <input type="hidden" name="game_uid" value="<?= $game['uid'] ?>">
                         <button type="submit" class="w-8 h-8 rounded-full bg-brand-600 flex items-center justify-center text-white hover:bg-brand-500 transition-colors shadow-lg shadow-brand-500/20">
                             <ion-icon name="play"></ion-icon>
                         </button>
                     </form>
                </div>
            </div>

            <!-- Hover Overlay -->
            <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center pointer-events-none">
                 <span class="text-white font-bold text-sm tracking-widest uppercase border-b-2 border-brand-500 pb-1">Play Now</span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
