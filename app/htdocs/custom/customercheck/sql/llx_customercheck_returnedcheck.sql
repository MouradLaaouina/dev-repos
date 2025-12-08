-- Table for tracking returned/unpaid checks with sanction management
-- Copyright (C) 2024

CREATE TABLE llx_customercheck_returnedcheck (
    rowid           INTEGER AUTO_INCREMENT PRIMARY KEY,
    ref             VARCHAR(50) NOT NULL,               -- Reference number
    fk_soc          INTEGER NOT NULL,                   -- Customer ID
    fk_payment      INTEGER DEFAULT NULL,               -- Link to payment if from standard table
    amount          DOUBLE(24,8) NOT NULL DEFAULT 0,    -- Check amount
    num_check       VARCHAR(50) DEFAULT NULL,           -- Check number
    bank_name       VARCHAR(255) DEFAULT NULL,          -- Bank name
    date_check      DATE DEFAULT NULL,                  -- Check date
    date_returned   DATE DEFAULT NULL,                  -- Date when check was returned
    reason          VARCHAR(255) DEFAULT NULL,          -- Reason for return
    sanction_active TINYINT(1) NOT NULL DEFAULT 1,      -- 1 = sanction active, 0 = sanction lifted
    date_sanction_lifted DATETIME DEFAULT NULL,         -- When sanction was lifted
    fk_user_sanction_lifted INTEGER DEFAULT NULL,       -- Who lifted the sanction
    note_sanction   TEXT DEFAULT NULL,                  -- Note when lifting sanction
    date_creation   DATETIME NOT NULL,                  -- Creation date
    fk_user_creat   INTEGER NOT NULL,                   -- Created by
    tms             TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    entity          INTEGER NOT NULL DEFAULT 1          -- Multi-company
) ENGINE=InnoDB;

ALTER TABLE llx_customercheck_returnedcheck ADD INDEX idx_returnedcheck_fk_soc (fk_soc);
ALTER TABLE llx_customercheck_returnedcheck ADD INDEX idx_returnedcheck_sanction (sanction_active);
ALTER TABLE llx_customercheck_returnedcheck ADD UNIQUE INDEX uk_returnedcheck_ref (ref, entity);
