<?php
require_once __DIR__ . '/admin/common/header.php';

// Handle Direct User Wallet Update or Block/Unblock Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $targetUserId = (int)($_POST['user_id'] ?? 0);
    $action = $_POST['action'];

    if ($action === 'update_balance') {
        $amount = (float)($_POST['amount'] ?? 0);
        $type = $_POST['type'] ?? 'add'; // 'add' or 'deduct'
        $remarks = sanitize_input($_POST['remarks'] ?? 'Admin wallet adjustment');

        if ($amount > 0) {
            $pdo->beginTransaction();
            try {
                if ($type === 'add') {
                    $uStmt = $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
                    $uStmt->execute([$amount, $targetUserId]);

                    $tStmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, payment_method, status, remarks) VALUES (?, 'admin_credit', ?, 'Admin', 'approved', ?)");
                    $tStmt->execute([$targetUserId, $amount, $remarks]);

                    set_flash('success', 'Credited ' . format_currency($amount) . ' to user wallet!');
                } else {
                    $uStmt = $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?");
                    $uStmt->execute([$amount, $targetUserId]);

                    $tStmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, payment_method, status, remarks) VALUES (?, 'admin_debit', ?, 'Admin', 'approved', ?)");
                    $tStmt->execute([$targetUserId, $amount, $remarks]);

                    set_flash('success', 'Deducted ' . format_currency($amount) . ' from user wallet!');
                }
                $pdo->commit();
            } catch (Exception $e) {
                $pdo->rollBack();
                set_flash('danger', 'Error updating wallet.');
            }
        }
    } elseif ($action === 'toggle_status') {
        $newStatus = $_POST['status'] === 'blocked' ? 'blocked' : 'active';
        $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $targetUserId]);
        set_flash('success', 'User account status updated to ' . $newStatus);
    }
    redirect('user.php');
}

// Fetch Users List
$search = sanitize_input($_GET['q'] ?? '');
if ($search) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE is_admin = 0 AND (name LIKE ? OR phone LIKE ? OR email LIKE ? OR game_id LIKE ?) ORDER BY id DESC");
    $stmt->execute(["%$search%", "%$search%", "%$search%", "%$search%"]);
} else {
    $stmt = $pdo->query("SELECT * FROM users WHERE is_admin = 0 ORDER BY id DESC");
}
$users = $stmt->fetchAll();
?>

<div class="px-4 py-4 space-y-5">
    
    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
        <div>
            <h1 class="font-display text-2xl font-bold uppercase tracking-wider text-amber-400">User Management</h1>
            <p class="text-xs text-slate-400">View Players, Adjust Wallets & Block Accounts</p>
        </div>
    </div>

    <!-- Search Input -->
    <form method="GET" action="user.php" class="relative">
        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search user by Name, Phone, Email or Game ID..." 
            class="w-full bg-slate-900 border border-slate-800 focus:border-amber-500 rounded-2xl py-3 pl-10 pr-4 text-xs text-slate-100 placeholder-slate-500 focus:outline-none">
        <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3.5 text-slate-500 text-sm"></i>
    </form>

    <!-- Users List Feed -->
    <div class="space-y-4">
        <?php if (empty($users)): ?>
            <div class="text-center py-8 bg-slate-900 border border-slate-800 rounded-2xl text-xs text-slate-500">
                No users found.
            </div>
        <?php else: ?>
            <?php foreach ($users as $u): ?>
                <div class="bg-brand-card border border-slate-800 rounded-2xl p-4 space-y-3 shadow-md">
                    <div class="flex justify-between items-start border-b border-slate-800 pb-2">
                        <div>
                            <h3 class="font-bold text-slate-100 text-sm flex items-center gap-2">
                                <span><?= htmlspecialchars($u['name']) ?></span>
                                <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-full <?= $u['status'] === 'blocked' ? 'bg-rose-500/20 text-rose-400' : 'bg-emerald-500/20 text-emerald-400' ?>">
                                    <?= strtoupper($u['status']) ?>
                                </span>
                            </h3>
                            <div class="text-xs text-slate-400 font-mono mt-0.5">
                                Phone: <?= htmlspecialchars($u['phone']) ?> | Email: <?= htmlspecialchars($u['email']) ?>
                            </div>
                            <div class="text-xs text-cyan-400 font-mono font-bold mt-0.5">
                                IGN: <?= htmlspecialchars($u['game_id'] ?: 'Not Set') ?>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] text-slate-500 block uppercase font-bold">Wallet</span>
                            <span class="text-base font-extrabold text-amber-400 font-display"><?= format_currency($u['wallet_balance']) ?></span>
                        </div>
                    </div>

                    <!-- Actions Form: Adjust Balance -->
                    <form method="POST" action="user.php" class="bg-slate-950 p-3 rounded-xl border border-slate-800 space-y-2 text-xs">
                        <input type="hidden" name="action" value="update_balance">
                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">

                        <div class="font-bold text-slate-300 text-[11px]">Direct Wallet Adjustment:</div>
                        <div class="grid grid-cols-3 gap-2">
                            <div>
                                <select name="type" class="w-full bg-slate-900 border border-slate-800 rounded-lg p-2 text-slate-100 font-bold focus:outline-none">
                                    <option value="add">+ Add Credit</option>
                                    <option value="deduct">- Deduct Cash</option>
                                </select>
                            </div>
                            <div>
                                <input type="number" name="amount" required step="1" placeholder="Amount ₹" 
                                    class="w-full bg-slate-900 border border-slate-800 rounded-lg p-2 text-slate-100 font-bold focus:outline-none">
                            </div>
                            <button type="submit" class="bg-amber-500 hover:bg-amber-400 text-slate-950 font-extrabold py-2 rounded-lg text-xs uppercase transition-all">
                                Update Wallet
                            </button>
                        </div>
                    </form>

                    <!-- Block / Unblock Button -->
                    <form method="POST" action="user.php" class="text-right">
                        <input type="hidden" name="action" value="toggle_status">
                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                        <input type="hidden" name="status" value="<?= $u['status'] === 'blocked' ? 'active' : 'blocked' ?>">
                        <button type="submit" class="text-xs font-bold px-3 py-1.5 rounded-lg border <?= $u['status'] === 'blocked' ? 'bg-emerald-950 text-emerald-400 border-emerald-500/40' : 'bg-rose-950 text-rose-400 border-rose-500/40' ?>">
                            <i class="fa-solid <?= $u['status'] === 'blocked' ? 'fa-lock-open' : 'fa-ban' ?> mr-1"></i>
                            <?= $u['status'] === 'blocked' ? 'Unblock Account' : 'Block User Account' ?>
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

<?php require_once __DIR__ . '/admin/common/bottom.php'; ?>
