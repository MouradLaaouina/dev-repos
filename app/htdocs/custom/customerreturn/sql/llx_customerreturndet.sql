-- Table for customer return lines (lignes de bon de retour)
-- ============================================================

CREATE TABLE llx_customerreturndet (
    rowid           INTEGER AUTO_INCREMENT PRIMARY KEY,
    fk_customerreturn INTEGER NOT NULL,
    fk_product      INTEGER DEFAULT NULL,
    fk_entrepot     INTEGER DEFAULT NULL,
    label           VARCHAR(255),
    description     TEXT,
    qty             REAL DEFAULT 1,
    subprice        DOUBLE(24,8) DEFAULT 0,
    total_ht        DOUBLE(24,8) DEFAULT 0,
    total_tva       DOUBLE(24,8) DEFAULT 0,
    total_ttc       DOUBLE(24,8) DEFAULT 0,
    tva_tx          DOUBLE(7,4) DEFAULT 0,
    batch           VARCHAR(128) DEFAULT NULL,
    rang            INTEGER DEFAULT 0,
    reason          VARCHAR(255),
    import_key      VARCHAR(14)
) ENGINE=innodb;

-- Indexes
ALTER TABLE llx_customerreturndet ADD INDEX idx_customerreturndet_fk_customerreturn (fk_customerreturn);
ALTER TABLE llx_customerreturndet ADD INDEX idx_customerreturndet_fk_product (fk_product);
ALTER TABLE llx_customerreturndet ADD INDEX idx_customerreturndet_fk_entrepot (fk_entrepot);

-- Foreign keys
ALTER TABLE llx_customerreturndet ADD CONSTRAINT fk_customerreturndet_fk_customerreturn FOREIGN KEY (fk_customerreturn) REFERENCES llx_customerreturn(rowid);
