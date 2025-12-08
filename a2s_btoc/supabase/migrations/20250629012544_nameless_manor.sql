/*
  # Add Clients platform to contacts table

  1. Changes
    - Update contacts_plateforme_check constraint to include 'Clients' as a valid platform
    - Add 'Imported Client List' as a valid source
    
  2. Benefits
    - Allows importing existing client lists with 'Clients' platform
    - Provides a specific source for imported clients
*/

-- Drop the existing platform constraint
ALTER TABLE contacts DROP CONSTRAINT IF EXISTS contacts_plateforme_check;

-- Add the updated constraint with 'Clients' as a valid platform
ALTER TABLE contacts ADD CONSTRAINT contacts_plateforme_check 
CHECK (plateforme = ANY (ARRAY['Facebook'::text, 'Instagram'::text, 'WhatsApp'::text, 'Clients'::text]));

-- Add comment to explain the change
COMMENT ON CONSTRAINT contacts_plateforme_check ON contacts IS 'Updated to include Clients platform for imported client lists';