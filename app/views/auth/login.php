<?php

declare(strict_types=1);

use App\Helpers\Security;

$config = require dirname(__DIR__, 3) . '/config/app.php';
$baseUrl = rtrim($config['url'], '/');

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in | xinClash</title>
    <link rel="stylesheet" href="<?= Security::escape($baseUrl) ?>/assets/css/output.css">
</head>
<body class="min-h-screen bg-slate-950 text-slate-100">
    <main class="mx-auto flex min-h-screen w-full max-w-md items-center px-5 py-12">
        <section class="w-full rounded-2xl border border-slate-800 bg-slate-900 p-7 shadow-xl">
            <div class="mb-8">
                <p class="text-sm font-semibold uppercase tracking-[0.25em] text-emerald-400">xinClash</p>
                <h1 class="mt-3 text-3xl font-bold tracking-tight">Welcome back</h1>
                <p class="mt-2 text-sm text-slate-400">Sign in to manage your Clash of Clans progress.</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="mb-5 rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-200">
                    <?= Security::escape($error) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="mb-5 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200">
                    <?= Security::escape($success) ?>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= Security::escape($baseUrl) ?>/login" class="space-y-5">
                <input type="hidden" name="_token" value="<?= Security::escape($csrfToken) ?>">

                <div>
                    <label for="email" class="mb-2 block text-sm font-medium text-slate-200">Email address</label>
                    <input id="email" name="email" type="email" autocomplete="email" required maxlength="254"
                        value="<?= Security::escape($old['email'] ?? '') ?>"
                        class="w-full rounded-lg border border-slate-700 bg-slate-950 px-4 py-3 text-sm text-white outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20">
                </div>

                <div>
                    <label for="password" class="mb-2 block text-sm font-medium text-slate-200">Password</label>
                    <input id="password" name="password" type="password" autocomplete="current-password" required
                        class="w-full rounded-lg border border-slate-700 bg-slate-950 px-4 py-3 text-sm text-white outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20">
                </div>

                <button type="submit" class="w-full rounded-lg bg-emerald-500 px-4 py-3 text-sm font-semibold text-slate-950 transition hover:bg-emerald-400">
                    Sign in
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-slate-400">
                Have an invitation?
                <a href="<?= Security::escape($baseUrl) ?>/register" class="font-semibold text-emerald-400 hover:text-emerald-300">Create an account</a>
            </p>
        </section>
    </main>
</body>
</html>
