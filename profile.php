<?php
require_once __DIR__ . '/common/config.php';

if (!is_logged_in()) {
    set_flash('danger', 'Please login to view your profile.');
    redirect('login.php');
}

$currentUser = get_logged_in_user();

// Handle Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    set_flash('success', 'Logged out successfully.');
    redirect('login.php');
}

// Handle Profile Update
$updateMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $name = sanitize_input($_POST['name'] ?? '');
    $gameId = sanitize_input($_POST['game_id'] ?? '');
    $newPass = $_POST['new_password'] ?? '';

    if (empty($name)) {
        set_flash('danger', 'Name cannot be empty.');
        redirect('profile.php');
    }

    if (!empty($newPass)) {
        $hashed = password_hash($newPass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET name = ?, game_id = ?, password = ? WHERE id = ?");
        $stmt->execute([$name, $gameId, $hashed, $currentUser['id']]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET name = ?, game_id = ? WHERE id = ?");
        $stmt->execute([$name, $gameId, $currentUser['id']]);
    }

    set_flash('success', 'Profile updated successfully!');
    redirect('profile.php');
}

// Calculate User Lifetime Stats
$mStmt = $pdo->prepare("SELECT COUNT(*) FROM tournament_participants WHERE user_id = ?");
$mStmt->execute([$currentUser['id']]);
$totalMatches = $mStmt->fetchColumn() ?: 0;

$kStmt = $pdo->prepare("SELECT SUM(kills_count), SUM(prize_won) FROM tournament_participants WHERE user_id = ?");
$kStmt->execute([$currentUser['id']]);
$statData = $kStmt->fetch(PDO::FETCH_NUM);
$totalKills = $statData[0] ?: 0;
$totalPrizeWon = $statData[1] ?: 0.00;

$supportPhone = get_setting('support_phone', '+91 98765 43210');
$supportEmail = get_setting('support_email', 'support@ifreefire.com');

require_once __DIR__ . '/common/header.php';
?>

<div class="px-4 py-4 space-y-5">
    
    <!-- User Profile Header Card -->
    <div class="bg-brand-card border border-slate-800 rounded-3xl p-5 shadow-2xl space-y-4">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-cyan-500 via-indigo-500 to-amber-500 flex items-center justify-center font-display text-2xl font-bold text-slate-950 shadow-lg shadow-cyan-500/20">
                <?= strtoupper(substr($currentUser['name'], 0, 1)) ?>
            </div>
            <div>
                <h2 class="font-display text-2xl font-bold text-slate-100 tracking-wide uppercase">
                    <?= htmlspecialchars($currentUser['name']) ?>
                </h2>
                <span class="text-xs font-mono text-cyan-400 font-bold block">
                    IGN: <?= htmlspecialchars($currentUser['game_id'] ?: 'Not Set') ?>
                </span>
                <span class="text-[11px] text-slate-400 block">
                    <i class="fa-solid fa-phone text-slate-500 mr-1"></i> <?= htmlspecialchars($currentUser['phone']) ?>
                </span>
            </div>
        </div>

        <!-- User Lifetime Stats Grid -->
        <div class="grid grid-cols-3 gap-2 bg-slate-950 p-3 rounded-2xl border border-slate-800/80 text-center">
            <div>
                <span class="text-[10px] text-slate-500 uppercase font-bold block">Matches</span>
                <span class="text-base font-extrabold text-cyan-400"><?= $totalMatches ?></span>
            </div>
            <div>
                <span class="text-[10px] text-slate-500 uppercase font-bold block">Total Kills</span>
                <span class="text-base font-extrabold text-amber-400"><?= $totalKills ?></span>
            </div>
            <div>
                <span class="text-[10px] text-slate-500 uppercase font-bold block">Winnings</span>
                <span class="text-base font-extrabold text-emerald-400"><?= format_currency($totalPrizeWon) ?></span>
            </div>
        </div>
    </div>

    <!-- Admin Panel Shortcut (if user is admin) -->
    <?php if ($currentUser['is_admin'] == 1): ?>
        <a href="admin/index.php" class="block w-full bg-gradient-to-r from-amber-500 to-amber-600 text-slate-950 font-extrabold py-3.5 px-4 rounded-2xl text-center text-xs uppercase tracking-wider shadow-lg shadow-amber-500/20">
            <i class="fa-solid fa-shield-halved mr-1"></i> Open Admin Management Dashboard
        </a>
    <?php endif; ?>

    <!-- Edit Profile Section -->
    <div class="bg-brand-card border border-slate-800 rounded-3xl p-5 space-y-4 shadow-xl">
        <h3 class="font-display text-xl font-bold uppercase tracking-wide text-slate-200">Account Settings</h3>

        <form method="POST" action="profile.php" class="space-y-3.5">
            <input type="hidden" name="action" value="update_profile">

            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Full Name</label>
                <input type="text" name="name" required value="<?= htmlspecialchars($currentUser['name']) ?>" 
                    class="w-full bg-slate-950 border border-slate-800 focus:border-cyan-500 rounded-xl py-2.5 px-3 text-xs text-slate-100 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">In-Game Player ID / IGN</label>
                <input type="text" name="game_id" value="<?= htmlspecialchars($currentUser['game_id']) ?>" placeholder="e.g. BGMI_PRO_99" 
                    class="w-full bg-slate-950 border border-slate-800 focus:border-cyan-500 rounded-xl py-2.5 px-3 text-xs text-slate-100 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Change Password (leave blank to keep current)</label>
                <input type="password" name="new_password" placeholder="New Password" 
                    class="w-full bg-slate-950 border border-slate-800 focus:border-cyan-500 rounded-xl py-2.5 px-3 text-xs text-slate-100 focus:outline-none">
            </div>

            <button type="submit" class="w-full btn-gradient text-slate-950 font-extrabold py-3 px-4 rounded-xl text-xs uppercase tracking-wider shadow-lg shadow-cyan-500/20">
                <i class="fa-solid fa-floppy-disk mr-1"></i> Save Changes
            </button>
        </form>
    </div>

    <!-- App Support & Rules Links -->
    <div class="bg-brand-card border border-slate-800 rounded-3xl p-4 space-y-2 text-xs">
        <div class="font-bold text-slate-300 pb-1 border-b border-slate-800">Help & Support</div>

        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $supportPhone) ?>" target="_blank" class="flex items-center justify-between p-2.5 rounded-xl bg-slate-950 border border-slate-800/80 hover:border-emerald-500/50 transition-all text-slate-200">
            <span class="flex items-center gap-2">
                <i class="fa-brands fa-whatsapp text-emerald-400 text-sm"></i>
                <span>WhatsApp Customer Support</span>
            </span>
            <i class="fa-solid fa-chevron-right text-slate-600 text-xs"></i>
        </a>

        <div class="p-3 bg-slate-950 rounded-xl border border-slate-800 space-y-1.5 text-slate-400">
            <div class="font-bold text-cyan-400 flex items-center gap-1">
                <i class="fa-solid fa-scale-balanced"></i> Fair Play Guidelines:
            </div>
            <ul class="list-disc list-inside space-y-0.5 text-[11px]">
                <li>Emulators are strictly prohibited unless mentioned.</li>
                <li>Hacking or exploiting results in immediate ban & wallet freeze.</li>
                <li>Submit exact In-Game Username before joining match.</li>
            </ul>
        </div>
    </div>

    <!-- Logout Button -->
    <a href="profile.php?action=logout" class="block w-full bg-rose-950/80 hover:bg-rose-900 border border-rose-500/40 text-rose-300 font-extrabold py-3.5 px-4 rounded-2xl text-center text-xs uppercase tracking-wider transition-all shadow-lg shadow-rose-950/50">
        <i class="fa-solid fa-right-from-bracket mr-1"></i> Log Out
    </a>

</div>

<?php require_once __DIR__ . '/common/bottom.php'; ?>
