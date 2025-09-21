ALTER TABLE settings DROP CONSTRAINT IF EXISTS settings_name_key_unique;
ALTER TABLE settings ADD CONSTRAINT settings_name_key_unique UNIQUE (name);
INSERT INTO settings values ('installed', '1') ON CONFLICT DO NOTHING;

