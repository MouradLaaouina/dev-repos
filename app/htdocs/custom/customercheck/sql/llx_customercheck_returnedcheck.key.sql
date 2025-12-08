-- Keys for llx_customercheck_returnedcheck
-- Copyright (C) 2024

ALTER TABLE llx_customercheck_returnedcheck ADD CONSTRAINT fk_returnedcheck_fk_soc FOREIGN KEY (fk_soc) REFERENCES llx_societe(rowid);
