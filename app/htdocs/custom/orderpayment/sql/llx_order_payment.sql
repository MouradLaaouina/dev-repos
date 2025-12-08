-- ============================================================================
-- Copyright (C) 2024 Order Payment Module
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
-- ============================================================================

CREATE TABLE IF NOT EXISTS llx_order_payment (
    rowid               INTEGER AUTO_INCREMENT PRIMARY KEY,
    entity              INTEGER DEFAULT 1 NOT NULL,
    ref                 VARCHAR(50) NOT NULL,
    fk_soc              INTEGER NOT NULL,
    amount              DOUBLE(24,8) DEFAULT 0 NOT NULL,
    datep               DATETIME,
    fk_mode_reglement   INTEGER,
    num_payment         VARCHAR(255),
    fk_bank             INTEGER,
    fk_account          INTEGER,
    note_public         TEXT,
    note_private        TEXT,
    status              SMALLINT DEFAULT 1 NOT NULL,
    fk_user_creat       INTEGER,
    fk_user_modif       INTEGER,
    fk_user_cancel      INTEGER,
    datec               DATETIME,
    tms                 TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    date_cancel         DATETIME,
    cancel_reason       VARCHAR(255),
    is_returned_check   SMALLINT DEFAULT 0,
    -- Sanction management for returned checks (used by customercheck module)
    sanction_lifted     TINYINT(1) DEFAULT 0 NOT NULL,
    date_sanction_lifted DATETIME DEFAULT NULL,
    fk_user_sanction_lifted INTEGER DEFAULT NULL,
    note_sanction       TEXT DEFAULT NULL
) ENGINE=innodb;
