-- ============================================================================
-- Copyright (C) 2024 Order Payment Module
-- ============================================================================

ALTER TABLE llx_order_payment_det ADD INDEX idx_order_payment_det_fk_payment (fk_payment);
ALTER TABLE llx_order_payment_det ADD INDEX idx_order_payment_det_fk_commande (fk_commande);
ALTER TABLE llx_order_payment_det ADD INDEX idx_order_payment_det_entity (entity);
ALTER TABLE llx_order_payment_det ADD CONSTRAINT fk_order_payment_det_payment FOREIGN KEY (fk_payment) REFERENCES llx_order_payment (rowid) ON DELETE CASCADE;
ALTER TABLE llx_order_payment_det ADD CONSTRAINT fk_order_payment_det_commande FOREIGN KEY (fk_commande) REFERENCES llx_commande (rowid);
