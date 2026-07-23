<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
    <!-- Admin Fixed Bottom Navigation -->
    <nav class="fixed bottom-0 left-0 right-0 z-50 bg-slate-950/95 backdrop-blur-lg border-t border-amber-500/30 px-2 py-2">
        <div class="max-w-[480px] mx-auto flex items-center justify-around">
            
            <!-- Dashboard Tab -->
            <a href="index.php" class="flex flex-col items-center gap-1 py-1 px-3 rounded-2xl transition-all <?= $currentPage === 'index.php' ? 'text-amber-400 font-bold scale-105' : 'text-slate-400 hover:text-slate-200' ?>">
                <i class="fa-solid fa-chart-line text-lg"></i>
                <span class="text-[10px] uppercase font-bold tracking-wider">Dash</span>
            </a>

            <!-- Tournaments Tab -->
            <a href="tournament.php" class="flex flex-col items-center gap-1 py-1 px-3 rounded-2xl transition-all <?= ($currentPage === 'tournament.php' || $currentPage === 'manage_tournament.php') ? 'text-amber-400 font-bold scale-105' : 'text-slate-400 hover:text-slate-200' ?>">
                <i class="fa-solid fa-trophy text-lg"></i>
                <span class="text-[10px] uppercase font-bold tracking-wider">Matches</span>
            </a>

            <!-- Users Tab -->
            <a href="user.php" class="flex flex-col items-center gap-1 py-1 px-3 rounded-2xl transition-all <?= $currentPage === 'user.php' ? 'text-amber-400 font-bold scale-105' : 'text-slate-400 hover:text-slate-200' ?>">
                <i class="fa-solid fa-users text-lg"></i>
                <span class="text-[10px] uppercase font-bold tracking-wider">Users</span>
            </a>

            <!-- Settings Tab -->
            <a href="setting.php" class="flex flex-col items-center gap-1 py-1 px-3 rounded-2xl transition-all <?= $currentPage === 'setting.php' ? 'text-amber-400 font-bold scale-105' : 'text-slate-400 hover:text-slate-200' ?>">
                <i class="fa-solid fa-gears text-lg"></i>
                <span class="text-[10px] uppercase font-bold tracking-wider">Setting</span>
            </a>

        </div>
    </nav>
</div> <!-- End admin-container -->
</body>
</html>
