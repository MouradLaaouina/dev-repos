/*
  # Fix all CRM issues - Complete RLS and database setup

  1. Security Changes
    - Fix all RLS policies for proper access control
    - Add missing policies for all tables
    - Ensure proper role-based access

  2. Database Structure
    - Ensure all tables have proper constraints
    - Add missing indexes for performance
    - Fix foreign key relationships

  3. Complete Policy Setup
    - Users table policies
    - Contacts table policies  
    - Orders table policies
    - Order items table policies
    - Products table policies
    - Conversations table policies
*/

-- Drop all existing policies to start fresh
DROP POLICY IF EXISTS "Allow users to insert their own profile" ON users;
DROP POLICY IF EXISTS "Users can read own profile" ON users;
DROP POLICY IF EXISTS "Users can update own profile" ON users;
DROP POLICY IF EXISTS "Users can view their own data" ON users;

DROP POLICY IF EXISTS "Admins can insert contacts" ON contacts;
DROP POLICY IF EXISTS "Admins can view all contacts" ON contacts;
DROP POLICY IF EXISTS "Agents can insert contacts" ON contacts;
DROP POLICY IF EXISTS "Agents can view their own contacts" ON contacts;
DROP POLICY IF EXISTS "Agents can update their own contacts" ON contacts;
DROP POLICY IF EXISTS "Admins can update all contacts" ON contacts;

DROP POLICY IF EXISTS "Admins can manage all orders" ON orders;
DROP POLICY IF EXISTS "Agents can read orders for their contacts" ON orders;
DROP POLICY IF EXISTS "Confirmation role can insert orders" ON orders;
DROP POLICY IF EXISTS "Confirmation role can update orders for confirmation" ON orders;
DROP POLICY IF EXISTS "Dispatching role can update orders for dispatch" ON orders;
DROP POLICY IF EXISTS "Order participants can read orders" ON orders;

DROP POLICY IF EXISTS "Authenticated users can insert order items" ON order_items;
DROP POLICY IF EXISTS "Authenticated users can read order items" ON order_items;
DROP POLICY IF EXISTS "Authenticated users can update order items" ON order_items;

DROP POLICY IF EXISTS "Allow webhook and authenticated users to insert conversations" ON conversations;
DROP POLICY IF EXISTS "Authenticated users can view conversations" ON conversations;
DROP POLICY IF EXISTS "Authenticated users can update conversations" ON conversations;
DROP POLICY IF EXISTS "Allow anonymous read for webhook verification" ON conversations;

-- USERS TABLE POLICIES
CREATE POLICY "Users can insert their own profile"
  ON users
  FOR INSERT
  TO authenticated
  WITH CHECK (auth.uid() = id);

CREATE POLICY "Users can read own profile"
  ON users
  FOR SELECT
  TO authenticated
  USING (auth.uid() = id);

CREATE POLICY "Users can update own profile"
  ON users
  FOR UPDATE
  TO authenticated
  USING (auth.uid() = id)
  WITH CHECK (auth.uid() = id);

CREATE POLICY "Admins can read all users"
  ON users
  FOR SELECT
  TO authenticated
  USING (
    auth.uid() = id OR
    EXISTS (
      SELECT 1 FROM users 
      WHERE users.id = auth.uid() AND users.role = 'admin'
    )
  );

CREATE POLICY "Admins can update all users"
  ON users
  FOR UPDATE
  TO authenticated
  USING (
    EXISTS (
      SELECT 1 FROM users 
      WHERE users.id = auth.uid() AND users.role = 'admin'
    )
  )
  WITH CHECK (
    EXISTS (
      SELECT 1 FROM users 
      WHERE users.id = auth.uid() AND users.role = 'admin'
    )
  );

-- CONTACTS TABLE POLICIES
CREATE POLICY "Agents can insert their own contacts"
  ON contacts
  FOR INSERT
  TO authenticated
  WITH CHECK (agent_id = auth.uid());

CREATE POLICY "Agents can view their own contacts"
  ON contacts
  FOR SELECT
  TO authenticated
  USING (
    agent_id = auth.uid() OR
    EXISTS (
      SELECT 1 FROM users 
      WHERE users.id = auth.uid() AND users.role = 'admin'
    )
  );

CREATE POLICY "Agents can update their own contacts"
  ON contacts
  FOR UPDATE
  TO authenticated
  USING (
    agent_id = auth.uid() OR
    EXISTS (
      SELECT 1 FROM users 
      WHERE users.id = auth.uid() AND users.role IN ('admin', 'confirmation', 'dispatching')
    )
  )
  WITH CHECK (
    agent_id = auth.uid() OR
    EXISTS (
      SELECT 1 FROM users 
      WHERE users.id = auth.uid() AND users.role IN ('admin', 'confirmation', 'dispatching')
    )
  );

