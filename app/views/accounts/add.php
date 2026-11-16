<?php

declare(strict_types=1);

use App\Helpers\Security;

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#090f1c">
    <title>Connect Account | xinClash</title>
    <link rel="stylesheet" href="<?= Security::escape($baseUrl) ?>/assets/css/output.css">
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 antialiased">
<main class="mx-auto flex min-h-screen max-w-2xl items-center px-4 py-10 sm:px-6">
    <section class="w-full rounded-2xl border border-white/10 bg-slate-900/80 p-6 shadow-2xl shadow-black/20 sm:p-8">
        <a href="<?= Security::escape($baseUrl) ?>/accounts" class="text-sm font-medium text-emerald-400 hover:text-emerald-300">← Back to game accounts</a>
        <div class="mt-7">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-400/10 text-2xl">⚔</div>
            <h1 class="mt-5 text-2xl font-black tracking-tight">Connect a Clash of Clans account</h1>
            <p class="mt-2 text-sm leading-6 text-slate-400">Enter your player tag and the API token shown in your game's settings to verify that you own the account.</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="mt-6 rounded-xl border border-red-400/20 bg-red-400/10 px-4 py-3 text-sm leading-6 text-red-200"><?= Security::escape($error) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= Security::escape($baseUrl) ?>/accounts/store" class="mt-7 space-y-5" autocomplete="off">
            <input type="hidden" name="_token" value="<?= Security::escape($csrfToken) ?>">
            <div>
                <label for="player_tag" class="mb-2 block text-sm font-semibold text-slate-200">Player tag</label>
                <input id="player_tag" name="player_tag" type="text" required maxlength="16" placeholder="#2ABC..." value="<?= Security::escape($oldTag ?? '') ?>" class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 font-mono text-sm text-white outline-none transition placeholder:text-slate-600 focus:border-emerald-400 focus:ring-2 focus:ring-emerald-400/10">
                <p class="mt-2 text-xs text-slate-500">You can find your player tag on your in-game profile.</p>
            </div>
            <div>
                <label for="player_token" class="mb-2 block text-sm font-semibold text-slate-200">In-game API token</label>
                <input id="player_token" name="player_token" type="password" required maxlength="128" autocomplete="new-password" placeholder="Enter the token from Game Settings" class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 font-mono text-sm text-white outline-none transition placeholder:text-slate-600 focus:border-emerald-400 focus:ring-2 focus:ring-emerald-400/10">
                <p class="mt-2 text-xs text-slate-500">This token is sent only to Supercell for ownership verification. xinClash does not save it.</p>
            </div>
            <div class="rounded-xl border border-amber-400/15 bg-amber-400/5 p-4">
                <p class="text-xs font-bold uppercase tracking-wider text-amber-200">Before you continue</p>
                <p class="mt-2 text-xs leading-5 text-slate-400">Make sure your Supercell developer API key is configured on the server and its allowed IP address matches the public IP of the server making the request.</p>
            </div>
            <button type="submit" class="w-full rounded-xl bg-emerald-400 px-5 py-3.5 text-sm font-bold text-slate-950 transition hover:bg-emerald-300">Verify and connect account</button>
        </form>
    </section>
</main>
</body>
</html>
