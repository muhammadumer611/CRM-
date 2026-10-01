-- Allow reservation creation without CNIC or district while preserving existing values.
ALTER TABLE reservations
    MODIFY COLUMN cnic VARCHAR(15) DEFAULT NULL,
    MODIFY COLUMN district VARCHAR(80) DEFAULT NULL;