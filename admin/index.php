<?php
require_once __DIR__ . '/admin/common/header.php';

// Handle Transaction Approval / Rejection Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $txId = (int)($_POST['tx_id'] ?? 0);
    $txAction = $_POST['action'];

    $stmt = $pdo->prepare("SELECT * FROM transactions WHERE id = ?");
    $stmt->execute([$txId]);
    $tx = $stmt->fetch();

    if ($tx && $tx['status'] === 'pending') {
        if ($txAction === 'approve_deposit') {
            $pdo->beginTransaction();
            try {
                // Update transaction status
                $uStmt = $pdo->prepare("UPDATE transactions SET status = 'approved' WHERE id = ?");
                $uStmt->execute([$txId]);

                // Credit user wallet balance
                $wStmt = $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
                $wStmt->execute([$tx['amount'], $tx['user_id']]);

                $pdo->commit();
                set_flash('success', 'Deposit of ' . format_currency($tx['amount']) . ' approved & credited to user wallet.');
            } catch (Exception $e) {
                $pdo->rollBack();
                set_flash('danger', 'Error approving deposit.');
            }
        } elseif ($txAction === 'reject_deposit') {
            $uStmt = $pdo->prepare("UPDATE transactions SET status = 'rejected' WHERE id = ?");
            $uStmt->execute([$txId]);
            set_flash('success', 'Deposit request rejected.');
        } elseif ($txAction === 'approve_withdrawal') {
            $uStmt = $pdo->prepare("UPDATE transactions SET status = 'approved' WHERE id = ?");
            $uStmt->execute([$txId]);
            set_flash('success', 'Withdrawal request marked as completed/sent!');
        } elseif ($txAction === 'reject_withdrawal') {
            $pdo->beginTransaction();
            try {
                // Mark rejected
                $uStmt = $pdo->prepare("UPDATE transactions SET status = 'rejected' WHERE id = ?");
                $uStmt->execute([$txId]);

                // Refund wallet balance
                $wStmt = $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
                $wStmt->execute([$tx['amount'], $tx['user_id']]);

                $pdo->commit();
                set_flash('success', 'Withdrawal request rejected. Amount refunded to user wallet.');
            } catch (Exception $e) {
                $pdo->rollBack();
                set_flash('danger', 'Error rejecting withdrawal.');
            }
        }
    }
    redirect('index.php');
}

// Fetch Metrics
$userCount = $pdo->query("SELECT COUNT(*) FROM users WHERE is_admin = 0")->fetchColumn();
$tournamentCount = $pdo->query("SELECT COUNT(*) FROM tournaments")->fetchColumn();
$pendingDepositsCount = $pdo->query("SELECT COUNT(*) FROM transactions WHERE type = 'deposit' AND status = 'pending'")->fetchColumn();
$pendingWithdrawalsCount = $pdo->query("SELECT COUNT(*) FROM transactions WHERE type = 'withdrawal' AND status = 'pending'")->fetchColumn();

// Fetch Pending Transactions
$dStmt = $pdo->prepare("SELECT t.*, u.name as user_name, u.phone as user_phone FROM transactions t JOIN users u ON t.user_id = u.id WHERE t.type = 'deposit' AND t.status = 'pending' ORDER BY t.id DESC");
$dStmt->execute();
$pendingDeposits = $dStmt->fetchAll();

$wStmt = $pdo->prepare("SELECT t.*, u.name as user_name, u.phone as user_phone FROM transactions t JOIN users u ON t.user_id = u.id WHERE t.type = 'withdrawal' AND t.status = 'pending' ORDER BY t.id DESC");
$wStmt->execute();
$pendingWithdrawals = $wStmt->fetchAll();
?>

