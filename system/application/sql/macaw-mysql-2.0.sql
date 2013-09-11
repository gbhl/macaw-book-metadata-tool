ALTER TABLE settings ADD CONSTRAINT settings_name_key_unique UNIQUE (name);
INSERT INTO settings values ('installed', '1');
