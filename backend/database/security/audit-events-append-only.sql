DROP TRIGGER IF EXISTS audit_events_prevent_update;
DROP TRIGGER IF EXISTS audit_events_prevent_delete;

CREATE TRIGGER audit_events_prevent_update
BEFORE UPDATE ON audit_events
FOR EACH ROW
SIGNAL SQLSTATE '45000'
SET MESSAGE_TEXT = 'audit_events is append-only';

CREATE TRIGGER audit_events_prevent_delete
BEFORE DELETE ON audit_events
FOR EACH ROW
SIGNAL SQLSTATE '45000'
SET MESSAGE_TEXT = 'audit_events is append-only';

SELECT TRIGGER_NAME, EVENT_MANIPULATION, ACTION_TIMING
FROM information_schema.TRIGGERS
WHERE TRIGGER_SCHEMA = DATABASE()
  AND EVENT_OBJECT_TABLE = 'audit_events'
ORDER BY TRIGGER_NAME;
