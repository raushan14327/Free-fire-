<?php
require_once __DIR__ . '/common/config.php';

$currentUser = get_logged_in_user();
$noticeText = get_setting('notice_text', '🔥 Join Tournaments Daily & Earn Wallet Money!');

// Filter parameters
$selectedCategory = sanitize_input($_GET['category'] ?? 'ALL');
$selectedStatus = sanitize_input($_GET['status'] ?? 'upcoming');

// Handle Tournament Join Request (Traditional PHP Form Submit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'join_tournament') {
    if (!is_logged_in()) {
        set_flash('danger', 'Please login to join tournaments.');
        redirect('login.php');
    }

    $tournamentId = (int)($_POST['tournament_id'] ?? 0);
    $gameUsername = sanitize_input($_POST['game_username'] ?? '');
    $gamePlayerId = sanitize_input($_POST['game_player_id'] ?? '');

    if (empty($gameUsername) || empty($gamePlayerId)) {
        set_flash('danger', 'Please enter your In-Game Username and Player ID.');
        redirect("index.php?category=$selectedCategory&status=$selectedStatus");
    }

    // Fetch tournament
    $stmt = $pdo->prepare("SELECT * FROM tournaments WHERE id = ?");
    $stmt->execute([$tournamentId]);
    $t = $stmt->fetch();

    if (!$t) {
        set_flash('danger', 'Invalid tournament selected.');
        redirect('index.php');
    }

    // Check if already joined
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tournament_participants WHERE tournament_id = ? AND user_id = ?");
    $stmt->execute([$tournamentId, $currentUser['id']]);
    if ($stmt->fetchColumn() > 0) {
        set_flash('danger', 'You have already joined this tournament!');
        redirect('my_tournaments.php');
    }

    // Check slots
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tournament_participants WHERE tournament_id = ?");
    $stmt->execute([$tournamentId]);
    $joinedCount = $stmt->fetchColumn();

    if ($joinedCount >= $t['max_players']) {
        set_flash('danger', 'Match is full! All slots occupied.');
        redirect("index.php?category=$selectedCategory&status=$selectedStatus");
    }

    // Check user wallet balance
    $entryFee = (float)$t['entry_fee'];
    if ((float)$currentUser['wallet_balance'] < $entryFee) {
        set_flash('danger', 'Insufficient wallet balance. Please add money to your wallet to join.');
        redirect('wallet.php');
    }

    // Process Join: Deduct wallet & record participant & transaction
    $pdo->beginTransaction();
    try {
        // Deduct wallet
        $newBalance = (float)$currentUser['wallet_balance'] - $entryFee;
        $uStmt = $pdo->prepare("UPDATE users SET wallet_balance = ? WHERE id = ?");
        $uStmt->execute([$newBalance, $currentUser['id']]);

        // Record participant
        $pStmt = $pdo->prepare("INSERT INTO tournament_participants (tournament_id, user_id, game_username, game_player_id, payment_status) VALUES (?, ?, ?, ?, 'success')");
        $pStmt->execute([$tournamentId, $currentUser['id'], $gameUsername, $gamePlayerId]);

        // Record transaction
        $trStmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, payment_method, status, remarks) VALUES (?, 'entry_fee', ?, 'Wallet', 'approved', ?)");
        $trStmt->execute([$currentUser['id'], $entryFee, "Entry fee for match #" . $t['id'] . ": " . $t['title']]);

        $pdo->commit();
        set_flash('success', 'Successfully joined tournament! Check "Matches" tab for Room ID.');
        redirect('my_tournaments.php');
    } catch (Exception $ex) {
        $pdo->rollBack();
        set_flash('danger', 'Failed to join tournament: ' . $ex->getMessage());
        redirect("index.php?category=$selectedCategory&status=$selectedStatus");
    }
}

// Fetch Tournaments Query
$sql = "SELECT t.*, 
        (SELECT COUNT(*) FROM tournament_participants WHERE tournament_id = t.id) as joined_count
        FROM tournaments t WHERE 1=1";
$params = [];

if ($selectedCategory !== 'ALL') {
    $sql .= " AND category = ?";
    $params[] = $selectedCategory;
}

