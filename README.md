# xinClash

Private, multi-user Clash of Clans upgrade tracker.

## Requirements

- PHP 8.3+
- MySQL 8.0.16+
- Composer
- Node.js and npm
- Apache (XAMPP is suitable for local development)

## Local setup

1. Place this project at `C:\xampp\htdocs\xinClash`.
2. Keep Apache's public document root pointed at `C:\xampp\htdocs\xinClash\public`.
3. Copy `.env.example` to `.env` and set your local database credentials.
4. Run `composer install`.
5. Create the `xinclash_db` database and import `database/schema.sql` using phpMyAdmin or the MySQL CLI.
6. Open the project URL in your browser. The root page and `/health` endpoint confirm that the foundation is running.

The Supercell API key must remain server-side in `.env`. Never commit `.env` or expose the project root as the Apache document root.
