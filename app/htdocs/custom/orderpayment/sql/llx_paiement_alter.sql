-- ============================================================================
-- Alteration queries for llx_paiement table (invoice payments)
-- Required for cancel payment and returned check functionality
-- Run these queries to add the necessary columns to the existing table
-- ============================================================================

-- Add cancel user column
ALTER TABLE llx_paiement ADD COLUMN fk_user_cancel INTEGER DEFAULT NULL;

-- Add cancel date column
ALTER TABLE llx_paiement ADD COLUMN date_cancel DATETIME DEFAULT NULL;

-- Add cancel reason column
ALTER TABLE llx_paiement ADD COLUMN cancel_reason VARCHAR(255) DEFAULT NULL;

-- Add returned check flag column
ALTER TABLE llx_paiement ADD COLUMN is_returned_check SMALLINT DEFAULT 0;

-- Add index for performance on status queries
ALTER TABLE llx_paiement ADD INDEX idx_paiement_statut (statut);

-- Add index for returned checks queries
ALTER TABLE llx_paiement ADD INDEX idx_paiement_returned_check (is_returned_check);
