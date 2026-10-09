-- Extend the existing security ledger without replacing or deleting historical rows.
-- Retain legacy PAID/RETURNED states and PENDING/PAYMENT/WAIVER/RETURN transaction
-- types found in deployed databases, even though new payments use HELD/HOLD.
-- Existing zero-value HELD/HOLD history is retained; application services reject
-- zero-value new security payments.

ALTER TABLE security_deposits
    MODIFY COLUMN status ENUM(
        'PENDING',
        'PAID',
        'WAIVED',
        'RETURNED',
        'HELD',
        'ADJUSTED',
        'PARTIALLY_REFUNDED',
        'REFUNDED',
        'FORFEITED'
    ) NOT NULL DEFAULT 'HELD',
    ADD COLUMN expected_payment_date DATE NULL AFTER status,
    ADD COLUMN note TEXT NULL AFTER expected_payment_date,
    ADD KEY idx_security_deposits_status_due (status, expected_payment_date),
    ADD CONSTRAINT chk_security_deposits_waived
        CHECK (status <> 'WAIVED' OR (original_amount = 0 AND remaining_amount = 0 AND note IS NOT NULL AND CHAR_LENGTH(TRIM(note)) > 0)),
    ADD CONSTRAINT chk_security_deposits_pending
        CHECK (status <> 'PENDING' OR (original_amount > 0 AND remaining_amount = 0 AND expected_payment_date IS NOT NULL AND note IS NOT NULL AND CHAR_LENGTH(TRIM(note)) > 0));

ALTER TABLE security_deposit_transactions
    ADD COLUMN payment_method ENUM('Cash','Bank Transfer','Online','Card','JazzCash','EasyPaisa','Other') NULL AFTER reference_number,
    ADD CONSTRAINT chk_security_deposit_transactions_amount
        CHECK (amount >= 0),
    ADD CONSTRAINT chk_security_deposit_transactions_reason
        CHECK (CHAR_LENGTH(TRIM(reason)) > 0);

DELIMITER //
CREATE TRIGGER trg_security_deposits_validate_insert
BEFORE INSERT ON security_deposits
FOR EACH ROW
BEGIN
    IF NEW.original_amount < 0 OR NEW.remaining_amount < 0 OR NEW.remaining_amount > NEW.original_amount THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid security deposit amounts.';
    END IF;

    IF NEW.status = 'WAIVED' AND (NEW.original_amount <> 0 OR NEW.remaining_amount <> 0 OR NEW.note IS NULL OR CHAR_LENGTH(TRIM(NEW.note)) = 0) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Waived security requires zero balance and a reason.';
    ELSEIF NEW.status = 'PENDING' AND (NEW.original_amount <= 0 OR NEW.remaining_amount <> 0 OR NEW.expected_payment_date IS NULL OR NEW.note IS NULL OR CHAR_LENGTH(TRIM(NEW.note)) = 0) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Pending security requires an amount, expected date, and reason.';
    ELSEIF NEW.status IN ('HELD', 'PAID') AND (NEW.original_amount <= 0 OR NEW.remaining_amount <= 0) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Held security requires a positive balance.';
    ELSEIF NEW.status = 'PARTIALLY_REFUNDED' AND (NEW.remaining_amount <= 0 OR NEW.remaining_amount >= NEW.original_amount) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Partially refunded security requires a reduced positive balance.';
    ELSEIF NEW.status IN ('REFUNDED', 'FORFEITED') AND NEW.remaining_amount <> 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Settled security must have a zero balance.';
    END IF;
END//

CREATE TRIGGER trg_security_deposits_validate_update
BEFORE UPDATE ON security_deposits
FOR EACH ROW
BEGIN
    IF OLD.status = 'WAIVED' AND NEW.status <> 'WAIVED' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Waived security cannot be converted to a paid state.';
    ELSEIF OLD.status IN ('REFUNDED', 'FORFEITED', 'RETURNED') AND NEW.status <> OLD.status THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Settled security cannot be reopened.';
    ELSEIF OLD.status = 'PENDING' AND NEW.status NOT IN ('PENDING', 'HELD', 'PAID') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Pending security can only remain pending or become held.';
    ELSEIF OLD.status = 'PENDING' AND NEW.status IN ('HELD', 'PAID')
        AND (NEW.original_amount <> OLD.original_amount OR NEW.remaining_amount <> OLD.original_amount) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Pending security must be paid in full before becoming held.';
    ELSEIF OLD.status NOT IN ('PENDING', 'WAIVED') AND NEW.status IN ('PENDING', 'WAIVED') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'A paid or settled security record cannot become pending or waived.';
    END IF;

    IF NEW.original_amount < 0 OR NEW.remaining_amount < 0 OR NEW.remaining_amount > NEW.original_amount THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid security deposit amounts.';
    END IF;
    IF NEW.status = 'WAIVED' AND (NEW.original_amount <> 0 OR NEW.remaining_amount <> 0 OR NEW.note IS NULL OR CHAR_LENGTH(TRIM(NEW.note)) = 0) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Waived security requires zero balance and a reason.';
    ELSEIF NEW.status = 'PENDING' AND (NEW.original_amount <= 0 OR NEW.remaining_amount <> 0 OR NEW.expected_payment_date IS NULL OR NEW.note IS NULL OR CHAR_LENGTH(TRIM(NEW.note)) = 0) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Pending security requires an amount, expected date, and reason.';
    ELSEIF NEW.status IN ('HELD', 'PAID') AND (NEW.original_amount <= 0 OR NEW.remaining_amount <= 0) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Held security requires a positive balance.';
    ELSEIF NEW.status = 'PARTIALLY_REFUNDED' AND (NEW.remaining_amount <= 0 OR NEW.remaining_amount >= NEW.original_amount) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Partially refunded security requires a reduced positive balance.';
    ELSEIF NEW.status IN ('REFUNDED', 'FORFEITED') AND NEW.remaining_amount <> 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Settled security must have a zero balance.';
    END IF;
END//

CREATE TRIGGER trg_security_deposit_transactions_validate_insert
BEFORE INSERT ON security_deposit_transactions
FOR EACH ROW
BEGIN
    DECLARE deposit_status VARCHAR(20);
    DECLARE deposit_balance DECIMAL(12,2);

    IF NEW.amount <= 0 OR CHAR_LENGTH(TRIM(NEW.reason)) = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Security transactions require a positive amount and reason.';
    END IF;

    IF NEW.transaction_type = 'HOLD' THEN
        IF NEW.payment_method IS NULL THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Security payment method is required.';
        END IF;
        SELECT status, remaining_amount INTO deposit_status, deposit_balance
        FROM security_deposits
        WHERE id = NEW.security_deposit_id;
        IF deposit_status NOT IN ('HELD', 'PAID', 'ADJUSTED', 'PARTIALLY_REFUNDED')
            OR NEW.amount > deposit_balance THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Security payment does not match a held balance.';
        END IF;
    END IF;
END//
DELIMITER ;
