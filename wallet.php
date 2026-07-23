<?php
require_once __DIR__ . '/common/config.php';

if (!is_logged_in()) {
    set_flash('danger', 'Please login to access your wallet.');
    redirect('login.php');
}

$currentUser = get_logged_in_user();
$minDeposit = (float)get_setting('min_deposit', '10');
$minWithdrawal = (float)get_setting('min_withdrawal', '50');
$upiId = get_setting('upi_id', 'adeptplay@upi');
$qrCodeUrl = get_setting('qr_code_url', 'https://images.unsplash.com/photo-1628155930542-3c7a64e2c833?auto=format&fit=crop&w=400&q=80');
$paymentInstructions = get_setting('payment_instructions', 'Scan QR or pay to UPI ID. Submit 12-digit UTR number.');

$activeTab = sanitize_input($_GET['tab'] ?? 'deposit');

// Handle Add Money Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_money') {
    $amount = (float)($_POST['amount'] ?? 0);
    $utrRef = sanitize_input($_POST['utr_reference'] ?? '');

    if ($amount < $minDeposit) {
        set_flash('danger', 'Minimum deposit amount is ' . format_currency($minDeposit));
        redirect('wallet.php?tab=deposit');
    } elseif (empty($utrRef) || strlen($utrRef) < 6) {
        set_flash('danger', 'Please enter a valid 12-digit UTR/UPI Transaction Reference number.');
        redirect('wallet.php?tab=deposit');
    } else {
        $stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, payment_method, utr_reference, status, remarks) VALUES (?, 'deposit', ?, 'UPI QR', ?, 'pending', 'Deposit request submitted by user')");
        if ($stmt->execute([$currentUser['id'], $amount, $utrRef])) {
            set_flash('success', 'Deposit request of ' . format_currency($amount) . ' submitted! Admin will verify UTR and credit wallet shortly.');
            redirect('wallet.php?tab=history');
        } else {
            set_flash('danger', 'Failed to submit deposit request.');
            redirect('wallet.php?tab=deposit');
        }
    }
}

// Handle Withdraw Money Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'withdraw_money') {
    $amount = (float)($_POST['amount'] ?? 0);
    $payMethod = sanitize_input($_POST['payment_method'] ?? 'UPI');
    $payDetails = sanitize_input($_POST['payment_details'] ?? '');

    if ($amount < $minWithdrawal) {
        set_flash('danger', 'Minimum withdrawal amount is ' . format_currency($minWithdrawal));
        redirect('wallet.php?tab=withdraw');
    } elseif ($amount > (float)$currentUser['wallet_balance']) {
        set_flash('danger', 'Insufficient wallet balance for this withdrawal request.');
        redirect('wallet.php?tab=withdraw');
    } elseif (empty($payDetails)) {
        set_flash('danger', 'Please provide your UPI ID / Phone / Bank Account details.');
        redirect('wallet.php?tab=withdraw');
    } else {
        $pdo->beginTransaction();
        try {
            // Deduct wallet balance temporarily
            $newBalance = (float)$currentUser['wallet_balance'] - $amount;
            $uStmt = $pdo->prepare("UPDATE users SET wallet_balance = ? WHERE id = ?");
            $uStmt->execute([$newBalance, $currentUser['id']]);

            // Create transaction entry
            $tStmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, payment_method, utr_reference, status, remarks) VALUES (?, 'withdrawal', ?, ?, ?, 'pending', ?)");
            $tStmt->execute([$currentUser['id'], $amount, $payMethod, 'PAYMENT_ID: ' . $payDetails, 'Withdrawal request submitted to ' . $payDetails]);

            $pdo->commit();
            set_flash('success', 'Withdrawal request of ' . format_currency($amount) . ' submitted! Admin will transfer funds soon.');
            redirect('wallet.php?tab=history');
        } catch (Exception $e) {
            $pdo->rollBack();
            set_flash('danger', 'Failed to process withdrawal request.');
            redirect('wallet.php?tab=withdraw');
        }
    }
}

