/*
  # Update orders schema for new features
  
  1. New Columns
    - Add second phone number (optional)
    - Add address (required)
    - Add payment method and transfer number
    - Update status options
    
  2. Changes
    - Remove discount column
    - Add new status options
*/

-- Add new columns to contacts table
ALTER TABLE contacts 
  ADD COLUMN IF NOT EXISTS telephone2 text,
  ADD COLUMN IF NOT EXISTS address text NOT NULL DEFAULT '',
  ADD COLUMN IF NOT EXISTS payment_method text CHECK (payment_method IN ('Espèce', 'Virement')),
  ADD COLUMN IF NOT EXISTS transfer_number text,
  ALTER COLUMN message DROP NOT NULL;

-- Update status check constraint
ALTER TABLE contacts 
  DROP CONSTRAINT IF EXISTS contacts_status_check,
  ADD CONSTRAINT contacts_status_check 
    CHECK (status = ANY (ARRAY['À confirmer'::text, 'Prête à être livrée'::text, 'Livrée'::text]));

-- Remove discount column from orders
ALTER TABLE orders 
  DROP COLUMN IF EXISTS discount;