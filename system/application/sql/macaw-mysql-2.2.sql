ALTER TABLE item DROP ia_ready_images;
ALTER TABLE item ADD ia_ready_images BOOL not null default false;
