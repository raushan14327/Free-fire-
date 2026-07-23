<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
    <!-- Fixed Bottom Navigation Bar -->
    <nav class="fixed bottom-0 left-0 right-0 z-50 bg-brand-dark/95 backdrop-blur-lg border-t border-slate-800/80 px-2 py-2">
        <div class="max-w-[480px] mx-auto flex items-center justify-around">
            
            <!-- Home Tab -->
            <a href="index.php" class="flex flex-col items-center gap-1 py-1 px-3 rounded-2xl transition-all duration-200 <?= $currentPage === 'index.php' ? 'text-cyan-400 font-bold scale-105' : 'text-slate-400 hover:text-slate-200' ?>">
                <div class="relative">
                    <i class="fa-solid fa-house text-lg"></i>
                    <?php if ($currentPage === 'index.php'): ?>
                        <span class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-1.5 h-1.5 bg-cyan-400 rounded-full shadow-sm shadow-cyan-400"></span>
                    <?php endif; ?>
                </div>
                <span class="text-[11px] tracking-wide">Home</span>
            </a>

            <!-- My Tournaments Tab -->
            <a href="my_tournaments.php" class="flex flex-col items-center gap-1 py-1 px-3 rounded-2xl transition-all duration-200 <?= $currentPage === 'my_tournaments.php' ? 'text-cyan-400 font-bold scale-105' : 'text-slate-400 hover:text-slate-200' ?>">
                <div class="relative">
                    <i class="fa-solid fa-trophy text-lg"></i>
                    <?php if ($currentPage === 'my_tournaments.php'): ?>
                        <span class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-1.5 h-1.5 bg-cyan-400 rounded-full shadow-sm shadow-cyan-400"></span>
                    <?php endif; ?>
                </div>
                <span class="text-[11px] tracking-wide">Matches</span>
            </a>

            <!-- Wallet Tab -->
            <a href="wallet.php" class="flex flex-col items-center gap-1 py-1 px-3 rounded-2xl transition-all duration-200 <?= $currentPage === 'wallet.php' ? 'text-cyan-400 font-bold scale-105' : 'text-slate-400 hover:text-slate-200' ?>">
                <div class="relative">
                    <i class="fa-solid fa-wallet text-lg"></i>
                    <?php if ($currentPage === 'wallet.php'): ?>
                        <span class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-1.5 h-1.5 bg-cyan-400 rounded-full shadow-sm shadow-cyan-400"></span>
                    <?php endif; ?>
                </div>
                <span class="text-[11px] tracking-wide">Wallet</span>
            </a>

            <!-- Profile Tab -->
            <a href="profile.php" class="flex flex-col items-center gap-1 py-1 px-3 rounded-2xl transition-all duration-200 <?= $currentPage === 'profile.php' ? 'text-cyan-400 font-bold scale-105' : 'text-slate-400 hover:text-slate-200' ?>">
                <div class="relative">
                    <i class="fa-solid fa-user text-lg"></i>
                    <?php if ($currentPage === 'profile.php'): ?>
                        <span class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-1.5 h-1.5 bg-cyan-400 rounded-full shadow-sm shadow-cyan-400"></span>
                    <?php endif; ?>
                </div>
                <span class="text-[11px] tracking-wide">Profile</span>
            </a>

        </div>
    </nav>
</div> <!-- End mobile-container -->
</body>
</html>
