CREATE TABLE password_reset_tokens (
    id int(11) auto_increment NOT NULL,
    account_id int(11) NOT NULL,
    token varchar(64) NOT NULL,
    created timestamp DEFAULT CURRENT_TIMESTAMP,
    expires timestamp,
    used timestamp NULL,
    PRIMARY KEY(id),
    FOREIGN KEY(account_id) REFERENCES account(id) ON DELETE CASCADE,
    UNIQUE KEY(token)
) ENGINE=InnoDB CHARACTER SET=utf8 COLLATE=utf8_unicode_ci;
