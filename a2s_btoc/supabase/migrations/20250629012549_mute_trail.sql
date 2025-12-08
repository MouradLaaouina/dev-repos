/*
  # Update RLS policies to allow all team members to read all contacts

  1. Security Changes
    - Drop existing policies that restrict contact visibility
    - Add new policy to allow all authenticated users to read all contacts
    - Keep existing policies for insert/update operations
    
  2. Benefits
    - All team members can search and view all contacts
    - Maintains data integrity by keeping insert/update restrictions
    - Supervisors can still manage their team's contacts
*/

-- Drop existing policies that restrict contact visibility
DROP POLICY IF EXISTS "Agents can view their own contacts" ON contacts;
DROP POLICY IF EXISTS "Superviseurs can view their team contacts" ON contacts;

-- Create new policy to allow all authenticated users to read all contacts
CREATE POLICY "Allow authenticated users to read all contacts"
  ON contacts
  FOR SELECT
  TO authenticated
  USING (true);

-- Add comment to explain the change
COMMENT ON TABLE contacts IS 'Contacts table with updated RLS policies to allow all team members to read all contacts';