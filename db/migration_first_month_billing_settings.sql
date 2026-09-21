-- First-month billing settings. Safe to run on an existing InnoDB database.
CREATE TABLE IF NOT EXISTS hostel_settings (
  setting_key varchar(80) NOT NULL,
  setting_value varchar(255) NOT NULL,
  updated_by_admin int(11) DEFAULT NULL,
  updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (setting_key),
  KEY idx_hostel_settings_admin (updated_by_admin),
  CONSTRAINT fk_hostel_settings_admin FOREIGN KEY (updated_by_admin) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO hostel_settings (setting_key, setting_value) VALUES
('late_joining_enabled', '1'),
('late_joining_cutoff_day', '10'),
('proration_method', 'calendar_days'),
('include_joining_day', '1'),
('rounding_method', 'nearest_rupee'),
('default_due_day', '10'),
('manual_first_month_discount_enabled', '1'),
('maximum_first_month_discount', '100')
ON DUPLICATE KEY UPDATE setting_key = VALUES(setting_key);

-- Before normalizing existing records, inspect duplicates using the canonical form:
-- SELECT REPLACE(REPLACE(cnic, '-', ''), ' ', '') AS canonical_cnic, COUNT(*) AS total
-- FROM students GROUP BY canonical_cnic HAVING COUNT(*) > 1;
-- Resolve any duplicate identities manually, then normalize in a reviewed deployment step.
