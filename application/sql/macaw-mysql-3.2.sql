-- Macaw MySQL migration 3.2
-- Adds TOTP (Time-Based One-Time Password) fields to the account table.

ALTER TABLE account ADD COLUMN totp_secret VARCHAR(64) NULL DEFAULT NULL;
ALTER TABLE account ADD COLUMN totp_enabled TINYINT(1) NOT NULL DEFAULT 0;
