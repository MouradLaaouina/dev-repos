-- Copyright (C) 2024 Utopios
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.

-- Foreign keys for llx_exportpaie_exports table

ALTER TABLE llx_exportpaie_exports ADD CONSTRAINT fk_exportpaie_exports_fk_user_creat FOREIGN KEY (fk_user_creat) REFERENCES llx_user(rowid);
