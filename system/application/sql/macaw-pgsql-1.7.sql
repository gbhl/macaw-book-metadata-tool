CREATE SEQUENCE organization_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

create table organization (
    id integer NOT NULL,
    name VARCHAR(100),
    person VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(30),
    address VARCHAR(100),
    address2 VARCHAR(100),
    city VARCHAR(100),
    state VARCHAR(30),
    postal VARCHAR(30),
    country VARCHAR(50),
    created TIMESTAMP WITHOUT TIME ZONE,
    modified TIMESTAMP WITHOUT TIME ZONE
);

ALTER TABLE organization ALTER COLUMN id SET DEFAULT nextval('organization_id_seq'::regclass);
ALTER TABLE ONLY organization ADD CONSTRAINT organization_pkey PRIMARY KEY (id);
INSERT INTO organization (name) VALUES ('Default');

ALTER TABLE account ADD org_id INTEGER;
UPDATE account SET org_id = 1;

ALTER TABLE item ADD org_id INTEGER;
UPDATE item SET org_id = 1;

ALTER TABLE ONLY account ADD CONSTRAINT account_org_id_fkey FOREIGN KEY (org_id) REFERENCES organization(id);

