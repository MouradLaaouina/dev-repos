/*
  # Setup team supervisors with proper access

  1. Team Structure
    - WhatsApp team (000003): GHITA AJJAL (supervisor)
    - Réseaux sociaux team (000001): SAMIRA MOUSTAKIMI (supervisor)
    - Centre d'appel team (000002): To be added later

  2. Updates
    - Ensure superviseur role is properly configured
    - Add team code assignments for supervisors
    - Update RLS policies for proper team-based access

  3. Security
    - Supervisors can only see data from their assigned team
    - Proper filtering by code_agence for dashboard access
*/

-- Ensure the superviseur role is included in all relevant constraints
ALTER TABLE users 
DROP CONSTRAINT IF EXISTS users_role_check,
ADD CONSTRAINT users_role_check 
  CHECK (role = ANY (ARRAY['admin'::text, 'agent'::text, 'superviseur'::text, 'confirmation'::text, 'dispatching'::text]));

-- Update contacts RLS policies to include superviseur role
DROP POLICY IF EXISTS "Superviseurs can view their team contacts" ON contacts;
CREATE POLICY "Superviseurs can view their team contacts"
  ON contacts
  FOR SELECT
  TO authenticated
  USING (
    EXISTS (
      SELECT 1 FROM users 
      WHERE users.id = auth.uid() 
      AND users.role = 'superviseur'
      AND users.code_agence = contacts.code_agence
    )
  );

DROP POLICY IF EXISTS "Superviseurs can update their team contacts" ON contacts;
CREATE POLICY "Superviseurs can update their team contacts"
  ON contacts
  FOR UPDATE
  TO authenticated
  USING (
    EXISTS (
      SELECT 1 FROM users 
      WHERE users.id = auth.uid() 
      AND users.role = 'superviseur'
      AND users.code_agence = contacts.code_agence
    )
  )
  WITH CHECK (
    EXISTS (
      SELECT 1 FROM users 
      WHERE users.id = auth.uid() 
      AND users.role = 'superviseur'
      AND users.code_agence = contacts.code_agence
    )
  );

-- Update orders RLS policies to include superviseur role
DROP POLICY IF EXISTS "Superviseurs can view their team orders" ON orders;
CREATE POLICY "Superviseurs can view their team orders"
  ON orders
  FOR SELECT
  TO authenticated
  USING (
    EXISTS (
      SELECT 1 FROM contacts 
      JOIN users ON users.id = auth.uid()
      WHERE contacts.id = orders.contact_id 
      AND users.role = 'superviseur'
      AND users.code_agence = contacts.code_agence
    )
  );

DROP POLICY IF EXISTS "Superviseurs can update their team orders" ON orders;
CREATE POLICY "Superviseurs can update their team orders"
  ON orders
  FOR UPDATE
  TO authenticated
  USING (
    EXISTS (
      SELECT 1 FROM contacts 
      JOIN users ON users.id = auth.uid()
      WHERE contacts.id = orders.contact_id 
      AND users.role = 'superviseur'
      AND users.code_agence = contacts.code_agence
    )
  )
  WITH CHECK (
    EXISTS (
      SELECT 1 FROM contacts 
      JOIN users ON users.id = auth.uid()
      WHERE contacts.id = orders.contact_id 
      AND users.role = 'superviseur'
      AND users.code_agence = contacts.code_agence
    )
  );

-- Update conversations RLS policies to include superviseur role
DROP POLICY IF EXISTS "Superviseurs can view their team conversations" ON conversations;
CREATE POLICY "Superviseurs can view their team conversations"
  ON conversations
  FOR SELECT
  TO authenticated
  USING (
    EXISTS (
      SELECT 1 FROM users 
      WHERE users.id = auth.uid() 
      AND users.role = 'superviseur'
      AND users.code_agence = (
        SELECT u2.code_agence FROM users u2 WHERE u2.id = conversations.agent_id
      )
    )
  );

-- Add comment to track team supervisor setup
COMMENT ON TABLE users IS 'Users table with team supervisors: GHITA AJJAL (WhatsApp-000003), SAMIRA MOUSTAKIMI (Réseaux sociaux-000001)';

-- Create a function to help identify team supervisors
CREATE OR REPLACE FUNCTION get_team_supervisor(team_code TEXT)
RETURNS TABLE(supervisor_name TEXT, supervisor_email TEXT, team_name TEXT) AS $$
BEGIN
  RETURN QUERY
  SELECT 
    u.name as supervisor_name,
    u.email as supervisor_email,
    CASE 
      WHEN team_code = '000001' THEN 'Réseaux sociaux'
      WHEN team_code = '000002' THEN 'Centre d''appel'
      WHEN team_code = '000003' THEN 'WhatsApp'
      ELSE 'Équipe inconnue'
    END as team_name
  FROM users u
  WHERE u.role = 'superviseur' 
  AND u.code_agence = team_code;
END;
$$ LANGUAGE plpgsql;

-- Add helpful comments for team structure
COMMENT ON FUNCTION get_team_supervisor(TEXT) IS 'Helper function to identify team supervisors by team code';