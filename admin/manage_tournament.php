<?php
require_once __DIR__ . '/admin/common/header.php';

$tournamentId = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM tournaments WHERE id = ?");
$stmt->execute([$tournamentId]);
$tournament = $stmt->fetch();

if (!$tournament) {
    set_flash('danger', 'Tournament not found.');
    redirect('tournament.php');
}

// Handle Room Credentials Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_room') {
    $roomId = sanitize_input($_POST['room_id'] ?? '');
    $roomPass = sanitize_input($_POST['room_password'] ?? '');
    $status = sanitize_input($_POST['status'] ?? 'live');

    $uStmt = $pdo->prepare("UPDATE tournaments SET room_id = ?, room_password = ?, status = ? WHERE id = ?");
    $uStmt->execute([$roomId, $roomPass, $status, $tournamentId]);
    set_flash('success', 'Room ID & Password published to joined players!');
    redirect("manage_tournament.php?id=$tournamentId");
}

// Handle Declare Results & Winnings Payout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'declare_result') {
    $partId = (int)($_POST['participant_id'] ?? 0);
    $rank = (int)($_POST['rank_achieved'] ?? 0);
    $kills = (int)($_POST['kills_count'] ?? 0);
    $prizeWon = (float)($_POST['prize_won'] ?? 0);

    // Fetch participant
    $pStmt = $pdo->prepare("SELECT * FROM tournament_participants WHERE id = ?");
    $pStmt->execute([$partId]);
    $participant = $pStmt->fetch();

    if ($participant) {
        $pdo->beginTransaction();
        try {
            // Update participant rank, kills & prize
            $uStmt = $pdo->prepare("UPDATE tournament_participants SET rank_achieved = ?, kills_count = ?, prize_won = ? WHERE id = ?");
            $uStmt->execute([$rank, $kills, $prizeWon, $partId]);

            // If prizeWon > 0, credit user wallet
            if ($prizeWon > 0) {
                $wStmt = $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
                $wStmt->execute([$prizeWon, $participant['user_id']]);

                // Record transaction
                $tStmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, payment_method, status, remarks) VALUES (?, 'winnings', ?, 'System Credit', 'approved', ?)");
                $tStmt->execute([$participant['user_id'], $prizeWon, "Prize winnings for match #" . $tournament['id'] . " (Rank #" . $rank . ", Kills: " . $kills . ")"]);
            }

            $pdo->commit();
            set_flash('success', 'Results saved and winnings credited to player wallet!');
        } catch (Exception $e) {
            $pdo->rollBack();
            set_flash('danger', 'Error declaring result.');
        }
    }
    redirect("manage_tournament.php?id=$tournamentId");
}

// Fetch Participants List
$pStmt = $pdo->prepare("SELECT tp.*, u.name as user_name, u.phone as user_phone FROM tournament_participants tp JOIN users u ON tp.user_id = u.id WHERE tp.tournament_id = ? ORDER BY tp.id ASC");
$pStmt->execute([$tournamentId]);
$participants = $pStmt->fetchAll();
?>

