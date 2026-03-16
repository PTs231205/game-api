<!DOCTYPE html>
<html lang="<?= $langManager->getCurrentLang() ?>" dir="<?= $langManager->getCurrentLang() == 'ar' ? 'rtl' : 'ltr' ?>" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $config['app']['name'] ?></title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
          darkMode: 'class',
          theme: {
            extend: {
              colors: {
                'brand': {
                  50: '#f0f9ff',
                  100: '#e0f2fe',
                  200: '#bae6fd',
                  300: '#7dd3fc',
                  400: '#38bdf8',
                  500: '#0ea5e9',
                  600: '#0284c7',
                  700: '#0369a1',
                  800: '#075985',
                  900: '#0c4a6e',
                  950: '#082f49',
                },
                'dark-bg': '#0f172a',
                'panel-bg': '#1e293b',
              },
              fontFamily: {
                sans: ['Inter', 'ui-sans-serif', 'system-ui'],
              },
              backgroundImage: {
                'gradient-radial': 'radial-gradient(var(--tw-gradient-stops))',
                'glass': 'linear-gradient(135deg, rgba(255, 255, 255, 0.05), rgba(255, 255, 255, 0.01))',
              },
              boxShadow: {
                'neon': '0 0 10px rgba(14, 165, 233, 0.5), 0 0 20px rgba(14, 165, 233, 0.3)',
              },
            },
          }
        }
    </script>
    <style type="text/tailwindcss">
        @layer base {
            body {
                @apply antialiased text-slate-200 bg-dark-bg selection:bg-brand-500 selection:text-white;
                font-family: 'Inter', sans-serif;
            }
        }
        @layer components {
            .glass-panel {
                @apply backdrop-blur-xl bg-white/5 border border-white/10 rounded-2xl shadow-lg;
            }
            .btn-primary {
                @apply bg-brand-600 hover:bg-brand-500 text-white font-medium py-2 px-4 rounded-xl transition-all duration-300 shadow-lg shadow-brand-500/20 active:scale-95;
            }
        }
    </style>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Ionicons for Icons -->
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>
<body class="bg-dark-bg text-slate-200 min-h-screen font-sans antialiased overflow-x-hidden">

    <!-- Mobile Nav Toggle -->
    <div class="lg:hidden fixed top-4 left-4 z-50">
        <button id="nav-toggle" class="p-2 glass-panel text-brand-400">
            <ion-icon name="menu-outline" size="large"></ion-icon>
        </button>
    </div>

    <div class="flex h-screen overflow-hidden">
        
        <!-- Sidebar -->
        <aside id="sidebar" class="fixed inset-y-0 left-0 z-40 w-72 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out border-r border-white/5 bg-dark-bg/95 backdrop-blur-xl flex flex-col">
            <!-- Brand -->
            <div class="h-20 flex items-center px-8 border-b border-white/5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 flex items-center justify-center shadow-lg shadow-brand-500/20">
                        <ion-icon name="infinite-outline" class="text-white text-2xl"></ion-icon>
                    </div>
                    <div>
                        <h1 class="font-bold text-xl tracking-tight text-white">Infinity<span class="text-brand-400">API</span></h1>
                        <p class="text-xs text-slate-500 uppercase tracking-wider">Client Panel</p>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 overflow-y-auto py-6 px-4 space-y-1">
                <?php
                $navItems = [
                    ['icon' => 'grid-outline', 'label' => 'Dashboard', 'url' => '/'],
                    ['icon' => 'wallet-outline', 'label' => 'Wallet & GGR', 'url' => '/wallet'],
                    ['icon' => 'key-outline', 'label' => 'API Tokens', 'url' => '/tokens'],
                    ['icon' => 'shield-checkmark-outline', 'label' => 'IP Whitelist', 'url' => '/ip-whitelist'],
                    ['icon' => 'sync-outline', 'label' => 'Game Callbacks', 'url' => '/logs'],
                    ['icon' => 'code-slash-outline', 'label' => 'Request Tester', 'url' => '/tester'],
                    ['icon' => 'book-outline', 'label' => 'Integration Guide', 'url' => '/docs'],
                ];

                foreach($navItems as $item): 
                    $active = ($_SERVER['REQUEST_URI'] == $item['url'] || ($_SERVER['REQUEST_URI'] == '' && $item['url'] == '/')) ? 'bg-brand-500/10 text-brand-400 border-l-2 border-brand-500' : 'text-slate-400 hover:text-white hover:bg-white/5 border-l-2 border-transparent';
                ?>
                <a href="<?= $item['url'] ?>" class="group flex items-center gap-3 px-4 py-3 rounded-r-xl transition-all duration-200 <?= $active ?>">
                    <ion-icon name="<?= $item['icon'] ?>" class="text-xl transition-transform group-hover:scale-110"></ion-icon>
                    <span class="font-medium"><?= $item['label'] ?></span>
                </a>
                <?php endforeach; ?>
            </nav>

            <!-- User/Footer -->
            <div class="p-4 border-t border-white/5">
                <div class="glass-panel p-4 rounded-xl flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-slate-700 flex items-center justify-center">
                        <?php
                        $clientAuth = $_SESSION['client_auth'] ?? [];
                        $clientName = is_array($clientAuth) ? (string)($clientAuth['name'] ?? 'Client') : 'Client';
                        $initials = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $clientName), 0, 2));
                        if ($initials === '') $initials = 'CL';
                        ?>
                        <span class="font-bold text-white"><?= htmlspecialchars($initials) ?></span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-white truncate"><?= htmlspecialchars($clientName) ?></p>
                        <p class="text-xs text-slate-400 truncate"><?= htmlspecialchars((string)($clientAuth['client_id'] ?? 'Client')) ?></p>
                    </div>
                    <a href="/logout" class="text-slate-400 hover:text-red-400 transition-colors">
                        <ion-icon name="log-out-outline" class="text-xl"></ion-icon>
                    </a>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto lg:ml-72 relative">
            <!-- Top Bar -->
            <header class="sticky top-0 z-30 h-20 px-8 flex items-center justify-between backdrop-blur-md bg-dark-bg/80 border-b border-white/5">
                <div>
                     <!-- Breadcrumbs could go here -->
                     <h2 class="text-lg font-medium text-slate-200"><?= $pageTitle ?? 'Dashboard' ?></h2>
                </div>

                <div class="flex items-center gap-4">
                    <!-- Language Selector -->
                    <div class="relative group">
                        <button class="flex items-center gap-2 text-sm font-medium text-slate-400 hover:text-white transition-colors">
                            <ion-icon name="globe-outline" class="text-lg"></ion-icon>
                            <span><?= strtoupper($langManager->getCurrentLang()) ?></span>
                            <ion-icon name="chevron-down-outline" class="text-xs"></ion-icon>
                        </button>
                        <!-- Dropdown -->
                        <div class="absolute right-0 mt-2 w-32 bg-slate-800 rounded-xl shadow-xl border border-white/10 hidden group-hover:block transition-all">
                            <a href="?lang=en" class="block px-4 py-2 text-sm text-slate-300 hover:text-white hover:bg-white/5 first:rounded-t-xl">English</a>
                            <a href="?lang=hi" class="block px-4 py-2 text-sm text-slate-300 hover:text-white hover:bg-white/5">Hindi</a>
                            <a href="?lang=ar" class="block px-4 py-2 text-sm text-slate-300 hover:text-white hover:bg-white/5 last:rounded-b-xl">Arabic</a>
                        </div>
                    </div>

                    <!-- Notifications -->
                    <button class="relative text-slate-400 hover:text-brand-400 transition-colors">
                        <ion-icon name="notifications-outline" class="text-xl"></ion-icon>
                        <span class="absolute -top-1 -right-1 w-2 h-2 bg-red-500 rounded-full animate-pulse"></span>
                    </button>
                </div>
            </header>

            <!-- Page Content -->
            <div class="p-8 pb-20">
                <?= $content ?? '' ?>
            </div>
        </main>

    </div>

    <script>
        const navToggle = document.getElementById('nav-toggle');
        const sidebar = document.getElementById('sidebar');
        
        navToggle.addEventListener('click', () => {
            sidebar.classList.toggle('-translate-x-full');
        });
    </script>
</body>
</html>
