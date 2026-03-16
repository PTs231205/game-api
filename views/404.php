<?php
// Simple 404
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
 <title>404 Not Found</title>
 <script src="https://cdn.tailwindcss.com"></script>
 <script>
        tailwind.config = {
          darkMode: 'class',
          theme: {
            extend: {
              colors: {
                'brand': {
                  500: '#0ea5e9',
                },
                'dark-bg': '#0f172a',
              },
            }
          }
        }
    </script>
</head>
<body class="bg-dark-bg text-white h-screen flex items-center justify-center">
    <div class="text-center">
        <h1 class="text-6xl font-bold text-brand-500">404</h1>
        <p class="text-xl mt-4">Page Not Found</p>
        <?php if (!empty($GLOBALS['_router_404_path'])): ?>
        <p class="text-sm mt-2 text-slate-400">Requested: <code><?= htmlspecialchars($GLOBALS['_router_404_path']) ?></code></p>
        <?php endif; ?>
        <a href="/" class="mt-8 inline-block px-6 py-3 bg-brand-500 rounded-lg">Go Home</a>
    </div>
</body>
</html>
