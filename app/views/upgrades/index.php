<?php

declare(strict_types=1);

use App\Helpers\Security;

$baseUrl = $baseUrl ?? '';
$sections = [
    ['defenses', 'Defenses', '🛡️', 'Manual levels for defensive structures'],
    ['traps', 'Traps', '💣', 'Manual levels for village traps'],
    ['buildings', 'Buildings', '🏗️', 'Manual levels for support buildings'],
    ['troops', 'Troops', '⚔️', 'API levels for troops and siege machines'],
    ['spells', 'Spells', '✨', 'API levels for spells'],
    ['heroes', 'Heroes', '👑', 'API levels for heroes'],
    ['pets', 'Pets', '🐾', 'API levels for hero pets'],
    ['equipment', 'Equipment', '🧩', 'API levels for hero equipment'],
];

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Upgrade Tracker | xinClash</title>
    <link rel="stylesheet" href="<?= Security::escape($baseUrl) ?>/assets/css/output.css">
</head>
<body class="min-h-screen bg-slate-950 text-slate-100">
<main class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
    <a href="<?= Security::escape($baseUrl) ?>/dashboard" class="text-xs font-semibold text-emerald-400">← Dashboard</a>
    <h1 class="mt-5 text-3xl font-black">Upgrade tracker</h1>
    <p class="mt-2 text-sm text-slate-400">Choose a category to view and manage your village progression.</p>
    <div class="mt-7 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        <?php foreach ($sections as [$key, $title, $icon, $description]): ?>
            <a href="<?= Security::escape($baseUrl) ?>/upgrades/<?= Security::escape($key) ?>" class="rounded-2xl border border-white/5 bg-slate-900/70 p-5 transition hover:border-emerald-400/30">
                <span class="text-2xl"><?= $icon ?></span>
                <h2 class="mt-4 font-bold text-white"><?= Security::escape($title) ?></h2>
                <p class="mt-1 text-xs leading-5 text-slate-500"><?= Security::escape($description) ?></p>
            </a>
        <?php endforeach; ?>
    </div>
    <a href="<?= Security::escape($baseUrl) ?>/upgrades/planner" class="mt-6 inline-flex rounded-xl bg-emerald-400 px-4 py-3 text-sm font-bold text-slate-950">Open upgrade planner</a>
</main>
</body>
</html>
