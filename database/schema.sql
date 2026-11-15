CREATE DATABASE IF NOT EXISTS xinclash_db CHARACTER
SET
    utf8mb4 COLLATE utf8mb4_unicode_ci;

USE xinclash_db;

CREATE TABLE
    users (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        first_name VARCHAR(100) NOT NULL,
        middle_initial VARCHAR(5) NULL,
        last_name VARCHAR(100) NOT NULL,
        birthdate DATE NOT NULL,
        email VARCHAR(254) NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        role ENUM ('admin', 'member') NOT NULL DEFAULT 'member',
        status ENUM ('active', 'pending', 'suspended') NOT NULL DEFAULT 'pending',
        email_verified_at DATETIME NULL,
        last_login_at DATETIME NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_users_email (email),
        KEY idx_users_status (status),
        KEY idx_users_role (role)
    ) ENGINE = InnoDB;

CREATE TABLE
    registration_invites (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        token_hash CHAR(64) NOT NULL,
        created_by_user_id BIGINT UNSIGNED NULL,
        max_uses SMALLINT UNSIGNED NOT NULL DEFAULT 1,
        uses_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
        expires_at DATETIME NULL,
        revoked_at DATETIME NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_registration_invites_token (token_hash),
        KEY idx_registration_invites_creator (created_by_user_id),
        CONSTRAINT fk_invites_creator FOREIGN KEY (created_by_user_id) REFERENCES users (id) ON DELETE SET NULL,
        CONSTRAINT chk_invites_usage CHECK (uses_count <= max_uses)
    ) ENGINE = InnoDB;

CREATE TABLE
    coc_accounts (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NOT NULL,
        player_tag VARCHAR(15) NOT NULL,
        player_name VARCHAR(100) NULL,
        town_hall_level TINYINT UNSIGNED NULL,
        exp_level SMALLINT UNSIGNED NULL,
        trophies INT UNSIGNED NULL,
        best_trophies INT UNSIGNED NULL,
        is_verified BOOLEAN NOT NULL DEFAULT FALSE,
        verified_at DATETIME NULL,
        is_active BOOLEAN NOT NULL DEFAULT TRUE,
        last_synced_at DATETIME NULL,
        next_sync_at DATETIME NULL,
        sync_status ENUM ('never', 'success', 'failed') NOT NULL DEFAULT 'never',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_user_player_tag (user_id, player_tag),
        UNIQUE KEY uq_coc_account_owner (id, user_id),
        KEY idx_coc_accounts_user (user_id),
        KEY idx_coc_accounts_sync (is_active, next_sync_at),
        KEY idx_coc_accounts_player_tag (player_tag),
        CONSTRAINT fk_coc_accounts_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
        CONSTRAINT chk_coc_town_hall CHECK (
            town_hall_level IS NULL
            OR town_hall_level BETWEEN 1 AND 20
        ),
        CONSTRAINT chk_coc_verified CHECK (
            (
                is_verified = FALSE
                AND verified_at IS NULL
            )
            OR (
                is_verified = TRUE
                AND verified_at IS NOT NULL
            )
        )
    ) ENGINE = InnoDB;

CREATE TABLE
    upgrade_items (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        item_key VARCHAR(100) NOT NULL,
        item_name VARCHAR(120) NOT NULL,
        category ENUM (
            'defense',
            'trap',
            'building',
            'troop',
            'super_troop',
            'spell',
            'hero',
            'pet',
            'equipment'
        ) NOT NULL,
        tracking_source ENUM ('api', 'manual') NOT NULL DEFAULT 'api',
        max_level TINYINT UNSIGNED NULL,
        min_town_hall TINYINT UNSIGNED NULL,
        max_town_hall TINYINT UNSIGNED NULL,
        is_active BOOLEAN NOT NULL DEFAULT TRUE,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_upgrade_items_key (item_key),
        KEY idx_upgrade_items_category (category, is_active),
        KEY idx_upgrade_items_source (tracking_source),
        CONSTRAINT chk_upgrade_item_levels CHECK (
            max_level IS NULL
            OR max_level >= 1
        ),
        CONSTRAINT chk_upgrade_item_town_hall CHECK (
            min_town_hall IS NULL
            OR max_town_hall IS NULL
            OR min_town_hall <= max_town_hall
        )
    ) ENGINE = InnoDB;

CREATE TABLE
    player_snapshots (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NOT NULL,
        coc_account_id BIGINT UNSIGNED NOT NULL,
        snapshot_type ENUM ('full', 'partial') NOT NULL DEFAULT 'full',
        player_data JSON NOT NULL,
        fetched_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_snapshots_account_date (coc_account_id, fetched_at),
        KEY idx_snapshots_owner_account (coc_account_id, user_id),
        CONSTRAINT fk_snapshots_account_owner FOREIGN KEY (coc_account_id, user_id) REFERENCES coc_accounts (id, user_id) ON DELETE CASCADE
    ) ENGINE = InnoDB;

