/*
  # Add new team roles and order tracking fields

  1. Changes
    - Update users table role constraint to include new roles
    - Add new fields to orders table for confirmation and dispatching tracking
    
  2. New Roles
    - confirmation: For confirmation team
    - dispatching: For dispatching team
    
  3. New Order Fields
    - confirmation_note: Note from confirmation team
    - confirmed_by: User ID who confirmed the order
    - confirmed_at: Timestamp of confirmation
    - dispatched_by: User ID who dispatched the order
    - dispatched_at: Timestamp of dispatch
    - tracking_number: Shipping tracking number
    - delivery_note: Note about delivery status
*/

-- Update users role constraint to include new roles
ALTER TABLE users 
DROP CONSTRAINT IF EXISTS users_role_check,
ADD CONSTRAINT users_role_check 
  CHECK (role = ANY (ARRAY['admin'::text, 'agent'::text, 'confirmation'::text, 'dispatching'::text]));

-- Add new fields to orders table (if it exists)
DO $$
BEGIN
  -- Check if orders table exists, if not create it
  IF NOT EXISTS (SELECT FROM information_schema.tables WHERE table_name = 'orders') THEN
    CREATE TABLE orders (
      id uuid PRIMARY KEY DEFAULT uuid_generate_v4(),
      contact_id uuid NOT NULL REFERENCES contacts(id),
      order_number text UNIQUE NOT NULL,
      shipping_cost numeric(10,2) DEFAULT 0,
      total numeric(10,2) NOT NULL,
      created_at timestamptz DEFAULT timezone('utc'::text, now()),
      updated_at timestamptz DEFAULT timezone('utc'::text, now()),
      delivery_date timestamptz
    );
    
    ALTER TABLE orders ENABLE ROW LEVEL SECURITY;
  END IF;
  
  -- Add new columns if they don't exist
  IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'orders' AND column_name = 'confirmation_note') THEN
    ALTER TABLE orders ADD COLUMN confirmation_note text;
  END IF;
  
  IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'orders' AND column_name = 'confirmed_by') THEN
    ALTER TABLE orders ADD COLUMN confirmed_by uuid REFERENCES users(id);
  END IF;
  
  IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'orders' AND column_name = 'confirmed_at') THEN
    ALTER TABLE orders ADD COLUMN confirmed_at timestamptz;
  END IF;
  
  IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'orders' AND column_name = 'dispatched_by') THEN
    ALTER TABLE orders ADD COLUMN dispatched_by uuid REFERENCES users(id);
  END IF;
  
  IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'orders' AND column_name = 'dispatched_at') THEN
    ALTER TABLE orders ADD COLUMN dispatched_at timestamptz;
  END IF;
  
  IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'orders' AND column_name = 'tracking_number') THEN
    ALTER TABLE orders ADD COLUMN tracking_number text;
  END IF;
  
  IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'orders' AND column_name = 'delivery_note') THEN
    ALTER TABLE orders ADD COLUMN delivery_note text;
  END IF;
END $$;

-- Update contacts status constraint to include new order statuses
ALTER TABLE contacts 
DROP CONSTRAINT IF EXISTS contacts_status_check,
ADD CONSTRAINT contacts_status_check 
  CHECK (status = ANY (ARRAY['À confirmer'::text, 'Confirmée'::text, 'Prête à être livrée'::text, 'Expédiée'::text, 'Livrée'::text, 'Retournée'::text, 'Annulée'::text]));