if ($selectedStatus !== 'ALL') {
    $sql .= " AND status = ?";
    $params[] = $selectedStatus;
}

$sql .= " ORDER BY id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tournaments = $stmt->fetchAll();

// Get joined tournament IDs for current user
$userJoinedIds = [];
if ($currentUser) {
    $jStmt = $pdo->prepare("SELECT tournament_id FROM tournament_participants WHERE user_id = ?");
    $jStmt->execute([$currentUser['id']]);
    $userJoinedIds = $jStmt->fetchAll(PDO::FETCH_COLUMN);
}

require_once __DIR__ . '/common/header.php';
?>

<!-- Notice Ticker Banner -->
<?php if ($noticeText): ?>
<div class="bg-gradient-to-r from-cyan-950 via-slate-900 to-indigo-950 border-b border-cyan-500/30 px-4 py-2 flex items-center gap-2 text-xs font-semibold text-cyan-300">
    <span class="bg-cyan-500 text-slate-950 font-extrabold text-[10px] px-2 py-0.5 rounded-full uppercase tracking-wider animate-pulse flex-shrink-0">
        <i class="fa-solid fa-bullhorn"></i> NOTICE
    </span>
    <div class="overflow-hidden whitespace-nowrap w-full">
        <marquee scrollamount="4" class="text-xs"><?= htmlspecialchars($noticeText) ?></marquee>
    </div>
</div>
<?php endif; ?>