CREATE TABLE
    account_items (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NOT NULL,
        coc_account_id BIGINT UNSIGNED NOT NULL,
        upgrade_item_id BIGINT UNSIGNED NOT NULL,
        current_level TINYINT UNSIGNED NOT NULL DEFAULT 0,
        target_level TINYINT UNSIGNED NULL,
        data_source ENUM ('api', 'manual') NOT NULL DEFAULT 'api',
        last_synced_at DATETIME NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_account_item (coc_account_id, upgrade_item_id),
        UNIQUE KEY uq_account_item_owner (id, user_id, coc_account_id),
        KEY idx_account_items_owner (user_id, coc_account_id),
        KEY idx_account_items_catalog (upgrade_item_id),
        CONSTRAINT fk_account_items_account_owner FOREIGN KEY (coc_account_id, user_id) REFERENCES coc_accounts (id, user_id) ON DELETE CASCADE,
        CONSTRAINT fk_account_items_catalog FOREIGN KEY (upgrade_item_id) REFERENCES upgrade_items (id) ON DELETE RESTRICT,
        CONSTRAINT chk_account_item_target CHECK (
            target_level IS NULL
            OR target_level >= current_level
        )
    ) ENGINE = InnoDB;

CREATE TABLE
    upgrade_plans (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NOT NULL,
        coc_account_id BIGINT UNSIGNED NOT NULL,
        upgrade_item_id BIGINT UNSIGNED NOT NULL,
        from_level TINYINT UNSIGNED NOT NULL,
        target_level TINYINT UNSIGNED NOT NULL,
        status ENUM (
            'planned',
            'in_progress',
            'completed',
            'cancelled'
        ) NOT NULL DEFAULT 'planned',
        priority ENUM ('low', 'medium', 'high') NOT NULL DEFAULT 'medium',
        started_at DATETIME NULL,
        estimated_completion_at DATETIME NULL,
        completed_at DATETIME NULL,
        notes VARCHAR(1000) NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_upgrade_plans_account (coc_account_id, status),
        KEY idx_upgrade_plans_owner (user_id, coc_account_id),
        KEY idx_upgrade_plans_completion (status, estimated_completion_at),
        CONSTRAINT fk_upgrade_plans_account_owner FOREIGN KEY (coc_account_id, user_id) REFERENCES coc_accounts (id, user_id) ON DELETE CASCADE,
        CONSTRAINT fk_upgrade_plans_item FOREIGN KEY (upgrade_item_id) REFERENCES upgrade_items (id) ON DELETE RESTRICT,
        CONSTRAINT chk_upgrade_plan_levels CHECK (target_level > from_level),
        CONSTRAINT chk_upgrade_plan_dates CHECK (
            completed_at IS NULL
            OR status = 'completed'
        )
    ) ENGINE = InnoDB;

CREATE TABLE
    upgrade_history (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NOT NULL,
        coc_account_id BIGINT UNSIGNED NOT NULL,
        upgrade_item_id BIGINT UNSIGNED NOT NULL,
        from_level TINYINT UNSIGNED NOT NULL,
        to_level TINYINT UNSIGNED NOT NULL,
        completion_source ENUM ('manual', 'api') NOT NULL DEFAULT 'manual',
        completed_at DATETIME NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_upgrade_history_account (coc_account_id, completed_at),
        KEY idx_upgrade_history_owner (user_id, coc_account_id),
        CONSTRAINT fk_upgrade_history_account_owner FOREIGN KEY (coc_account_id, user_id) REFERENCES coc_accounts (id, user_id) ON DELETE CASCADE,
        CONSTRAINT fk_upgrade_history_item FOREIGN KEY (upgrade_item_id) REFERENCES upgrade_items (id) ON DELETE RESTRICT,
        CONSTRAINT chk_upgrade_history_levels CHECK (to_level > from_level)
    ) ENGINE = InnoDB;

CREATE TABLE
    sync_logs (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NOT NULL,
        coc_account_id BIGINT UNSIGNED NOT NULL,
        sync_type ENUM ('manual', 'automatic') NOT NULL,
        status ENUM ('success', 'partial', 'failed') NOT NULL,
        started_at DATETIME NOT NULL,
        finished_at DATETIME NULL,
        http_status SMALLINT UNSIGNED NULL,
        items_updated INT UNSIGNED NOT NULL DEFAULT 0,
        error_code VARCHAR(100) NULL,
        error_message VARCHAR(1000) NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_sync_logs_account_date (coc_account_id, created_at),
        KEY idx_sync_logs_owner (user_id, coc_account_id),
        CONSTRAINT fk_sync_logs_account_owner FOREIGN KEY (coc_account_id, user_id) REFERENCES coc_accounts (id, user_id) ON DELETE CASCADE
    ) ENGINE = InnoDB;

CREATE TABLE
    password_reset_tokens (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NOT NULL,
        token_hash CHAR(64) NOT NULL,
        expires_at DATETIME NOT NULL,
        used_at DATETIME NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_password_reset_token (token_hash),
        KEY idx_password_reset_user (user_id),
        CONSTRAINT fk_password_reset_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
    ) ENGINE = InnoDB;

CREATE TABLE
    login_attempts (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(254) NOT NULL,
        ip_hash CHAR(64) NOT NULL,
        attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        was_successful BOOLEAN NOT NULL DEFAULT FALSE,
        KEY idx_login_email_time (email, attempted_at),
        KEY idx_login_ip_time (ip_hash, attempted_at)
    ) ENGINE = InnoDB;