<?php
require_once __DIR__ . '/admin/common/header.php';

// Handle Add / Edit / Delete Tournament Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'create_tournament' || $action === 'edit_tournament') {
        $title = sanitize_input($_POST['title'] ?? '');
        $category = sanitize_input($_POST['category'] ?? 'BGMI');
        $bannerUrl = sanitize_input($_POST['banner_url'] ?? '');
        $entryFee = (float)($_POST['entry_fee'] ?? 0);
        $prizePool = (float)($_POST['prize_pool'] ?? 0);
        $perKill = (float)($_POST['per_kill_bonus'] ?? 0);
        $maxPlayers = (int)($_POST['max_players'] ?? 100);
        $matchTime = sanitize_input($_POST['match_time'] ?? '');
        $mapName = sanitize_input($_POST['map_name'] ?? 'Erangel');
        $gameMode = sanitize_input($_POST['game_mode'] ?? 'Solo');
        $roomId = sanitize_input($_POST['room_id'] ?? '');
        $roomPass = sanitize_input($_POST['room_password'] ?? '');
        $status = sanitize_input($_POST['status'] ?? 'upcoming');

        if ($action === 'create_tournament') {
            $stmt = $pdo->prepare("INSERT INTO tournaments (title, category, banner_url, entry_fee, prize_pool, per_kill_bonus, max_players, match_time, map_name, game_mode, room_id, room_password, status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$title, $category, $bannerUrl, $entryFee, $prizePool, $perKill, $maxPlayers, $matchTime, $mapName, $gameMode, $roomId, $roomPass, $status]);
            set_flash('success', 'New tournament created successfully!');
        } else {
            $tId = (int)($_POST['tournament_id'] ?? 0);
            $stmt = $pdo->prepare("UPDATE tournaments SET title = ?, category = ?, banner_url = ?, entry_fee = ?, prize_pool = ?, per_kill_bonus = ?, max_players = ?, match_time = ?, map_name = ?, game_mode = ?, room_id = ?, room_password = ?, status = ? WHERE id = ?");
            $stmt->execute([$title, $category, $bannerUrl, $entryFee, $prizePool, $perKill, $maxPlayers, $matchTime, $mapName, $gameMode, $roomId, $roomPass, $status, $tId]);
            set_flash('success', 'Tournament updated successfully!');
        }
        redirect('tournament.php');
    } elseif ($action === 'delete_tournament') {
        $tId = (int)($_POST['tournament_id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM tournaments WHERE id = ?");
        $stmt->execute([$tId]);
        set_flash('success', 'Tournament deleted.');
        redirect('tournament.php');
    }
}

// Fetch Tournaments List
$stmt = $pdo->query("SELECT t.*, (SELECT COUNT(*) FROM tournament_participants WHERE tournament_id = t.id) as joined_count FROM tournaments t ORDER BY id DESC");
$tournaments = $stmt->fetchAll();

$editItem = null;
if (isset($_GET['edit'])) {
    $eId = (int)$_GET['edit'];
    $eStmt = $pdo->prepare("SELECT * FROM tournaments WHERE id = ?");
    $eStmt->execute([$eId]);
    $editItem = $eStmt->fetch();
}
?>

<div class="px-4 py-4 space-y-5">
    
    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
        <div>
            <h1 class="font-display text-2xl font-bold uppercase tracking-wider text-amber-400">Manage Tournaments</h1>
            <p class="text-xs text-slate-400">Add, Edit & Publish Room Passwords</p>
        </div>
    </div>

    <!-- Create / Edit Form -->
    <div class="bg-brand-card border border-slate-800 rounded-3xl p-5 space-y-4 shadow-xl">
        <h3 class="font-display text-xl font-bold uppercase tracking-wide text-cyan-400">
            <?= $editItem ? 'Edit Tournament #' . $editItem['id'] : 'Create New Tournament' ?>
        </h3>

        <form method="POST" action="tournament.php" class="space-y-3 text-xs">
            <input type="hidden" name="action" value="<?= $editItem ? 'edit_tournament' : 'create_tournament' ?>">
            <?php if ($editItem): ?>
                <input type="hidden" name="tournament_id" value="<?= $editItem['id'] ?>">
            <?php endif; ?>

            <div>
                <label class="block font-bold text-slate-300 mb-1">Tournament Title</label>
                <input type="text" name="title" required value="<?= htmlspecialchars($editItem['title'] ?? '') ?>" placeholder="e.g. BGMI Erangel Championship #101" 
                    class="w-full bg-slate-950 border border-slate-800 focus:border-amber-500 rounded-xl py-2.5 px-3 text-slate-100 focus:outline-none">
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block font-bold text-slate-300 mb-1">Category</label>
                    <select name="category" class="w-full bg-slate-950 border border-slate-800 focus:border-amber-500 rounded-xl py-2.5 px-3 text-slate-100 focus:outline-none font-bold">
                        <?php foreach (['BGMI', 'Free Fire', 'Call of Duty', 'Ludo'] as $cat): ?>
                            <option value="<?= $cat ?>" <?= ($editItem['category'] ?? '') === $cat ? 'selected' : '' ?>><?= $cat ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-300 mb-1">Game Mode</label>
                    <select name="game_mode" class="w-full bg-slate-950 border border-slate-800 focus:border-amber-500 rounded-xl py-2.5 px-3 text-slate-100 focus:outline-none font-bold">
                        <?php foreach (['Solo', 'Duo', 'Squad', '1v1'] as $gm): ?>
                            <option value="<?= $gm ?>" <?= ($editItem['game_mode'] ?? '') === $gm ? 'selected' : '' ?>><?= $gm ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-2">
                <div>
                    <label class="block font-bold text-slate-300 mb-1">Entry Fee (₹)</label>
                    <input type="number" name="entry_fee" required step="1" value="<?= $editItem['entry_fee'] ?? '20' ?>" 
                        class="w-full bg-slate-950 border border-slate-800 focus:border-amber-500 rounded-xl py-2.5 px-2 text-slate-100 focus:outline-none font-bold">
                </div>
                <div>
                    <label class="block font-bold text-slate-300 mb-1">Prize Pool (₹)</label>
                    <input type="number" name="prize_pool" required step="1" value="<?= $editItem['prize_pool'] ?? '500' ?>" 
                        class="w-full bg-slate-950 border border-slate-800 focus:border-amber-500 rounded-xl py-2.5 px-2 text-slate-100 focus:outline-none font-bold">
                </div>
                <div>
                    <label class="block font-bold text-slate-300 mb-1">Per Kill (₹)</label>
                    <input type="number" name="per_kill_bonus" required step="1" value="<?= $editItem['per_kill_bonus'] ?? '10' ?>" 
                        class="w-full bg-slate-950 border border-slate-800 focus:border-amber-500 rounded-xl py-2.5 px-2 text-slate-100 focus:outline-none font-bold">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block font-bold text-slate-300 mb-1">Max Slots</label>
                    <input type="number" name="max_players" required value="<?= $editItem['max_players'] ?? '100' ?>" 
                        class="w-full bg-slate-950 border border-slate-800 focus:border-amber-500 rounded-xl py-2.5 px-3 text-slate-100 focus:outline-none">
                </div>
                <div>
                    <label class="block font-bold text-slate-300 mb-1">Map Name</label>
                    <input type="text" name="map_name" required value="<?= htmlspecialchars($editItem['map_name'] ?? 'Erangel') ?>" 
                        class="w-full bg-slate-950 border border-slate-800 focus:border-amber-500 rounded-xl py-2.5 px-3 text-slate-100 focus:outline-none">
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-300 mb-1">Match Date & Time String</label>
                <input type="text" name="match_time" required value="<?= htmlspecialchars($editItem['match_time'] ?? 'Today 8:00 PM') ?>" placeholder="e.g. Today 8:00 PM" 
                    class="w-full bg-slate-950 border border-slate-800 focus:border-amber-500 rounded-xl py-2.5 px-3 text-slate-100 focus:outline-none">
            </div>

            <div class="grid grid-cols-2 gap-2 bg-slate-950 p-3 rounded-2xl border border-slate-800">
                <div>
                    <label class="block font-bold text-amber-400 mb-1">Room ID</label>
                    <input type="text" name="room_id" value="<?= htmlspecialchars($editItem['room_id'] ?? '') ?>" placeholder="e.g. 8839210" 
                        class="w-full bg-slate-900 border border-slate-800 focus:border-amber-500 rounded-xl py-2 px-3 text-slate-100 font-mono focus:outline-none">
                </div>
                <div>
                    <label class="block font-bold text-amber-400 mb-1">Room Password</label>
                    <input type="text" name="room_password" value="<?= htmlspecialchars($editItem['room_password'] ?? '') ?>" placeholder="e.g. 1234" 
                        class="w-full bg-slate-900 border border-slate-800 focus:border-amber-500 rounded-xl py-2 px-3 text-slate-100 font-mono focus:outline-none">
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-300 mb-1">Match Status</label>
                <select name="status" class="w-full bg-slate-950 border border-slate-800 focus:border-amber-500 rounded-xl py-2.5 px-3 text-slate-100 focus:outline-none font-bold">
                    <option value="upcoming" <?= ($editItem['status'] ?? '') === 'upcoming' ? 'selected' : '' ?>>Upcoming</option>
                    <option value="live" <?= ($editItem['status'] ?? '') === 'live' ? 'selected' : '' ?>>Live Now</option>
                    <option value="completed" <?= ($editItem['status'] ?? '') === 'completed' ? 'selected' : '' ?>>Completed / Finished</option>
                </select>
            </div>

            <button type="submit" class="w-full btn-amber text-slate-950 font-extrabold py-3 px-4 rounded-xl text-xs uppercase tracking-wider shadow-lg shadow-amber-500/20">
                <i class="fa-solid fa-floppy-disk mr-1"></i> <?= $editItem ? 'Save Updates' : 'Create Tournament' ?>
            </button>
            <?php if ($editItem): ?>
                <a href="tournament.php" class="block text-center w-full bg-slate-800 text-slate-400 py-2 rounded-xl">Cancel Edit</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Existing Tournaments List -->
    <div class="space-y-3">
        <h3 class="font-display text-xl font-bold uppercase tracking-wide text-slate-200">All Matches</h3>

        <?php foreach ($tournaments as $t): ?>
            <div class="bg-brand-card border border-slate-800 rounded-2xl p-4 space-y-3 shadow-md">
                <div class="flex justify-between items-start">
                    <div>
                        <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-full bg-slate-950 border border-slate-800 text-cyan-400">
                            <?= htmlspecialchars($t['category']) ?>
                        </span>
                        <h4 class="font-display text-lg font-bold text-slate-100 mt-1"><?= htmlspecialchars($t['title']) ?></h4>
                        <div class="text-[11px] text-slate-400">
                            <span><?= $t['joined_count'] ?> / <?= $t['max_players'] ?> Players</span> | 
                            <span>Fee: <?= format_currency($t['entry_fee']) ?></span> | 
                            <span>Prize: <?= format_currency($t['prize_pool']) ?></span>
                        </div>
                    </div>
                    <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-full <?= $t['status'] === 'live' ? 'bg-rose-500/20 text-rose-400' : ($t['status'] === 'completed' ? 'bg-slate-800 text-slate-400' : 'bg-emerald-500/20 text-emerald-400') ?>">
                        <?= strtoupper($t['status']) ?>
                    </span>
                </div>

                <div class="grid grid-cols-3 gap-1.5 pt-1 border-t border-slate-800 text-[11px]">
                    <a href="manage_tournament.php?id=<?= $t['id'] ?>" class="bg-indigo-950 text-indigo-300 border border-indigo-500/30 py-2 rounded-xl text-center font-bold">
                        <i class="fa-solid fa-users-gear mr-1"></i> Players
                    </a>
                    <a href="tournament.php?edit=<?= $t['id'] ?>" class="bg-slate-800 text-cyan-400 py-2 rounded-xl text-center font-bold">
                        <i class="fa-solid fa-pen mr-1"></i> Edit
                    </a>
                    <form method="POST" action="tournament.php" onsubmit="return confirm('Delete tournament?');">
                        <input type="hidden" name="action" value="delete_tournament">
                        <input type="hidden" name="tournament_id" value="<?= $t['id'] ?>">
                        <button type="submit" class="w-full bg-rose-950/80 text-rose-400 border border-rose-500/30 py-2 rounded-xl font-bold">
                            <i class="fa-solid fa-trash mr-1"></i> Delete
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

</div>

<?php require_once __DIR__ . '/admin/common/bottom.php'; ?>
