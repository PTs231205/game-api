<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Master Panel' ?> | InfinityAPI Admin</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
          darkMode: 'class',
          theme: {
            extend: {
              colors: {
                'brand': {
                  500: '#0ea5e9',
                  600: '#0284c7',
                },
                'primary': {
                  500: '#8b5cf6', // Violet
                  600: '#7c3aed',
                },
                'dark-bg': '#0f172a',
                'panel-bg': '#1e293b',
              },
              fontFamily: {
                sans: ['Inter', 'ui-sans-serif', 'system-ui'],
              },
            },
          }
        }
    </script>
    <style type="text/tailwindcss">
        @layer components {
            .glass-panel {
                @apply backdrop-blur-xl bg-slate-900/80 border border-white/10 rounded-xl shadow-lg;
            }
            .nav-item {
                @apply flex items-center gap-3 px-4 py-3 text-slate-400 hover:text-white hover:bg-white/5 transition-all rounded-lg;
            }
            .nav-item.active {
                @apply bg-primary-600/10 text-primary-500 font-medium;
            }
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>
<body class="bg-dark-bg text-slate-200 min-h-screen font-sans antialiased overflow-hidden flex">

    <!-- Sidebar -->
    <aside class="w-56 border-r border-white/5 bg-slate-900 flex flex-col fixed inset-y-0 left-0 z-50">
        <div class="h-12 flex items-center px-4 border-b border-white/5 bg-slate-950/50">
            <div class="w-6 h-6 rounded bg-gradient-to-br from-primary-600 to-indigo-600 flex items-center justify-center mr-2">
                <ion-icon name="shield-checkmark" class="text-white text-sm"></ion-icon>
            </div>
            <div>
                <h1 class="font-bold text-white text-sm leading-none">MASTER</h1>
            </div>
        </div>

        <nav class="flex-1 overflow-y-auto p-2 space-y-0.5">
            <a href="/master/dashboard" class="nav-item py-2 px-3 text-sm <?= $pageTitle === 'Dashboard' ? 'active' : '' ?>">
                <ion-icon name="speedometer-outline"></ion-icon> Dashboard
            </a>
            <a href="/master/clients" class="nav-item py-2 px-3 text-sm <?= $pageTitle === 'Clients Management' ? 'active' : '' ?>">
                <ion-icon name="people-outline"></ion-icon> Clients
            </a>
            <a href="/master/reports" class="nav-item py-2 px-3 text-sm <?= $pageTitle === 'Analytics & Reports' ? 'active' : '' ?>">
                <ion-icon name="bar-chart-outline"></ion-icon> Analytics
            </a>
            <a href="/master/users" class="nav-item py-2 px-3 text-sm <?= $pageTitle === 'Users Management' ? 'active' : '' ?>">
                <ion-icon name="person-outline"></ion-icon> Users
            </a>
            <a href="/master/logs" class="nav-item py-2 px-3 text-sm <?= $pageTitle === 'Access Logs' ? 'active' : '' ?>">
                <ion-icon name="list-outline"></ion-icon> Logs
            </a>
            <a href="/master/settings" class="nav-item py-2 px-3 text-sm <?= $pageTitle === 'Setup & Settings' ? 'active' : '' ?>">
                <ion-icon name="settings-outline"></ion-icon> Settings
            </a>
        </nav>

        <div class="p-2 border-t border-white/5 bg-slate-950/30">
            <div class="flex items-center justify-between px-2">
                <span class="text-[10px] text-slate-500 italic">Connected</span>
                <a href="/master/logout" class="text-[10px] text-red-500 hover:underline">Logout</a>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 ml-56 flex flex-col h-screen overflow-hidden bg-slate-950 relative">
        <!-- Top Header -->
        <header class="h-12 border-b border-white/5 flex items-center justify-between px-4 bg-slate-900/50 backdrop-blur-sm z-40 sticky top-0">
            <h2 class="text-sm font-bold text-white uppercase tracking-wider"><?= $pageTitle ?></h2>
            <div class="flex items-center gap-3">
                <div class="text-[10px] text-slate-500">ADMIN: SA</div>
                <div class="w-6 h-6 rounded bg-primary-600 flex items-center justify-center text-white text-[10px] font-bold">SA</div>
            </div>
        </header>

        <!-- Content Scrollable -->
        <div class="flex-1 overflow-y-auto p-4 relative">
            <div class="relative z-10 max-w-full space-y-4 pb-10">
                <?= $content ?? '' ?>
            </div>
        </div>
    </main>

</body>
</html>
