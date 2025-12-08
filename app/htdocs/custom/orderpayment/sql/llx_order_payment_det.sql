-- ============================================================================
-- Copyright (C) 2024 Order Payment Module
--
-- Table to link payments to orders (many-to-many relationship)
-- ============================================================================

CREATE TABLE IF NOT EXISTS llx_order_payment_det (
    rowid           INTEGER AUTO_INCREMENT PRIMARY KEY,
    fk_payment      INTEGER NOT NULL,
    fk_commande     INTEGER NOT NULL,
    amount          DOUBLE(24,8) DEFAULT 0 NOT NULL,
    entity          INTEGER DEFAULT 1 NOT NULL
) ENGINE=innodb;
