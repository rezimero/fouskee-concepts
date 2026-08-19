DROP TRIGGER IF EXISTS sales_orders_freeze_commercial_update;
DROP TRIGGER IF EXISTS sales_orders_prevent_delete_after_draft;
DROP TRIGGER IF EXISTS sales_order_lines_freeze_insert;
DROP TRIGGER IF EXISTS sales_order_lines_freeze_update;
DROP TRIGGER IF EXISTS sales_order_lines_freeze_delete;
DROP TRIGGER IF EXISTS sales_quotations_freeze_commercial_update;
DROP TRIGGER IF EXISTS sales_quotations_prevent_delete_after_draft;
DROP TRIGGER IF EXISTS sales_quotation_lines_freeze_insert;
DROP TRIGGER IF EXISTS sales_quotation_lines_freeze_update;
DROP TRIGGER IF EXISTS sales_quotation_lines_freeze_delete;

DELIMITER $$

CREATE TRIGGER sales_orders_freeze_commercial_update
BEFORE UPDATE ON sales_orders
FOR EACH ROW
BEGIN
    IF OLD.status <> 'draft' AND NEW.status = 'draft' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Non-draft sales orders cannot be reopened.';
    END IF;

    IF (OLD.status <> 'draft' OR NEW.status <> 'draft') AND (
        NOT (OLD.customer_id <=> NEW.customer_id) OR
        NOT (OLD.price_list_id <=> NEW.price_list_id) OR
        NOT (OLD.source_quotation_id <=> NEW.source_quotation_id) OR
        NOT (OLD.settlement_type <=> NEW.settlement_type) OR
        NOT (OLD.currency_code <=> NEW.currency_code) OR
        NOT (OLD.subtotal_amount <=> NEW.subtotal_amount) OR
        NOT (OLD.total_amount <=> NEW.total_amount) OR
        NOT (OLD.notes <=> NEW.notes) OR
        NOT (OLD.idempotency_key <=> NEW.idempotency_key) OR
        NOT (OLD.idempotency_fingerprint <=> NEW.idempotency_fingerprint) OR
        NOT (OLD.created_by_user_id <=> NEW.created_by_user_id)
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Non-draft sales commercial evidence is immutable.';
    END IF;
END$$

CREATE TRIGGER sales_orders_prevent_delete_after_draft
BEFORE DELETE ON sales_orders
FOR EACH ROW
BEGIN
    IF OLD.status <> 'draft' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Non-draft sales orders cannot be deleted.';
    END IF;
END$$

CREATE TRIGGER sales_order_lines_freeze_insert
BEFORE INSERT ON sales_order_lines
FOR EACH ROW
BEGIN
    DECLARE v_status VARCHAR(30);

    SELECT status INTO v_status
    FROM sales_orders
    WHERE id = NEW.sales_order_id;

    IF v_status <> 'draft' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Lines cannot be added to a non-draft sales order.';
    END IF;
END$$

CREATE TRIGGER sales_order_lines_freeze_update
BEFORE UPDATE ON sales_order_lines
FOR EACH ROW
BEGIN
    DECLARE v_status VARCHAR(30);

    SELECT status INTO v_status
    FROM sales_orders
    WHERE id = OLD.sales_order_id;

    IF v_status <> 'draft' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Non-draft sales order lines are immutable.';
    END IF;
END$$

CREATE TRIGGER sales_order_lines_freeze_delete
BEFORE DELETE ON sales_order_lines
FOR EACH ROW
BEGIN
    DECLARE v_status VARCHAR(30);

    SELECT status INTO v_status
    FROM sales_orders
    WHERE id = OLD.sales_order_id;

    IF v_status <> 'draft' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Non-draft sales order lines cannot be deleted.';
    END IF;
END$$

CREATE TRIGGER sales_quotations_freeze_commercial_update
BEFORE UPDATE ON sales_quotations
FOR EACH ROW
BEGIN
    IF OLD.status <> 'draft' AND NEW.status = 'draft' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Non-draft quotations cannot be reopened.';
    END IF;

    IF (OLD.status <> 'draft' OR NEW.status <> 'draft') AND (
        NOT (OLD.customer_id <=> NEW.customer_id) OR
        NOT (OLD.price_list_id <=> NEW.price_list_id) OR
        NOT (OLD.settlement_type <=> NEW.settlement_type) OR
        NOT (OLD.currency_code <=> NEW.currency_code) OR
        NOT (OLD.subtotal_amount <=> NEW.subtotal_amount) OR
        NOT (OLD.total_amount <=> NEW.total_amount) OR
        NOT (OLD.valid_until <=> NEW.valid_until) OR
        NOT (OLD.notes <=> NEW.notes) OR
        NOT (OLD.idempotency_key <=> NEW.idempotency_key) OR
        NOT (OLD.idempotency_fingerprint <=> NEW.idempotency_fingerprint) OR
        NOT (OLD.created_by_user_id <=> NEW.created_by_user_id)
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Sent quotation commercial evidence is immutable.';
    END IF;
END$$

CREATE TRIGGER sales_quotations_prevent_delete_after_draft
BEFORE DELETE ON sales_quotations
FOR EACH ROW
BEGIN
    IF OLD.status <> 'draft' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Non-draft quotations cannot be deleted.';
    END IF;
END$$

CREATE TRIGGER sales_quotation_lines_freeze_insert
BEFORE INSERT ON sales_quotation_lines
FOR EACH ROW
BEGIN
    DECLARE v_status VARCHAR(30);

    SELECT status INTO v_status
    FROM sales_quotations
    WHERE id = NEW.sales_quotation_id;

    IF v_status <> 'draft' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Lines cannot be added to a sent quotation.';
    END IF;
END$$

CREATE TRIGGER sales_quotation_lines_freeze_update
BEFORE UPDATE ON sales_quotation_lines
FOR EACH ROW
BEGIN
    DECLARE v_status VARCHAR(30);

    SELECT status INTO v_status
    FROM sales_quotations
    WHERE id = OLD.sales_quotation_id;

    IF v_status <> 'draft' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Sent quotation lines are immutable.';
    END IF;
END$$

CREATE TRIGGER sales_quotation_lines_freeze_delete
BEFORE DELETE ON sales_quotation_lines
FOR EACH ROW
BEGIN
    DECLARE v_status VARCHAR(30);

    SELECT status INTO v_status
    FROM sales_quotations
    WHERE id = OLD.sales_quotation_id;

    IF v_status <> 'draft' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Sent quotation lines cannot be deleted.';
    END IF;
END$$

DELIMITER ;
