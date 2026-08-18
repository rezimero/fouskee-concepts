DROP TRIGGER IF EXISTS inventory_movements_prevent_update;
DROP TRIGGER IF EXISTS inventory_movements_prevent_delete;
DROP TRIGGER IF EXISTS inventory_movement_lines_prevent_update;
DROP TRIGGER IF EXISTS inventory_movement_lines_prevent_delete;
DROP TRIGGER IF EXISTS stock_count_lines_prevent_update;
DROP TRIGGER IF EXISTS stock_count_lines_prevent_delete;
DROP TRIGGER IF EXISTS stock_transfer_lines_prevent_update;
DROP TRIGGER IF EXISTS stock_transfer_lines_prevent_delete;

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

CREATE TRIGGER stock_count_lines_prevent_update
BEFORE UPDATE ON stock_count_lines
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Submitted stock count evidence lines are append-only';
END//

CREATE TRIGGER stock_count_lines_prevent_delete
BEFORE DELETE ON stock_count_lines
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Submitted stock count evidence lines are append-only';
END//

CREATE TRIGGER stock_transfer_lines_prevent_update
BEFORE UPDATE ON stock_transfer_lines
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Stock transfer evidence lines are append-only';
END//

CREATE TRIGGER stock_transfer_lines_prevent_delete
BEFORE DELETE ON stock_transfer_lines
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Stock transfer evidence lines are append-only';
END//

DELIMITER ;
