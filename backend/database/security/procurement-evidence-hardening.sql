DROP TRIGGER IF EXISTS purchase_order_lines_freeze_after_draft_update;
DROP TRIGGER IF EXISTS purchase_order_lines_freeze_after_draft_delete;
DROP TRIGGER IF EXISTS goods_receipt_lines_prevent_update;
DROP TRIGGER IF EXISTS goods_receipt_lines_prevent_delete;
DROP TRIGGER IF EXISTS supplier_invoice_lines_prevent_update;
DROP TRIGGER IF EXISTS supplier_invoice_lines_prevent_delete;
DROP TRIGGER IF EXISTS procurement_match_runs_prevent_update;
DROP TRIGGER IF EXISTS procurement_match_runs_prevent_delete;
DROP TRIGGER IF EXISTS procurement_match_lines_prevent_update;
DROP TRIGGER IF EXISTS procurement_match_lines_prevent_delete;

DELIMITER //

CREATE TRIGGER purchase_order_lines_freeze_after_draft_update
BEFORE UPDATE ON purchase_order_lines
FOR EACH ROW
BEGIN
    DECLARE po_status VARCHAR(30);

    SELECT status INTO po_status
    FROM purchase_orders
    WHERE id = OLD.purchase_order_id;

    IF po_status <> 'draft' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Submitted purchase-order lines are append-only';
    END IF;
END//

CREATE TRIGGER purchase_order_lines_freeze_after_draft_delete
BEFORE DELETE ON purchase_order_lines
FOR EACH ROW
BEGIN
    DECLARE po_status VARCHAR(30);

    SELECT status INTO po_status
    FROM purchase_orders
    WHERE id = OLD.purchase_order_id;

    IF po_status <> 'draft' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Submitted purchase-order lines are append-only';
    END IF;
END//

CREATE TRIGGER goods_receipt_lines_prevent_update
BEFORE UPDATE ON goods_receipt_lines
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Goods-receipt evidence lines are append-only';
END//

CREATE TRIGGER goods_receipt_lines_prevent_delete
BEFORE DELETE ON goods_receipt_lines
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Goods-receipt evidence lines are append-only';
END//

CREATE TRIGGER supplier_invoice_lines_prevent_update
BEFORE UPDATE ON supplier_invoice_lines
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Supplier-invoice evidence lines are append-only';
END//

CREATE TRIGGER supplier_invoice_lines_prevent_delete
BEFORE DELETE ON supplier_invoice_lines
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Supplier-invoice evidence lines are append-only';
END//

CREATE TRIGGER procurement_match_runs_prevent_update
BEFORE UPDATE ON procurement_match_runs
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Three-way-match runs are append-only';
END//

CREATE TRIGGER procurement_match_runs_prevent_delete
BEFORE DELETE ON procurement_match_runs
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Three-way-match runs are append-only';
END//

CREATE TRIGGER procurement_match_lines_prevent_update
BEFORE UPDATE ON procurement_match_lines
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Three-way-match evidence lines are append-only';
END//

CREATE TRIGGER procurement_match_lines_prevent_delete
BEFORE DELETE ON procurement_match_lines
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Three-way-match evidence lines are append-only';
END//

DELIMITER ;
