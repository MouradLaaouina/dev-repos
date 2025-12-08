/*
  # Fix order policies for proper confirmation flow

  1. Security Changes
    - Ensure admins have full access to orders table
    - Allow confirmation team to properly update order status
    - Fix RLS policies that were blocking order operations

  This migration ensures that when orders are confirmed, they properly
  transition from "À confirmer" to "Confirmée" status and are visible
  to the expedition team.
*/

-- Drop existing policies to recreate them properly
DROP POLICY IF EXISTS "Admins can manage all orders" ON orders;
DROP POLICY IF EXISTS "Confirmation role can insert orders" ON orders;
DROP POLICY IF EXISTS "Confirmation role can update orders for confirmation" ON orders;
DROP POLICY IF EXISTS "Dispatching role can update orders for dispatch" ON orders;
DROP POLICY IF EXISTS "Agents can read orders for their contacts" ON orders;
DROP POLICY IF EXISTS "Order participants can read orders" ON orders;

-- Policy for admins to have full access to orders
CREATE POLICY "Admins can manage all orders"
  ON orders
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

-- Policy for agents to read orders related to their contacts
CREATE POLICY "Agents can read orders for their contacts"
  ON orders
  FOR SELECT
  TO authenticated
  USING (
    EXISTS (
      SELECT 1 FROM contacts 
      WHERE contacts.id = orders.contact_id 
      AND contacts.agent_id = auth.uid()
    )
    OR
    EXISTS (
      SELECT 1 FROM users 
      WHERE users.id = auth.uid() 
      AND users.role IN ('admin', 'confirmation', 'dispatching')
    )
  );

-- Policy for confirmation role to insert new orders
CREATE POLICY "Confirmation role can insert orders"
  ON orders
  FOR INSERT
  TO authenticated
  WITH CHECK (
    EXISTS (
      SELECT 1 FROM users 
      WHERE users.id = auth.uid() 
      AND users.role = 'confirmation'
    )
  );

-- Policy for confirmation role to update orders for confirmation
CREATE POLICY "Confirmation role can update orders for confirmation"
  ON orders
  FOR UPDATE
  TO authenticated
  USING (
    EXISTS (
      SELECT 1 FROM users 
      WHERE users.id = auth.uid() 
      AND users.role = 'confirmation'
    )
  )
  WITH CHECK (
    EXISTS (
      SELECT 1 FROM users 
      WHERE users.id = auth.uid() 
      AND users.role = 'confirmation'
    )
  );

-- Policy for dispatching role to update orders for dispatch
CREATE POLICY "Dispatching role can update orders for dispatch"
  ON orders
  FOR UPDATE
  TO authenticated
  USING (
    EXISTS (
      SELECT 1 FROM users 
      WHERE users.id = auth.uid() 
      AND users.role = 'dispatching'
    )
  )
  WITH CHECK (
    EXISTS (
      SELECT 1 FROM users 
      WHERE users.id = auth.uid() 
      AND users.role = 'dispatching'
    )
  );

-- Policy for users involved in the order process to read orders
CREATE POLICY "Order participants can read orders"
  ON orders
  FOR SELECT
  TO authenticated
  USING (
    confirmed_by = auth.uid() 
    OR dispatched_by = auth.uid()
    OR EXISTS (
      SELECT 1 FROM users 
      WHERE users.id = auth.uid() 
      AND users.role IN ('admin', 'confirmation', 'dispatching')
    )
  );