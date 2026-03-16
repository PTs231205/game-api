<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InfinityAPI Login</title>
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
        @layer components {
            .glass-panel {
                @apply backdrop-blur-xl bg-white/5 border border-white/10 rounded-2xl shadow-lg;
            }
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-dark-bg min-h-screen flex items-center justify-center p-4">
    
    <div class="relative w-full max-w-md">
        <!-- Background Glows -->
        <div class="absolute -top-20 -left-20 w-64 h-64 bg-brand-500/20 rounded-full blur-3xl animate-pulse"></div>
        <div class="absolute -bottom-20 -right-20 w-64 h-64 bg-purple-500/20 rounded-full blur-3xl animate-pulse delay-1000"></div>

        <div class="glass-panel p-8 relative z-10 backdrop-blur-2xl border border-white/10 shadow-2xl rounded-2xl">
            
            <div class="text-center mb-8">
                <div class="inline-flex w-16 h-16 rounded-2xl bg-gradient-to-br from-brand-500 to-brand-700 items-center justify-center shadow-lg shadow-brand-500/20 mb-4">
                     <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-white tracking-tight">Welcome Back</h1>
                <p class="text-sm text-slate-400 mt-2">Sign into your client dashboard</p>
            </div>

            <?php if (isset($_GET['err'])): ?>
                <div class="p-3 rounded-xl bg-red-500/10 border border-red-500/20 text-sm text-red-200">
                    Invalid credentials
                </div>
            <?php endif; ?>

            <form action="/login" method="POST" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">Username / Client ID</label>
                    <input type="text" name="client_id" required class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-all outline-none" placeholder="demo">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">Password / Access Key</label>
                    <input type="password" name="access_key" required class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-all outline-none" placeholder="demo123">
                </div>

                <div class="flex items-center justify-between text-sm">
                    <label class="flex items-center gap-2 text-slate-400 cursor-pointer hover:text-white transition-colors">
                        <input type="checkbox" class="rounded bg-white/5 border-white/10 text-brand-500 focus:ring-brand-500">
                        <span>Remember me</span>
                    </label>
                    <a href="#" class="text-brand-400 hover:text-brand-300 transition-colors">Forgot key?</a>
                </div>

                <button type="submit" class="w-full bg-brand-600 hover:bg-brand-500 text-white font-semibold py-3 px-4 rounded-xl transition-all duration-300 shadow-lg shadow-brand-500/20 active:scale-[0.98]">
                    Authenticate
                </button>
            </form>

            <div class="mt-8 pt-6 border-t border-white/5 text-center text-sm text-slate-500">
                Need access? <a href="#" class="text-brand-400 hover:text-brand-300 font-medium transition-colors">Contact Support</a>
            </div>
        </div>
    </div>

</body>
</html>
