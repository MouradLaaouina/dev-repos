/*
  # Add Code_client column to call_center_leads table

  1. Changes
    - Add `code_client` column to `call_center_leads` table
    - This column will store the unique client code to prevent duplicates when importing exported data to ATLAS software
    - Column is optional (nullable) to allow existing records to remain valid
    - Add index for performance when searching by client code

  2. Security
    - No changes to RLS policies needed
    - Existing policies will apply to the new column
*/

-- Add code_client column to call_center_leads table
ALTER TABLE call_center_leads 
ADD COLUMN IF NOT EXISTS code_client text;

-- Add index for code_client for better performance
CREATE INDEX IF NOT EXISTS idx_call_center_leads_code_client 
  ON call_center_leads(code_client);

-- Add comment to explain the purpose of the column
COMMENT ON COLUMN call_center_leads.code_client IS 'Unique client code to prevent duplicates when importing exported data to ATLAS software';