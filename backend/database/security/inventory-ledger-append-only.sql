DROP TRIGGER IF EXISTS inventory_movements_prevent_update;
DROP TRIGGER IF EXISTS inventory_movements_prevent_delete;
DROP TRIGGER IF EXISTS inventory_movement_lines_prevent_update;
DROP TRIGGER IF EXISTS inventory_movement_lines_prevent_delete;

DELIMITER //

CREATE TRIGGER inventory_movements_prevent_update
BEFORE UPDATE ON inventory_movements
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Posted inventory movements are append-only';
END//

CREATE TRIGGER inventory_movements_prevent_delete
BEFORE DELETE ON inventory_movements
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Posted inventory movements are append-only';
END//

CREATE TRIGGER inventory_movement_lines_prevent_update
BEFORE UPDATE ON inventory_movement_lines
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Posted inventory movement lines are append-only';
END//

CREATE TRIGGER inventory_movement_lines_prevent_delete
BEFORE DELETE ON inventory_movement_lines
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Posted inventory movement lines are append-only';
END//

DELIMITER ;
