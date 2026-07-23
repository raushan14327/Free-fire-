<?php
require_once __DIR__ . '/common/config.php';

if (!is_logged_in()) {
    set_flash('danger', 'Please login to view your joined tournaments.');
    redirect('login.php');
}

$currentUser = get_logged_in_user();
$selectedStatus = sanitize_input($_GET['status'] ?? 'all');

// Query joined matches for current user
$sql = "SELECT tp.*, t.title, t.category, t.banner_url, t.map_name, t.game_mode, t.match_time, t.entry_fee, t.prize_pool, t.per_kill_bonus, t.room_id, t.room_password, t.status as match_status
        FROM tournament_participants tp
        JOIN tournaments t ON tp.tournament_id = t.id
        WHERE tp.user_id = ?";

if ($selectedStatus !== 'all') {
    $sql .= " AND t.status = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$currentUser['id'], $selectedStatus]);
} else {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$currentUser['id']]);
}

$myMatches = $stmt->fetchAll();

require_once __DIR__ . '/common/header.php';
?>

<div class="px-4 py-4 space-y-5">
    
    <!-- Title Header -->
    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
        <div>
            <h1 class="font-display text-2xl font-bold uppercase tracking-wider text-slate-100">My Joined Matches</h1>
            <p class="text-xs text-slate-400">View Room ID, Passwords & Winnings</p>
        </div>
        <div class="w-10 h-10 rounded-2xl bg-cyan-500/10 border border-cyan-500/30 flex items-center justify-center text-cyan-400 text-lg">
            <i class="fa-solid fa-trophy"></i>
        </div>
    </div>

    <!-- Status Filter Tabs -->
    <div class="grid grid-cols-4 gap-1 p-1 bg-slate-900 border border-slate-800 rounded-2xl text-center text-xs font-bold">
        <a href="my_tournaments.php?status=all" class="py-2 rounded-xl transition-all <?= $selectedStatus === 'all' ? 'bg-cyan-500 text-slate-950' : 'text-slate-400 hover:text-slate-200' ?>">All</a>
        <a href="my_tournaments.php?status=upcoming" class="py-2 rounded-xl transition-all <?= $selectedStatus === 'upcoming' ? 'bg-cyan-500 text-slate-950' : 'text-slate-400 hover:text-slate-200' ?>">Upcoming</a>
        <a href="my_tournaments.php?status=live" class="py-2 rounded-xl transition-all <?= $selectedStatus === 'live' ? 'bg-cyan-500 text-slate-950' : 'text-slate-400 hover:text-slate-200' ?>">Live</a>
        <a href="my_tournaments.php?status=completed" class="py-2 rounded-xl transition-all <?= $selectedStatus === 'completed' ? 'bg-cyan-500 text-slate-950' : 'text-slate-400 hover:text-slate-200' ?>">Finished</a>
    </div>

    <!-- Matches Feed -->
    <div class="space-y-4">
        <?php if (empty($myMatches)): ?>
            <div class="text-center py-12 bg-slate-900/50 border border-slate-800 rounded-3xl p-6 space-y-3">
                <div class="w-12 h-12 rounded-full bg-slate-800 flex items-center justify-center mx-auto text-slate-500 text-xl">
                    <i class="fa-solid fa-gamepad"></i>
                </div>
                <h4 class="text-sm font-bold text-slate-300">No Joined Matches</h4>
                <p class="text-xs text-slate-500">You haven't joined any <?= $selectedStatus !== 'all' ? $selectedStatus : '' ?> matches yet.</p>
                <a href="index.php" class="inline-block btn-gradient text-slate-950 font-bold px-4 py-2 rounded-xl text-xs uppercase tracking-wider">
                    Browse Matches
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($myMatches as $m): ?>
                <div class="bg-brand-card border border-slate-800 rounded-3xl p-4 shadow-xl space-y-3">
                    
                    <div class="flex items-center justify-between border-b border-slate-800/80 pb-2.5">
                        <div class="flex items-center gap-2">
                            <span class="bg-cyan-500/10 text-cyan-400 border border-cyan-500/30 text-[10px] font-extrabold px-2.5 py-0.5 rounded-full uppercase">
                                <?= htmlspecialchars($m['category']) ?>
                            </span>
                            <span class="text-xs font-bold text-slate-300"><?= htmlspecialchars($m['game_mode']) ?> | <?= htmlspecialchars($m['map_name']) ?></span>
                        </div>
                        <span class="text-[10px] font-extrabold px-2.5 py-0.5 rounded-full uppercase <?= $m['match_status'] === 'live' ? 'bg-rose-500/20 text-rose-400 border border-rose-500' : ($m['match_status'] === 'completed' ? 'bg-slate-800 text-slate-400' : 'bg-emerald-500/20 text-emerald-400 border border-emerald-500') ?>">
                            <?= strtoupper($m['match_status']) ?>
                        </span>
                    </div>

                    <div>
                        <h3 class="font-display text-xl font-bold uppercase tracking-wider text-slate-100">
                            <?= htmlspecialchars($m['title']) ?>
                        </h3>
                        <div class="text-xs text-slate-400 flex items-center gap-2 mt-0.5">
                            <i class="fa-regular fa-clock text-indigo-400"></i>
                            <span><?= htmlspecialchars($m['match_time']) ?></span>
                        </div>
                    </div>

                    <!-- Registered IGN info -->
                    <div class="bg-slate-950 p-2.5 rounded-2xl border border-slate-800/80 text-xs flex justify-between items-center text-slate-300">
                        <div>
                            <span class="text-slate-500 block text-[10px]">Your Registered IGN:</span>
                            <span class="font-bold text-slate-200"><?= htmlspecialchars($m['game_username']) ?> (ID: <?= htmlspecialchars($m['game_player_id']) ?>)</span>
                        </div>
                        <div class="text-right">
                            <span class="text-slate-500 block text-[10px]">Entry Fee Paid:</span>
                            <span class="font-bold text-emerald-400"><?= format_currency($m['entry_fee']) ?></span>
                        </div>
                    </div>

                    <!-- Room Credentials Box (if published) -->
                    <?php if (!empty($m['room_id'])): ?>
                        <div class="bg-gradient-to-r from-amber-950/60 via-slate-900 to-cyan-950/60 border border-amber-500/50 rounded-2xl p-3.5 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-amber-400 flex items-center gap-1.5">
                                    <i class="fa-solid fa-key text-amber-400"></i> Room ID & Password Available
                                </span>
                                <span class="text-[10px] bg-amber-500/20 text-amber-300 px-2 py-0.5 rounded-full font-bold">LIVE ACCESS</span>
                            </div>

                            <div class="grid grid-cols-2 gap-2 text-center pt-1">
                                <div class="bg-slate-950 p-2 rounded-xl border border-slate-800">
                                    <span class="text-[10px] text-slate-500 block uppercase font-bold">Room ID</span>
                                    <span class="text-sm font-mono font-bold text-cyan-400 select-all" id="roomId_<?= $m['id'] ?>"><?= htmlspecialchars($m['room_id']) ?></span>
                                </div>
                                <div class="bg-slate-950 p-2 rounded-xl border border-slate-800">
                                    <span class="text-[10px] text-slate-500 block uppercase font-bold">Password</span>
                                    <span class="text-sm font-mono font-bold text-amber-400 select-all" id="roomPass_<?= $m['id'] ?>"><?= htmlspecialchars($m['room_password']) ?></span>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="bg-slate-950/60 p-3 rounded-2xl border border-slate-800/60 text-center text-xs text-slate-400">
                            <i class="fa-solid fa-lock text-slate-500 mr-1"></i> Room ID & Password will be revealed 15 mins before match time.
                        </div>
                    <?php endif; ?>

                    <!-- Results Summary (if match completed) -->
                    <?php if ($m['match_status'] === 'completed'): ?>
                        <div class="bg-emerald-950/40 border border-emerald-500/30 rounded-2xl p-3 flex items-center justify-between text-xs">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center font-bold">
                                    #<?= $m['rank_achieved'] > 0 ? $m['rank_achieved'] : '-' ?>
                                </div>
                                <div>
                                    <span class="font-bold text-emerald-300 block">Match Completed</span>
                                    <span class="text-slate-400 text-[11px]"><?= $m['kills_count'] ?> Kills Recorded</span>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-[10px] text-slate-400 block">Prize Won</span>
                                <span class="font-extrabold text-amber-400 text-sm"><?= format_currency($m['prize_won']) ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/common/bottom.php'; ?>
