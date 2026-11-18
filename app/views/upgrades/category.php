<?php

declare(strict_types=1);

use App\Helpers\Security;

$items = $items ?? [];
$accounts = $accounts ?? [];
$selectedAccount = $selectedAccount ?? null;
$manualCategories = ['defenses', 'traps', 'buildings'];
$tabs = [
    'defenses' => 'Defenses',
    'traps' => 'Traps',
    'buildings' => 'Buildings',
    'troops' => 'Troops',
    'spells' => 'Spells',
    'heroes' => 'Heroes',
    'pets' => 'Pets',
    'equipment' => 'Equipment',
];
$accountId = (int) ($selectedAccount['id'] ?? 0);
$formatCategory = static fn (string $value): string => ucwords(str_replace('_', ' ', $value));

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#090f1c">
    <title><?= Security::escape($categoryTitle) ?> | xinClash</title>
    <link rel="stylesheet" href="<?= Security::escape($baseUrl) ?>/assets/css/output.css">
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 antialiased">
<main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <header class="mb-8 flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
        <div>
            <a href="<?= Security::escape($baseUrl) ?>/dashboard" class="text-xs font-semibold text-emerald-400 hover:text-emerald-300">← Dashboard</a>
            <p class="mt-5 text-[10px] font-bold uppercase tracking-[0.24em] text-emerald-400">Upgrade tracker</p>
            <h1 class="mt-2 text-3xl font-black tracking-tight sm:text-4xl"><?= Security::escape($categoryTitle) ?></h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-400"><?= Security::escape($categoryDescription) ?></p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="<?= Security::escape($baseUrl) ?>/upgrades/planner?account=<?= $accountId ?>" class="rounded-xl border border-white/10 bg-slate-900 px-4 py-2.5 text-xs font-semibold text-slate-200 hover:border-emerald-400/30">Upgrade planner</a>
            <a href="<?= Security::escape($baseUrl) ?>/accounts/show?id=<?= $accountId ?>" class="rounded-xl bg-emerald-400 px-4 py-2.5 text-xs font-bold text-slate-950 hover:bg-emerald-300">Player profile</a>
        </div>
    </header>

    <?php if (!empty($success)): ?>
        <div class="mb-5 rounded-xl border border-emerald-400/20 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-200"><?= Security::escape($success) ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="mb-5 rounded-xl border border-rose-400/20 bg-rose-400/10 px-4 py-3 text-sm text-rose-200"><?= Security::escape($error) ?></div>
    <?php endif; ?>

    <section class="mb-5 flex flex-col gap-4 rounded-2xl border border-white/5 bg-slate-900/70 p-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Selected player</p>
            <p class="mt-1 font-semibold text-white"><?= Security::escape($selectedAccount['player_name'] ?: $selectedAccount['player_tag']) ?></p>
            <p class="mt-0.5 font-mono text-xs text-slate-500"><?= Security::escape($selectedAccount['player_tag']) ?></p>
        </div>
        <?php if (count($accounts) > 1): ?>
            <form method="get" action="<?= Security::escape($baseUrl) ?>/upgrades/<?= Security::escape($categoryKey) ?>" class="flex items-center gap-2">
                <label for="account" class="text-xs text-slate-400">Switch account</label>
                <select id="account" name="account" onchange="this.form.submit()" class="max-w-[220px] rounded-lg border border-white/10 bg-slate-950 px-3 py-2 text-xs text-slate-200">
                    <?php foreach ($accounts as $account): ?>
                        <option value="<?= (int) $account['id'] ?>" <?= (int) $account['id'] === $accountId ? 'selected' : '' ?>><?= Security::escape($account['player_name'] ?: $account['player_tag']) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
        <?php endif; ?>
    </section>

    <nav class="mb-6 flex gap-2 overflow-x-auto pb-1" aria-label="Upgrade categories">
        <?php foreach ($tabs as $key => $label): ?>
            <a href="<?= Security::escape($baseUrl) ?>/upgrades/<?= Security::escape($key) ?>?account=<?= $accountId ?>" class="shrink-0 rounded-xl px-4 py-2.5 text-xs font-semibold transition <?= $categoryKey === $key ? 'bg-emerald-400 text-slate-950' : 'border border-white/10 bg-slate-900 text-slate-400 hover:text-white' ?>"><?= Security::escape($label) ?></a>
        <?php endforeach; ?>
    </nav>

    <?php if ($items === []): ?>
        <section class="rounded-2xl border border-dashed border-slate-700 bg-slate-900/50 px-6 py-14 text-center">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-slate-800 text-2xl"><?= $isManual ? '🛡️' : '⚔️' ?></div>
            <h2 class="mt-4 text-lg font-bold text-white"><?= $isManual ? 'No catalog items available' : 'No synchronized items yet' ?></h2>
            <p class="mx-auto mt-2 max-w-lg text-sm leading-6 text-slate-400"><?= $isManual ? 'The manual catalog will appear here after it is initialized.' : 'Synchronize your player profile to import the levels returned by the Clash of Clans API.' ?></p>
            <?php if (!$isManual): ?>
                <a href="<?= Security::escape($baseUrl) ?>/accounts/show?id=<?= $accountId ?>" class="mt-5 inline-flex rounded-xl bg-emerald-400 px-4 py-3 text-xs font-bold text-slate-950 hover:bg-emerald-300">Open player profile and sync</a>
            <?php endif; ?>
        </section>
    <?php elseif ($isManual): ?>
        <section class="overflow-hidden rounded-2xl border border-white/5 bg-slate-900/70">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-white/5 px-5 py-4">
                <div><h2 class="font-bold text-white">Manual level records</h2><p class="mt-1 text-xs text-slate-500">Set each item's current in-game level.</p></div>
                <span class="rounded-full bg-amber-400/10 px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-amber-200">Manual tracking</span>
            </div>
            <div class="divide-y divide-white/5">
                <?php foreach ($items as $item): ?>
                    <form method="post" action="<?= Security::escape($baseUrl) ?>/upgrades/manual-level" class="grid gap-3 px-4 py-4 sm:grid-cols-[minmax(0,1fr)_110px_100px] sm:items-center sm:px-5">
                        <input type="hidden" name="_token" value="<?= Security::escape($csrfToken) ?>">
                        <input type="hidden" name="account_id" value="<?= $accountId ?>">
                        <input type="hidden" name="item_id" value="<?= (int) $item['upgrade_item_id'] ?>">
                        <input type="hidden" name="category" value="<?= Security::escape($categoryKey) ?>">
                        <div class="min-w-0">
                            <p class="font-semibold text-slate-100"><?= Security::escape($item['item_name']) ?></p>
                            <p class="mt-1 text-[11px] text-slate-500"><?= Security::escape($formatCategory($item['category'])) ?> · <?= $item['data_source'] === 'manual' ? 'Saved manually' : 'Not recorded yet' ?></p>
                        </div>
                        <label class="flex items-center gap-2 sm:block">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 sm:mb-1 sm:block">Current level</span>
                            <input type="number" name="current_level" min="0" max="255" required value="<?= (int) $item['current_level'] ?>" class="w-24 rounded-lg border border-white/10 bg-slate-950 px-3 py-2 text-sm font-bold text-white outline-none focus:border-emerald-400 sm:w-full">
                        </label>
                        <button type="submit" class="rounded-lg border border-emerald-400/20 bg-emerald-400/10 px-3 py-2.5 text-xs font-bold text-emerald-300 hover:bg-emerald-400/20">Save level</button>
                    </form>
                <?php endforeach; ?>
            </div>
        </section>
    <?php else: ?>
        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            <?php foreach ($items as $item): ?>
                <?php
                    $level = (int) $item['current_level'];
                    $maxLevel = $item['api_max_level'] !== null ? (int) $item['api_max_level'] : null;
                    $progress = $maxLevel && $maxLevel > 0 ? min(100, (int) round(($level / $maxLevel) * 100)) : 0;
                    $isMaxed = $maxLevel !== null && $level >= $maxLevel;
                    $village = str_contains($item['item_key'], ':builderbase:') ? 'Builder Base' : 'Home Village';
                ?>
                <article class="rounded-2xl border border-white/5 bg-slate-900/70 p-4 sm:p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h2 class="truncate font-bold text-white"><?= Security::escape($item['item_name']) ?></h2>
                            <p class="mt-1 text-[10px] uppercase tracking-wider text-slate-500"><?= Security::escape($village) ?> · <?= Security::escape($formatCategory($item['category'])) ?></p>
                        </div>
                        <?php if ($isMaxed): ?>
                            <span class="shrink-0 rounded-full bg-emerald-400/10 px-2 py-1 text-[9px] font-bold uppercase text-emerald-300">Maxed</span>
                        <?php else: ?>
                            <span class="shrink-0 rounded-full bg-slate-800 px-2 py-1 text-[9px] font-bold uppercase text-slate-400">Tracked</span>
                        <?php endif; ?>
                    </div>
                    <div class="mt-6 flex items-end justify-between">
                        <p class="text-3xl font-black tracking-tight text-white"><?= $level ?><span class="ml-1 text-sm font-semibold text-slate-500"><?= $maxLevel !== null ? '/ ' . $maxLevel : '' ?></span></p>
                        <?php if ($maxLevel !== null): ?><p class="text-xs font-semibold text-slate-400"><?= $progress ?>%</p><?php endif; ?>
                    </div>
                    <?php if ($maxLevel !== null): ?>
                        <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-800"><div class="h-full rounded-full <?= $isMaxed ? 'bg-emerald-400' : 'bg-sky-400' ?>" style="width: <?= $progress ?>%"></div></div>
                    <?php else: ?>
                        <p class="mt-3 text-[11px] text-slate-500">Maximum level not provided by this player response.</p>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>

    <p class="mt-6 text-[11px] leading-5 text-slate-600">API levels reflect the player profile returned by Supercell. Manual structures are not included in the player profile endpoint and must be maintained here.</p>
</main>
</body>
</html>
