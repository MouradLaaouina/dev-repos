/*
  # Fix order number generation with PostgreSQL auto-generation

  1. Changes
    - Change order_number column from text to BIGSERIAL for auto-generation
    - Remove the existing order_number column and recreate it as BIGSERIAL
    - Ensure uniqueness is handled at database level
    
  2. Benefits
    - Eliminates race conditions in order number generation
    - Ensures uniqueness at database level
    - Simplifies frontend logic by removing manual generation
*/

-- First, drop the existing unique constraint
ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_order_number_key;

-- Drop the existing order_number column
ALTER TABLE orders DROP COLUMN IF EXISTS order_number;

-- Add new order_number column as BIGSERIAL with UNIQUE constraint
ALTER TABLE orders ADD COLUMN order_number BIGSERIAL UNIQUE;

-- Add comment to explain the auto-generation
COMMENT ON COLUMN orders.order_number IS 'Auto-generated sequential order number starting from 1';

-- Create index for better performance on order_number lookups
CREATE INDEX IF NOT EXISTS idx_orders_order_number ON orders(order_number);