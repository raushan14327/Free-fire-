<?php
require_once __DIR__ . '/common/config.php';

$message = '';
$status = '';

if (isset($_POST['install_now'])) {
    try {
        auto_init_db($pdo);
        $message = "Database & Tables successfully installed! Default admin account created (admin@adeptplay.com / admin123).";
        $status = "success";
    } catch (Exception $e) {
        $message = "Installation failed: " . $e->getMessage();
        $status = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - Adept Play</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-2xl space-y-6">
        <div class="text-center space-y-2">
            <div class="w-16 h-16 bg-cyan-500/10 border border-cyan-500/30 rounded-2xl flex items-center justify-center mx-auto text-cyan-400 text-2xl shadow-lg shadow-cyan-500/20">
                <i class="fa-solid fa-database"></i>
            </div>
            <h1 class="text-2xl font-bold text-slate-100">Adept Play Database Setup</h1>
            <p class="text-xs text-slate-400">Initialize MySQL/SQLite database schema, sample tournaments, admin credentials, and default settings.</p>
        </div>

        <?php if ($message): ?>
            <div class="p-4 rounded-2xl text-sm font-semibold border flex items-center gap-3 <?= $status === 'success' ? 'bg-emerald-950/80 border-emerald-500/50 text-emerald-300' : 'bg-rose-950/80 border-rose-500/50 text-rose-300' ?>">
                <i class="fa-solid <?= $status === 'success' ? 'fa-circle-check text-emerald-400' : 'fa-circle-xmark text-rose-400' ?> text-lg"></i>
                <div><?= htmlspecialchars($message) ?></div>
            </div>
        <?php endif; ?>

        <div class="bg-slate-950/60 rounded-2xl p-4 border border-slate-800/80 space-y-3 text-xs text-slate-300">
            <div class="font-bold text-slate-200 border-b border-slate-800 pb-2">Default Setup Credentials:</div>
            <div class="flex justify-between">
                <span class="text-slate-400">Admin Email:</span>
                <span class="font-mono text-cyan-400">admin@adeptplay.com</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-400">Admin Password:</span>
                <span class="font-mono text-cyan-400">admin123</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-400">Demo Player Email:</span>
                <span class="font-mono text-amber-400">player@adeptplay.com</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-400">Demo Player Pass:</span>
                <span class="font-mono text-amber-400">player123</span>
            </div>
        </div>

        <form method="POST" action="install.php" class="space-y-3">
            <button type="submit" name="install_now" value="1" class="w-full bg-gradient-to-r from-cyan-500 to-indigo-600 hover:from-cyan-400 hover:to-indigo-500 text-slate-950 font-extrabold py-3.5 px-4 rounded-xl text-sm uppercase tracking-wider flex items-center justify-center gap-2 shadow-lg shadow-cyan-500/20 transition-all">
                <i class="fa-solid fa-gears"></i> Install / Reset Database Now
            </button>
            <a href="index.php" class="block text-center w-full bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold py-3 px-4 rounded-xl text-xs tracking-wider transition-all">
                <i class="fa-solid fa-arrow-left mr-1"></i> Go To App Home
            </a>
        </form>
    </div>
</body>
</html>
