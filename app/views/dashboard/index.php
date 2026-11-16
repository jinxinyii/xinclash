<?php

declare(strict_types=1);

use App\Helpers\Security;

$accounts = $accounts ?? [];
$selectedAccount = $selectedAccount ?? null;
$stats = $stats ?? [];
$planStats = $planStats ?? [];
$recentHistory = $recentHistory ?? [];
$recentPlans = $recentPlans ?? [];
$firstName = explode(' ', trim($userName ?? 'Player'))[0] ?: 'Player';
$avatarInitial = strtoupper(substr($firstName, 0, 1));
$formatDate = static function (?string $value): string {
    if (!$value) {
        return 'Not scheduled';
    }

    return date('M j, Y', strtotime($value));
};

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#090f1c">
    <title>Dashboard | xinClash</title>
    <link rel="stylesheet" href="<?= Security::escape($baseUrl) ?>/assets/css/output.css">
    <script defer src="<?= Security::escape($baseUrl) ?>/assets/js/dashboard.js"></script>
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 antialiased">
<div class="min-h-screen md:flex">
    <aside class="hidden w-64 shrink-0 border-r border-white/5 bg-slate-950 md:fixed md:inset-y-0 md:flex md:flex-col">
        <div class="flex h-20 items-center gap-3 border-b border-white/5 px-6">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-400 text-slate-950">
                <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M12 3 20 6v5c0 5-3.4 8.3-8 10-4.6-1.7-8-5-8-10V6l8-3Z" />
                    <path d="m8.5 12 2.2 2.2 4.8-5" />
                </svg>
            </div>
            <div>
                <p class="text-lg font-black tracking-tight">xin<span class="text-emerald-400">Clash</span></p>
                <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-500">Progress tracker</p>
            </div>
        </div>

        <nav class="flex-1 space-y-2 px-4 py-6" aria-label="Main navigation">
            <p class="px-3 pb-2 text-[10px] font-bold uppercase tracking-[0.2em] text-slate-600">Workspace</p>
            <a href="<?= Security::escape($baseUrl) ?>/dashboard" class="flex items-center gap-3 rounded-xl bg-emerald-400/10 px-3 py-3 text-sm font-semibold text-emerald-300 ring-1 ring-inset ring-emerald-400/15">
                <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="3" width="8" height="8" rx="2"/><rect x="13" y="3" width="8" height="5" rx="2"/><rect x="13" y="10" width="8" height="11" rx="2"/><rect x="3" y="13" width="8" height="8" rx="2"/></svg>
                Dashboard
            </a>
            <a href="<?= Security::escape($baseUrl) ?>/accounts" class="flex items-center justify-between rounded-xl px-3 py-3 text-sm text-slate-300 transition hover:bg-slate-900 hover:text-white">
                <span class="flex items-center gap-3"><span class="text-lg">⚔</span> Game accounts</span>
                <span class="rounded-md bg-emerald-400/10 px-2 py-1 text-[9px] font-bold uppercase tracking-wider text-emerald-300">Open</span>
            </a>
            <div class="flex items-center justify-between rounded-xl px-3 py-3 text-sm text-slate-500">
                <span class="flex items-center gap-3"><span class="text-lg">⬆</span> Upgrade tracker</span>
                <span class="rounded-md bg-slate-900 px-2 py-1 text-[9px] font-bold uppercase tracking-wider">Soon</span>
            </div>
            <div class="flex items-center justify-between rounded-xl px-3 py-3 text-sm text-slate-500">
                <span class="flex items-center gap-3"><span class="text-lg">▤</span> Upgrade planner</span>
                <span class="rounded-md bg-slate-900 px-2 py-1 text-[9px] font-bold uppercase tracking-wider">Soon</span>
            </div>
        </nav>

        <div class="border-t border-white/5 p-4">
            <div class="flex items-center gap-3 rounded-xl bg-slate-900/70 p-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-800 text-sm font-bold text-emerald-300"><?= Security::escape($avatarInitial) ?></div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold text-white"><?= Security::escape($userName) ?></p>
                    <p class="text-xs capitalize text-slate-500"><?= Security::escape($userRole) ?></p>
                </div>
                <form method="post" action="<?= Security::escape($baseUrl) ?>/logout">
                    <input type="hidden" name="_token" value="<?= Security::escape($csrfToken) ?>">
                    <button type="submit" class="rounded-lg p-2 text-slate-500 transition hover:bg-slate-800 hover:text-white" aria-label="Log out" title="Log out">
                        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M10 17l5-5-5-5M15 12H3"/><path d="M12 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6"/></svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <main class="min-w-0 flex-1 md:ml-64">
        <header class="sticky top-0 z-20 border-b border-white/5 bg-slate-950/90 backdrop-blur-xl">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-3 md:hidden">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-400 text-slate-950">
                        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 3 20 6v5c0 5-3.4 8.3-8 10-4.6-1.7-8-5-8-10V6l8-3Z"/><path d="m8.5 12 2.2 2.2 4.8-5"/></svg>
                    </div>
                    <span class="font-black tracking-tight">xin<span class="text-emerald-400">Clash</span></span>
                </div>
                <p class="hidden text-sm text-slate-500 md:block">Workspace <span class="px-2 text-slate-700">/</span> <span class="text-slate-200">Dashboard</span></p>
                <div class="flex items-center gap-3">
                    <?php if ($selectedAccount !== null): ?>
                        <form method="get" action="<?= Security::escape($baseUrl) ?>/dashboard">
                            <label for="account" class="sr-only">Select Clash account</label>
                            <select id="account" name="account" data-account-select class="max-w-[190px] rounded-lg border border-white/10 bg-slate-900 px-3 py-2 text-xs font-medium text-slate-200 outline-none transition focus:border-emerald-400 sm:max-w-xs sm:text-sm">
                                <?php foreach ($accounts as $account): ?>
                                    <option value="<?= (int) $account['id'] ?>" <?= (int) $account['id'] === (int) $selectedAccount['id'] ? 'selected' : '' ?>><?= Security::escape($account['player_name'] ?: $account['player_tag']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    <?php else: ?>
                        <span class="rounded-lg border border-white/10 bg-slate-900 px-3 py-2 text-xs text-slate-400">No player connected</span>
                    <?php endif; ?>
                    <form method="post" action="<?= Security::escape($baseUrl) ?>/logout" class="md:hidden">
                        <input type="hidden" name="_token" value="<?= Security::escape($csrfToken) ?>">
                        <button type="submit" class="rounded-lg border border-white/10 px-3 py-2 text-xs font-semibold text-slate-300">Log out</button>
                    </form>
                </div>
            </div>
        </header>

        <div class="mx-auto max-w-7xl space-y-8 px-4 py-8 sm:px-6 lg:px-8">
            <section class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.22em] text-emerald-400">Player overview</p>
                    <h1 class="mt-2 text-3xl font-black tracking-tight text-white sm:text-4xl">Welcome back, <?= Security::escape($firstName) ?><span class="text-emerald-400">.</span></h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-400">Your Clash of Clans progression, upgrades, and account activity in one place.</p>
                </div>
                <div class="flex items-center gap-2 text-xs text-slate-500">
                    <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                    Your private workspace
                </div>
            </section>

            <?php if ($selectedAccount === null): ?>
                <section class="relative overflow-hidden rounded-2xl border border-emerald-400/15 bg-gradient-to-br from-emerald-400/10 via-slate-900 to-slate-900 p-6 sm:p-8">
                    <div class="pointer-events-none absolute -right-12 -top-16 h-64 w-64 rounded-full bg-emerald-400/5 blur-3xl"></div>
                    <div class="relative max-w-2xl">
                        <div class="mb-5 inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-400/10 text-2xl text-emerald-300">⚔</div>
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-300">Your journey starts here</p>
                        <h2 class="mt-2 text-2xl font-bold tracking-tight text-white">Connect your first village</h2>
                        <p class="mt-3 text-sm leading-6 text-slate-400">Your dashboard is ready. Once you connect and verify a Clash of Clans player tag, your Town Hall, trophies, upgrade statistics, and recent activity will appear here.</p>
                        <div class="mt-6 inline-flex items-center gap-2 rounded-lg border border-white/10 bg-slate-950/50 px-4 py-3 text-sm font-semibold text-slate-300">
                            <span class="h-2 w-2 rounded-full bg-amber-400"></span>
                            Player account connection is the next milestone
                        </div>
                    </div>
                </section>
            <?php else: ?>
                <section class="overflow-hidden rounded-2xl border border-white/5 bg-gradient-to-br from-slate-900 via-slate-900 to-emerald-950/30 p-5 sm:p-7">
                    <div class="flex flex-col justify-between gap-6 sm:flex-row sm:items-center">
                        <div class="flex items-center gap-4">
                            <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl border border-emerald-400/20 bg-emerald-400/10 text-3xl">🏰</div>
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="text-xl font-bold text-white"><?= Security::escape($selectedAccount['player_name'] ?: 'Clash of Clans Player') ?></h2>
                                    <?php if ((int) $selectedAccount['is_verified'] === 1): ?>
                                        <span class="rounded-full bg-emerald-400/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-emerald-300">Verified</span>
                                    <?php else: ?>
                                        <span class="rounded-full bg-amber-400/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-amber-300">Verification pending</span>
                                    <?php endif; ?>
                                </div>
                                <p class="mt-1 text-sm text-slate-400"><?= Security::escape($selectedAccount['player_tag']) ?></p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3 sm:min-w-64">
                            <div class="rounded-xl border border-white/5 bg-slate-950/40 p-3">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Town Hall</p>
                                <p class="mt-1 text-2xl font-black text-white"><?= $selectedAccount['town_hall_level'] !== null ? (int) $selectedAccount['town_hall_level'] : '—' ?></p>
                            </div>
                            <div class="rounded-xl border border-white/5 bg-slate-950/40 p-3">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Trophies</p>
                                <p class="mt-1 text-2xl font-black text-white"><?= $selectedAccount['trophies'] !== null ? number_format((int) $selectedAccount['trophies']) : '—' ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-6 flex flex-wrap gap-x-6 gap-y-2 border-t border-white/5 pt-4 text-xs text-slate-500">
                        <span>Experience level: <strong class="text-slate-300"><?= $selectedAccount['exp_level'] !== null ? (int) $selectedAccount['exp_level'] : '—' ?></strong></span>
                        <span>Best trophies: <strong class="text-slate-300"><?= $selectedAccount['best_trophies'] !== null ? number_format((int) $selectedAccount['best_trophies']) : '—' ?></strong></span>
                        <span>Last sync: <strong class="text-slate-300"><?= $formatDate($selectedAccount['last_synced_at']) ?></strong></span>
                    </div>
                </section>
            <?php endif; ?>

            <section>
                <div class="mb-4 flex items-end justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-bold text-white">Progress at a glance</h2>
                        <p class="mt-1 text-xs text-slate-500">Statistics for the selected player account</p>
                    </div>
                    <?php if ($selectedAccount !== null): ?><span class="text-xs text-slate-500"><?= (int) $stats['total_items'] ?> tracked items</span><?php endif; ?>
                </div>
                <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                    <article class="rounded-2xl border border-white/5 bg-slate-900/80 p-4 sm:p-5">
                        <div class="flex items-center justify-between"><span class="text-xs font-medium text-slate-400">Defenses</span><span class="text-lg">🛡️</span></div>
                        <p class="mt-4 text-3xl font-black tracking-tight text-white"><?= (int) $stats['defenses'] ?></p>
                        <p class="mt-1 text-[11px] text-slate-500">Tracked structures</p>
                    </article>
                    <article class="rounded-2xl border border-white/5 bg-slate-900/80 p-4 sm:p-5">
                        <div class="flex items-center justify-between"><span class="text-xs font-medium text-slate-400">Troops</span><span class="text-lg">⚔️</span></div>
                        <p class="mt-4 text-3xl font-black tracking-tight text-white"><?= (int) $stats['troops'] ?></p>
                        <p class="mt-1 text-[11px] text-slate-500">Tracked units</p>
                    </article>
                    <article class="rounded-2xl border border-white/5 bg-slate-900/80 p-4 sm:p-5">
                        <div class="flex items-center justify-between"><span class="text-xs font-medium text-slate-400">Active upgrades</span><span class="text-lg">⏳</span></div>
                        <p class="mt-4 text-3xl font-black tracking-tight text-white"><?= (int) $planStats['in_progress'] ?></p>
                        <p class="mt-1 text-[11px] text-slate-500">Currently in progress</p>
                    </article>
                    <article class="rounded-2xl border border-white/5 bg-slate-900/80 p-4 sm:p-5">
                        <div class="flex items-center justify-between"><span class="text-xs font-medium text-slate-400">Max-level items</span><span class="text-lg">✦</span></div>
                        <p class="mt-4 text-3xl font-black tracking-tight text-white"><?= (int) $stats['maxed_items'] ?></p>
                        <p class="mt-1 text-[11px] text-slate-500">At catalog max level</p>
                    </article>
                </div>
            </section>

            <section class="grid gap-5 xl:grid-cols-5">
                <div class="overflow-hidden rounded-2xl border border-white/5 bg-slate-900/70 xl:col-span-3">
                    <div class="flex items-center justify-between border-b border-white/5 px-5 py-4">
                        <div><h2 class="font-bold text-white">Upgrade planner</h2><p class="mt-1 text-xs text-slate-500">Your next planned improvements</p></div>
                        <span class="rounded-lg bg-slate-800 px-2.5 py-1 text-[10px] font-bold text-slate-400">COMING NEXT</span>
                    </div>
                    <?php if ($recentPlans === []): ?>
                        <div class="px-5 py-10 text-center">
                            <div class="mx-auto flex h-11 w-11 items-center justify-center rounded-xl bg-slate-800 text-xl">📋</div>
                            <p class="mt-3 text-sm font-semibold text-slate-300">No upgrades planned yet</p>
                            <p class="mt-1 text-xs text-slate-500">Your upgrade plans will show here when you add them.</p>
                        </div>
                    <?php else: ?>
                        <div class="divide-y divide-white/5">
                            <?php foreach ($recentPlans as $plan): ?>
                                <div class="flex items-center justify-between gap-4 px-5 py-4">
                                    <div class="min-w-0"><p class="truncate text-sm font-semibold text-slate-200"><?= Security::escape($plan['item_name']) ?></p><p class="mt-1 text-xs text-slate-500">Level <?= (int) $plan['from_level'] ?> → <?= (int) $plan['target_level'] ?> · <?= Security::escape(ucwords(str_replace('_', ' ', $plan['status']))) ?></p></div>
                                    <span class="shrink-0 rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider <?= $plan['priority'] === 'high' ? 'bg-rose-400/10 text-rose-300' : ($plan['priority'] === 'medium' ? 'bg-amber-400/10 text-amber-300' : 'bg-slate-800 text-slate-400') ?>"><?= Security::escape($plan['priority']) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="overflow-hidden rounded-2xl border border-white/5 bg-slate-900/70 xl:col-span-2">
                    <div class="border-b border-white/5 px-5 py-4"><h2 class="font-bold text-white">Recent activity</h2><p class="mt-1 text-xs text-slate-500">Latest completed upgrades</p></div>
                    <?php if ($recentHistory === []): ?>
                        <div class="px-5 py-10 text-center">
                            <div class="mx-auto flex h-11 w-11 items-center justify-center rounded-xl bg-slate-800 text-xl">✧</div>
                            <p class="mt-3 text-sm font-semibold text-slate-300">Nothing to show yet</p>
                            <p class="mt-1 text-xs text-slate-500">Completed upgrades will appear here.</p>
                        </div>
                    <?php else: ?>
                        <div class="divide-y divide-white/5">
                            <?php foreach ($recentHistory as $activity): ?>
                                <div class="flex items-center gap-3 px-5 py-4">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-400/10 text-emerald-300">✓</div>
                                    <div class="min-w-0 flex-1"><p class="truncate text-sm font-semibold text-slate-200"><?= Security::escape($activity['item_name']) ?></p><p class="mt-1 text-xs text-slate-500">Level <?= (int) $activity['from_level'] ?> → <?= (int) $activity['to_level'] ?></p></div>
                                    <time class="shrink-0 text-[10px] text-slate-500"><?= $formatDate($activity['completed_at']) ?></time>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <footer class="border-t border-white/5 py-5 text-center text-[11px] text-slate-600">
                xinClash <span class="px-1">·</span> Private Clash of Clans progress tracker
            </footer>
        </div>
    </main>
</div>
</body>
</html>