// Fetch Transaction History
$tStmt = $pdo->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY id DESC");
$tStmt->execute([$currentUser['id']]);
$transactions = $tStmt->fetchAll();

// Calculate total earnings
$eStmt = $pdo->prepare("SELECT SUM(amount) FROM transactions WHERE user_id = ? AND type = 'winnings' AND status = 'approved'");
$eStmt->execute([$currentUser['id']]);
$totalWinnings = $eStmt->fetchColumn() ?: 0.00;

require_once __DIR__ . '/common/header.php';
?>

<div class="px-4 py-4 space-y-5">
    
    <!-- Balance Header Card -->
    <div class="relative rounded-3xl overflow-hidden bg-gradient-to-br from-slate-900 via-brand-card to-indigo-950 border border-slate-800 p-5 shadow-2xl space-y-4">
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1.5">
                <i class="fa-solid fa-wallet text-amber-400"></i> My Wallet Balance
            </span>
            <span class="bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 text-[10px] font-extrabold px-2.5 py-0.5 rounded-full uppercase">
                ACTIVE
            </span>
        </div>

        <div>
            <span class="text-3xl font-extrabold text-slate-100 font-display tracking-wide">
                <?= format_currency($currentUser['wallet_balance']) ?>
            </span>
        </div>

        <div class="grid grid-cols-2 gap-2 pt-2 border-t border-slate-800/80 text-xs">
            <div>
                <span class="text-slate-500 text-[10px] block uppercase font-bold">Total Winnings</span>
                <span class="font-extrabold text-amber-400"><?= format_currency($totalWinnings) ?></span>
            </div>
            <div class="text-right">
                <span class="text-slate-500 text-[10px] block uppercase font-bold">Min Withdrawal</span>
                <span class="font-extrabold text-cyan-400"><?= format_currency($minWithdrawal) ?></span>
            </div>
        </div>
    </div>

    <!-- Tab Selector -->
    <div class="grid grid-cols-3 gap-1 p-1 bg-slate-900 border border-slate-800 rounded-2xl text-center text-xs font-bold">
        <a href="wallet.php?tab=deposit" class="py-2.5 rounded-xl transition-all flex items-center justify-center gap-1.5 <?= $activeTab === 'deposit' ? 'bg-cyan-500 text-slate-950 shadow-md' : 'text-slate-400 hover:text-slate-200' ?>">
            <i class="fa-solid fa-plus-circle"></i> Add Money
        </a>
        <a href="wallet.php?tab=withdraw" class="py-2.5 rounded-xl transition-all flex items-center justify-center gap-1.5 <?= $activeTab === 'withdraw' ? 'bg-cyan-500 text-slate-950 shadow-md' : 'text-slate-400 hover:text-slate-200' ?>">
            <i class="fa-solid fa-money-bill-transfer"></i> Withdraw
        </a>
        <a href="wallet.php?tab=history" class="py-2.5 rounded-xl transition-all flex items-center justify-center gap-1.5 <?= $activeTab === 'history' ? 'bg-cyan-500 text-slate-950 shadow-md' : 'text-slate-400 hover:text-slate-200' ?>">
            <i class="fa-solid fa-clock-rotate-left"></i> History
        </a>
    </div>

    <?php if ($activeTab === 'deposit'): ?>
        <!-- ADD MONEY FORM -->
        <div class="bg-brand-card border border-slate-800 rounded-3xl p-5 space-y-4 shadow-xl">
            <h3 class="font-display text-xl font-bold uppercase tracking-wide text-cyan-400">Add Wallet Money</h3>

            <!-- Payment QR & UPI Info -->
            <div class="bg-slate-950 border border-slate-800 rounded-2xl p-4 text-center space-y-3">
                <div class="text-xs font-bold text-slate-300">Scan QR Code or copy UPI ID to pay:</div>
                
                <div class="w-40 h-40 bg-white p-2 rounded-2xl mx-auto shadow-lg">
                    <img src="<?= htmlspecialchars($qrCodeUrl) ?>" alt="UPI QR Code" class="w-full h-full object-cover rounded-xl">
                </div>

                <div class="flex items-center justify-center gap-2 bg-slate-900 border border-slate-800 rounded-xl py-2 px-3">
                    <span class="text-xs font-mono font-bold text-cyan-400" id="upiIdText"><?= htmlspecialchars($upiId) ?></span>
                    <button type="button" onclick="navigator.clipboard.writeText('<?= htmlspecialchars($upiId) ?>'); alert('UPI ID copied!');" class="text-xs text-slate-400 hover:text-slate-100">
                        <i class="fa-solid fa-copy"></i>
                    </button>
                </div>

                <p class="text-[11px] text-slate-400 leading-relaxed"><?= htmlspecialchars($paymentInstructions) ?></p>
            </div>

            <!-- Submit Form -->
            <form method="POST" action="wallet.php?tab=deposit" class="space-y-4">
                <input type="hidden" name="action" value="add_money">

                <!-- Quick Amount Chips -->
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-2">Deposit Amount (₹)</label>
                    <div class="grid grid-cols-4 gap-2 mb-2">
                        <?php foreach ([50, 100, 200, 500] as $chipAmt): ?>
                            <button type="button" onclick="document.getElementById('depositAmt').value = '<?= $chipAmt ?>'" 
                                    class="py-2 bg-slate-950 border border-slate-800 hover:border-cyan-500/50 rounded-xl text-xs font-bold text-slate-200">
                                +₹<?= $chipAmt ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                    <input type="number" name="amount" id="depositAmt" required min="<?= $minDeposit ?>" step="1" placeholder="Enter amount (Min ₹<?= $minDeposit ?>)" 
                        class="w-full bg-slate-950 border border-slate-800 focus:border-cyan-500 rounded-xl py-3 px-3 text-xs text-slate-100 font-bold focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">12-Digit UTR / UPI Ref Number</label>
                    <input type="text" name="utr_reference" required placeholder="e.g. 329182391029" 
                        class="w-full bg-slate-950 border border-slate-800 focus:border-cyan-500 rounded-xl py-3 px-3 text-xs text-slate-100 font-mono focus:outline-none">
                    <span class="text-[10px] text-slate-500 block mt-1">Found in your GPay / PhonePe / Paytm transaction receipt details.</span>
                </div>

                <button type="submit" class="w-full btn-gradient text-slate-950 font-extrabold py-3.5 px-4 rounded-xl text-xs uppercase tracking-wider shadow-lg shadow-cyan-500/20 hover:opacity-90 transition-all flex items-center justify-center gap-2">
                    <i class="fa-solid fa-paper-plane"></i> Submit Deposit Request
                </button>
            </form>
        </div>

    <?php elseif ($activeTab === 'withdraw'): ?>
        <!-- WITHDRAW MONEY FORM -->
        <div class="bg-brand-card border border-slate-800 rounded-3xl p-5 space-y-4 shadow-xl">
            <h3 class="font-display text-xl font-bold uppercase tracking-wide text-amber-400">Withdraw Winnings</h3>

            <form method="POST" action="wallet.php?tab=withdraw" class="space-y-4">
                <input type="hidden" name="action" value="withdraw_money">

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Select Payment Method</label>
                    <select name="payment_method" class="w-full bg-slate-950 border border-slate-800 focus:border-cyan-500 rounded-xl py-3 px-3 text-xs text-slate-100 focus:outline-none font-bold">
                        <option value="UPI">UPI ID (Google Pay / PhonePe / Paytm)</option>
                        <option value="Paytm Wallet">Paytm Wallet Mobile Number</option>
                        <option value="Bank Transfer">Bank Account Transfer</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">UPI ID or Phone / Account Details</label>
                    <input type="text" name="payment_details" required placeholder="e.g. username@upi or 9876543210" 
                        class="w-full bg-slate-950 border border-slate-800 focus:border-cyan-500 rounded-xl py-3 px-3 text-xs text-slate-100 focus:outline-none font-mono">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Withdrawal Amount (₹)</label>
                    <input type="number" name="amount" required min="<?= $minWithdrawal ?>" max="<?= (float)$currentUser['wallet_balance'] ?>" step="1" placeholder="Min ₹<?= $minWithdrawal ?> - Max <?= format_currency($currentUser['wallet_balance']) ?>" 
                        class="w-full bg-slate-950 border border-slate-800 focus:border-cyan-500 rounded-xl py-3 px-3 text-xs text-slate-100 font-bold focus:outline-none">
                </div>

                <div class="bg-slate-950 p-3 rounded-xl border border-slate-800 text-[11px] text-slate-400 space-y-1">
                    <div class="flex justify-between">
                        <span>Min Withdrawal Limit:</span>
                        <span class="font-bold text-slate-200"><?= format_currency($minWithdrawal) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span>Transfer Processing Time:</span>
                        <span class="font-bold text-cyan-400">Within 1-2 Hours</span>
                    </div>
                </div>

                <button type="submit" class="w-full btn-gradient-yellow text-slate-950 font-extrabold py-3.5 px-4 rounded-xl text-xs uppercase tracking-wider shadow-lg shadow-amber-500/20 hover:opacity-90 transition-all flex items-center justify-center gap-2">
                    <i class="fa-solid fa-money-bill-wave"></i> Request Instant Payout
                </button>
            </form>
        </div>

    <?php else: ?>
        <!-- TRANSACTION HISTORY LIST -->
        <div class="space-y-3">
            <h3 class="font-display text-xl font-bold uppercase tracking-wide text-slate-200">Recent Transactions</h3>

            <?php if (empty($transactions)): ?>
                <div class="text-center py-8 bg-slate-900/50 border border-slate-800 rounded-3xl p-4 text-xs text-slate-500">
                    No transaction records found.
                </div>
            <?php else: ?>
                <?php foreach ($transactions as $t): 
                    $isCredit = in_array($t['type'], ['deposit', 'winnings', 'admin_credit']);
                ?>
                    <div class="bg-brand-card border border-slate-800 rounded-2xl p-3.5 flex items-center justify-between shadow-md">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl flex items-center justify-center text-sm font-bold <?= $isCredit ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' : 'bg-rose-500/10 text-rose-400 border border-rose-500/30' ?>">
                                <i class="fa-solid <?= $isCredit ? 'fa-arrow-down-left' : 'fa-arrow-up-right' ?>"></i>
                            </div>
                            <div>
                                <span class="font-bold text-xs text-slate-200 block uppercase tracking-wide">
                                    <?= htmlspecialchars(str_replace('_', ' ', $t['type'])) ?>
                                </span>
                                <span class="text-[10px] text-slate-500 block">
                                    <?= date('d M, Y h:i A', strtotime($t['created_at'])) ?>
                                </span>
                                <?php if ($t['utr_reference']): ?>
                                    <span class="text-[10px] font-mono text-slate-400 block">
                                        <?= htmlspecialchars($t['utr_reference']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="text-right">
                            <span class="font-extrabold text-sm block <?= $isCredit ? 'text-emerald-400' : 'text-rose-400' ?>">
                                <?= $isCredit ? '+' : '-' ?><?= format_currency($t['amount']) ?>
                            </span>
                            <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-full inline-block mt-0.5 <?= $t['status'] === 'approved' ? 'bg-emerald-500/20 text-emerald-400' : ($t['status'] === 'rejected' ? 'bg-rose-500/20 text-rose-400' : 'bg-amber-500/20 text-amber-400') ?>">
                                <?= strtoupper($t['status']) ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/common/bottom.php'; ?>
