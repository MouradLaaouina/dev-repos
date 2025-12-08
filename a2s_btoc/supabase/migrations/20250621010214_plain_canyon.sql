/*
  # Add superviseur role to users table

  1. Changes
    - Update users role constraint to include 'superviseur' role
    - This allows for the new superviseur role in the dashboard system
    
  2. Security
    - Maintain existing RLS policies
    - Superviseur role will have access to team-filtered data
*/

-- Update users role constraint to include superviseur role
ALTER TABLE users 
DROP CONSTRAINT IF EXISTS users_role_check,
ADD CONSTRAINT users_role_check 
  CHECK (role = ANY (ARRAY['admin'::text, 'agent'::text, 'superviseur'::text, 'confirmation'::text, 'dispatching'::text]));

-- Add comment to track this update
COMMENT ON CONSTRAINT users_role_check ON users IS 'Updated to include superviseur role for dashboard access control';