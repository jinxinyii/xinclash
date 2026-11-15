<?php

declare(strict_types=1);

use App\Helpers\Security;

$config = require dirname(__DIR__, 3) . '/config/app.php';
$baseUrl = rtrim($config['url'], '/');
$old = $old ?? [];
$errors = $errors ?? [];

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create account | xinClash</title>
    <link rel="stylesheet" href="<?= Security::escape($baseUrl) ?>/assets/css/output.css">
</head>
<body class="min-h-screen bg-slate-950 text-slate-100">
    <main class="mx-auto flex min-h-screen w-full max-w-2xl items-center px-5 py-12">
        <section class="w-full rounded-2xl border border-slate-800 bg-slate-900 p-7 shadow-xl">
            <div class="mb-8">
                <p class="text-sm font-semibold uppercase tracking-[0.25em] text-emerald-400">xinClash</p>
                <h1 class="mt-3 text-3xl font-bold tracking-tight">Create your account</h1>
                <p class="mt-2 text-sm text-slate-400">Registration is private and requires an invitation.</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="mb-5 rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-200">
                    <?= Security::escape($error) ?>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= Security::escape($baseUrl) ?>/register" class="space-y-5">
                <input type="hidden" name="_token" value="<?= Security::escape($csrfToken) ?>">
                <input type="hidden" name="invite_token" value="<?= Security::escape($old['invite_token'] ?? $inviteToken ?? '') ?>">

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="first_name" class="mb-2 block text-sm font-medium">First name</label>
                        <input id="first_name" name="first_name" required maxlength="100" autocomplete="given-name"
                            value="<?= Security::escape($old['first_name'] ?? '') ?>"
                            class="w-full rounded-lg border border-slate-700 bg-slate-950 px-4 py-3 text-sm outline-none focus:border-emerald-500">
                        <?php if (isset($errors['first_name'])): ?><p class="mt-1 text-xs text-red-300"><?= Security::escape($errors['first_name']) ?></p><?php endif; ?>
                    </div>
                    <div>
                        <label for="middle_initial" class="mb-2 block text-sm font-medium">Middle initial <span class="text-slate-500">(optional)</span></label>
                        <input id="middle_initial" name="middle_initial" maxlength="5"
                            value="<?= Security::escape($old['middle_initial'] ?? '') ?>"
                            class="w-full rounded-lg border border-slate-700 bg-slate-950 px-4 py-3 text-sm outline-none focus:border-emerald-500">
                        <?php if (isset($errors['middle_initial'])): ?><p class="mt-1 text-xs text-red-300"><?= Security::escape($errors['middle_initial']) ?></p><?php endif; ?>
                    </div>
                </div>

                <div>
                    <label for="last_name" class="mb-2 block text-sm font-medium">Last name</label>
                    <input id="last_name" name="last_name" required maxlength="100" autocomplete="family-name"
                        value="<?= Security::escape($old['last_name'] ?? '') ?>"
                        class="w-full rounded-lg border border-slate-700 bg-slate-950 px-4 py-3 text-sm outline-none focus:border-emerald-500">
                    <?php if (isset($errors['last_name'])): ?><p class="mt-1 text-xs text-red-300"><?= Security::escape($errors['last_name']) ?></p><?php endif; ?>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="birthdate" class="mb-2 block text-sm font-medium">Birthdate</label>
                        <input id="birthdate" name="birthdate" type="date" required max="<?= date('Y-m-d') ?>"
                            value="<?= Security::escape($old['birthdate'] ?? '') ?>"
                            class="w-full rounded-lg border border-slate-700 bg-slate-950 px-4 py-3 text-sm outline-none focus:border-emerald-500">
                        <?php if (isset($errors['birthdate'])): ?><p class="mt-1 text-xs text-red-300"><?= Security::escape($errors['birthdate']) ?></p><?php endif; ?>
                    </div>
                    <div>
                        <label for="email" class="mb-2 block text-sm font-medium">Email address</label>
                        <input id="email" name="email" type="email" required maxlength="254" autocomplete="email"
                            value="<?= Security::escape($old['email'] ?? '') ?>"
                            class="w-full rounded-lg border border-slate-700 bg-slate-950 px-4 py-3 text-sm outline-none focus:border-emerald-500">
                        <?php if (isset($errors['email'])): ?><p class="mt-1 text-xs text-red-300"><?= Security::escape($errors['email']) ?></p><?php endif; ?>
                    </div>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="password" class="mb-2 block text-sm font-medium">Password</label>
                        <input id="password" name="password" type="password" required minlength="12" autocomplete="new-password"
                            class="w-full rounded-lg border border-slate-700 bg-slate-950 px-4 py-3 text-sm outline-none focus:border-emerald-500">
                        <p class="mt-1 text-xs text-slate-500">Use at least 12 characters.</p>
                        <?php if (isset($errors['password'])): ?><p class="mt-1 text-xs text-red-300"><?= Security::escape($errors['password']) ?></p><?php endif; ?>
                    </div>
                    <div>
                        <label for="password_confirmation" class="mb-2 block text-sm font-medium">Confirm password</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required minlength="12" autocomplete="new-password"
                            class="w-full rounded-lg border border-slate-700 bg-slate-950 px-4 py-3 text-sm outline-none focus:border-emerald-500">
                        <?php if (isset($errors['password_confirmation'])): ?><p class="mt-1 text-xs text-red-300"><?= Security::escape($errors['password_confirmation']) ?></p><?php endif; ?>
                    </div>
                </div>

                <?php if (isset($errors['invite_token'])): ?><p class="text-xs text-red-300"><?= Security::escape($errors['invite_token']) ?></p><?php endif; ?>

                <button type="submit" class="w-full rounded-lg bg-emerald-500 px-4 py-3 text-sm font-semibold text-slate-950 transition hover:bg-emerald-400">
                    Create account
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-slate-400">
                Already have an account?
                <a href="<?= Security::escape($baseUrl) ?>/login" class="font-semibold text-emerald-400 hover:text-emerald-300">Sign in</a>
            </p>
        </section>
    </main>
</body>
</html>
