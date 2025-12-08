/*
  # Add 'Commande' to call_logs.call_status constraint

  1. Changes
    - Update the call_logs_call_status_check constraint to include 'Commande'
    - This allows call center agents to mark calls as resulting in an order
    
  2. Benefits
    - Enables proper tracking of calls that result in orders
    - Allows filtering of call logs by 'Commande' status
    - Ensures database consistency with frontend changes
*/

-- Drop the existing constraint
ALTER TABLE call_logs DROP CONSTRAINT IF EXISTS call_logs_call_status_check;

-- Add the updated constraint with 'Commande' included
ALTER TABLE call_logs ADD CONSTRAINT call_logs_call_status_check
CHECK (call_status = ANY (ARRAY['À rappeler'::text, 'Pas intéressé(e)'::text, 'Commande'::text, 'Ne réponds jamais'::text, 'Faux numéro'::text, 'Intéressé(e)'::text]));

-- Add comment to explain the change
COMMENT ON CONSTRAINT call_logs_call_status_check ON call_logs IS 'Updated to include Commande status for call center order tracking';