-- Remove the retired Notifications feature from an existing installation.
-- Take a database backup before applying this destructive schema change.
DROP TABLE IF EXISTS notifications;