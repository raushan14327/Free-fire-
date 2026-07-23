<?php
require_once __DIR__ . '/config.php';
$currentUser = get_logged_in_user();
$appName = get_setting('app_name', 'Adept Play');
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= htmlspecialchars($appName) ?> - Esports & Gaming Tournaments</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Teko:wght@500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: {
                            cyan: '#06b6d4',
                            yellow: '#f59e0b',
                            purple: '#8b5cf6',
                            emerald: '#10b981',
                            dark: '#090d16',
                            card: '#121826',
                            cardHover: '#1a2235'
                        }
                    },
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                        display: ['Teko', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        body {
            user-select: none !important;
            -webkit-user-select: none !important;
            -moz-user-select: none !important;
            -ms-user-select: none !important;
            touch-action: manipulation;
            background-color: #060911;
            color: #f3f4f6;
            font-family: 'Plus Jakarta Sans', sans-serif;
            overflow-x: hidden;
        }
        /* Mobile Frame Container for Desktop Preview */
        .mobile-container {
            max-width: 480px;
            margin: 0 auto;
            min-height: 100vh;
            background: #090d16;
            position: relative;
            box-shadow: 0 0 50px rgba(0,0,0,0.8);
            display: flex;
            flex-direction: column;
        }
        .text-glow-cyan {
            text-shadow: 0 0 12px rgba(6, 182, 212, 0.6);
        }
        .text-glow-yellow {
            text-shadow: 0 0 12px rgba(245, 158, 11, 0.6);
        }
        .btn-gradient {
            background: linear-gradient(135deg, #06b6d4 0%, #3b82f6 100%);
        }
        .btn-gradient-yellow {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }
        /* Hide scrollbars */
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>
    <script>
        // Security & User Restrictions: Disable Selection, Context Menu & Zoom
        document.addEventListener('contextmenu', function(e) {
            e.preventDefault();
        });
        document.addEventListener('selectstart', function(e) {
            e.preventDefault();
        });
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && (e.key === 'u' || e.key === 's' || e.key === 'i' || e.key === 'c' || e.key === '+' || e.key === '-')) {
                e.preventDefault();
            }
        });
        document.addEventListener('wheel', function(e) {
            if (e.ctrlKey) {
                e.preventDefault();
            }
        }, { passive: false });
        document.addEventListener('gesturestart', function(e) {
            e.preventDefault();
        });
    </script>
</head>
<body class="bg-slate-950 text-slate-100 antialiased selection:bg-cyan-500 selection:text-black">
<div class="mobile-container pb-20">
    <!-- Top Header Bar -->
    <header class="sticky top-0 z-40 bg-brand-dark/90 backdrop-blur-md border-b border-slate-800/80 px-4 py-3 flex items-center justify-between">
        <a href="index.php" class="flex items-center gap-2">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-cyan-500 via-indigo-500 to-amber-500 flex items-center justify-center shadow-lg shadow-cyan-500/20">
                <i class="fa-solid fa-gamepad text-slate-950 text-lg"></i>
            </div>
            <div>
                <span class="font-display text-2xl tracking-wider font-bold bg-gradient-to-r from-cyan-400 via-indigo-300 to-amber-400 bg-clip-text text-transparent uppercase">
                    <?= htmlspecialchars($appName) ?>
                </span>
            </div>
        </a>

        <!-- User Wallet Balance Header Chip -->
        <?php if ($currentUser): ?>
            <a href="wallet.php" class="flex items-center gap-2 bg-slate-900 border border-slate-800 hover:border-cyan-500/50 rounded-full px-3 py-1.5 transition-all">
                <i class="fa-solid fa-wallet text-amber-400 text-sm"></i>
                <span class="font-bold text-sm text-slate-100">
                    <?= format_currency($currentUser['wallet_balance']) ?>
                </span>
                <span class="w-5 h-5 rounded-full bg-cyan-500/20 text-cyan-400 flex items-center justify-center text-xs ml-0.5">
                    <i class="fa-solid fa-plus text-[10px]"></i>
                </span>
            </a>
        <?php else: ?>
            <a href="login.php" class="bg-cyan-500 hover:bg-cyan-400 text-slate-950 font-bold px-4 py-1.5 rounded-full text-xs uppercase tracking-wider flex items-center gap-1.5 transition-all shadow-md shadow-cyan-500/20">
                <i class="fa-solid fa-right-to-bracket"></i> Login
            </a>
        <?php endif; ?>
    </header>

    <!-- Global Alert Flash Banners -->
    <?php $flash = get_flash(); if ($flash): ?>
        <div class="mx-4 mt-3 p-3 rounded-xl text-sm font-semibold flex items-center justify-between border shadow-lg <?= $flash['type'] === 'success' ? 'bg-emerald-950/80 border-emerald-500/40 text-emerald-300' : 'bg-rose-950/80 border-rose-500/40 text-rose-300' ?>">
            <div class="flex items-center gap-2">
                <i class="fa-solid <?= $flash['type'] === 'success' ? 'fa-circle-check text-emerald-400' : 'fa-triangle-exclamation text-rose-400' ?>"></i>
                <span><?= htmlspecialchars($flash['message']) ?></span>
            </div>
        </div>
    <?php endif; ?>
