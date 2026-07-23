<?php
require_once __DIR__ . '/../../common/config.php';

// Handle Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['admin_logged_in']);
    unset($_SESSION['admin_user_id']);
    set_flash('success', 'Admin logged out.');
    redirect('login.php');
}

if (is_admin_logged_in()) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter both admin email and password.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_admin = 1");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_user_id'] = $admin['id'];
            set_flash('success', 'Welcome, Administrator!');
            redirect('index.php');
        } else {
            $error = 'Invalid admin credentials.';
        }
    }
}

require_once __DIR__ . '/common/header.php';
?>

<div class="px-4 py-8 space-y-6">
    <div class="text-center space-y-2">
        <div class="w-16 h-16 bg-amber-500/10 border border-amber-500/30 rounded-3xl flex items-center justify-center mx-auto text-amber-400 text-2xl shadow-xl shadow-amber-500/10">
            <i class="fa-solid fa-shield-halved"></i>
        </div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-100">Adept Admin Login</h1>
        <p class="text-xs text-slate-400">Restricted Administration Panel Access</p>
    </div>

    <?php if ($error): ?>
        <div class="p-3.5 rounded-2xl bg-rose-950/80 border border-rose-500/50 text-rose-300 text-xs font-semibold flex items-center gap-2">
            <i class="fa-solid fa-circle-exclamation text-rose-400 text-sm"></i>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
    <?php endif; ?>

    <div class="bg-brand-card border border-amber-500/20 rounded-3xl p-5 shadow-2xl space-y-4">
        <form method="POST" action="login.php" class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Admin Email</label>
                <input type="email" name="email" required placeholder="admin@adeptplay.com" value="admin@adeptplay.com"
                    class="w-full bg-slate-950 border border-slate-800 focus:border-amber-500 rounded-xl py-3 px-3 text-xs text-slate-100 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Admin Password</label>
                <input type="password" name="password" required placeholder="admin123" value="admin123"
                    class="w-full bg-slate-950 border border-slate-800 focus:border-amber-500 rounded-xl py-3 px-3 text-xs text-slate-100 focus:outline-none">
            </div>

            <button type="submit" class="w-full btn-amber text-slate-950 font-extrabold py-3.5 px-4 rounded-xl text-xs uppercase tracking-wider shadow-lg shadow-amber-500/20 hover:opacity-90 transition-all flex items-center justify-center gap-2">
                <i class="fa-solid fa-key"></i> Authenticate Admin Access
            </button>
        </form>

        <div class="p-3 bg-slate-950 rounded-2xl border border-slate-800 text-[11px] text-slate-400 text-center">
            Default Credentials: <span class="text-amber-400 font-mono font-bold">admin@adeptplay.com</span> / <span class="text-amber-400 font-mono font-bold">admin123</span>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/common/bottom.php'; ?>
