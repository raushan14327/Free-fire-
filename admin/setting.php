<?php
require_once __DIR__ . '/admin/common/header.php';

// Handle Save Settings Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_settings') {
    $settingsToUpdate = [
        'app_name' => sanitize_input($_POST['app_name'] ?? 'i Free Fire'),
        'support_phone' => sanitize_input($_POST['support_phone'] ?? ''),
        'support_email' => sanitize_input($_POST['support_email'] ?? ''),
        'min_deposit' => (float)($_POST['min_deposit'] ?? 10),
        'min_withdrawal' => (float)($_POST['min_withdrawal'] ?? 50),
        'upi_id' => sanitize_input($_POST['upi_id'] ?? ''),
        'qr_code_url' => sanitize_input($_POST['qr_code_url'] ?? ''),
        'payment_instructions' => sanitize_input($_POST['payment_instructions'] ?? ''),
        'notice_text' => sanitize_input($_POST['notice_text'] ?? '')
    ];

    foreach ($settingsToUpdate as $key => $val) {
        $stmt = $pdo->prepare("INSERT OR REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?)");
        $stmt->execute([$key, $val]);
    }

    set_flash('success', 'Application settings saved successfully!');
    redirect('setting.php');
}

$appName = get_setting('app_name', 'i Free Fire');
$supportPhone = get_setting('support_phone', '+91 98765 43210');
$supportEmail = get_setting('support_email', 'support@ifreefire.com');
$minDeposit = get_setting('min_deposit', '10');
$minWithdrawal = get_setting('min_withdrawal', '50');
$upiId = get_setting('upi_id', 'ifreefire@upi');
$qrCodeUrl = get_setting('qr_code_url', 'https://images.unsplash.com/photo-1628155930542-3c7a64e2c833?auto=format&fit=crop&w=400&q=80');
$paymentInstructions = get_setting('payment_instructions', 'Pay via UPI or scan QR code. Submit 12-digit UTR reference ID.');
$noticeText = get_setting('notice_text', '🔥 Join Free Fire Max Tournaments Daily & Win Real Cash Rewards!');
?>

<div class="px-4 py-4 space-y-5">
    
    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
        <div>
            <h1 class="font-display text-2xl font-bold uppercase tracking-wider text-amber-400">System Settings</h1>
            <p class="text-xs text-slate-400">Payment Gateways, Limits & Announcements</p>
        </div>
    </div>

    <div class="bg-brand-card border border-slate-800 rounded-3xl p-5 space-y-4 shadow-xl">
        <form method="POST" action="setting.php" class="space-y-4 text-xs">
            <input type="hidden" name="action" value="save_settings">

            <div class="space-y-3">
                <h3 class="font-display text-lg font-bold uppercase text-cyan-400 border-b border-slate-800 pb-1">General App Details</h3>

                <div>
                    <label class="block font-bold text-slate-300 mb-1">App Name</label>
                    <input type="text" name="app_name" required value="<?= htmlspecialchars($appName) ?>" 
                        class="w-full bg-slate-950 border border-slate-800 focus:border-amber-500 rounded-xl py-2.5 px-3 text-slate-100 focus:outline-none font-bold">
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block font-bold text-slate-300 mb-1">Support Phone / WhatsApp</label>
                        <input type="text" name="support_phone" value="<?= htmlspecialchars($supportPhone) ?>" 
                            class="w-full bg-slate-950 border border-slate-800 focus:border-amber-500 rounded-xl py-2.5 px-3 text-slate-100 focus:outline-none">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-300 mb-1">Support Email</label>
                        <input type="email" name="support_email" value="<?= htmlspecialchars($supportEmail) ?>" 
                            class="w-full bg-slate-950 border border-slate-800 focus:border-amber-500 rounded-xl py-2.5 px-3 text-slate-100 focus:outline-none">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-300 mb-1">Announcement Notice Bar Text</label>
                    <input type="text" name="notice_text" value="<?= htmlspecialchars($noticeText) ?>" placeholder="Ticker banner text" 
                        class="w-full bg-slate-950 border border-slate-800 focus:border-amber-500 rounded-xl py-2.5 px-3 text-slate-100 focus:outline-none">
                </div>
            </div>

            <div class="space-y-3 pt-2">
                <h3 class="font-display text-lg font-bold uppercase text-amber-400 border-b border-slate-800 pb-1">Payment & Wallet Limits</h3>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block font-bold text-slate-300 mb-1">Min Deposit (₹)</label>
                        <input type="number" name="min_deposit" value="<?= $minDeposit ?>" 
                            class="w-full bg-slate-950 border border-slate-800 focus:border-amber-500 rounded-xl py-2.5 px-3 text-slate-100 font-bold focus:outline-none">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-300 mb-1">Min Withdrawal (₹)</label>
                        <input type="number" name="min_withdrawal" value="<?= $minWithdrawal ?>" 
                            class="w-full bg-slate-950 border border-slate-800 focus:border-amber-500 rounded-xl py-2.5 px-3 text-slate-100 font-bold focus:outline-none">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-300 mb-1">UPI VPA ID for Deposits</label>
                    <input type="text" name="upi_id" value="<?= htmlspecialchars($upiId) ?>" placeholder="e.g. merchant@upi" 
                        class="w-full bg-slate-950 border border-slate-800 focus:border-amber-500 rounded-xl py-2.5 px-3 font-mono text-cyan-400 font-bold focus:outline-none">
                </div>

                <div>
                    <label class="block font-bold text-slate-300 mb-1">Payment QR Code Image URL</label>
                    <input type="text" name="qr_code_url" value="<?= htmlspecialchars($qrCodeUrl) ?>" placeholder="https://..." 
                        class="w-full bg-slate-950 border border-slate-800 focus:border-amber-500 rounded-xl py-2.5 px-3 text-slate-100 focus:outline-none text-[11px]">
                </div>

                <div>
                    <label class="block font-bold text-slate-300 mb-1">Payment Instructions</label>
                    <textarea name="payment_instructions" rows="2" 
                        class="w-full bg-slate-950 border border-slate-800 focus:border-amber-500 rounded-xl p-2.5 text-slate-100 focus:outline-none text-xs"><?= htmlspecialchars($paymentInstructions) ?></textarea>
                </div>
            </div>

            <button type="submit" class="w-full btn-amber text-slate-950 font-extrabold py-3.5 px-4 rounded-xl text-xs uppercase tracking-wider shadow-lg shadow-amber-500/20">
                <i class="fa-solid fa-floppy-disk mr-1"></i> Save Configuration
            </button>
        </form>
    </div>

</div>

<?php require_once __DIR__ . '/admin/common/bottom.php'; ?>
