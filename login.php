<?php
require_once __DIR__ . '/common/config.php';

if (is_logged_in()) {
    redirect('index.php');
}

$activeTab = isset($_GET['tab']) && $_GET['tab'] === 'register' ? 'register' : 'login';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'login') {
        $loginInput = sanitize_input($_POST['login_input'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($loginInput) || empty($password)) {
            $error = 'Please enter both phone/email and password.';
        } else {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE (email = ? OR phone = ?) AND is_admin = 0");
            $stmt->execute([$loginInput, $loginInput]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                if ($user['status'] === 'blocked') {
                    $error = 'Your account has been blocked by administrator.';
                } else {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    set_flash('success', 'Welcome back, ' . $user['name'] . '!');
                    redirect('index.php');
                }
            } else {
                $error = 'Invalid credentials. Please try again.';
            }
        }
    } elseif ($action === 'register') {
        $activeTab = 'register';
        $name = sanitize_input($_POST['name'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');
        $phone = sanitize_input($_POST['phone'] ?? '');
        $gameId = sanitize_input($_POST['game_id'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($name) || empty($email) || empty($phone) || empty($password)) {
            $error = 'All fields are required for registration.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email address format.';
        } else {
            // Check if phone or email exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? OR phone = ?");
            $stmt->execute([$email, $phone]);
            if ($stmt->fetchColumn() > 0) {
                $error = 'User with this email or phone number already exists.';
            } else {
                $hashedPass = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password, game_id, wallet_balance, is_admin) VALUES (?, ?, ?, ?, ?, 50.00, 0)");
                if ($stmt->execute([$name, $email, $phone, $hashedPass, $gameId])) {
                    $newUserId = $pdo->lastInsertId();
                    $_SESSION['user_id'] = $newUserId;
                    $_SESSION['user_name'] = $name;

                    // Add signup bonus transaction
                    $tStmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, payment_method, status, remarks) VALUES (?, 'deposit', 50.00, 'Signup Bonus', 'approved', 'Welcome Sign-up Bonus Added')");
                    $tStmt->execute([$newUserId]);

                    set_flash('success', 'Account created! ₹50 Welcome Bonus credited to your wallet!');
                    redirect('index.php');
                } else {
                    $error = 'Registration failed. Please try again.';
                }
            }
        }
    }
}

require_once __DIR__ . '/common/header.php';
?>

<div class="px-4 py-6 space-y-6">
    <!-- Brand Banner Header -->
    <div class="text-center space-y-2">
        <img src="assets/logo.jpg" alt="i Free Fire Logo" class="w-16 h-16 rounded-3xl object-cover ring-2 ring-amber-500 shadow-xl shadow-amber-500/20 mx-auto" onerror="this.src='https://images.unsplash.com/photo-1542751371-adc38448a05e?auto=format&fit=crop&w=200&q=80'">
        <h1 class="text-2xl font-bold tracking-tight text-slate-100">Welcome to i Free Fire</h1>
        <p class="text-xs text-slate-400">Play Free Fire Tournaments & Win Real Cash Money Daily</p>
    </div>

    <!-- Error Banner -->
    <?php if ($error): ?>
        <div class="p-3.5 rounded-2xl bg-rose-950/80 border border-rose-500/50 text-rose-300 text-xs font-semibold flex items-center gap-2">
            <i class="fa-solid fa-circle-exclamation text-rose-400 text-sm"></i>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
    <?php endif; ?>

    <!-- Form Container -->
    <div class="bg-brand-card border border-slate-800 rounded-3xl p-5 shadow-2xl">
        <!-- Tab Selector -->
        <div class="grid grid-cols-2 gap-2 p-1 bg-slate-950/80 rounded-2xl border border-slate-800/80 mb-5">
            <a href="login.php?tab=login" class="py-2.5 rounded-xl text-center text-xs font-bold transition-all <?= $activeTab === 'login' ? 'bg-cyan-500 text-slate-950 shadow-md' : 'text-slate-400 hover:text-slate-200' ?>">
                <i class="fa-solid fa-right-to-bracket mr-1"></i> Login
            </a>
            <a href="login.php?tab=register" class="py-2.5 rounded-xl text-center text-xs font-bold transition-all <?= $activeTab === 'register' ? 'bg-cyan-500 text-slate-950 shadow-md' : 'text-slate-400 hover:text-slate-200' ?>">
                <i class="fa-solid fa-user-plus mr-1"></i> Register
            </a>
        </div>

        <?php if ($activeTab === 'login'): ?>
            <!-- LOGIN FORM -->
            <form method="POST" action="login.php?tab=login" class="space-y-4">
                <input type="hidden" name="action" value="login">
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1.5">Phone Number or Email</label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-3.5 text-slate-500 text-sm"><i class="fa-solid fa-user"></i></span>
                        <input type="text" name="login_input" required placeholder="e.g., 9876543210 or player@gmail.com" 
                            class="w-full bg-slate-950 border border-slate-800 focus:border-cyan-500 rounded-xl py-3 pl-10 pr-4 text-xs text-slate-100 placeholder-slate-600 focus:outline-none transition-all">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1.5">Password</label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-3.5 text-slate-500 text-sm"><i class="fa-solid fa-lock"></i></span>
                        <input type="password" name="password" required placeholder="Enter password" 
                            class="w-full bg-slate-950 border border-slate-800 focus:border-cyan-500 rounded-xl py-3 pl-10 pr-4 text-xs text-slate-100 placeholder-slate-600 focus:outline-none transition-all">
                    </div>
                </div>

                <button type="submit" class="w-full btn-gradient text-slate-950 font-extrabold py-3.5 px-4 rounded-xl text-xs uppercase tracking-wider shadow-lg shadow-cyan-500/20 hover:opacity-90 transition-all flex items-center justify-center gap-2">
                    <i class="fa-solid fa-right-to-bracket"></i> Sign In To Play
                </button>
            </form>

            <div class="mt-4 text-center">
                <span class="text-xs text-slate-500">Demo Login:</span>
                <span class="text-xs font-mono text-cyan-400 font-bold ml-1">player@ifreefire.com</span> /
                <span class="text-xs font-mono text-cyan-400 font-bold">player123</span>
            </div>

        <?php else: ?>
            <!-- REGISTER FORM -->
            <form method="POST" action="login.php?tab=register" class="space-y-3.5">
                <input type="hidden" name="action" value="register">
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Full Name</label>
                    <input type="text" name="name" required placeholder="Your Gaming Name" 
                        class="w-full bg-slate-950 border border-slate-800 focus:border-cyan-500 rounded-xl py-2.5 px-3 text-xs text-slate-100 placeholder-slate-600 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Phone Number</label>
                    <input type="tel" name="phone" required placeholder="10-digit Mobile Number" 
                        class="w-full bg-slate-950 border border-slate-800 focus:border-cyan-500 rounded-xl py-2.5 px-3 text-xs text-slate-100 placeholder-slate-600 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Email Address</label>
                    <input type="email" name="email" required placeholder="name@example.com" 
                        class="w-full bg-slate-950 border border-slate-800 focus:border-cyan-500 rounded-xl py-2.5 px-3 text-xs text-slate-100 placeholder-slate-600 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Game Username / In-Game ID (IGN)</label>
                    <input type="text" name="game_id" placeholder="e.g. BGMI_KING_99" 
                        class="w-full bg-slate-950 border border-slate-800 focus:border-cyan-500 rounded-xl py-2.5 px-3 text-xs text-slate-100 placeholder-slate-600 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Create Password</label>
                    <input type="password" name="password" required placeholder="At least 6 characters" 
                        class="w-full bg-slate-950 border border-slate-800 focus:border-cyan-500 rounded-xl py-2.5 px-3 text-xs text-slate-100 placeholder-slate-600 focus:outline-none">
                </div>

                <div class="bg-cyan-500/10 border border-cyan-500/30 rounded-xl p-2.5 text-[11px] text-cyan-300 flex items-center gap-2">
                    <i class="fa-solid fa-gift text-cyan-400 text-sm"></i>
                    <span>Get ₹50 Instant Welcome Bonus on Registration!</span>
                </div>

                <button type="submit" class="w-full btn-gradient text-slate-950 font-extrabold py-3.5 px-4 rounded-xl text-xs uppercase tracking-wider shadow-lg shadow-cyan-500/20 hover:opacity-90 transition-all flex items-center justify-center gap-2">
                    <i class="fa-solid fa-user-plus"></i> Create Account & Get Bonus
                </button>
            </form>
        <?php endif; ?>
    </div>

    <!-- Admin Panel Quick Link -->
    <div class="text-center pt-2">
        <a href="admin/login.php" class="text-xs text-slate-500 hover:text-cyan-400 flex items-center justify-center gap-1 transition-all">
            <i class="fa-solid fa-shield-halved"></i> Switch to Admin Panel Login
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/common/bottom.php'; ?>
