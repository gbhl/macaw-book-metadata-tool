CREATE TABLE password_reset_tokens (
    id integer NOT NULL,
    account_id integer NOT NULL,
    token character varying(64) NOT NULL,
    created timestamp without time zone DEFAULT now(),
    expires timestamp without time zone,
    used timestamp without time zone
);
