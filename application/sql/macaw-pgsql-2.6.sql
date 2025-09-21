ALTER TABLE item ADD COLUMN IF NOT EXISTS date_qa_start timestamp with time zone;
ALTER TABLE item ADD COLUMN IF NOT EXISTS date_qa_end timestamp with time zone;
