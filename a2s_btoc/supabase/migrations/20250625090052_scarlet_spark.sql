/*
  # Add CASMARA RETAIL brand to constraints

  1. Changes
     - Add CASMARA RETAIL to products brand constraint
     - Add CASMARA RETAIL to order_items brand constraint
*/

-- Update products table constraint to include CASMARA RETAIL
ALTER TABLE products DROP CONSTRAINT IF EXISTS products_brand_check;
ALTER TABLE products ADD CONSTRAINT products_brand_check 
CHECK (brand = ANY (ARRAY[
  'D-WHITE'::text, 
  'D-CAP'::text, 
  'SENSILIS'::text, 
  'CUMLAUDE'::text, 
  'BABE'::text, 
  'BUCCOTHERM'::text, 
  'CASMARA RETAIL'::text
]));

-- Update order_items table constraint to include CASMARA RETAIL
ALTER TABLE order_items DROP CONSTRAINT IF EXISTS order_items_brand_check;
ALTER TABLE order_items ADD CONSTRAINT order_items_brand_check 
CHECK (brand = ANY (ARRAY[
  'D-WHITE'::text, 
  'D-CAP'::text, 
  'SENSILIS'::text, 
  'CUMLAUDE'::text, 
  'BABE'::text, 
  'BUCCOTHERM'::text,
  'CASMARA RETAIL'::text
]));

-- Add comment to explain the change
COMMENT ON CONSTRAINT products_brand_check ON products IS 'Updated to include CASMARA RETAIL brand';
COMMENT ON CONSTRAINT order_items_brand_check ON order_items IS 'Updated to include CASMARA RETAIL brand';