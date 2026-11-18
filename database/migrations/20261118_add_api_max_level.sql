ALTER TABLE account_items
ADD COLUMN api_max_level TINYINT UNSIGNED NULL AFTER current_level;

ALTER TABLE account_items
ADD CONSTRAINT chk_account_item_api_max
CHECK (api_max_level IS NULL OR api_max_level >= 1);
