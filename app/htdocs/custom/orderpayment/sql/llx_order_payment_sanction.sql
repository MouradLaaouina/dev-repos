-- ============================================================================
-- Add sanction columns for returned checks management
-- Used by customercheck module
-- ============================================================================

ALTER TABLE llx_order_payment ADD COLUMN sanction_lifted TINYINT(1) DEFAULT 0 NOT NULL;
ALTER TABLE llx_order_payment ADD COLUMN date_sanction_lifted DATETIME DEFAULT NULL;
ALTER TABLE llx_order_payment ADD COLUMN fk_user_sanction_lifted INTEGER DEFAULT NULL;
ALTER TABLE llx_order_payment ADD COLUMN note_sanction TEXT DEFAULT NULL;
