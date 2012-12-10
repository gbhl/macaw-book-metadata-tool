-- Required Version: 1.3.105
ALTER TABLE item ADD missing_pages boolean;
ALTER TABLE item ADD pages_found integer;
ALTER TABLE item ADD pages_scanned integer;
ALTER TABLE item ADD scan_time integer;

ALTER TABLE page ADD is_missing boolean;

