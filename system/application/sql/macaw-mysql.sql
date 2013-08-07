# Converted with pg2mysql-1.9
# Converted on Wed, 17 Jul 2013 13:58:51 -0400
# Lightbox Technologies Inc. http://www.lightbox.ca

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone="+00:00";

CREATE TABLE account (
    id int(11) auto_increment NOT NULL,
    username varchar(32) NOT NULL,
    password varchar(64),
    org_id int(11),
    last_login timestamp,
    created timestamp,
    modified timestamp,
    widgets varchar(255) DEFAULT '[[`summary`,`perday`],[`disk`,`pages`]]',
    full_name varchar(128),
    email varchar(128),
    organization int(11)
, PRIMARY KEY(`id`)
) TYPE=InnoDB;

CREATE TABLE item (
    id int(11) auto_increment NOT NULL,
    barcode varchar(32),
    status_code varchar(32),
    missing_pages bool,
    pages_found int(11),
    pages_scanned int(11),
    scan_time int(11),
    needs_qa bool,
    org_id int(11),
    date_created timestamp,
    date_scanning_start timestamp,
    date_scanning_end timestamp,
    date_review_start timestamp,
    date_review_end timestamp,
    date_export_start timestamp,
    date_completed timestamp,
    date_archived timestamp
, PRIMARY KEY(`id`)
) TYPE=InnoDB;

CREATE TABLE item_export_status (
    item_id int(11),
    export_module varchar(64),
    status_code varchar(32),
    date timestamp
) TYPE=InnoDB;

CREATE TABLE logging (
    date timestamp DEFAULT CURRENT_TIMESTAMP NOT NULL,
    statistic varchar(16),
    value bigint,
    value_text varchar(128)
) TYPE=InnoDB;

CREATE TABLE metadata (
    item_id int(11) NOT NULL,
    page_id int(11),
    fieldname varchar(32),
    counter int(11) DEFAULT 1,
    value text,
    value_large text
) TYPE=InnoDB;

CREATE TABLE organization (
    id int(11) auto_increment NOT NULL,
    name varchar(100),
    person varchar(100),
    email varchar(100),
    phone varchar(30),
    address varchar(100),
    address2 varchar(100),
    city varchar(100),
    state varchar(30),
    postal varchar(30),
    country varchar(50),
    created timestamp,
    modified timestamp
, PRIMARY KEY(`id`)
) TYPE=InnoDB;

CREATE TABLE page (
    id int(11) auto_increment NOT NULL,
    item_id int(11) NOT NULL,
    sequence_number int(11),
    filebase varchar(64) NOT NULL,
    `status` varchar(16) NOT NULL,
    created timestamp DEFAULT CURRENT_TIMESTAMP NOT NULL,
    bytes int(11),
    extension varchar(16),
    width int(11),
    height int(11),
    is_missing bool
, PRIMARY KEY(`id`)
) TYPE=InnoDB;

CREATE TABLE permission (
    username varchar(32),
    permission varchar(32)
) TYPE=InnoDB;

CREATE TABLE session (
    session_id varchar(40) DEFAULT '0',
    ip_address varchar(16) DEFAULT '0',
    user_agent varchar(50) NOT NULL,
    last_activity bigint DEFAULT 0 NOT NULL,
    user_data text
) TYPE=InnoDB;

CREATE TABLE settings (
    name varchar(64),
    value varchar(64)
) TYPE=InnoDB;

INSERT INTO account (id, username, password, org_id, last_login, created, modified, widgets, full_name, email, organization) VALUES
('1','admin',NULL,'1',NULL,'2013-07-17 13:54:38.200205',NULL,'[["summary","perday"],["disk","pages"]]',NULL,NULL,'\N');

INSERT INTO organization (id, name, person, email, phone, address, address2, city, state, postal, country, created, modified) VALUES
('1','Default',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2013-07-17 13:54:38.198841','\N');

INSERT INTO permission (username, permission) VALUES
('admin','admin'),
('admin','scan');

INSERT INTO settings (name, value) VALUES
('version','1.7');

ALTER TABLE account
    ADD CONSTRAINT account_pkey PRIMARY KEY (id);
ALTER TABLE item
    ADD CONSTRAINT item_pkey PRIMARY KEY (id);
ALTER TABLE organization
    ADD CONSTRAINT organization_pkey PRIMARY KEY (id);
ALTER TABLE page
    ADD CONSTRAINT page_pkey PRIMARY KEY (id);
ALTER TABLE `metadata` ADD INDEX ( fieldname ) ;
