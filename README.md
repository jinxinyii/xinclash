# xinClash

Private, multi-user Clash of Clans upgrade tracker built with PHP, MySQL, Tailwind CSS, and JavaScript.

## Requirements

- PHP 8.3+
- MySQL 8.0.16+
- Composer
- Node.js and npm
- Apache with `mod_rewrite` enabled
- PHP extensions: PDO MySQL, cURL, JSON, mbstring

## Local setup

1. Place this project at `C:\xampp\htdocs\xinClash`.
2. For standard XAMPP development, keep the project under `htdocs` and use `APP_URL=http://localhost/xinClash/public`. The root `.htaccess` blocks direct access to private project folders. For production, configure an Apache VirtualHost whose document root is the project's `public/` directory and set `APP_URL` to the site's domain root.
3. Copy `.env.example` to `.env`.
4. Set `APP_KEY` to a generated random secret and add your Supercell developer API key to `SUPERCELL_API_KEY`.
5. Create the `xinclash_db` database and import `database/schema.sql` if this is a fresh installation.
6. For an existing database created from an earlier schema, run `database/migrations/20261118_add_api_max_level.sql` once in phpMyAdmin.
7. Run `composer install`.
8. Run `composer dump-autoload`.
9. Run `npm install`.
10. Run `npm run build:css`.
11. Sign in and connect a verified Clash of Clans player account.

Never commit `.env`, expose the developer API key in JavaScript, or upload the project root as a public document root.

## Supercell API integration

The developer API key is stored server-side in `.env` as `SUPERCELL_API_KEY`. Its allowed IP address must match the public IP seen by Supercell.

The player API provides profile information and item levels in the `troops`, `heroes`, `spells`, and `heroEquipment` collections. Pets are included in the `troops` collection and are classified by a maintained name list. Builder Base items are distinguished using the API item's `village` property.

The API's `maxLevel` is stored in `account_items.api_max_level`, not the global `upgrade_items` catalog, because the maximum available level can differ between player accounts at different Town Hall levels.

The player endpoint does not provide the complete defensive building, trap, or building levels. These are tracked manually in xinClash.

## Upgrade tracker

- API-managed: troops, super troops, siege machines, spells, heroes, pets, and hero equipment.
- Manual: defenses, traps, and buildings.
- A level increase detected during a successful API sync is written to `upgrade_history`.
- Upgrade plans are automatically marked completed when a synchronized or manually recorded level reaches the target level.
- Account-specific data queries always scope by the authenticated `user_id` and `coc_account_id`.

The manual catalog is initialized automatically when a manual category is opened. It can also be initialized from the CLI:

`C:\xampp\php\php.exe bin/seed-upgrade-items.php`

## Automatic synchronization

The CLI runner is `cron/sync-accounts.php`. It synchronizes verified active accounts whose `next_sync_at` is due. A successful sync schedules the next run six hours later; a failed sync retries after thirty minutes.

For Windows Task Scheduler, create a task that runs every 15 minutes:

- Program: `C:\xampp\php\php.exe`
- Arguments: `C:\xampp\htdocs\xinClash\cron\sync-accounts.php`
- Start in: `C:\xampp\htdocs\xinClash`

The runner uses a process lock to avoid overlapping executions. It must not be exposed as a public web route.

## Main routes

- `/dashboard`
- `/accounts`
- `/upgrades`
- `/upgrades/defenses`
- `/upgrades/traps`
- `/upgrades/buildings`
- `/upgrades/troops`
- `/upgrades/spells`
- `/upgrades/heroes`
- `/upgrades/pets`
- `/upgrades/equipment`
- `/upgrades/planner`

## Security notes

- Keep `.env` out of Git.
- Keep the Supercell developer API key server-side.
- Player ownership tokens are used only for verification and are not persisted.
- Every account-specific read and write must use the authenticated user ID and verify account ownership.
- Use HTTPS in production.
- Keep Apache error logs private and disable debug output in production.