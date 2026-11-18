# Database migrations

For an existing xinClash database created before Milestone 5, run:

`20261118_add_api_max_level.sql`

This adds `account_items.api_max_level`, which stores the maximum level currently available to a specific player account as returned by Supercell.

For a fresh database, import the updated `database/schema.sql` instead. Do not run the migration after importing the updated schema.