<div class="px-4 pt-4 pb-6 space-y-5">
    
    <!-- Hero Slider / Promo Card -->
    <div class="relative rounded-3xl overflow-hidden bg-slate-900 border border-slate-800 p-5 shadow-2xl">
        <div class="absolute inset-0 bg-gradient-to-r from-cyan-900/60 via-slate-900/80 to-indigo-900/60 z-0"></div>
        <div class="relative z-10 space-y-2">
            <span class="bg-amber-500/20 text-amber-400 border border-amber-500/40 text-[10px] font-bold px-2.5 py-0.5 rounded-full uppercase tracking-wider">
                <i class="fa-solid fa-fire mr-1"></i> Featured Tournaments
            </span>
            <h2 class="font-display text-3xl font-bold uppercase tracking-wide text-slate-100 leading-none">
                Play BGMI, Free Fire & Win Real Cash
            </h2>
            <p class="text-xs text-slate-300">Instant UPI & Paytm Wallet Withdrawals. Guaranteed Prize Pools!</p>
            <div class="pt-2 flex items-center gap-3 text-xs font-bold text-cyan-400">
                <span class="flex items-center gap-1"><i class="fa-solid fa-bolt"></i> 100% Fair Play</span>
                <span class="flex items-center gap-1"><i class="fa-solid fa-shield-halved"></i> Verified Rooms</span>
            </div>
        </div>
    </div>

    <!-- Game Categories Horizontal Scroll Chips -->
    <div>
        <div class="flex items-center justify-between mb-2">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Select Game Category</h3>
        </div>
        <div class="flex items-center gap-2 overflow-x-auto no-scrollbar pb-1">
            <?php 
            $categories = [
                'ALL' => ['label' => 'All Games', 'icon' => 'fa-gamepad'],
                'BGMI' => ['label' => 'BGMI / PUBG', 'icon' => 'fa-crosshairs'],
                'Free Fire' => ['label' => 'Free Fire', 'icon' => 'fa-fire'],
                'Call of Duty' => ['label' => 'COD Mobile', 'icon' => 'fa-gun'],
                'Ludo' => ['label' => 'Ludo King', 'icon' => 'fa-dice']
            ];
            foreach ($categories as $catKey => $catInfo):
                $isActive = $selectedCategory === $catKey;
            ?>
                <a href="index.php?category=<?= urlencode($catKey) ?>&status=<?= urlencode($selectedStatus) ?>" 
                   class="flex-shrink-0 flex items-center gap-2 px-3.5 py-2 rounded-2xl text-xs font-bold border transition-all <?= $isActive ? 'bg-cyan-500 text-slate-950 border-cyan-400 shadow-lg shadow-cyan-500/20' : 'bg-slate-900 border-slate-800 text-slate-400 hover:text-slate-200 hover:border-slate-700' ?>">
                    <i class="fa-solid <?= $catInfo['icon'] ?>"></i>
                    <span><?= $catInfo['label'] ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Match Status Tabs (Upcoming, Live, Completed) -->
    <div class="grid grid-cols-3 gap-1.5 p-1 bg-slate-900 border border-slate-800/80 rounded-2xl">
        <?php
        $statuses = [
            'upcoming' => ['label' => 'Upcoming', 'icon' => 'fa-clock'],
            'live' => ['label' => 'Live', 'icon' => 'fa-tower-broadcast'],
            'completed' => ['label' => 'Completed', 'icon' => 'fa-circle-check']
        ];
        foreach ($statuses as $stKey => $stInfo):
            $isActive = $selectedStatus === $stKey;
        ?>
            <a href="index.php?category=<?= urlencode($selectedCategory) ?>&status=<?= $stKey ?>" 
               class="py-2 rounded-xl text-center text-xs font-bold transition-all flex items-center justify-center gap-1.5 <?= $isActive ? 'bg-slate-800 text-cyan-400 border border-cyan-500/30 shadow-md' : 'text-slate-400 hover:text-slate-200' ?>">
                <i class="fa-solid <?= $stInfo['icon'] ?> text-[11px] <?= $stKey === 'live' && $isActive ? 'text-rose-400 animate-ping' : '' ?>"></i>
                <span><?= $stInfo['label'] ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Tournaments List -->
    <div class="space-y-4">
        <?php if (empty($tournaments)): ?>
            <div class="text-center py-12 bg-slate-900/50 border border-slate-800/60 rounded-3xl p-6 space-y-3">
                <div class="w-12 h-12 rounded-full bg-slate-800 flex items-center justify-center mx-auto text-slate-500 text-xl">
                    <i class="fa-solid fa-gamepad"></i>
                </div>
                <h4 class="text-sm font-bold text-slate-300">No Tournaments Found</h4>
                <p class="text-xs text-slate-500">There are no <?= strtolower($selectedStatus) ?> matches for <?= htmlspecialchars($selectedCategory) ?> right now.</p>
                <a href="index.php" class="inline-block bg-slate-800 text-cyan-400 text-xs font-bold px-4 py-2 rounded-xl border border-slate-700">
                    Reset Filters
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($tournaments as $t): 
                $slotsPercent = min(100, round(($t['joined_count'] / $t['max_players']) * 100));
                $isJoined = in_array($t['id'], $userJoinedIds);
                $isFull = $t['joined_count'] >= $t['max_players'];
            ?>
                <div class="bg-brand-card border border-slate-800 hover:border-slate-700 rounded-3xl overflow-hidden shadow-xl transition-all space-y-3">
                    
                    <!-- Card Top Banner/Image & Badges -->
                    <div class="relative h-32 bg-slate-900 overflow-hidden">
                        <img src="<?= htmlspecialchars($t['banner_url'] ?: 'https://images.unsplash.com/photo-1542751371-adc38448a05e?auto=format&fit=crop&w=600&q=80') ?>" 
                             alt="Game Banner" class="w-full h-full object-cover opacity-60">
                        <div class="absolute inset-0 bg-gradient-to-t from-brand-card via-transparent to-black/60"></div>
                        
                        <!-- Game Category Badge -->
                        <div class="absolute top-3 left-3 flex items-center gap-1.5 bg-slate-950/80 backdrop-blur-md border border-slate-800 px-3 py-1 rounded-full text-xs font-bold text-cyan-400">
                            <i class="fa-solid fa-headset"></i>
                            <span><?= htmlspecialchars($t['category']) ?></span>
                        </div>

                        <!-- Match Status Badge -->
                        <div class="absolute top-3 right-3 flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider <?= $t['status'] === 'live' ? 'bg-rose-500/20 border border-rose-500 text-rose-400 animate-pulse' : ($t['status'] === 'completed' ? 'bg-slate-800 text-slate-400' : 'bg-emerald-500/20 border border-emerald-500 text-emerald-400') ?>">
                            <i class="fa-solid <?= $t['status'] === 'live' ? 'fa-tower-broadcast' : ($t['status'] === 'completed' ? 'fa-check-circle' : 'fa-clock') ?>"></i>
                            <span><?= strtoupper($t['status']) ?></span>
                        </div>

                        <!-- Title Over Banner -->
                        <div class="absolute bottom-2 left-3 right-3">
                            <h3 class="font-display text-xl font-bold uppercase tracking-wider text-slate-100 drop-shadow-md">
                                <?= htmlspecialchars($t['title']) ?>
                            </h3>
                            <div class="flex items-center gap-3 text-[11px] text-slate-300 font-semibold">
                                <span><i class="fa-solid fa-map-location-dot text-amber-400 mr-1"></i> <?= htmlspecialchars($t['map_name']) ?></span>
                                <span><i class="fa-solid fa-users text-cyan-400 mr-1"></i> <?= htmlspecialchars($t['game_mode']) ?></span>
                                <span><i class="fa-regular fa-clock text-indigo-400 mr-1"></i> <?= htmlspecialchars($t['match_time']) ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Prize Metrics Grid -->
                    <div class="px-4 grid grid-cols-3 gap-2 text-center">
                        <div class="bg-slate-950/80 border border-slate-800/80 rounded-2xl p-2">
                            <span class="block text-[10px] uppercase font-bold text-slate-500">Prize Pool</span>
                            <span class="text-sm font-extrabold text-amber-400"><?= format_currency($t['prize_pool']) ?></span>
                        </div>
                        <div class="bg-slate-950/80 border border-slate-800/80 rounded-2xl p-2">
                            <span class="block text-[10px] uppercase font-bold text-slate-500">Per Kill</span>
                            <span class="text-sm font-extrabold text-cyan-400"><?= format_currency($t['per_kill_bonus']) ?></span>
                        </div>
                        <div class="bg-slate-950/80 border border-slate-800/80 rounded-2xl p-2">
                            <span class="block text-[10px] uppercase font-bold text-slate-500">Entry Fee</span>
                            <span class="text-sm font-extrabold text-emerald-400"><?= $t['entry_fee'] == 0 ? 'FREE' : format_currency($t['entry_fee']) ?></span>
                        </div>
                    </div>

                    <!-- Slots Progress Bar & Action Button -->
                    <div class="px-4 pb-4 space-y-3">
                        <div class="space-y-1">
                            <div class="flex justify-between text-[11px] font-bold text-slate-400">
                                <span>Spots Filled</span>
                                <span class="text-slate-200"><?= $t['joined_count'] ?> / <?= $t['max_players'] ?> Joined</span>
                            </div>
                            <div class="w-full bg-slate-950 h-2 rounded-full overflow-hidden border border-slate-800">
                                <div class="bg-gradient-to-r from-cyan-500 to-indigo-500 h-full rounded-full transition-all" style="width: <?= $slotsPercent ?>%"></div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <?php if ($isJoined): ?>
                            <a href="my_tournaments.php" class="w-full bg-slate-800 hover:bg-slate-700 text-emerald-400 border border-emerald-500/40 font-extrabold py-3 rounded-xl text-xs uppercase tracking-wider flex items-center justify-center gap-2 transition-all">
                                <i class="fa-solid fa-circle-check text-emerald-400"></i> Joined - View Room Details
                            </a>
                        <?php elseif ($t['status'] === 'upcoming'): ?>
                            <?php if ($isFull): ?>
                                <button disabled class="w-full bg-slate-800 text-slate-500 font-bold py-3 rounded-xl text-xs uppercase tracking-wider cursor-not-allowed border border-slate-700">
                                    <i class="fa-solid fa-lock mr-1"></i> Match Full
                                </button>
                            <?php else: ?>
                                <button onclick="openJoinModal(<?= $t['id'] ?>, '<?= htmlspecialchars(addslashes($t['title'])) ?>', '<?= $t['entry_fee'] ?>')" 
                                        class="w-full btn-gradient text-slate-950 font-extrabold py-3 rounded-xl text-xs uppercase tracking-wider flex items-center justify-center gap-2 shadow-lg shadow-cyan-500/20 hover:opacity-90 transition-all">
                                    <i class="fa-solid fa-right-to-bracket"></i> Join Match (<?= $t['entry_fee'] == 0 ? 'Free' : format_currency($t['entry_fee']) ?>)
                                </button>
                            <?php endif; ?>
                        <?php else: ?>
                            <a href="my_tournaments.php" class="w-full bg-slate-900 border border-slate-800 text-slate-400 font-bold py-3 rounded-xl text-xs uppercase tracking-wider flex items-center justify-center gap-2 transition-all">
                                <i class="fa-solid fa-eye"></i> View Match Status
                            </a>
                        <?php endif; ?>
                    </div>

                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Dialog for Joining Tournament -->
