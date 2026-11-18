<?php

declare(strict_types=1);

use App\Helpers\Security;

$items = $items ?? [];
$plans = $plans ?? [];
$accountId = (int) ($selectedAccount['id'] ?? 0);
$formatDate = static function (?string $value): string {
    return $value ? date('M j, Y', strtotime($value)) : 'Not scheduled';
};

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#090f1c">
    <title>Upgrade Planner | xinClash</title>
    <link rel="stylesheet" href="<?= Security::escape($baseUrl) ?>/assets/css/output.css">
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 antialiased">
<main class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
    <a href="<?= Security::escape($baseUrl) ?>/dashboard" class="text-xs font-semibold text-emerald-400">← Dashboard</a>
    <div class="mt-5 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-[10px] font-bold uppercase tracking-[0.24em] text-emerald-400">Planning</p>
            <h1 class="mt-2 text-3xl font-black">Upgrade planner</h1>
            <p class="mt-2 text-sm text-slate-400">Plan your next upgrade targets and track their status.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <?php if (count($accounts) > 1): ?>
                <form method="get" action="<?= Security::escape($baseUrl) ?>/upgrades/planner">
                    <label for="planner-account" class="sr-only">Switch player account</label>
                    <select id="planner-account" name="account" onchange="this.form.submit()" class="max-w-[200px] rounded-xl border border-white/10 bg-slate-900 px-3 py-2.5 text-xs text-slate-200">
                        <?php foreach ($accounts as $account): ?>
                            <option value="<?= (int) $account['id'] ?>" <?= (int) $account['id'] === $accountId ? 'selected' : '' ?>><?= Security::escape($account['player_name'] ?: $account['player_tag']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            <?php endif; ?>
            <a href="<?= Security::escape($baseUrl) ?>/upgrades/defenses?account=<?= $accountId ?>" class="rounded-xl border border-white/10 bg-slate-900 px-4 py-2.5 text-xs font-semibold text-slate-200">Back to tracker</a>
        </div>
    </div>

    <?php if (!empty($success)): ?>
        <div class="mt-6 rounded-xl border border-emerald-400/20 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-200"><?= Security::escape($success) ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="mt-6 rounded-xl border border-rose-400/20 bg-rose-400/10 px-4 py-3 text-sm text-rose-200"><?= Security::escape($error) ?></div>
    <?php endif; ?>

    <section class="mt-7 grid gap-5 lg:grid-cols-[360px_minmax(0,1fr)]">
        <div class="h-fit rounded-2xl border border-white/5 bg-slate-900/70 p-5">
            <h2 class="font-bold text-white">Create an upgrade plan</h2>
            <p class="mt-1 text-xs leading-5 text-slate-500">Items appear after a player sync or after you save a manual level.</p>
            <?php if ($items === []): ?>
                <div class="mt-5 rounded-xl border border-dashed border-slate-700 p-4 text-center">
                    <p class="text-sm font-semibold text-slate-300">No tracked items yet</p>
                    <p class="mt-1 text-xs leading-5 text-slate-500">Synchronize your player profile or enter manual building levels first.</p>
                </div>
            <?php else: ?>
                <form method="post" action="<?= Security::escape($baseUrl) ?>/upgrades/planner/create" class="mt-5 space-y-4">
                    <input type="hidden" name="_token" value="<?= Security::escape($csrfToken) ?>">
                    <input type="hidden" name="account_id" value="<?= $accountId ?>">
                    <div>
                        <label for="item_id" class="mb-2 block text-xs font-semibold text-slate-300">Upgrade item</label>
                        <select id="item_id" name="item_id" required class="w-full rounded-xl border border-white/10 bg-slate-950 px-3 py-3 text-sm text-slate-200">
                            <option value="">Choose an item</option>
                            <?php foreach ($items as $item): ?>
                                <option value="<?= (int) $item['upgrade_item_id'] ?>"><?= Security::escape($item['item_name']) ?> · Lv <?= (int) $item['current_level'] ?><?= $item['api_max_level'] !== null ? ' / ' . (int) $item['api_max_level'] : '' ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="target_level" class="mb-2 block text-xs font-semibold text-slate-300">Target level</label>
                        <input id="target_level" name="target_level" type="number" min="1" max="255" required class="w-full rounded-xl border border-white/10 bg-slate-950 px-3 py-3 text-sm text-white outline-none focus:border-emerald-400">
                    </div>
                    <div>
                        <label for="priority" class="mb-2 block text-xs font-semibold text-slate-300">Priority</label>
                        <select id="priority" name="priority" class="w-full rounded-xl border border-white/10 bg-slate-950 px-3 py-3 text-sm text-slate-200">
                            <option value="high">High</option>
                            <option value="medium" selected>Medium</option>
                            <option value="low">Low</option>
                        </select>
                    </div>
                    <div>
                        <label for="estimated_completion_at" class="mb-2 block text-xs font-semibold text-slate-300">Estimated completion</label>
                        <input id="estimated_completion_at" name="estimated_completion_at" type="datetime-local" class="w-full rounded-xl border border-white/10 bg-slate-950 px-3 py-3 text-sm text-slate-200">
                    </div>
                    <div>
                        <label for="notes" class="mb-2 block text-xs font-semibold text-slate-300">Notes</label>
                        <textarea id="notes" name="notes" maxlength="1000" rows="3" class="w-full resize-y rounded-xl border border-white/10 bg-slate-950 px-3 py-3 text-sm text-slate-200 outline-none focus:border-emerald-400" placeholder="Optional upgrade notes"></textarea>
                    </div>
                    <button type="submit" class="w-full rounded-xl bg-emerald-400 px-4 py-3 text-sm font-bold text-slate-950 hover:bg-emerald-300">Add to planner</button>
                </form>
            <?php endif; ?>
        </div>

        <section class="overflow-hidden rounded-2xl border border-white/5 bg-slate-900/70">
            <div class="border-b border-white/5 px-5 py-4">
                <h2 class="font-bold text-white">Your upgrade queue</h2>
                <p class="mt-1 text-xs text-slate-500"><?= count($plans) ?> plan records for this account</p>
            </div>
            <?php if ($plans === []): ?>
                <div class="px-5 py-14 text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-slate-800 text-2xl">📋</div>
                    <p class="mt-4 text-sm font-semibold text-slate-300">Your planner is empty</p>
                    <p class="mt-1 text-xs text-slate-500">Create a plan to start organizing your next upgrades.</p>
                </div>
            <?php else: ?>
                <div class="divide-y divide-white/5">
                    <?php foreach ($plans as $plan): ?>
                        <article class="p-5">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <h3 class="font-bold text-white"><?= Security::escape($plan['item_name']) ?></h3>
                                    <p class="mt-1 text-xs text-slate-500"><?= Security::escape(ucwords(str_replace('_', ' ', $plan['category']))) ?> · Level <?= (int) $plan['from_level'] ?> → <?= (int) $plan['target_level'] ?></p>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <span class="rounded-full px-2.5 py-1 text-[10px] font-bold uppercase <?= $plan['status'] === 'completed' ? 'bg-emerald-400/10 text-emerald-300' : ($plan['status'] === 'in_progress' ? 'bg-sky-400/10 text-sky-300' : ($plan['status'] === 'cancelled' ? 'bg-slate-800 text-slate-500' : 'bg-amber-400/10 text-amber-200')) ?>"><?= Security::escape(str_replace('_', ' ', $plan['status'])) ?></span>
                                    <span class="rounded-full bg-slate-800 px-2.5 py-1 text-[10px] font-bold uppercase text-slate-400"><?= Security::escape($plan['priority']) ?> priority</span>
                                </div>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-x-5 gap-y-1 text-[11px] text-slate-500">
                                <span>Current: <strong class="text-slate-300"><?= $plan['current_level'] !== null ? (int) $plan['current_level'] : (int) $plan['from_level'] ?></strong></span>
                                <span>Estimated: <strong class="text-slate-300"><?= $formatDate($plan['estimated_completion_at']) ?></strong></span>
                            </div>
                            <?php if (!empty($plan['notes'])): ?><p class="mt-3 rounded-lg bg-slate-950/70 px-3 py-2 text-xs leading-5 text-slate-400"><?= Security::escape($plan['notes']) ?></p><?php endif; ?>
                            <?php if (in_array($plan['status'], ['planned', 'in_progress'], true)): ?>
                                <div class="mt-4 flex flex-wrap gap-2">
                                    <?php if ($plan['status'] === 'planned'): ?>
                                        <form method="post" action="<?= Security::escape($baseUrl) ?>/upgrades/planner/status">
                                            <input type="hidden" name="_token" value="<?= Security::escape($csrfToken) ?>">
                                            <input type="hidden" name="account_id" value="<?= $accountId ?>">
                                            <input type="hidden" name="plan_id" value="<?= (int) $plan['id'] ?>">
                                            <input type="hidden" name="status" value="in_progress">
                                            <button class="rounded-lg bg-sky-400/10 px-3 py-2 text-[11px] font-bold text-sky-300 hover:bg-sky-400/20">Start upgrade</button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="post" action="<?= Security::escape($baseUrl) ?>/upgrades/planner/status" onsubmit="return confirm('Cancel this upgrade plan?')">
                                        <input type="hidden" name="_token" value="<?= Security::escape($csrfToken) ?>">
                                        <input type="hidden" name="account_id" value="<?= $accountId ?>">
                                        <input type="hidden" name="plan_id" value="<?= (int) $plan['id'] ?>">
                                        <input type="hidden" name="status" value="cancelled">
                                        <button class="rounded-lg bg-rose-400/10 px-3 py-2 text-[11px] font-bold text-rose-300 hover:bg-rose-400/20">Cancel plan</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </section>
</main>
</body>
</html>
