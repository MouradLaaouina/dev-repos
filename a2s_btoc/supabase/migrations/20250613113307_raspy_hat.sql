/*
  # Fix RLS policies for conversations table

  1. Security Changes
    - Update RLS policies to allow webhook insertions
    - Allow anonymous users to insert conversations (for webhooks)
    - Maintain security for authenticated users

  This migration fixes the RLS policies to allow the WhatsApp webhook to insert
  conversations while maintaining proper security for authenticated users.
*/

-- Drop existing policies
DROP POLICY IF EXISTS "Agents can view their own conversations" ON conversations;
DROP POLICY IF EXISTS "Allow webhook to insert conversations" ON conversations;
DROP POLICY IF EXISTS "Agents can update their own conversations" ON conversations;

-- Create new policies that allow webhook access
CREATE POLICY "Allow webhook and authenticated users to insert conversations"
  ON conversations
  FOR INSERT
  TO anon, authenticated
  WITH CHECK (true);

CREATE POLICY "Authenticated users can view conversations"
  ON conversations
  FOR SELECT
  TO authenticated
  USING (
    agent_id = auth.uid() OR 
    EXISTS (
      SELECT 1 FROM users 
      WHERE users.id = auth.uid() 
      AND users.role = 'admin'
    )
  );

CREATE POLICY "Authenticated users can update conversations"
  ON conversations
  FOR UPDATE
  TO authenticated
  USING (
    agent_id = auth.uid() OR 
    EXISTS (
      SELECT 1 FROM users 
      WHERE users.id = auth.uid() 
      AND users.role = 'admin'
    )
  );

-- Allow anonymous users to read conversations (needed for webhook verification)
CREATE POLICY "Allow anonymous read for webhook verification"
  ON conversations
  FOR SELECT
  TO anon
  USING (true);