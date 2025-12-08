-- ============================================================================
-- Table for delivery assignments to shipments
-- Links shipments (expeditions) to deliverymen (users)
-- ============================================================================

CREATE TABLE IF NOT EXISTS llx_deliverymen_shipment (
    rowid               INTEGER AUTO_INCREMENT PRIMARY KEY,
    entity              INTEGER DEFAULT 1 NOT NULL,
    fk_expedition       INTEGER NOT NULL,              -- Link to llx_expedition
    fk_user_deliveryman INTEGER NOT NULL,              -- User ID of the deliveryman
    date_assignment     DATETIME NOT NULL,             -- When assignment was made
    fk_user_assign      INTEGER NOT NULL,              -- Who made the assignment
    delivery_date       DATE DEFAULT NULL,             -- Planned delivery date
    delivery_status     VARCHAR(32) DEFAULT 'pending', -- pending, in_progress, delivered, failed
    date_delivery_done  DATETIME DEFAULT NULL,         -- When delivery was completed
    note_delivery       TEXT DEFAULT NULL,             -- Delivery notes
    signature           TEXT DEFAULT NULL,             -- Base64 signature if captured
    datec               DATETIME NOT NULL,             -- Creation date
    tms                 TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

ALTER TABLE llx_deliverymen_shipment ADD INDEX idx_deliverymen_expedition (fk_expedition);
ALTER TABLE llx_deliverymen_shipment ADD INDEX idx_deliverymen_user (fk_user_deliveryman);
ALTER TABLE llx_deliverymen_shipment ADD INDEX idx_deliverymen_date (delivery_date);
ALTER TABLE llx_deliverymen_shipment ADD INDEX idx_deliverymen_status (delivery_status);
ALTER TABLE llx_deliverymen_shipment ADD UNIQUE INDEX uk_deliverymen_expedition (fk_expedition, entity);
