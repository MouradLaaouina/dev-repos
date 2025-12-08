CREATE TABLE IF NOT EXISTS llx_arrivalcost (
    rowid INTEGER AUTO_INCREMENT PRIMARY KEY,
    ref VARCHAR(64) NOT NULL,
    label VARCHAR(255) NOT NULL,
    amount_ht DOUBLE(24,8) NOT NULL,
    mode VARCHAR(10) NOT NULL,
    note TEXT NULL,
    fk_supplier_invoice INTEGER NULL,
    entity INTEGER NOT NULL DEFAULT 1,
    datec DATETIME NULL,
    tms TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    fk_user_creat INTEGER NULL,
    status INTEGER NOT NULL DEFAULT 1,
    INDEX idx_arrivalcost_entity (entity),
    INDEX idx_arrivalcost_ref (ref),
    INDEX idx_arrivalcost_invoice (fk_supplier_invoice)
) ENGINE=innodb;

CREATE TABLE IF NOT EXISTS llx_arrivalcost_line (
    rowid INTEGER AUTO_INCREMENT PRIMARY KEY,
    fk_arrivalcost INTEGER NOT NULL,
    fk_supplier_order INTEGER NOT NULL,
    fk_supplier_orderdet INTEGER NOT NULL,
    fk_product INTEGER DEFAULT NULL,
    qty DOUBLE(24,8) NOT NULL DEFAULT 0,
    base_amount DOUBLE(24,8) NOT NULL DEFAULT 0,
    allocated_ht DOUBLE(24,8) NOT NULL DEFAULT 0,
    allocated_unit DOUBLE(24,8) NOT NULL DEFAULT 0,
    entity INTEGER NOT NULL DEFAULT 1,
    datec DATETIME NULL,
    INDEX idx_arrivalcost_line_entity (entity),
    INDEX idx_arrivalcost_line_arrival (fk_arrivalcost),
    INDEX idx_arrivalcost_line_order (fk_supplier_order),
    INDEX idx_arrivalcost_line_orderdet (fk_supplier_orderdet),
    INDEX idx_arrivalcost_line_product (fk_product),
    CONSTRAINT fk_arrivalcost_line_arrival FOREIGN KEY (fk_arrivalcost) REFERENCES llx_arrivalcost (rowid) ON DELETE CASCADE
) ENGINE=innodb;
