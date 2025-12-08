/*
  # Update order and contact tables for new features

  1. Changes
    - Add new columns to contacts table for additional phone number and address
    - Add payment method and transfer number fields
    - Update status constraints for new order workflow
    - Remove discount column from orders table
    - Make message column optional

  2. Security
    - Maintain existing RLS policies
    - Add constraints for payment method values
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