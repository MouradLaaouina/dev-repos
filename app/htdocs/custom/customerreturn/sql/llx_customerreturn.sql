-- Table for customer returns (bons de retour)
-- ============================================================

CREATE TABLE llx_customerreturn (
    rowid           INTEGER AUTO_INCREMENT PRIMARY KEY,
    ref             VARCHAR(30) NOT NULL,
    entity          INTEGER DEFAULT 1 NOT NULL,
    fk_soc          INTEGER NOT NULL,
    fk_expedition   INTEGER DEFAULT NULL,
    fk_deliveryman  INTEGER DEFAULT NULL,
    datec           DATETIME,
    date_return     DATE,
    date_valid      DATETIME,
    fk_user_author  INTEGER,
    fk_user_valid   INTEGER,
    total_ht        DOUBLE(24,8) DEFAULT 0,
    total_tva       DOUBLE(24,8) DEFAULT 0,
    total_ttc       DOUBLE(24,8) DEFAULT 0,
    note_private    TEXT,
    note_public     TEXT,
    fk_statut       SMALLINT DEFAULT 0 NOT NULL,
    fk_facture_avoir INTEGER DEFAULT NULL,
    model_pdf       VARCHAR(255),
    import_key      VARCHAR(14),
    tms             TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=innodb;

-- Indexes
ALTER TABLE llx_customerreturn ADD INDEX idx_customerreturn_ref (ref);
ALTER TABLE llx_customerreturn ADD INDEX idx_customerreturn_fk_soc (fk_soc);
ALTER TABLE llx_customerreturn ADD INDEX idx_customerreturn_fk_expedition (fk_expedition);
ALTER TABLE llx_customerreturn ADD INDEX idx_customerreturn_fk_deliveryman (fk_deliveryman);
ALTER TABLE llx_customerreturn ADD INDEX idx_customerreturn_fk_statut (fk_statut);
ALTER TABLE llx_customerreturn ADD INDEX idx_customerreturn_date_return (date_return);

-- Foreign keys
ALTER TABLE llx_customerreturn ADD CONSTRAINT fk_customerreturn_fk_soc FOREIGN KEY (fk_soc) REFERENCES llx_societe(rowid);
