SET default_tablespace = '';
SET default_with_oids = false;

CREATE SEQUENCE organization_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

CREATE SEQUENCE account_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

CREATE SEQUENCE item_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

CREATE SEQUENCE page_id_seq
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

CREATE TABLE account (
    id integer NOT NULL,
    username character varying(32) NOT NULL,
    password character varying(64),
    org_id integer, 
    last_login timestamp without time zone,
    created timestamp without time zone,
    modified timestamp without time zone,
    widgets text DEFAULT '[["summary","perday"],["disk","pages"]]'::text,
    full_name character varying(128),
    email character varying(128),
    terms_conditions timestamp without time zone
);

CREATE TABLE permission (
    username character varying(32),
    permission character varying(32)
);

CREATE TABLE item (
    id integer NOT NULL,
    barcode character varying(32),
    status_code character varying(32),
    missing_pages boolean,
    pages_found integer,
    pages_scanned integer,
    scan_time integer,
    needs_qa boolean,
    org_id integer,
    total_mbytes integer default 0,
    date_created timestamp with time zone,
    date_scanning_start timestamp with time zone,
    date_scanning_end timestamp with time zone,
    date_review_start timestamp with time zone,
    date_review_end timestamp with time zone,
    date_qa_start timestamp with time zone,
    date_qa_end timestamp with time zone,
    date_export_start timestamp with time zone,
    date_completed timestamp with time zone,
    date_archived timestamp with time zone,
    ia_ready_images boolean DEFAULT false NOT NULL,
    page_progression varchar(3) default 'ltr'
);

CREATE TABLE page (
    id integer NOT NULL,
    item_id integer NOT NULL,
    sequence_number integer,
    filebase character varying(128) NOT NULL,
    status character varying(16) NOT NULL,
    created timestamp with time zone DEFAULT now() NOT NULL,
    bytes integer,
    extension character varying(16),
    width integer,
    height integer,
    is_missing boolean
);

CREATE TABLE metadata (
    item_id integer NOT NULL,
    page_id integer,
    fieldname character varying(32),
    counter integer DEFAULT 1,
    value character varying(1024),
    value_large text
);

CREATE TABLE session (
    session_id character varying(40) DEFAULT '0'::character varying NOT NULL,
    ip_address character varying(16) DEFAULT '0'::character varying NOT NULL,
    user_agent character varying(120) NOT NULL,
    last_activity bigint DEFAULT 0 NOT NULL,
    user_data text
);

CREATE TABLE logging (
    date timestamp with time zone DEFAULT now() NOT NULL,
    statistic character varying(16),
    value bigint,
    value_text character varying(128)
);

CREATE TABLE item_export_status (
    item_id integer,
    export_module character varying(64),
    status_code character varying(32),
    date timestamp with time zone
);

CREATE TABLE settings (
	name varchar(64), 
	value varchar(64)
);

-- ALTER SEQUENCE account_id_seq OWNED BY account.id;
-- ALTER SEQUENCE item_id_seq OWNED BY item.id;
-- ALTER SEQUENCE page_id_seq OWNED BY page.id;

ALTER TABLE organization ALTER COLUMN id SET DEFAULT nextval('organization_id_seq'::regclass);
ALTER TABLE account ALTER COLUMN id SET DEFAULT nextval('account_id_seq'::regclass);
ALTER TABLE item ALTER COLUMN id SET DEFAULT nextval('item_id_seq'::regclass);
ALTER TABLE page ALTER COLUMN id SET DEFAULT nextval('page_id_seq'::regclass);

ALTER TABLE ONLY organization ADD CONSTRAINT organization_pkey PRIMARY KEY (id);
ALTER TABLE ONLY account ADD CONSTRAINT account_pkey PRIMARY KEY (id);
ALTER TABLE ONLY item ADD CONSTRAINT item_pkey PRIMARY KEY (id);
ALTER TABLE ONLY page ADD CONSTRAINT page_pkey PRIMARY KEY (id);

CREATE UNIQUE INDEX idx_account_username ON account USING btree (username);
CREATE UNIQUE INDEX idx_item_barcode ON item USING btree (barcode);
CREATE UNIQUE INDEX idx_permission_user_permission ON permission USING btree (username, permission);
CREATE UNIQUE INDEX idx_metadata_all ON metadata USING btree (item_id, page_id, fieldname, counter);
CREATE UNIQUE INDEX idx_logging_date_statistic ON logging USING btree (date, statistic);
CREATE INDEX idx_metadata_fieldname ON metadata USING btree (fieldname);

ALTER TABLE ONLY account ADD CONSTRAINT account_org_id_fkey FOREIGN KEY (org_id) REFERENCES organization(id);
ALTER TABLE ONLY page ADD CONSTRAINT page_item_id_fkey FOREIGN KEY (item_id) REFERENCES item(id);
ALTER TABLE ONLY permission ADD CONSTRAINT permission_username_fkey FOREIGN KEY (username) REFERENCES account(username);
ALTER TABLE ONLY metadata ADD CONSTRAINT metadata_item_fkey FOREIGN KEY (item_id) REFERENCES item(id);
ALTER TABLE ONLY metadata ADD CONSTRAINT metadata_page_fkey FOREIGN KEY (page_id) REFERENCES page(id);
ALTER TABLE ONLY item_export_status ADD CONSTRAINT item_export_status_item_id_fkey FOREIGN KEY (item_id) REFERENCES item(id);
ALTER TABLE settings ADD CONSTRAINT settings_name_key_unique UNIQUE (name);

-- CREATE FUNCTION xpath_list(text, text) RETURNS text
--     LANGUAGE sql IMMUTABLE STRICT
--     AS $_$SELECT xpath_list($1,$2,',')$_$;
-- 
-- CREATE FUNCTION xpath_nodeset(text, text) RETURNS text
--     LANGUAGE sql IMMUTABLE STRICT
--     AS $_$SELECT xpath_nodeset($1,$2,'','')$_$;
-- 
-- CREATE FUNCTION xpath_nodeset(text, text, text) RETURNS text
--     LANGUAGE sql IMMUTABLE STRICT
--     AS $_$SELECT xpath_nodeset($1,$2,'',$3)$_$;


-- DATA --

INSERT INTO organization (name, created) VALUES ('Default', now());

INSERT INTO account VALUES (1, 'admin', null, 1, null, now());

INSERT INTO permission VALUES ('admin', 'admin');
INSERT INTO permission VALUES ('admin', 'scan');
INSERT INTO settings VALUES ('version', '2.8');
INSERT INTO settings values ('installed', '1');

SELECT pg_catalog.setval('account_id_seq', 1, true);