<div id="joinModal" class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-end sm:items-center justify-center p-0 sm:p-4 hidden">
    <div class="w-full max-w-md bg-brand-card border border-slate-800 rounded-t-3xl sm:rounded-3xl p-6 shadow-2xl space-y-4 animate-in slide-in-from-bottom duration-200">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <h3 class="font-display text-2xl font-bold uppercase tracking-wide text-cyan-400" id="modalTournamentTitle">Join Match</h3>
            <button onclick="closeJoinModal()" class="w-8 h-8 rounded-full bg-slate-800 text-slate-400 hover:text-slate-100 flex items-center justify-center">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="POST" action="index.php?category=<?= urlencode($selectedCategory) ?>&status=<?= urlencode($selectedStatus) ?>" class="space-y-4">
            <input type="hidden" name="action" value="join_tournament">
            <input type="hidden" name="tournament_id" id="modalTournamentId">

            <div class="bg-slate-950 p-3 rounded-2xl border border-slate-800 space-y-1.5 text-xs">
                <div class="flex justify-between text-slate-400">
                    <span>Entry Fee:</span>
                    <span class="font-bold text-emerald-400" id="modalEntryFee">₹0.00</span>
                </div>
                <div class="flex justify-between text-slate-400">
                    <span>Your Wallet Balance:</span>
                    <span class="font-bold text-amber-400"><?= $currentUser ? format_currency($currentUser['wallet_balance']) : '₹0.00' ?></span>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">In-Game Username (IGN)</label>
                <input type="text" name="game_username" required placeholder="Exact Game Username" value="<?= $currentUser ? htmlspecialchars($currentUser['name']) : '' ?>"
                    class="w-full bg-slate-950 border border-slate-800 focus:border-cyan-500 rounded-xl py-3 px-3 text-xs text-slate-100 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Game Player Character ID</label>
                <input type="text" name="game_player_id" required placeholder="Numeric/Character ID (e.g. 512938420)" value="<?= $currentUser ? htmlspecialchars($currentUser['game_id']) : '' ?>"
                    class="w-full bg-slate-950 border border-slate-800 focus:border-cyan-500 rounded-xl py-3 px-3 text-xs text-slate-100 focus:outline-none">
            </div>

            <div class="text-[11px] text-slate-400 leading-relaxed">
                <i class="fa-solid fa-circle-info text-cyan-400 mr-1"></i> Ensure game credentials are accurate. Room ID & Password will be published in your "Matches" tab before start time.
            </div>

            <button type="submit" class="w-full btn-gradient text-slate-950 font-extrabold py-3.5 px-4 rounded-xl text-xs uppercase tracking-wider shadow-lg shadow-cyan-500/20 hover:opacity-90 transition-all flex items-center justify-center gap-2">
                <i class="fa-solid fa-check-double"></i> Confirm & Pay Entry Fee
            </button>
        </form>
    </div>
</div>

<script>
function openJoinModal(id, title, fee) {
    <?php if (!$currentUser): ?>
        window.location.href = 'login.php';
        return;
    <?php endif; ?>
    document.getElementById('modalTournamentId').value = id;
    document.getElementById('modalTournamentTitle').innerText = 'Join ' + title;
    document.getElementById('modalEntryFee').innerText = fee == 0 ? 'FREE' : '₹' + parseFloat(fee).toFixed(2);
    document.getElementById('joinModal').classList.remove('hidden');
}

function closeJoinModal() {
    document.getElementById('joinModal').classList.add('hidden');
}
</script>

<?php require_once __DIR__ . '/common/bottom.php'; ?>
