<?php

declare(strict_types=1);

use App\Helpers\Security;

$lastSynced = $account['last_synced_at']
    ? date('M j, Y g:i A', strtotime($account['last_synced_at']))
    : 'Not synchronized yet';

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#090f1c">
    <title><?= Security::escape($account['player_name'] ?: 'Player') ?> | xinClash</title>
    <link rel="stylesheet" href="<?= Security::escape($baseUrl) ?>/assets/css/output.css">
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 antialiased">
<main class="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
    <a href="<?= Security::escape($baseUrl) ?>/accounts" class="text-sm font-medium text-emerald-400 hover:text-emerald-300">← Game accounts</a>

    <?php if (!empty($success)): ?>
        <div class="mt-6 rounded-xl border border-emerald-400/20 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-200"><?= Security::escape($success) ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="mt-6 rounded-xl border border-red-400/20 bg-red-400/10 px-4 py-3 text-sm text-red-200"><?= Security::escape($error) ?></div>
    <?php endif; ?>

    <section class="mt-7 overflow-hidden rounded-2xl border border-white/10 bg-slate-900/70">
        <div class="border-b border-white/5 bg-gradient-to-r from-emerald-400/10 to-transparent p-6 sm:p-8">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-400/10 text-3xl">🏰</div>
                    <div>
                        <h1 class="text-2xl font-black tracking-tight"><?= Security::escape($account['player_name'] ?: 'Clash of Clans Player') ?></h1>
                        <p class="mt-1 font-mono text-sm text-slate-400"><?= Security::escape($account['player_tag']) ?></p>
                    </div>
                </div>
                <span class="rounded-full bg-emerald-400/10 px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider text-emerald-300">Verified account</span>
            </div>
        </div>

        <div class="grid gap-4 p-6 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border border-white/5 bg-slate-950/70 p-4"><p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Town Hall</p><p class="mt-2 text-2xl font-black"><?= $account['town_hall_level'] !== null ? (int) $account['town_hall_level'] : '—' ?></p></div>
            <div class="rounded-xl border border-white/5 bg-slate-950/70 p-4"><p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Experience</p><p class="mt-2 text-2xl font-black"><?= $account['exp_level'] !== null ? (int) $account['exp_level'] : '—' ?></p></div>
            <div class="rounded-xl border border-white/5 bg-slate-950/70 p-4"><p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Trophies</p><p class="mt-2 text-2xl font-black"><?= $account['trophies'] !== null ? number_format((int) $account['trophies']) : '—' ?></p></div>
            <div class="rounded-xl border border-white/5 bg-slate-950/70 p-4"><p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Best trophies</p><p class="mt-2 text-2xl font-black"><?= $account['best_trophies'] !== null ? number_format((int) $account['best_trophies']) : '—' ?></p></div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-4 border-t border-white/5 px-6 py-5">
            <div>
                <p class="text-xs font-semibold text-slate-300">Last synchronized</p>
                <p class="mt-1 text-xs text-slate-500"><?= Security::escape($lastSynced) ?></p>
            </div>
            <form method="post" action="<?= Security::escape($baseUrl) ?>/accounts/sync">
                <input type="hidden" name="_token" value="<?= Security::escape($csrfToken) ?>">
                <input type="hidden" name="account_id" value="<?= (int) $account['id'] ?>">
                <button type="submit" class="rounded-xl bg-emerald-400 px-4 py-3 text-sm font-bold text-slate-950 transition hover:bg-emerald-300">Synchronize profile</button>
            </form>
        </div>
    </section>

    <p class="mt-5 text-xs leading-5 text-slate-500">Supercell's player profile API provides profile information and supported unit data. It does not provide the complete levels of defensive buildings, so defense progression will be maintained separately in xinClash.</p>
</main>
</body>
</html>
