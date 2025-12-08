-- Add code_client column to call_center_leads table
ALTER TABLE call_center_leads 
ADD COLUMN IF NOT EXISTS code_client text;

-- Add index for code_client for better performance
CREATE INDEX IF NOT EXISTS idx_call_center_leads_code_client 
  ON call_center_leads(code_client);

-- Add comment to explain the purpose of the column
COMMENT ON COLUMN call_center_leads.code_client IS 'Unique client code to prevent duplicates when importing exported data to ATLAS software';