<div class="px-4 py-4 space-y-5">
    
    <!-- Admin Dashboard Header -->
    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
        <div>
            <h1 class="font-display text-2xl font-bold uppercase tracking-wider text-amber-400">Admin Control Panel</h1>
            <p class="text-xs text-slate-400">System Metrics & Payment Approvals</p>
        </div>
        <a href="login.php?action=logout" class="bg-rose-950/80 border border-rose-500/40 text-rose-300 px-3 py-1.5 rounded-xl text-xs font-bold">
            <i class="fa-solid fa-right-from-bracket mr-1"></i> Logout
        </a>
    </div>

    <!-- Quick Stats Grid -->
    <div class="grid grid-cols-2 gap-2 text-center">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-3 shadow-md space-y-1">
            <span class="text-[10px] text-slate-500 uppercase font-bold block">Total Players</span>
            <span class="text-2xl font-extrabold text-cyan-400 font-display"><?= $userCount ?></span>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-3 shadow-md space-y-1">
            <span class="text-[10px] text-slate-500 uppercase font-bold block">Tournaments</span>
            <span class="text-2xl font-extrabold text-amber-400 font-display"><?= $tournamentCount ?></span>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-3 shadow-md space-y-1">
            <span class="text-[10px] text-slate-500 uppercase font-bold block">Pending Deposits</span>
            <span class="text-2xl font-extrabold text-emerald-400 font-display"><?= $pendingDepositsCount ?></span>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-3 shadow-md space-y-1">
            <span class="text-[10px] text-slate-500 uppercase font-bold block">Pending Payouts</span>
            <span class="text-2xl font-extrabold text-rose-400 font-display"><?= $pendingWithdrawalsCount ?></span>
        </div>
    </div>

    <!-- Pending Deposit Approvals -->
    <div class="bg-brand-card border border-slate-800 rounded-3xl p-4 space-y-3 shadow-xl">
        <h3 class="font-display text-xl font-bold uppercase tracking-wider text-emerald-400 flex items-center justify-between">
            <span>Pending Wallet Deposits</span>
            <span class="bg-emerald-500/20 text-emerald-400 text-xs px-2.5 py-0.5 rounded-full font-sans font-bold"><?= count($pendingDeposits) ?></span>
        </h3>

        <?php if (empty($pendingDeposits)): ?>
            <p class="text-xs text-slate-500 py-3 text-center">No pending deposit requests.</p>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($pendingDeposits as $d): ?>
                    <div class="bg-slate-950 border border-slate-800 rounded-2xl p-3.5 space-y-2 text-xs">
                        <div class="flex justify-between items-center border-b border-slate-800 pb-2">
                            <div>
                                <span class="font-bold text-slate-200 block"><?= htmlspecialchars($d['user_name']) ?></span>
                                <span class="text-[10px] text-slate-500 font-mono"><?= htmlspecialchars($d['user_phone']) ?></span>
                            </div>
                            <span class="text-base font-extrabold text-emerald-400"><?= format_currency($d['amount']) ?></span>
                        </div>

                        <div class="text-[11px] text-slate-400 flex justify-between">
                            <span>UTR / Ref No:</span>
                            <span class="font-mono font-bold text-cyan-400"><?= htmlspecialchars($d['utr_reference']) ?></span>
                        </div>

                        <div class="grid grid-cols-2 gap-2 pt-1">
                            <form method="POST" action="index.php">
                                <input type="hidden" name="tx_id" value="<?= $d['id'] ?>">
                                <button type="submit" name="action" value="approve_deposit" class="w-full bg-emerald-600 hover:bg-emerald-500 text-slate-950 font-extrabold py-2 rounded-xl text-xs uppercase transition-all">
                                    Approve & Credit
                                </button>
                            </form>
                            <form method="POST" action="index.php">
                                <input type="hidden" name="tx_id" value="<?= $d['id'] ?>">
                                <button type="submit" name="action" value="reject_deposit" class="w-full bg-rose-950 text-rose-300 border border-rose-500/40 font-bold py-2 rounded-xl text-xs uppercase transition-all">
                                    Reject
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Pending Withdrawal Requests -->
    <div class="bg-brand-card border border-slate-800 rounded-3xl p-4 space-y-3 shadow-xl">
        <h3 class="font-display text-xl font-bold uppercase tracking-wider text-rose-400 flex items-center justify-between">
            <span>Pending Withdrawal Requests</span>
            <span class="bg-rose-500/20 text-rose-400 text-xs px-2.5 py-0.5 rounded-full font-sans font-bold"><?= count($pendingWithdrawals) ?></span>
        </h3>

        <?php if (empty($pendingWithdrawals)): ?>
            <p class="text-xs text-slate-500 py-3 text-center">No pending withdrawal requests.</p>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($pendingWithdrawals as $w): ?>
                    <div class="bg-slate-950 border border-slate-800 rounded-2xl p-3.5 space-y-2 text-xs">
                        <div class="flex justify-between items-center border-b border-slate-800 pb-2">
                            <div>
                                <span class="font-bold text-slate-200 block"><?= htmlspecialchars($w['user_name']) ?></span>
                                <span class="text-[10px] text-slate-500 font-mono"><?= htmlspecialchars($w['user_phone']) ?></span>
                            </div>
                            <span class="text-base font-extrabold text-amber-400"><?= format_currency($w['amount']) ?></span>
                        </div>

                        <div class="text-[11px] text-slate-400 space-y-0.5">
                            <div class="flex justify-between">
                                <span>Method:</span>
                                <span class="font-bold text-slate-200"><?= htmlspecialchars($w['payment_method']) ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span>Details:</span>
                                <span class="font-mono text-cyan-400 font-bold"><?= htmlspecialchars($w['utr_reference']) ?></span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2 pt-1">
                            <form method="POST" action="index.php">
                                <input type="hidden" name="tx_id" value="<?= $w['id'] ?>">
                                <button type="submit" name="action" value="approve_withdrawal" class="w-full bg-cyan-500 hover:bg-cyan-400 text-slate-950 font-extrabold py-2 rounded-xl text-xs uppercase transition-all">
                                    Mark Paid
                                </button>
                            </form>
                            <form method="POST" action="index.php">
                                <input type="hidden" name="tx_id" value="<?= $w['id'] ?>">
                                <button type="submit" name="action" value="reject_withdrawal" class="w-full bg-rose-950 text-rose-300 border border-rose-500/40 font-bold py-2 rounded-xl text-xs uppercase transition-all">
                                    Reject & Refund
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php require_once __DIR__ . '/admin/common/bottom.php'; ?>
