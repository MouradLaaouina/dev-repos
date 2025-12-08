/*
  # Fix D-CAP brand constraint and ensure shipping cost updates

  1. Database Changes
    - Update order_items brand constraint to include D-CAP
    - Ensure all brand constraints are consistent across tables
    
  2. Brand List
    - D-WHITE, D-CAP, SENSILIS, CUMLAUDE, BABE, BUCCOTHERM
*/

-- Update order_items brand constraint to include D-CAP
ALTER TABLE order_items 
DROP CONSTRAINT IF EXISTS order_items_brand_check,
ADD CONSTRAINT order_items_brand_check 
  CHECK (brand = ANY (ARRAY['D-WHITE'::text, 'D-CAP'::text, 'SENSILIS'::text, 'CUMLAUDE'::text, 'BABE'::text, 'BUCCOTHERM'::text]));

-- Ensure products table has the same constraint (should already be correct)
ALTER TABLE products 
DROP CONSTRAINT IF EXISTS products_brand_check,
ADD CONSTRAINT products_brand_check 
  CHECK (brand = ANY (ARRAY['D-WHITE'::text, 'D-CAP'::text, 'SENSILIS'::text, 'CUMLAUDE'::text, 'BABE'::text, 'BUCCOTHERM'::text]));

-- Add comment to track this fix
COMMENT ON CONSTRAINT order_items_brand_check ON order_items IS 'Updated to include D-CAP brand';