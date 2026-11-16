<?php

declare(strict_types=1);

use App\Helpers\Security;

$accounts = $accounts ?? [];

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#090f1c">
    <title>Game Accounts | xinClash</title>
    <link rel="stylesheet" href="<?= Security::escape($baseUrl) ?>/assets/css/output.css">
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 antialiased">
<main class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
    <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
        <div>
            <a href="<?= Security::escape($baseUrl) ?>/dashboard" class="text-sm font-medium text-emerald-400 hover:text-emerald-300">← Dashboard</a>
            <h1 class="mt-3 text-3xl font-black tracking-tight">Game accounts</h1>
            <p class="mt-2 text-sm text-slate-400">Connect and manage your Clash of Clans player profiles.</p>
        </div>
        <a href="<?= Security::escape($baseUrl) ?>/accounts/add" class="inline-flex items-center rounded-xl bg-emerald-400 px-4 py-3 text-sm font-bold text-slate-950 transition hover:bg-emerald-300">Connect account</a>
    </div>

    <?php if (!empty($success)): ?>
        <div class="mb-5 rounded-xl border border-emerald-400/20 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-200"><?= Security::escape($success) ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="mb-5 rounded-xl border border-red-400/20 bg-red-400/10 px-4 py-3 text-sm text-red-200"><?= Security::escape($error) ?></div>
    <?php endif; ?>

    <?php if ($accounts === []): ?>
        <section class="rounded-2xl border border-dashed border-slate-700 bg-slate-900/60 px-6 py-14 text-center">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-400/10 text-3xl">⚔</div>
            <h2 class="mt-5 text-lg font-bold">No player accounts connected</h2>
            <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-400">Connect your player tag and verify ownership with the API token from your in-game settings.</p>
            <a href="<?= Security::escape($baseUrl) ?>/accounts/add" class="mt-6 inline-flex rounded-xl bg-emerald-400 px-5 py-3 text-sm font-bold text-slate-950 hover:bg-emerald-300">Connect your first account</a>
        </section>
    <?php else: ?>
        <div class="grid gap-4 sm:grid-cols-2">
            <?php foreach ($accounts as $account): ?>
                <a href="<?= Security::escape($baseUrl) ?>/accounts/show?id=<?= (int) $account['id'] ?>" class="group rounded-2xl border border-white/10 bg-slate-900/70 p-5 transition hover:border-emerald-400/30 hover:bg-slate-900">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-400/10 text-2xl">🏰</div>
                        <span class="rounded-full bg-emerald-400/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-emerald-300">Verified</span>
                    </div>
                    <h2 class="mt-5 text-lg font-bold text-white group-hover:text-emerald-300"><?= Security::escape($account['player_name'] ?: 'Clash of Clans Player') ?></h2>
                    <p class="mt-1 font-mono text-xs text-slate-500"><?= Security::escape($account['player_tag']) ?></p>
                    <div class="mt-5 grid grid-cols-3 gap-3 border-t border-white/5 pt-4">
                        <div><p class="text-[10px] uppercase tracking-wider text-slate-500">Town Hall</p><p class="mt-1 text-sm font-bold"><?= $account['town_hall_level'] !== null ? (int) $account['town_hall_level'] : '—' ?></p></div>
                        <div><p class="text-[10px] uppercase tracking-wider text-slate-500">Level</p><p class="mt-1 text-sm font-bold"><?= $account['exp_level'] !== null ? (int) $account['exp_level'] : '—' ?></p></div>
                        <div><p class="text-[10px] uppercase tracking-wider text-slate-500">Trophies</p><p class="mt-1 text-sm font-bold"><?= $account['trophies'] !== null ? number_format((int) $account['trophies']) : '—' ?></p></div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
</body>
</html>
