CREATE TABLE session2 (
    "id" varchar(128) NOT NULL,
    "ip_address" varchar(45) NOT NULL,
    "timestamp" bigint DEFAULT 0 NOT NULL,
    "data" text DEFAULT '' NOT NULL
);