<div class="px-4 py-4 space-y-5">
    
    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
        <div>
            <a href="tournament.php" class="text-xs text-amber-400 font-bold hover:underline mb-1 inline-block">
                <i class="fa-solid fa-arrow-left"></i> Back to Tournaments
            </a>
            <h1 class="font-display text-2xl font-bold uppercase tracking-wider text-slate-100">
                <?= htmlspecialchars($tournament['title']) ?>
            </h1>
            <p class="text-xs text-slate-400">Manage Room & Player Winnings</p>
        </div>
    </div>

    <!-- Room Credentials Publisher Form -->
    <div class="bg-brand-card border border-amber-500/30 rounded-3xl p-5 space-y-3 shadow-xl">
        <h3 class="font-display text-xl font-bold uppercase tracking-wide text-amber-400">Publish Room Credentials</h3>

        <form method="POST" action="manage_tournament.php?id=<?= $tournamentId ?>" class="space-y-3 text-xs">
            <input type="hidden" name="action" value="update_room">

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block font-bold text-slate-300 mb-1">Room ID</label>
                    <input type="text" name="room_id" required value="<?= htmlspecialchars($tournament['room_id']) ?>" placeholder="e.g. 582019" 
                        class="w-full bg-slate-950 border border-slate-800 focus:border-amber-500 rounded-xl py-2.5 px-3 font-mono text-cyan-400 font-bold focus:outline-none">
                </div>
                <div>
                    <label class="block font-bold text-slate-300 mb-1">Room Password</label>
                    <input type="text" name="room_password" required value="<?= htmlspecialchars($tournament['room_password']) ?>" placeholder="e.g. 1234" 
                        class="w-full bg-slate-950 border border-slate-800 focus:border-amber-500 rounded-xl py-2.5 px-3 font-mono text-amber-400 font-bold focus:outline-none">
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-300 mb-1">Match Status</label>
                <select name="status" class="w-full bg-slate-950 border border-slate-800 focus:border-amber-500 rounded-xl py-2.5 px-3 text-slate-100 focus:outline-none font-bold">
                    <option value="upcoming" <?= $tournament['status'] === 'upcoming' ? 'selected' : '' ?>>Upcoming</option>
                    <option value="live" <?= $tournament['status'] === 'live' ? 'selected' : '' ?>>Live Now</option>
                    <option value="completed" <?= $tournament['status'] === 'completed' ? 'selected' : '' ?>>Completed / Finished</option>
                </select>
            </div>

            <button type="submit" class="w-full btn-amber text-slate-950 font-extrabold py-3 px-4 rounded-xl text-xs uppercase tracking-wider shadow-lg shadow-amber-500/20">
                <i class="fa-solid fa-bullhorn mr-1"></i> Send Credentials to Joined Players
            </button>
        </form>
    </div>

    <!-- Joined Participants & Result Declaration -->
    <div class="space-y-3">
        <h3 class="font-display text-xl font-bold uppercase tracking-wide text-slate-200 flex justify-between items-center">
            <span>Joined Players (<?= count($participants) ?>)</span>
            <span class="text-xs text-amber-400 font-sans font-bold">Slots: <?= count($participants) ?>/<?= $tournament['max_players'] ?></span>
        </h3>

        <?php if (empty($participants)): ?>
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 text-center text-xs text-slate-500">
                No players have joined this match yet.
            </div>
        <?php else: ?>
            <?php foreach ($participants as $idx => $p): ?>
                <div class="bg-brand-card border border-slate-800 rounded-2xl p-4 space-y-3 shadow-md">
                    <div class="flex justify-between items-start border-b border-slate-800/80 pb-2">
                        <div>
                            <span class="text-[10px] font-extrabold text-amber-400 uppercase">Slot #<?= $idx + 1 ?></span>
                            <h4 class="font-bold text-slate-100 text-sm"><?= htmlspecialchars($p['game_username']) ?></h4>
                            <span class="text-[11px] text-slate-400 block font-mono">Game Character ID: <?= htmlspecialchars($p['game_player_id']) ?></span>
                            <span class="text-[10px] text-slate-500 block">User: <?= htmlspecialchars($p['user_name']) ?> (<?= htmlspecialchars($p['user_phone']) ?>)</span>
                        </div>
                        <span class="text-xs font-extrabold text-emerald-400 bg-emerald-500/10 border border-emerald-500/30 px-2 py-0.5 rounded-full">
                            PAID
                        </span>
                    </div>

                    <!-- Result Form -->
                    <form method="POST" action="manage_tournament.php?id=<?= $tournamentId ?>" class="bg-slate-950 p-3 rounded-xl border border-slate-800 space-y-2 text-xs">
                        <input type="hidden" name="action" value="declare_result">
                        <input type="hidden" name="participant_id" value="<?= $p['id'] ?>">

                        <div class="font-bold text-slate-300 text-[11px]">Declare Match Results:</div>
                        <div class="grid grid-cols-3 gap-2">
                            <div>
                                <label class="block text-[10px] text-slate-500 mb-0.5">Rank #</label>
                                <input type="number" name="rank_achieved" value="<?= $p['rank_achieved'] ?: '1' ?>" 
                                    class="w-full bg-slate-900 border border-slate-800 rounded-lg p-2 text-slate-100 font-bold focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-[10px] text-slate-500 mb-0.5">Kills</label>
                                <input type="number" name="kills_count" value="<?= $p['kills_count'] ?: '0' ?>" 
                                    class="w-full bg-slate-900 border border-slate-800 rounded-lg p-2 text-slate-100 font-bold focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-[10px] text-slate-500 mb-0.5">Prize (₹)</label>
                                <input type="number" name="prize_won" step="1" value="<?= $p['prize_won'] ?: '0' ?>" 
                                    class="w-full bg-slate-900 border border-slate-800 rounded-lg p-2 text-amber-400 font-extrabold focus:outline-none">
                            </div>
                        </div>

                        <button type="submit" class="w-full bg-cyan-600 hover:bg-cyan-500 text-slate-950 font-extrabold py-2 rounded-lg text-xs uppercase tracking-wider transition-all">
                            Save Results & Pay Winnings
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

<?php require_once __DIR__ . '/admin/common/bottom.php'; ?>
