-- ============================================================================
-- Copyright (C) 2024 Order Payment Module
-- ============================================================================

ALTER TABLE llx_order_payment ADD UNIQUE INDEX uk_order_payment_ref (ref, entity);
ALTER TABLE llx_order_payment ADD INDEX idx_order_payment_entity (entity);
ALTER TABLE llx_order_payment ADD INDEX idx_order_payment_fk_soc (fk_soc);
ALTER TABLE llx_order_payment ADD INDEX idx_order_payment_status (status);
ALTER TABLE llx_order_payment ADD CONSTRAINT fk_order_payment_fk_soc FOREIGN KEY (fk_soc) REFERENCES llx_societe (rowid);