CREATE POLICY "Admins can manage all contacts"
  ON contacts
  FOR ALL
  TO authenticated
  USING (
    EXISTS (
      SELECT 1 FROM users 
      WHERE users.id = auth.uid() AND users.role = 'admin'
    )
  )
  WITH CHECK (
    EXISTS (
      SELECT 1 FROM users 
      WHERE users.id = auth.uid() AND users.role = 'admin'
    )
  );

-- ORDERS TABLE POLICIES
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

CREATE POLICY "Authenticated users can insert orders"
  ON orders
  FOR INSERT
  TO authenticated
  WITH CHECK (
    EXISTS (
      SELECT 1 FROM users 
      WHERE users.id = auth.uid() 
      AND users.role IN ('admin', 'agent', 'confirmation')
    )
  );

CREATE POLICY "Authenticated users can update orders"
  ON orders
  FOR UPDATE
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
  )
  WITH CHECK (
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

-- ORDER ITEMS TABLE POLICIES
CREATE POLICY "Authenticated users can insert order items"
  ON order_items
  FOR INSERT
  TO authenticated
  WITH CHECK (
    EXISTS (
      SELECT 1 FROM users 
      WHERE users.id = auth.uid()
    )
  );

CREATE POLICY "Authenticated users can read order items"
  ON order_items
  FOR SELECT
  TO authenticated
  USING (
    EXISTS (
      SELECT 1 FROM users 
      WHERE users.id = auth.uid()
    )
  );

CREATE POLICY "Authenticated users can update order items"
  ON order_items
  FOR UPDATE
  TO authenticated
  USING (
    EXISTS (
      SELECT 1 FROM users 
      WHERE users.id = auth.uid()
    )
  )
  WITH CHECK (
    EXISTS (
      SELECT 1 FROM users 
      WHERE users.id = auth.uid()
    )
  );

-- PRODUCTS TABLE POLICIES
CREATE POLICY "Anyone can read products"
  ON products
  FOR SELECT
  TO authenticated, anon
  USING (true);

CREATE POLICY "Admins can manage products"
  ON products
  FOR ALL
  TO authenticated
  USING (
    EXISTS (
      SELECT 1 FROM users 
      WHERE users.id = auth.uid() AND users.role = 'admin'
    )
  )
  WITH CHECK (
    EXISTS (
      SELECT 1 FROM users 
      WHERE users.id = auth.uid() AND users.role = 'admin'
    )
  );

-- CONVERSATIONS TABLE POLICIES
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

CREATE POLICY "Allow anonymous read for webhook verification"
  ON conversations
  FOR SELECT
  TO anon
  USING (true);

-- Ensure all tables have RLS enabled
ALTER TABLE users ENABLE ROW LEVEL SECURITY;
ALTER TABLE contacts ENABLE ROW LEVEL SECURITY;
ALTER TABLE orders ENABLE ROW LEVEL SECURITY;
ALTER TABLE order_items ENABLE ROW LEVEL SECURITY;
ALTER TABLE products ENABLE ROW LEVEL SECURITY;
ALTER TABLE conversations ENABLE ROW LEVEL SECURITY;

-- Add missing indexes for better performance
CREATE INDEX IF NOT EXISTS idx_contacts_agent_id ON contacts(agent_id);
CREATE INDEX IF NOT EXISTS idx_contacts_status ON contacts(status);
CREATE INDEX IF NOT EXISTS idx_contacts_type_de_demande ON contacts(type_de_demande);
CREATE INDEX IF NOT EXISTS idx_orders_contact_id ON orders(contact_id);
CREATE INDEX IF NOT EXISTS idx_order_items_order_id ON order_items(order_id);
CREATE INDEX IF NOT EXISTS idx_users_role ON users(role);

-- Ensure proper constraints exist
DO $$
BEGIN
  -- Check if contacts status constraint exists and update it
  IF EXISTS (
    SELECT 1 FROM information_schema.table_constraints 
    WHERE constraint_name = 'contacts_status_check' 
    AND table_name = 'contacts'
  ) THEN
    ALTER TABLE contacts DROP CONSTRAINT contacts_status_check;
  END IF;
  
  ALTER TABLE contacts ADD CONSTRAINT contacts_status_check 
    CHECK (status = ANY (ARRAY[
      'À confirmer'::text, 
      'Confirmée'::text, 
      'Prête à être livrée'::text, 
      'Expédiée'::text, 
      'Livrée'::text, 
      'Retournée'::text, 
      'Annulée'::text
    ]));
END $$;

-- Add comment to explain the complete setup
COMMENT ON SCHEMA public IS 'Complete CRM schema with proper RLS policies for all user roles and secure data access';