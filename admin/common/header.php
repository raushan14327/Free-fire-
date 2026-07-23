<?php
require_once __DIR__ . '/../../common/config.php';

$currentPage = basename($_SERVER['PHP_SELF']);

if ($currentPage !== 'login.php' && !is_admin_logged_in()) {
    set_flash('danger', 'Admin access required.');
    redirect('login.php');
}

$appName = get_setting('app_name', 'i Free Fire');
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Admin Dashboard - <?= htmlspecialchars($appName) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Teko:wght@500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        body {
            background-color: #050811;
            color: #f3f4f6;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .admin-container {
            max-width: 480px;
            margin: 0 auto;
            min-height: 100vh;
            background: #090d16;
            position: relative;
            box-shadow: 0 0 50px rgba(0,0,0,0.9);
            display: flex;
            flex-direction: column;
        }
        .btn-amber {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 antialiased">
<div class="admin-container pb-20">
    
    <!-- Admin Top Header -->
    <header class="sticky top-0 z-40 bg-slate-900/90 backdrop-blur-md border-b border-amber-500/30 px-4 py-3 flex items-center justify-between">
        <a href="index.php" class="flex items-center gap-2.5">
            <img src="../assets/logo.jpg" alt="i Free Fire Logo" class="w-8 h-8 rounded-xl object-cover ring-2 ring-amber-500/50 shadow-md shadow-amber-500/20" onerror="this.src='https://images.unsplash.com/photo-1542751371-adc38448a05e?auto=format&fit=crop&w=100&q=80'">
            <div>
                <span class="font-display text-2xl font-bold uppercase tracking-wider text-amber-400">
                    i Free Fire Admin
                </span>
            </div>
        </a>

        <?php if (is_admin_logged_in()): ?>
            <a href="../index.php" class="bg-slate-800 hover:bg-slate-700 text-cyan-400 text-xs font-bold px-3 py-1.5 rounded-full border border-slate-700 flex items-center gap-1 transition-all">
                <i class="fa-solid fa-mobile-screen"></i> User View
            </a>
        <?php endif; ?>
    </header>

    <!-- Admin Alert Flash Messages -->
    <?php $flash = get_flash(); if ($flash): ?>
        <div class="mx-4 mt-3 p-3 rounded-xl text-xs font-semibold flex items-center gap-2 border <?= $flash['type'] === 'success' ? 'bg-emerald-950/80 border-emerald-500/50 text-emerald-300' : 'bg-rose-950/80 border-rose-500/50 text-rose-300' ?>">
            <i class="fa-solid <?= $flash['type'] === 'success' ? 'fa-circle-check text-emerald-400' : 'fa-triangle-exclamation text-rose-400' ?>"></i>
            <span><?= htmlspecialchars($flash['message']) ?></span>
        </div>
    <?php endif; ?>
