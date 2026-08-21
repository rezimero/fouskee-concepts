DROP TRIGGER IF EXISTS sales_fulfillments_freeze_identity;
DROP TRIGGER IF EXISTS sales_fulfillments_prevent_delete;
DROP TRIGGER IF EXISTS sales_fulfillment_lines_insert_guard;
DROP TRIGGER IF EXISTS sales_fulfillment_lines_freeze_update;
DROP TRIGGER IF EXISTS sales_fulfillment_lines_prevent_delete;
DROP TRIGGER IF EXISTS inventory_reservations_lifecycle_guard;

DELIMITER $$

CREATE TRIGGER sales_fulfillments_freeze_identity
BEFORE UPDATE ON sales_fulfillments
FOR EACH ROW
BEGIN
    IF NOT (
        (OLD.status = NEW.status) OR
        (OLD.status = 'draft' AND NEW.status = 'picked') OR
        (OLD.status = 'picked' AND NEW.status = 'dispatched') OR
        (OLD.status = 'dispatched' AND NEW.status = 'reversed')
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Invalid sales fulfillment status transition.';
    END IF;

    IF
        NOT (OLD.organization_id <=> NEW.organization_id) OR
        NOT (OLD.sales_order_id <=> NEW.sales_order_id) OR
        NOT (OLD.inventory_location_id <=> NEW.inventory_location_id) OR
        NOT (OLD.fulfillment_number <=> NEW.fulfillment_number) OR
        NOT (OLD.pick_idempotency_key <=> NEW.pick_idempotency_key) OR
        NOT (OLD.pick_idempotency_fingerprint <=> NEW.pick_idempotency_fingerprint) OR
        NOT (OLD.picked_by_user_id <=> NEW.picked_by_user_id) OR
        NOT (OLD.picked_at <=> NEW.picked_at)
    THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Sales fulfillment pick evidence is immutable.';
    END IF;

    IF OLD.status IN ('dispatched', 'reversed') AND (
        NOT (OLD.dispatch_idempotency_key <=> NEW.dispatch_idempotency_key) OR
        NOT (OLD.inventory_movement_id <=> NEW.inventory_movement_id) OR
        NOT (OLD.dispatched_by_user_id <=> NEW.dispatched_by_user_id) OR
        NOT (OLD.dispatched_at <=> NEW.dispatched_at)
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Sales dispatch evidence is immutable.';
    END IF;

    IF NEW.status = 'dispatched' AND (
        NEW.dispatch_idempotency_key IS NULL OR
        NEW.inventory_movement_id IS NULL OR
        NEW.dispatched_by_user_id IS NULL OR
        NEW.dispatched_at IS NULL
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Dispatch evidence is incomplete.';
    END IF;

    IF OLD.status = 'reversed' AND (
        NOT (OLD.reversal_idempotency_key <=> NEW.reversal_idempotency_key) OR
        NOT (OLD.reversal_movement_id <=> NEW.reversal_movement_id) OR
        NOT (OLD.reversed_by_user_id <=> NEW.reversed_by_user_id) OR
        NOT (OLD.reversed_at <=> NEW.reversed_at) OR
        NOT (OLD.reversal_reason <=> NEW.reversal_reason)
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Sales reversal evidence is immutable.';
    END IF;

    IF NEW.status = 'reversed' AND (
        NEW.reversal_idempotency_key IS NULL OR
        NEW.reversal_movement_id IS NULL OR
        NEW.reversed_by_user_id IS NULL OR
        NEW.reversed_at IS NULL OR
        NEW.reversal_reason IS NULL
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Reversal evidence is incomplete.';
    END IF;
END$$

CREATE TRIGGER sales_fulfillments_prevent_delete
BEFORE DELETE ON sales_fulfillments
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Sales fulfillment evidence cannot be deleted.';
END$$

CREATE TRIGGER sales_fulfillment_lines_insert_guard
BEFORE INSERT ON sales_fulfillment_lines
FOR EACH ROW
BEGIN
    DECLARE v_status VARCHAR(30);

    SELECT status INTO v_status
    FROM sales_fulfillments
    WHERE id = NEW.sales_fulfillment_id;

    IF v_status <> 'draft' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Fulfillment lines can only be built in draft.';
    END IF;
END$$

CREATE TRIGGER sales_fulfillment_lines_freeze_update
BEFORE UPDATE ON sales_fulfillment_lines
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Sales fulfillment lines are immutable.';
END$$

CREATE TRIGGER sales_fulfillment_lines_prevent_delete
BEFORE DELETE ON sales_fulfillment_lines
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Sales fulfillment lines cannot be deleted.';
END$$

CREATE TRIGGER inventory_reservations_lifecycle_guard
BEFORE UPDATE ON inventory_reservations
FOR EACH ROW
BEGIN
    IF NOT (
        (OLD.status = NEW.status) OR
        (OLD.status = 'active' AND NEW.status IN ('released', 'fulfilled', 'expired'))
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Invalid inventory reservation transition.';
    END IF;

    IF NEW.status = 'active' AND (
        NEW.released_by_user_id IS NOT NULL OR
        NEW.released_at IS NOT NULL OR
        NEW.fulfilled_by_user_id IS NOT NULL OR
        NEW.fulfilled_at IS NOT NULL
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Active reservation cannot contain closure evidence.';
    END IF;

    IF NEW.status = 'released' AND (
        NEW.released_by_user_id IS NULL OR
        NEW.released_at IS NULL OR
        NEW.fulfilled_by_user_id IS NOT NULL OR
        NEW.fulfilled_at IS NOT NULL
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Released reservation evidence is incomplete.';
    END IF;

    IF NEW.status = 'fulfilled' AND (
        NEW.fulfilled_by_user_id IS NULL OR
        NEW.fulfilled_at IS NULL OR
        NEW.released_by_user_id IS NOT NULL OR
        NEW.released_at IS NOT NULL
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Fulfilled reservation evidence is incomplete.';
    END IF;

    IF OLD.status <> 'active' AND (
        NOT (OLD.released_by_user_id <=> NEW.released_by_user_id) OR
        NOT (OLD.released_at <=> NEW.released_at) OR
        NOT (OLD.fulfilled_by_user_id <=> NEW.fulfilled_by_user_id) OR
        NOT (OLD.fulfilled_at <=> NEW.fulfilled_at)
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Reservation closure evidence is immutable.';
    END IF;
END$$

DELIMITER ;
