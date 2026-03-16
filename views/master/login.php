<?php
$pageTitle = 'Master Login';
$err = $_GET['err'] ?? null;
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Master Login | InfinityAPI</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 min-h-screen flex items-center justify-center p-4 text-slate-200">
  <div class="w-full max-w-md">
    <div class="backdrop-blur-xl bg-white/5 border border-white/10 rounded-2xl shadow-lg p-8">
      <div class="mb-6">
        <h1 class="text-2xl font-bold text-white">Master Admin</h1>
        <p class="text-sm text-slate-400 mt-1">Sign in to manage clients.</p>
      </div>

      <?php if ($err): ?>
        <div class="mb-4 p-3 rounded-xl bg-red-500/10 border border-red-500/20 text-sm text-red-200">
          <?= htmlspecialchars((string)$err) ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="/master/login" class="space-y-4">
        <div>
          <label class="block text-sm text-slate-300 mb-1">Username</label>
          <input name="username" required class="w-full bg-black/30 border border-white/10 rounded-xl px-4 py-3 text-white outline-none focus:border-violet-400/60" />
        </div>
        <div>
          <label class="block text-sm text-slate-300 mb-1">Password</label>
          <input type="password" name="password" required class="w-full bg-black/30 border border-white/10 rounded-xl px-4 py-3 text-white outline-none focus:border-violet-400/60" />
        </div>
        <button class="w-full bg-violet-600 hover:bg-violet-500 transition-colors text-white font-semibold rounded-xl py-3">
          Login
        </button>
      </form>
    </div>
    <p class="text-xs text-slate-500 mt-4 text-center">
      Default password is set in <code class="text-slate-300">config/master.php</code>.
      If you forgot it, run <code class="text-slate-300">php tools/reset_master_password.php</code> on the server to generate a new one.
    </p>
  </div>
</body>
</html>

