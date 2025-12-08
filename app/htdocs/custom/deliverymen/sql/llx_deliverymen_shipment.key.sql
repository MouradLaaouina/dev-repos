-- ============================================================================
-- Foreign keys for llx_deliverymen_shipment
-- ============================================================================

ALTER TABLE llx_deliverymen_shipment ADD CONSTRAINT fk_deliverymen_expedition FOREIGN KEY (fk_expedition) REFERENCES llx_expedition(rowid);
ALTER TABLE llx_deliverymen_shipment ADD CONSTRAINT fk_deliverymen_user FOREIGN KEY (fk_user_deliveryman) REFERENCES llx_user(rowid);
ALTER TABLE llx_deliverymen_shipment ADD CONSTRAINT fk_deliverymen_assign FOREIGN KEY (fk_user_assign) REFERENCES llx_user(rowid);
