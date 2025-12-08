/*
  # Call Center Setup

  1. New Tables
    - `call_logs` - Store call follow-up information
    
  2. Security
    - Enable RLS on call_logs table
    - Add policies for call center agents
    
  3. Constraints
    - Add check constraints for call status and interested fields
*/

-- Create call_logs table
CREATE TABLE IF NOT EXISTS call_logs (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  client_id uuid NOT NULL REFERENCES contacts(id) ON DELETE CASCADE,
  call_status text NOT NULL CHECK (call_status IN ('À rappeler', 'Pas intéressé(e)', 'Commande', 'Ne réponds jamais', 'Faux numéro', 'Intéressé(e)')),
  satisfaction_level integer NOT NULL CHECK (satisfaction_level >= 1 AND satisfaction_level <= 5),
  interested text NOT NULL CHECK (interested IN ('Oui', 'Non', 'Peut-être')),
  call_date timestamptz NOT NULL,
  next_call_date timestamptz,
  next_call_time text,
  notes text,
  agent_id uuid NOT NULL REFERENCES users(id),
  created_at timestamptz DEFAULT now()
);

-- Enable RLS
ALTER TABLE call_logs ENABLE ROW LEVEL SECURITY;

-- Create policies for call_logs
CREATE POLICY "Call center agents can insert their own call logs"
  ON call_logs
  FOR INSERT
  TO authenticated
  WITH CHECK (
    agent_id = auth.uid() AND
    EXISTS (
      SELECT 1 FROM users 
      WHERE users.id = auth.uid() 
      AND (users.role = 'agent' AND users.code_agence = '000002' OR users.role = 'admin')
    )
  );

CREATE POLICY "Call center agents can view their own call logs"
  ON call_logs
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

CREATE POLICY "Call center agents can update their own call logs"
  ON call_logs
  FOR UPDATE
  TO authenticated
  USING (
    agent_id = auth.uid() AND
    EXISTS (
      SELECT 1 FROM users 
      WHERE users.id = auth.uid() 
      AND (users.role = 'agent' AND users.code_agence = '000002' OR users.role = 'admin')
    )
  )
  WITH CHECK (
    agent_id = auth.uid() AND
    EXISTS (
      SELECT 1 FROM users 
      WHERE users.id = auth.uid() 
      AND (users.role = 'agent' AND users.code_agence = '000002' OR users.role = 'admin')
    )
  );

CREATE POLICY "Admins can manage all call logs"
  ON call_logs
  FOR ALL
  TO authenticated
  USING (
    EXISTS (
      SELECT 1 FROM users 
      WHERE users.id = auth.uid() 
      AND users.role = 'admin'
    )
  )
  WITH CHECK (
    EXISTS (
      SELECT 1 FROM users 
      WHERE users.id = auth.uid() 
      AND users.role = 'admin'
    )
  );

-- Create indexes for better performance
CREATE INDEX idx_call_logs_client_id ON call_logs(client_id);
CREATE INDEX idx_call_logs_agent_id ON call_logs(agent_id);
CREATE INDEX idx_call_logs_call_date ON call_logs(call_date);
CREATE INDEX idx_call_logs_next_call_date ON call_logs(next_call_date);

-- Add comment to explain the table purpose
COMMENT ON TABLE call_logs IS 'Call follow-up logs for call center agents to track customer interactions';