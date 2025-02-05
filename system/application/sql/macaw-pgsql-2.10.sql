-- Cumulative update? 

INSERT INTO settings VALUES ('version', '2.10');
ALTER TABLE account MODIFY widgets varchar(255) DEFAULT '[["summary","perday"],["disk","pages"]]';
CREATE INDEX idx_account_username ON account (username);
CREATE INDEX idx_metadata_fieldname ON metadata (fieldname);
CREATE INDEX idx_metadata_itemid_pageid ON metadata (itemid, pageid);
CREATE INDEX idx_metadata_itemid_pageid_fieldname_counter ON metadata (itemid, pageid, fieldname, counter);
CREATE INDEX idx_logging_date_statistic ON logging (date, statistic);
CREATE INDEX idx_permission_username_permission ON permission (username, permission);
CREATE INDEX idx_item_barcode ON item (barcode);
ALTER TABLE session MODIFY user_agent VARCHAR(120) NOT NULL;
ALTER TABLE item ADD needs_qa boolean;
ALTER TABLE account ADD email varchar(128);
DROP TABLE collection;
ALTER TABLE metadata MODIFY value VARCHAR(1024);