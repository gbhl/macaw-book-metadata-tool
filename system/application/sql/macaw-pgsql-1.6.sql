-- Required Version: 1.6.0
alter table metadata alter page_id drop not null;
alter table metadata add value_large text;
alter table page drop biblio_id;

create index idx_metadata_fieldname on metadata (fieldname);

insert into metadata select item_id, null, 'title', 1,  title from biblio;
insert into metadata select item_id, null, 'author', 1, author from biblio;
insert into metadata select item_id, null, 'call_number', 1, call_number from biblio;
insert into metadata select item_id, null, 'copyright', 1, copyright from biblio;
insert into metadata select item_id, null, 'location', 1, location from biblio;
insert into metadata select item_id, null, 'volume', 1, volume from biblio;
insert into metadata select id, null, 'sponsor', 1, sponsor from item;
insert into metadata select item_id, null, 'marc_xml', 1, null, marc_xml from biblio;
insert into metadata select item_id, null, 'mods_xml', 1, null, mods_xml from biblio;
insert into metadata select item_id, null, 'collections', 1, 'a:1:{i:0;s:'||length(collection)||':"'||collection||'";}'  from collection;


alter table item add date_completed timestamp with time zone;
alter table item add date_export_start timestamp with time zone;
update item set status_code = 'completed', date_completed = date_harvested where status_code = 'harvested';
update item set status_code = 'completed', date_completed = date_harvested where status_code = 'archived';

create table item_export_status (
	item_id int references item(id),
	export_module varchar(64),
	status_code varchar(32),
	date timestamp with time zone
);

insert into item_export_status select id, 'Internet_archive', 'uploaded', date_uploaded from item where date_uploaded is not null and date_verified is null and date_completed is null;
insert into item_export_status select id, 'Internet_archive', 'verified', date_verified from item where date_verified is not null and date_completed is null;
insert into item_export_status select id, 'Internet_archive', 'completed', date_harvested from item where date_completed is not null;

drop view harvested;
drop view pending_upload;
drop view upload_errors;
drop view uploaded;
drop view verified;
drop view archived;
drop view errors;
drop view bhl_pages_per_month;

alter table item drop date_uploaded;
alter table item drop date_verified;
alter table item drop date_harvested;


alter table item drop sponsor;
drop table biblio;
drop table collection;

drop table custom_galaxy_of_images;

create table settings (name varchar(64), value varchar(64));


create view pending_upload as
    SELECT i.id, c.identifier, i.date_review_end, i.status_code, i.barcode,
        (SELECT count(*) AS count FROM page p WHERE p.item_id = i.id) AS pages,
        (SELECT value from metadata m where m.page_id is null and fieldname = 'collections' and m.item_id = i.id) AS collection
    FROM item i
    LEFT JOIN custom_internet_archive c ON i.id = c.item_id
    WHERE i.status_code::text = 'reviewed'::text
    ORDER BY i.date_review_end;

create view uploaded as
    SELECT i.id, c.identifier, s.date as date_uploaded, i.status_code, i.barcode,
        (SELECT count(*) AS count FROM page p WHERE p.item_id = i.id) AS pages,
        (SELECT value from metadata m where m.page_id is null and fieldname = 'collections' and m.item_id = i.id) AS collection
    FROM item i
    INNER JOIN (select * from item_export_status where export_module = 'Internet_archive' and status_code = 'uploaded') s ON i.id = s.item_id
    LEFT JOIN custom_internet_archive c ON i.id = c.item_id
    where s.item_id is not null
    ORDER BY s.date;

create view verified as
    SELECT i.id, c.identifier, s.date as date_uploaded, i.status_code, i.barcode,
        (SELECT count(*) AS count FROM page p WHERE p.item_id = i.id) AS pages,
        (SELECT value from metadata m where m.page_id is null and fieldname = 'collections' and m.item_id = i.id) AS collection
    FROM item i
    INNER JOIN (select * from item_export_status where export_module = 'Internet_archive' and status_code = 'verified') s ON i.id = s.item_id
    LEFT JOIN custom_internet_archive c ON i.id = c.item_id
    ORDER BY s.date;

create view harvested as
    SELECT i.id, c.identifier, s.date as date_uploaded, i.status_code, i.barcode,
        (SELECT count(*) AS count FROM page p WHERE p.item_id = i.id) AS pages,
        (SELECT value from metadata m where m.page_id is null and fieldname = 'collections' and m.item_id = i.id) AS collection
    FROM item i
    INNER JOIN (select * from item_export_status where export_module = 'Internet_archive' and status_code = 'completed') s ON i.id = s.item_id
    LEFT JOIN custom_internet_archive c ON i.id = c.item_id
    ORDER BY s.date;

create view completed as
    SELECT i.id, c.identifier, i.date_completed, i.barcode,
        (SELECT count(*) AS count FROM page p WHERE p.item_id = i.id) AS pages,
        (SELECT value from metadata m where m.page_id is null and fieldname = 'collections' and m.item_id = i.id) AS collection
    FROM item i
    LEFT JOIN custom_internet_archive c ON i.id = c.item_id
    WHERE i.status_code::text = 'archived'::text
    ORDER BY i.date_completed;

create view archived as
    SELECT i.id, c.identifier, i.date_archived, i.barcode,
        (SELECT count(*) AS count FROM page p WHERE p.item_id = i.id) AS pages,
        (SELECT value from metadata m where m.page_id is null and fieldname = 'collections' and m.item_id = i.id) AS collection
    FROM item i
    LEFT JOIN custom_internet_archive c ON i.id = c.item_id
    WHERE i.status_code::text = 'archived'::text
    ORDER BY i.date_archived;

create view errors as
    SELECT i.id, c.identifier, i.barcode,
        (SELECT count(*) AS count FROM page p WHERE p.item_id = i.id) AS pages,
        (SELECT value from metadata m where m.page_id is null and fieldname = 'collections' and m.item_id = i.id) AS collection
    FROM item i
    LEFT JOIN custom_internet_archive c ON i.id = c.item_id
    WHERE i.status_code::text = 'error'::text
    ORDER BY i.id;



create view public.bhl_pages_per_month as
 SELECT to_char(date_trunc('month'::text, x.date_scanning_start), 'Month YYYY'::text) AS month, sum(x.page_count) AS page_count
   FROM ( SELECT i.id, count(p.id) AS page_count, max(i.date_scanning_start) AS date_scanning_start
           FROM item i
      JOIN (select * from metadata where fieldname = 'collections') c ON i.id = c.item_id
   JOIN page p ON i.id = p.item_id
  WHERE c.value like '%bhl%'
  GROUP BY i.id) x
  GROUP BY date_trunc('month'::text, x.date_scanning_start)
  ORDER BY date_trunc('month'::text, x.date_scanning_start);

