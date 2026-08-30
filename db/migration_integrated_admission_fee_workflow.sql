-- Integrated Admission + Fee Ledger Workflow Migration
-- Safe for existing production data; adds missing charge metadata and payment allocation support without dropping current tables.

ALTER TABLE fee_records
  ADD COLUMN IF NOT EXISTS charge_type VARCHAR(30) NOT NULL DEFAULT 'MONTHLY_FEE' AFTER status,
  ADD INDEX IF NOT EXISTS idx_fee_records_charge_type (charge_type);

ALTER TABLE fee_payments
  ADD COLUMN IF NOT EXISTS receipt_number VARCHAR(50) NULL AFTER invoice_id,
  ADD UNIQUE INDEX IF NOT EXISTS uk_fee_payments_receipt_number (receipt_number);

UPDATE fee_payments SET receipt_number = CONCAT('PAY-', DATE_FORMAT(payment_date, '%Y'), '-', LPAD(id, 6, '0')) WHERE receipt_number IS NULL OR receipt_number = '';

ALTER TABLE fee_payments
  MODIFY receipt_number VARCHAR(50) NOT NULL;

CREATE TABLE IF NOT EXISTS payment_allocations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_id INT NOT NULL,
    invoice_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    allocated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_payment_allocations_payment FOREIGN KEY (payment_id) REFERENCES fee_payments(id) ON DELETE CASCADE,
    CONSTRAINT fk_payment_allocations_invoice FOREIGN KEY (invoice_id) REFERENCES fee_records(id) ON DELETE CASCADE,
    KEY idx_payment_allocations_payment (payment_id),
    KEY idx_payment_allocations_invoice (invoice_id)
);

-- Optional: if you want explicit charge types for a future normalized architecture, keep the values constrained by application logic.
-- Supported values:
-- MONTHLY_FEE
-- SECURITY_DEPOSIT
-- OTHER_CHARGE
-- DISCOUNT
-- ADJUSTMENT
-- REFUND

-- A transaction-safe invoice number generator should rely on a UNIQUE invoice_number column already present in fee_records.
-- This migration intentionally does not recreate fee_records or fee_payments, to preserve existing data and compatibility.
