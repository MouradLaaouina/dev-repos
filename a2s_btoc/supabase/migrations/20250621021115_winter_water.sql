/*
  # Call Center Leads Table

  1. New Tables
    - `call_center_leads`
      - `id` (uuid, primary key)
      - `purchase_date` (timestamp)
      - `name` (text)
      - `phone_number` (text)
      - `commercial_agent` (text)
      - `product_bought` (text)
      - `assigned_agent` (uuid, foreign key to users)
      - `created_at` (timestamp)
      - `updated_at` (timestamp)

  2. Security
    - Enable RLS on `call_center_leads` table
    - Add policies for agents to see only their assigned leads
    - Add policies for admins to see all leads

  3. Indexes
    - Index on assigned_agent for performance
    - Index on purchase_date for sorting
*/

-- Create call_center_leads table
CREATE TABLE IF NOT EXISTS call_center_leads (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  purchase_date timestamptz NOT NULL,
  name text NOT NULL,
  phone_number text NOT NULL,
  commercial_agent text NOT NULL,
  product_bought text NOT NULL,
  assigned_agent uuid NOT NULL REFERENCES users(id),
  created_at timestamptz DEFAULT now(),
  updated_at timestamptz DEFAULT now()
);

-- Enable RLS
ALTER TABLE call_center_leads ENABLE ROW LEVEL SECURITY;

-- Create policies
CREATE POLICY "Agents can view their assigned leads"
  ON call_center_leads
  FOR SELECT
  TO authenticated
  USING (assigned_agent = auth.uid());

CREATE POLICY "Admins can view all leads"
  ON call_center_leads
  FOR SELECT
  TO authenticated
  USING (
    EXISTS (
      SELECT 1 FROM users 
      WHERE users.id = auth.uid() 
      AND users.role = 'admin'
    )
  );

CREATE POLICY "Admins can manage all leads"
  ON call_center_leads
  FOR ALL
  TO authenticated
  USING (
    EXISTS (
      SELECT 1 FROM users 
      WHERE users.id = auth.uid() 
      AND users.role = 'admin'
    )
  );

-- Create indexes
CREATE INDEX IF NOT EXISTS idx_call_center_leads_assigned_agent 
  ON call_center_leads(assigned_agent);

CREATE INDEX IF NOT EXISTS idx_call_center_leads_purchase_date 
  ON call_center_leads(purchase_date DESC);

CREATE INDEX IF NOT EXISTS idx_call_center_leads_phone 
  ON call_center_leads(phone_number);

-- Update call_logs to reference call_center_leads
ALTER TABLE call_logs 
ADD COLUMN IF NOT EXISTS lead_id uuid REFERENCES call_center_leads(id) ON DELETE CASCADE;

-- Create index for lead_id
CREATE INDEX IF NOT EXISTS idx_call_logs_lead_id 
  ON call_logs(lead_id);