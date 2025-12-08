-- Keys for llx_customerreturn
-- ============================================================

ALTER TABLE llx_customerreturn ADD UNIQUE INDEX uk_customerreturn_ref (ref, entity);
