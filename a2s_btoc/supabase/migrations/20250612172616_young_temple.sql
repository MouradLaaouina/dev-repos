/*
  # Create conversations table for WhatsApp messages

  1. New Tables
    - `conversations`
      - `id` (uuid, primary key)
      - `user_phone` (text, phone number)
      - `message_text` (text, message content)
      - `timestamp` (bigint, UNIX timestamp)
      - `direction` (text, 'in' or 'out')
      - `agent_id` (uuid, foreign key to users)
      - `created_at` (timestamp)
      - `is_read` (boolean, default false)

  2. Security
    - Enable RLS on `conversations` table
    - Add policies for agents to see their own conversations
    - Add policies for admins to see all conversations
*/

CREATE TABLE IF NOT EXISTS conversations (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  user_phone text NOT NULL,
  message_text text NOT NULL,
  timestamp bigint NOT NULL,
  direction text NOT NULL CHECK (direction IN ('in', 'out')),
  agent_id uuid REFERENCES users(id),
  created_at timestamptz DEFAULT now(),
  is_read boolean DEFAULT false
);

ALTER TABLE conversations ENABLE ROW LEVEL SECURITY;

CREATE POLICY "Agents can view their own conversations"
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

CREATE POLICY "Allow webhook to insert conversations"
  ON conversations
  FOR INSERT
  TO anon
  WITH CHECK (true);

CREATE POLICY "Agents can update their own conversations"
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

-- Create indexes for better performance
CREATE INDEX idx_conversations_agent_id ON conversations(agent_id);
CREATE INDEX idx_conversations_user_phone ON conversations(user_phone);
CREATE INDEX idx_conversations_timestamp ON conversations(timestamp);