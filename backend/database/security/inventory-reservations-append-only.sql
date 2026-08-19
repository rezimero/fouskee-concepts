DROP TRIGGER IF EXISTS inventory_reservations_freeze_identity;
DROP TRIGGER IF EXISTS inventory_reservations_prevent_delete;

DELIMITER $$

CREATE TRIGGER inventory_reservations_freeze_identity
BEFORE UPDATE ON inventory_reservations
FOR EACH ROW
BEGIN
    IF
        NOT (OLD.organization_id <=> NEW.organization_id) OR
        NOT (OLD.inventory_location_id <=> NEW.inventory_location_id) OR
        NOT (OLD.product_id <=> NEW.product_id) OR
        NOT (OLD.inventory_lot_id <=> NEW.inventory_lot_id) OR
        NOT (OLD.source_reference_type <=> NEW.source_reference_type) OR
        NOT (OLD.source_reference_public_id <=> NEW.source_reference_public_id) OR
        NOT (OLD.quantity_base <=> NEW.quantity_base) OR
        NOT (OLD.expires_at <=> NEW.expires_at) OR
        NOT (OLD.created_by_user_id <=> NEW.created_by_user_id)
    THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Inventory reservation identity is immutable.';
    END IF;
END$$

CREATE TRIGGER inventory_reservations_prevent_delete
BEFORE DELETE ON inventory_reservations
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Inventory reservation evidence cannot be deleted.';
END$$

DELIMITER ;
