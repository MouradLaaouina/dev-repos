-- Copyright (C) 2024 Utopios
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.

-- Table to track export history (optional feature for tracking exports)

CREATE TABLE IF NOT EXISTS llx_exportpaie_exports (
    rowid           integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
    entity          integer DEFAULT 1 NOT NULL,
    type_export     varchar(10) NOT NULL,               -- 'CNSS' or 'CIMR'
    periode         varchar(6) NOT NULL,                 -- Format: YYYYMM for CNSS, YYYYQQ for CIMR
    annee           integer NOT NULL,
    mois            integer,                             -- For CNSS exports
    trimestre       integer,                             -- For CIMR exports
    filename        varchar(255) NOT NULL,
    filepath        varchar(512),
    nb_salaries     integer DEFAULT 0,
    is_complementaire integer DEFAULT 0,                 -- For CNSS complementary declarations
    date_creation   datetime NOT NULL,
    fk_user_creat   integer,
    tms             timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    statut          integer DEFAULT 1 NOT NULL,          -- 1 = generated, 2 = sent, 0 = draft
    note_public     text,
    note_private    text
) ENGINE=innodb;

-- Add index for better performance
ALTER TABLE llx_exportpaie_exports ADD INDEX idx_exportpaie_entity (entity);
ALTER TABLE llx_exportpaie_exports ADD INDEX idx_exportpaie_type (type_export);
ALTER TABLE llx_exportpaie_exports ADD INDEX idx_exportpaie_periode (periode);
ALTER TABLE llx_exportpaie_exports ADD INDEX idx_exportpaie_annee_mois (annee, mois);
ALTER TABLE llx_exportpaie_exports ADD INDEX idx_exportpaie_annee_trimestre (annee, trimestre);
